<?php
/**
 * newsletter/import_mapping: a CSV import with a column mapping.
 *
 *   newsletter/import_mapping/0/(list)/<list node id>   the upload form
 *   newsletter/import_mapping/<import id>               the mapping, the preview, the dry run, the import, the report
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class ImportMapping extends \Exponential\Runnable\ModuleView
{
    const PREVIEW_ROWS = 10;

    protected static function tr( $text, $params = array() )
    {
        return \ezpI18n::tr( 'cjw_newsletter/importexport', $text, null, $params );
    }

    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );
        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $importId = (int)$Params['ImportId'];
        $userParameters = isset( $Params['UserParameters'] ) && is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();

        if ( $importId === 0 )
            return $this->upload( $module, $http, $userParameters );

        $import = \CjwNewsletterImport::fetch( $importId );
        if ( !is_object( $import ) || $import->attribute( 'type' ) !== \CjwNewsletterMappedImport::TYPE )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $listObject = \CjwNewsletterUtils::contentObject( (int)$import->attribute( 'list_contentobject_id' ) );
        $listNode = $listObject instanceof \eZContentObject ? $listObject->attribute( 'main_node' ) : null;
        if ( !$listNode instanceof \eZContentObjectTreeNode )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );

        if ( $http->hasPostVariable( 'CancelButton' ) )
            return $this->viewResult( null, $module->redirectTo( 'newsletter/subscription_list/' . $listNode->attribute( 'node_id' ) ) );

        $errors = array();
        $jobId = '';
        $settings = \CjwNewsletterMappedImport::settings( $import );
        $done = (int)$import->attribute( 'status' ) === \CjwNewsletterMappedImport::STATUS_DONE;
        $posted = $http->hasPostVariable( 'PreviewButton' ) || $http->hasPostVariable( 'DryRunButton' )
                  || $http->hasPostVariable( 'ImportButton' ) || $http->hasPostVariable( 'SaveMappingButton' );

        if ( $posted && !$done )
        {
            $settings = $this->postedSettings( $http, $settings );
            if ( $http->hasPostVariable( 'ConsentSource' ) )
                $import->setAttribute( 'consent_source', mb_substr( trim( (string)$http->postVariable( 'ConsentSource' ) ), 0, 100 ) );
            $mapper = new \CjwNewsletterCsvMapper( (string)$import->attribute( 'data_text' ), $settings['delimiter'], $settings['has_header'], $settings['encoding'] );
            $settings['mapping'] = \CjwNewsletterCsvMapper::cleanMapping( $settings['mapping'], count( $mapper->header() ) );
            \CjwNewsletterMappedImport::storeSettings( $import, $settings );
            if ( (int)$import->attribute( 'status' ) < \CjwNewsletterMappedImport::STATUS_PREVIEWED )
                $import->setAttribute( 'status', \CjwNewsletterMappedImport::STATUS_PREVIEWED );
            $import->store();

            if ( $http->hasPostVariable( 'SaveMappingButton' ) )
            {
                $name = trim( (string)$http->postVariable( 'MappingName', '' ) );
                if ( $name === '' )
                    $errors[] = self::tr( 'Give the mapping a name to save it.' );
                else
                {
                    $mapping = $this->saveMapping( $name, $import, $settings, $mapper );
                    $import->setAttribute( 'mapping_id', (int)$mapping->attribute( 'id' ) );
                    $import->store();
                    \CjwNewsletterUI::notice( 'feedback', self::tr( 'The mapping "%name" was saved.', array( '%name' => $name ) ) );
                }
            }
            else if ( $http->hasPostVariable( 'DryRunButton' ) || $http->hasPostVariable( 'ImportButton' ) )
            {
                $dryRun = $http->hasPostVariable( 'DryRunButton' );
                if ( !in_array( 'email', $settings['mapping'], true ) )
                    $errors[] = self::tr( 'Map one column to the e-mail address.' );
                if ( !$dryRun && trim( (string)$import->attribute( 'consent_source' ) ) === '' )
                    $errors[] = self::tr( 'Say where the people gave their consent (the consent source).' );
                if ( !$errors )
                    $jobId = $this->start( $import, $dryRun, $errors );
            }
            $import = \CjwNewsletterImport::fetch( $importId );
            $done = (int)$import->attribute( 'status' ) === \CjwNewsletterMappedImport::STATUS_DONE;
        }

        $mapper = \CjwNewsletterMappedImport::mapper( $import );
        if ( !$mapper )
            $errors[] = self::tr( 'The file of this import is no longer there.' );
        $header = $mapper ? $mapper->header() : array();
        $mapping = \CjwNewsletterCsvMapper::cleanMapping( $settings['mapping'], count( $header ) );
        $previewRows = array();
        $samples = array();
        if ( $mapper )
        {
            foreach ( $mapper->rows( 20 ) as $row )
                foreach ( $row as $index => $cell )
                    if ( $cell !== '' && ( !isset( $samples[$index] ) || count( $samples[$index] ) < 3 ) )
                        $samples[$index][] = mb_substr( $cell, 0, 40 );
            $seen = array();
            foreach ( $mapper->rows( self::PREVIEW_ROWS ) as $index => $row )
            {
                $values = \CjwNewsletterCsvMapper::mapRow( $row, $mapping );
                $email = isset( $values['email'] ) ? mb_strtolower( trim( $values['email'] ) ) : '';
                $check = $email !== '' && isset( $seen[$email] ) ? array( 'status' => 'skip', 'reason' => 'duplicate' ) : $this->check( $values );
                $seen[$email] = true;
                $previewRows[] = array( 'line' => $index + 1 + ( $settings['has_header'] ? 1 : 0 ), 'cells' => $row, 'check' => $check );
            }
        }

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'import', $import );
        $tpl->setVariable( 'list_node', $listNode );
        $tpl->setVariable( 'settings', $settings );
        $tpl->setVariable( 'delimiter_name', \CjwNewsletterCsvMapper::delimiterName( $settings['delimiter'] ) );
        $tpl->setVariable( 'header', $header );
        $tpl->setVariable( 'samples', $samples );
        $tpl->setVariable( 'mapping', $mapping );
        $tpl->setVariable( 'fields', \CjwNewsletterCsvMapper::fieldNames() );
        $tpl->setVariable( 'reasons', \CjwNewsletterMappedImport::reasonNames() );
        $tpl->setVariable( 'preview_rows', $previewRows );
        $tpl->setVariable( 'row_count', $mapper ? $mapper->rowCount() : 0 );
        $tpl->setVariable( 'encodings', \CjwNewsletterCsvMapper::$encodings );
        $tpl->setVariable( 'delimiters', array_keys( \CjwNewsletterCsvMapper::$delimiters ) );
        $tpl->setVariable( 'output_formats', $this->outputFormats( $listObject ) );
        $tpl->setVariable( 'consent_source', \CjwNewsletterMappedImport::consentSource( $import ) );
        $tpl->setVariable( 'result', \CjwNewsletterMappedImport::readResult( $importId ) );
        $tpl->setVariable( 'is_done', $done );
        $tpl->setVariable( 'job_id', $jobId );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );
        $tpl->setVariable( 'saved_mapping', (int)$import->attribute( 'mapping_id' ) ? \CjwNewsletterImportMapping::fetch( (int)$import->attribute( 'mapping_id' ) ) : null );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/importexport/import_mapping.tpl' );
        $Result['path'] = $this->path( $listNode, self::tr( 'CSV import %id', array( '%id' => $importId ) ) );
        return $this->viewResult( $Result, null );
    }

    /**
     * The upload form and the upload itself.
     */
    protected function upload( $module, $http, $userParameters )
    {
        $nodeId = isset( $userParameters['list'] ) ? (int)$userParameters['list'] : (int)$http->postVariable( 'ListNodeId', 0 );
        $listNode = $nodeId ? \eZContentObjectTreeNode::fetch( $nodeId ) : null;
        if ( !$listNode instanceof \eZContentObjectTreeNode || !\CjwNewsletterEznewsletterMigration::isListObject( $listNode->attribute( 'contentobject_id' ) ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $listId = (int)$listNode->attribute( 'contentobject_id' );
        if ( $http->hasPostVariable( 'CancelButton' ) )
            return $this->viewResult( null, $module->redirectTo( 'newsletter/subscription_list/' . $nodeId ) );

        $errors = array();
        if ( $http->hasPostVariable( 'UploadButton' ) )
        {
            if ( !\eZHTTPFile::canFetch( 'UploadCsvFile' ) )
                $errors[] = self::tr( 'Choose a CSV file.' );
            else
            {
                $import = $this->storeUpload( $http, $listId, $errors );
                if ( $import )
                    return $this->viewResult( null, $module->redirectTo( 'newsletter/import_mapping/' . (int)$import->attribute( 'id' ) ) );
            }
        }

        $mappings = array_merge( \CjwNewsletterImportMapping::fetchListByListContentobjectId( $listId ),
                                 \CjwNewsletterImportMapping::fetchListByListContentobjectId( 0 ) );
        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'list_node', $listNode );
        $tpl->setVariable( 'mappings', $mappings );
        $tpl->setVariable( 'encodings', \CjwNewsletterCsvMapper::$encodings );
        $tpl->setVariable( 'delimiters', array_keys( \CjwNewsletterCsvMapper::$delimiters ) );
        $tpl->setVariable( 'default_consent_source', \CjwNewsletterMappedImport::consentSource( \CjwNewsletterImport::create( $listId, \CjwNewsletterMappedImport::TYPE ) ) );
        $tpl->setVariable( 'fields', \CjwNewsletterCsvMapper::fieldNames() );
        $tpl->setVariable( 'reasons', \CjwNewsletterMappedImport::reasonNames() );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );
        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/importexport/import_upload.tpl' );
        $Result['path'] = $this->path( $listNode, self::tr( 'CSV import with column mapping' ) );
        return $this->viewResult( $Result, null );
    }

    /**
     * @return \CjwNewsletterImport|false the new import with its file, false on an error (in $errors)
     */
    protected function storeUpload( $http, $listId, &$errors )
    {
        $file = \eZHTTPFile::fetch( 'UploadCsvFile' );
        $tmp = $file ? (string)$file->attribute( 'filename' ) : '';
        $head = $tmp !== '' && is_file( $tmp ) ? (string)file_get_contents( $tmp, false, null, 0, 8192 ) : '';
        if ( $head === '' )
        {
            $errors[] = self::tr( 'The file is empty.' );
            return false;
        }
        if ( strpos( $head, "\0" ) !== false )
        {
            $errors[] = self::tr( 'This is not a CSV file.' );
            return false;
        }
        $settings = array( 'mapping' => array(),
                           'delimiter' => \CjwNewsletterCsvMapper::delimiterCharacter( (string)$http->postVariable( 'CsvDelimiter', 'semicolon' ) ),
                           'has_header' => $http->hasPostVariable( 'HasHeader' ),
                           'encoding' => in_array( $http->postVariable( 'Encoding', 'UTF-8' ), \CjwNewsletterCsvMapper::$encodings, true ) ? $http->postVariable( 'Encoding' ) : 'UTF-8',
                           'formats' => array( 0 ), 'update_existing' => true );
        $saved = (int)$http->postVariable( 'MappingId', 0 ) ? \CjwNewsletterImportMapping::fetch( (int)$http->postVariable( 'MappingId' ) ) : null;
        if ( $saved )
        {
            $settings['delimiter'] = \CjwNewsletterCsvMapper::delimiterCharacter( $saved->attribute( 'delimiter' ) );
            $settings['has_header'] = (bool)$saved->attribute( 'has_header' );
            $settings['encoding'] = in_array( $saved->attribute( 'encoding' ), \CjwNewsletterCsvMapper::$encodings, true ) ? $saved->attribute( 'encoding' ) : 'UTF-8';
        }

        $import = \CjwNewsletterImport::create( $listId, \CjwNewsletterMappedImport::TYPE, mb_substr( trim( (string)$http->postVariable( 'Note', '' ) ), 0, 255 ) );
        $import->setAttribute( 'consent_source', mb_substr( trim( (string)$http->postVariable( 'ConsentSource', '' ) ), 0, 100 ) );
        $import->setAttribute( 'mapping_id', $saved ? (int)$saved->attribute( 'id' ) : 0 );
        $import->store();
        $dir = \CjwNewsletterCsvMapper::directory();
        \eZDir::mkdir( $dir, false, true );
        $name = preg_replace( '/[^A-Za-z0-9._-]+/', '_', basename( str_replace( '\\', '/', (string)$file->attribute( 'original_filename' ) ) ) );
        $path = $dir . '/' . (int)$import->attribute( 'id' ) . '-' . date( 'Ymd-His' ) . '-mapped-' . ( $name !== '' ? $name : 'upload.csv' );
        if ( !copy( $tmp, $path ) )
        {
            $import->remove();
            $errors[] = self::tr( 'The file could not be stored in %dir. Check that the web server can write there.', array( '%dir' => $dir ) );
            return false;
        }
        $import->setAttribute( 'data_text', $path );
        $mapper = new \CjwNewsletterCsvMapper( $path, $settings['delimiter'], $settings['has_header'], $settings['encoding'] );
        $header = $mapper->header();
        if ( $saved )
        {
            $stored = json_decode( (string)$saved->attribute( 'mapping' ), true );
            $settings['mapping'] = \CjwNewsletterCsvMapper::mappingForHeader( is_array( $stored ) ? $stored : array(), $header );
            if ( trim( (string)$import->attribute( 'consent_source' ) ) === '' )
                $import->setAttribute( 'consent_source', (string)$saved->attribute( 'consent_source' ) );
        }
        else
            $settings['mapping'] = $mapper->guessMapping();
        $settings['mapping'] = \CjwNewsletterCsvMapper::cleanMapping( $settings['mapping'], count( $header ) );
        \CjwNewsletterMappedImport::storeSettings( $import, $settings );
        $import->store();
        return $import;
    }

    /** @return array the settings of the mapping form */
    protected function postedSettings( $http, $settings )
    {
        $settings['mapping'] = $http->hasPostVariable( 'Mapping' ) && is_array( $http->postVariable( 'Mapping' ) ) ? $http->postVariable( 'Mapping' ) : $settings['mapping'];
        if ( $http->hasPostVariable( 'CsvDelimiter' ) )
            $settings['delimiter'] = \CjwNewsletterCsvMapper::delimiterCharacter( (string)$http->postVariable( 'CsvDelimiter' ) );
        if ( $http->hasPostVariable( 'Encoding' ) && in_array( $http->postVariable( 'Encoding' ), \CjwNewsletterCsvMapper::$encodings, true ) )
            $settings['encoding'] = $http->postVariable( 'Encoding' );
        $settings['has_header'] = $http->hasPostVariable( 'HasHeader' );
        $settings['update_existing'] = $http->hasPostVariable( 'UpdateExisting' );
        $formats = $http->hasPostVariable( 'Formats' ) ? array_values( array_unique( array_map( 'intval', (array)$http->postVariable( 'Formats' ) ) ) ) : array();
        $formats = array_values( array_intersect( $formats, array( 0, 1 ) ) );
        $settings['formats'] = $formats ? $formats : array( 0 );
        return $settings;
    }

    /** @return \CjwNewsletterImportMapping the stored mapping (by column name when the file has a header) */
    protected function saveMapping( $name, $import, $settings, $mapper )
    {
        $byName = array();
        $header = $mapper->header();
        foreach ( $settings['mapping'] as $index => $field )
            $byName[$settings['has_header'] && isset( $header[$index] ) ? $header[$index] : (string)$index] = $field;
        $now = time();
        $mapping = \CjwNewsletterImportMapping::create( array(
            'name' => mb_substr( $name, 0, 255 ), 'list_contentobject_id' => (int)$import->attribute( 'list_contentobject_id' ),
            'mapping' => json_encode( $byName ), 'delimiter' => $settings['delimiter'], 'has_header' => $settings['has_header'] ? 1 : 0,
            'encoding' => $settings['encoding'], 'consent_source' => (string)$import->attribute( 'consent_source' ),
            'creator_contentobject_id' => (int)\eZUser::currentUserID(), 'created' => $now, 'modified' => $now ) );
        $mapping->store();
        return $mapping;
    }

    /**
     * Starts the import or its dry run: in the background when [NewsletterCsvImportSettings] ImportInBackground
     * allows it and a background run can be started, else in this request.
     *
     * @return string the job id, '' when it ran here
     */
    protected function start( $import, $dryRun, &$errors )
    {
        $ini = \eZINI::instance( 'cjw_newsletter.ini' );
        $background = !$ini->hasVariable( 'NewsletterCsvImportSettings', 'ImportInBackground' )
                      || $ini->variable( 'NewsletterCsvImportSettings', 'ImportInBackground' ) != 'disabled';
        $jobError = '';
        $arguments = array( '--import-id=' . (int)$import->attribute( 'id' ) );
        if ( $dryRun )
            $arguments[] = '--dry-run';
        $jobId = $background ? \CjwNewsletterJob::start( 'import', $arguments, $jobError ) : false;
        if ( $jobId )
            return $jobId;
        $result = \CjwNewsletterMappedImport::run( $import, $dryRun );
        if ( $result['error'] !== '' )
        {
            $texts = array( 'file' => self::tr( 'The file of this import is no longer there.' ), 'done' => self::tr( 'This import was done already.' ),
                            'no_email' => self::tr( 'Map one column to the e-mail address.' ) );
            $errors[] = isset( $texts[$result['error']] ) ? $texts[$result['error']] : $result['error'];
        }
        else if ( $dryRun )
            \CjwNewsletterUI::notice( 'feedback', self::tr( 'Dry run done: nothing was written. See the report below.' ) );
        else
        {
            \CjwNewsletterRunner::recordRun( \CjwNewsletterRunner::LAST_IMPORT, array( 'rows' => $result['totals']['rows'],
                'users' => $result['totals']['users_created'], 'subscriptions' => $result['totals']['subscriptions_created'],
                'skipped' => $result['totals']['skipped'], 'errors' => $result['totals']['errors'] ), \eZUser::currentUser()->attribute( 'login' ) );
            \CjwNewsletterUI::notice( 'feedback', self::tr( 'The import is done.' ) );
        }
        return '';
    }

    /**
     * What the import would do with a preview row.
     * @return array status (ok, update, skip), reason
     */
    protected function check( $values )
    {
        $email = isset( $values['email'] ) ? mb_strtolower( trim( $values['email'] ) ) : '';
        if ( $email === '' )
            return array( 'status' => 'skip', 'reason' => 'missing_email' );
        if ( !\ezcMailTools::validateEmailAddress( $email ) )
            return array( 'status' => 'skip', 'reason' => 'invalid' );
        $blocked = \CjwNewsletterImportConsent::blockReason( $email );
        if ( $blocked !== null )
            return array( 'status' => 'skip', 'reason' => $blocked );
        $user = \CjwNewsletterUser::fetchByEmail( $email );
        if ( is_object( $user ) && (int)$user->attribute( 'status' ) === \CjwNewsletterUser::STATUS_REMOVED_SELF )
            return array( 'status' => 'skip', 'reason' => 'removed_self' );
        $notes = array();
        \CjwNewsletterMappedImport::cleanValues( $values, $notes );
        return array( 'status' => is_object( $user ) ? 'update' : 'new', 'reason' => '', 'notes' => $notes );
    }

    /** @return array id => name of the output formats of the list */
    protected function outputFormats( $listObject )
    {
        $list = false;
        foreach ( $listObject->attribute( 'data_map' ) as $attribute )
            if ( $attribute->attribute( 'data_type_string' ) === 'cjwnewsletterlist' )
                $list = $attribute->attribute( 'content' );
        $formats = is_object( $list ) ? (array)$list->attribute( 'output_format_array' ) : array();
        return $formats ? $formats : \CjwNewsletterList::getAvailableOutputFormatArray();
    }

    protected function path( $listNode, $last )
    {
        $path = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ) );
        $system = $listNode->attribute( 'parent' );
        if ( $system )
            $path[] = array( 'url' => $system->attribute( 'url_alias' ), 'text' => $system->attribute( 'name' ) );
        $path[] = array( 'url' => $listNode->attribute( 'url_alias' ), 'text' => $listNode->attribute( 'name' ) );
        $path[] = array( 'url' => 'newsletter/subscription_list/' . $listNode->attribute( 'node_id' ), 'text' => \ezpI18n::tr( 'cjw_newsletter/subscription_list', 'Subscriptions' ) );
        $path[] = array( 'url' => false, 'text' => $last );
        return $path;
    }
}

}
