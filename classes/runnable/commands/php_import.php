<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:import - import the rows of an uploaded CSV file (the import of the admin's "Import all"), or a file by path.
 */
class Import extends \Exponential\Runnable\Command
{
    protected function commandName() { return 'import'; }

    protected function finish( $jobID, $command, $status, $result )
    {
        \CjwNewsletterJob::markFinished( $jobID, $command, $status, $result );
    }

    protected function setup( $options )
    {
        $jobID = ( !empty( $options['job'] ) && \CjwNewsletterJob::isID( $options['job'] ) ) ? $options['job'] : false;
        $out = new \CjwNewsletterJobOutput( $jobID ? false : $this->cli(), $jobID );
        if ( $jobID )
        {
            \CjwNewsletterJob::markRunning( $jobID, $this->commandName() );
        }
        return array( $jobID, $out );
    }

    protected function fail( $jobID, $out, $text )
    {
        $out->error( $text );
        $this->finish( $jobID, 'import', 'failed', array( 'error' => $text ) );
        $this->shutdown( 1 );
        return 1;
    }

    public function run()
    {
        $this->script( array( 'description' => 'Import the rows of a CSV file into a newsletter list',
                              'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $options = $this->startup( '[dry-run][import-id:][delimiter:][formats:][first-row-label][job:]', '',
            array( 'dry-run' => 'Read the file and say what it holds, import nothing',
                   'import-id' => 'The ID of the import (an upload of the admin: newsletter/import_list)',
                   'delimiter' => 'comma, semicolon, pipe or tab (default: semicolon)',
                   'formats' => 'Output formats of the new subscriptions, joined by "-": 0 is HTML, 1 is text (default: 0)',
                   'first-row-label' => 'The first row holds the names of the columns',
                   'job' => 'The ID of a background job (set by the admin)' ) );
        list( $jobID, $out ) = $this->setup( $options );
        $importObject = $options['import-id'] ? \CjwNewsletterImport::fetch( (int)$options['import-id'] ) : false;
        if ( !is_object( $importObject ) )
        {
            return $this->fail( $jobID, $out, 'Give --import-id of an import from newsletter/import_list.' );
        }
        $names = array( 'comma' => ',', 'semicolon' => ';', 'pipe' => '|', 'tab' => "\t" );
        $delimiterName = $options['delimiter'] ? $options['delimiter'] : 'semicolon';
        if ( !isset( $names[$delimiterName] ) )
        {
            return $this->fail( $jobID, $out, 'The delimiter must be comma, semicolon, pipe or tab.' );
        }
        $formats = array_map( 'intval', array_filter( explode( '-', $options['formats'] ? $options['formats'] : '0' ), 'strlen' ) );
        $file = (string)$importObject->attribute( 'data_text' );
        $dir = realpath( \eZSys::varDirectory() . '/cjw_newsletter/csvimport' );
        $real = $file !== '' ? realpath( $file ) : false;
        if ( !$dir || !$real || strpos( $real, $dir . DIRECTORY_SEPARATOR ) !== 0 )
        {
            return $this->fail( $jobID, $out, 'The file of the import is not in the import folder.' );
        }
        $ini = \eZINI::instance( 'cjw_newsletter.ini' );
        $mapping = array( 'email' => '', 'first_name' => '', 'last_name' => '', 'salutation' => '' );
        if ( $ini->hasVariable( 'NewsletterCsvImportSettings', 'CsvFieldMappingArray' ) )
        {
            $override = $ini->variable( 'NewsletterCsvImportSettings', 'CsvFieldMappingArray' );
            if ( array_key_exists( 'email', $override ) )
            {
                $mapping = $override;
            }
        }
        $utf8 = $ini->hasVariable( 'NewsletterCsvImportSettings', 'DefaultUtf8Encode' ) && $ini->variable( 'NewsletterCsvImportSettings', 'DefaultUtf8Encode' ) == 'true';
        $prio = $ini->hasVariable( 'NewsletterCsvImportSettings', 'CsvImportHasPrio' ) && $ini->variable( 'NewsletterCsvImportSettings', 'CsvImportHasPrio' ) == 'true';
        $parser = new \CjwNewsletterCsvParser( $real, $names[$delimiterName], (bool)$options['first-row-label'], $mapping, $utf8 );
        $rows = $parser->getCsvDataArray();
        $out->output( count( $rows ) . ' rows in ' . basename( $real ) );
        if ( $options['dry-run'] )
        {
            $this->finish( $jobID, 'import', 'done', array( 'rows' => count( $rows ) ) );
            $this->shutdown( 0 );
            return 0;
        }
        $lock = \CjwNewsletterRunner::lock( 'import' );
        if ( !$lock )
        {
            return $this->fail( $jobID, $out, 'Another import is running.' );
        }
        $result = \CjwNewsletterImport::runCsvImport( $importObject, $rows, (int)$importObject->attribute( 'list_contentobject_id' ), $formats, $prio, $out );
        $totals = array( 'rows' => count( $rows ), 'users' => 0, 'subscriptions' => 0, 'invalid' => 0 );
        foreach ( $result as $row )
        {
            if ( !$row['email_ok'] ) { ++$totals['invalid']; }
            if ( $row['user_created'] == 1 ) { ++$totals['users']; }
            if ( $row['subscription_created'] == 1 ) { ++$totals['subscriptions']; }
        }
        \CjwNewsletterRunner::recordRun( \CjwNewsletterRunner::LAST_IMPORT, $totals, $jobID ? 'job' : 'console' );
        \CjwNewsletterRunner::audit( 'import', $jobID ? 'job' : 'console', $totals, false );
        \CjwNewsletterRunner::unlock( $lock );
        $out->output( 'Imported: ' . $totals['users'] . ' new users, ' . $totals['subscriptions'] . ' new subscriptions, ' . $totals['invalid'] . ' invalid rows.' );
        $this->finish( $jobID, 'import', 'done', $totals );
        $this->shutdown( 0 );
        return 0;
    }
}

}
