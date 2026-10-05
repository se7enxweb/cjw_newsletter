<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/subscription_list_csvimport.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/subscription_list_csvimport.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/subscription_list_csvimport.php:
 *
 *
 * File subscription_list_csvimport.php
 *
 * import csv data to a subscription list
 * if an nl user with email of csv already exists => override existing data with csv data if it is not empty
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage modules
 * @filesource
 *
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SubscriptionListCsvimport extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $nodeId = (int) $Params['NodeId'];
        $importId = (int) $Params['ImportId'];
        $listNode = \eZContentObjectTreeNode::fetch( $nodeId );
        if ( !$listNode )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }
        $listContentObjectId = $listNode->attribute( 'contentobject_id' );
        $systemNode = $listNode->attribute( 'parent' );

        $cjwNewsletterIni = \eZINI::instance( 'cjw_newsletter.ini' );

        $csvDataArray = array();

        $utf8Encode = false;
        if ( $cjwNewsletterIni->hasVariable( 'NewsletterCsvImportSettings', 'DefaultUtf8Encode' ) )
        {
            if ( $cjwNewsletterIni->variable( 'NewsletterCsvImportSettings', 'DefaultUtf8Encode' ) == 'true' )
            {
                $utf8Encode = true;
            }
        }

        $csvDelimiter = ';';
        if ( $cjwNewsletterIni->hasVariable( 'NewsletterCsvImportSettings', 'DefaultCsvDelimiter' ) )
        {
            $csvDelimiter = $cjwNewsletterIni->variable( 'NewsletterCsvImportSettings', 'DefaultCsvDelimiter' );
        }

        $firstRowIsLabel = false;
        if ( $cjwNewsletterIni->hasVariable( 'NewsletterCsvImportSettings', 'DefaultFirstRowIsLabel' ) )
        {
            if ( $cjwNewsletterIni->variable( 'NewsletterCsvImportSettings', 'DefaultFirstRowIsLabel' ) == 'true' )
            {
                $firstRowIsLabel = true;
            }
        }

        $csvFieldMappingArray = array( 'email'      => '',
                                       'first_name' => '',
                                       'last_name'  => '',
                                       'salutation' => '' );

        if ( $cjwNewsletterIni->hasVariable( 'NewsletterCsvImportSettings', 'CsvFieldMappingArray' ) )
        {
            $csvFieldMappingArrayOverride = $cjwNewsletterIni->variable( 'NewsletterCsvImportSettings', 'CsvFieldMappingArray' );
            if ( array_key_exists ( 'email', $csvFieldMappingArrayOverride ) )
            {
                $csvFieldMappingArray = array();

                foreach ( $csvFieldMappingArrayOverride as $key => $item )
                {
                    $csvFieldMappingArray[ $key ] = $item;
                }
            }
        }

        $csvImportHasPrio = false;
        if ( $cjwNewsletterIni->hasVariable( 'NewsletterCsvImportSettings', 'CsvImportHasPrio' ) )
        {
            if ( $cjwNewsletterIni->variable( 'NewsletterCsvImportSettings', 'CsvImportHasPrio' ) == 'true' )
            {
                $csvImportHasPrio = true;
            }
        }

        $csvSupportedDelims = array( ',', ';', '\t', '|' );
        $csvFilePath = false;
        $importCsvFile = false;
        $selectedOutputFormatArray = array( 0 );
        $listSubscriptionArray = array();
        $note = '';
        $importObject = false;

        if ( \eZHTTPFile::canFetch( 'UploadCsvFile' ) )
        {
            $importId = 0;
        }
        if ( $http->hasPostVariable( 'CsvFilePath' ) )
        {
            $csvFilePath = $http->variable( 'CsvFilePath' );
            // only a file of the import directory can be named, never any other file of the server
            $importDirReal = realpath( \eZSys::varDirectory() . '/cjw_newsletter/csvimport' );
            $postedReal = is_string( $csvFilePath ) ? realpath( $csvFilePath ) : false;
            if ( !$importDirReal || !$postedReal || strpos( $postedReal, $importDirReal . DIRECTORY_SEPARATOR ) !== 0 )
            {
                $csvFilePath = false;
            }
        }
        if ( $http->hasPostVariable( 'SelectedOutputFormatArray' ) )
        {
            $selectedOutputFormatArray = $http->variable( 'SelectedOutputFormatArray' );
        }
        if ( $http->hasPostVariable( 'CsvDelimiter' ) )
        {
            $csvDelimiter = $http->variable( 'CsvDelimiter' );
            if ( !in_array( $csvDelimiter, $csvSupportedDelims  ) )
                return $this->viewResult( isset( $Result ) ? $Result : null,  $module->redirectToView( 'subscription_list_csvimport', array( $nodeId, $importId ), null, array( 'error' => 'CSV_DELIM_ERROR' ) ) );
        }
        if ( $http->hasPostVariable( 'FirstRowIsLabel' ) )
        {
            $firstRowIsLabel = true;
        }
        if ( $http->hasPostVariable( 'Note' ) )
        {
            $note = $http->variable( 'Note' );
        }

        if ( $http->hasPostVariable( 'CancelButton' ) )
        {
            $csvFilePath = '';
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->redirectToView( 'subscription_list', array( $nodeId ) ) );
        }
        elseif ( $http->hasPostVariable( 'ImportButton' ) )
        {
            // check if user has rights to import the users
            $user = \eZUser::currentUser();
            $access = $user->hasAccessTo( 'newsletter', 'subscription_list_csvimport_import' );
            if ( $access['accessWord'] == 'yes' )
            {
                $importCsvFile = true;
            }
            else
            {
                return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_ACCESS_DENIED, 'kernel' ) );
            }
        }

        // upload csv and store data
        if ( \eZHTTPFile::canFetch( 'UploadCsvFile' ) && $importId == 0 )
        {

            $importType = 'cjwnl_csv';
            $dataText = '';
            $remoteId = false;
            //$remoteId = 'csv:' . md5( $csvFilePath );

            // create new Import Object
            $importObject = \CjwNewsletterImport::create( $listContentObjectId,
                                                         $importType,
                                                         $note,
                                                         $dataText,
                                                         $remoteId );
            $importObject->store();

            $binaryFile = \eZHTTPFile::fetch( 'UploadCsvFile' );
            $filePathUpload = $binaryFile->attribute( 'filename' );

            $fileSep = \eZSys::fileSeparator();
            // $fileSize = filesize( $filePathUpload );
            //$siteDir =  eZSys::siteDir();
            $dir = \eZSys::varDirectory() . $fileSep . 'cjw_newsletter' . $fileSep . 'csvimport';

            $importId = $importObject->attribute('id');
            // the name the browser sent is a label, never a path
            $originalName = preg_replace( '/[^A-Za-z0-9._-]+/', '_', basename( str_replace( '\\', '/', (string)$binaryFile->attribute( 'original_filename' ) ) ) );
            $fileName = $importId .'-'. date( "Ymd-His", $importObject->attribute('created') ) .'-'. $originalName;
            $csvFilePath = $dir . $fileSep . $fileName;
            $importObject->setAttribute( 'data_text', $csvFilePath );
            $importObject->setAttribute( 'note', $note );

            // create dir
            \eZDir::mkdir( $dir, false, true );
            $createResult = copy( $filePathUpload, $csvFilePath );
            if ( !$createResult )
            {
                // the folder is not writable for the web server: say so instead of showing an import without rows
                $importObject->remove();
                \CjwNewsletterUI::notice( 'error', ezi18n( 'cjw_newsletter/subscription_list_csvimport', 'The file could not be stored in %dir. Check that the web server can write there.', '', array( '%dir' => $dir ) ) );
                return $this->viewResult( null, $module->redirectToView( 'subscription_list_csvimport', array( $nodeId, 0 ) ) );
            }

            $importObject->store();

            // after import object is created redirect to view with import_id
            //return $module->redirectToView( 'subscription_list_csvimport', array( $nodeId, $importId ) );
        }
        // load import Id if not 0
        else
        {
            if ( $importId != 0 )
            {
                $importObject = \CjwNewsletterImport::fetch( $importId );
                if ( !is_object( $importObject ) )
                {
                    return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
                }
                // get stored csv filepath from db
                $csvFilePath = $importObject->attribute( 'data_text' );
            }
        }

        // read import result
        if ( $importId && is_file( \CjwNewsletterImport::resultFilePath( $importId ) ) )
        {
            $listSubscriptionArray = \CjwNewsletterImport::readResult( $importId );
            $listSubscriptionArray = is_array( $listSubscriptionArray ) ? $listSubscriptionArray : array();
            $csvParserObject       = new \CjwNewsletterCsvParser( $csvFilePath, $csvDelimiter, $firstRowIsLabel, $csvFieldMappingArray, $utf8Encode );
            $csvDataArray          = $csvParserObject->getCsvDataArray();
        }
        else
        {
            if ( file_exists( $csvFilePath )  )
            {
                $csvParserObject = new \CjwNewsletterCsvParser( $csvFilePath, $csvDelimiter, $firstRowIsLabel, $csvFieldMappingArray, $utf8Encode );
                $csvDataArray    = $csvParserObject->getCsvDataArray();
            }
        }

        // read csv data


        //if ( file_exists( $csvFilePath )  )
        //{
            //$csvParserObject = new CjwNewsletterCsvParser( $csvFilePath, $csvDelimiter, $firstRowIsLabel, $csvFieldMappingArray, $utf8Encode );
            //$csvDataArray = $csvParserObject->getCsvDataArray();

            // start data import: a background run of the command, inline when none can be started
            $jobId = '';
            if ( $importCsvFile === TRUE && is_object( $importObject ) )
            {
                $delimiterNames = array( ',' => 'comma', ';' => 'semicolon', '\\t' => 'tab', '|' => 'pipe' );
                $arguments = array( '--import-id=' . (int)$importObject->attribute( 'id' ),
                                    '--delimiter=' . ( isset( $delimiterNames[$csvDelimiter] ) ? $delimiterNames[$csvDelimiter] : 'semicolon' ),
                                    '--formats=' . implode( '-', array_map( 'intval', (array)$selectedOutputFormatArray ) ) );
                if ( $firstRowIsLabel )
                {
                    $arguments[] = '--first-row-label';
                }
                if ( trim( (string)$note ) !== '' )
                {
                    $importObject->setAttribute( 'note', $note );
                    $importObject->store();
                }
                $jobError = '';
                // [NewsletterCsvImportSettings] ImportInBackground=disabled runs the import inside this request
                $inBackground = !$cjwNewsletterIni->hasVariable( 'NewsletterCsvImportSettings', 'ImportInBackground' )
                                || $cjwNewsletterIni->variable( 'NewsletterCsvImportSettings', 'ImportInBackground' ) != 'disabled';
                $jobId = $inBackground ? \CjwNewsletterJob::start( 'import', $arguments, $jobError ) : false;
                if ( !$jobId )
                {
                    // no background run possible here: do the work now
                    $jobId = '';
                    \CjwNewsletterImport::runCsvImport( $importObject, $csvDataArray, $listContentObjectId, $selectedOutputFormatArray, $csvImportHasPrio );
                    $listSubscriptionArray = \CjwNewsletterImport::readResult( $importId );
                    $listSubscriptionArray = is_array( $listSubscriptionArray ) ? $listSubscriptionArray : array();
                }
            }
        //}

        $viewParameters = array( 'offset' => 0,
                                 'namefilter' => '' );

        $userParameters = $Params['UserParameters'];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $tpl = templateInit();
        $tpl->setVariable( 'view_parameters', $viewParameters );
        $tpl->setVariable( 'list_node', $listNode );
        $tpl->setVariable( 'import_id', $importId );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );
        $tpl->setVariable( 'job_id', isset( $jobId ) ? $jobId : '' );
        $tpl->setVariable( 'import_object', $importObject );
        $tpl->setVariable( 'selected_output_format_array', $selectedOutputFormatArray );
        $tpl->setVariable( 'csv_data_array', $csvDataArray );
        $tpl->setVariable( 'list_subscription_array', $listSubscriptionArray );
        $tpl->setVariable( 'csv_field_mapping_array', $csvFieldMappingArray );
        $tpl->setVariable( 'csv_delimiter', $csvDelimiter );
        $tpl->setVariable( 'csv_supported_delims', $csvSupportedDelims );
        $tpl->setVariable( 'csv_file_path', $csvFilePath );
        $tpl->setVariable( 'first_row_is_label', $firstRowIsLabel );
        $tpl->setVariable( 'note', $note );
        if ( $http->hasPostVariable( 'RowNum' ) )
        {
            $tpl->setVariable( 'RowNum', $http->postVariable( 'RowNum' ) );
        }
        if ( isset( $warning ) )
        {
            $tpl->setVariable( 'warning', $warning );
        }
        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/subscription_list_csvimport.tpl' );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),

                                  array( 'url'  => $systemNode->attribute( 'url_alias' ),
                                         'text' => $systemNode->attribute( 'name' ) ),

                                  array( 'url'  => $listNode->attribute( 'url_alias' ),
                                         'text' => $listNode->attribute( 'name' ) ),

                                  array( 'url'  => 'newsletter/subscription_list/' . $nodeId,
                                         'text' => ezi18n( 'cjw_newsletter/subscription_list', 'Subscriptions' ) ),

                                  array( 'url'  => false,
                                         'text' => ezi18n( 'cjw_newsletter/subscription_list_csvimport', 'CSV import' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
