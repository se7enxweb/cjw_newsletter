<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/subscription_list_csvexport.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/subscription_list_csvexport.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/subscription_list_csvexport.php:
 *
 *
 * File subscription_list_csvexport.php
 *
 * export subscription data to  csv
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage modules
 * @filesource
 *
 */

namespace
{
if ( !function_exists( 'getDataForCsv' ) ) {
/**
 * fetch data for csv export
 *
 * @param integer $listContentObjectId
 * @return array with data
 */
function getDataForCsv( $listContentObjectId, $limit = 0, $module = false )
{
    if ( isset( $listContentObjectId ) )
    {
        $db = eZDB::instance();

        // set optional limit, for example, for the preview of csv export;
        // the database driver writes it in its own dialect (LIMIT is not Oracle SQL)
        if ( isset( $limit ) && $limit > 0 )
            $qryParams = array( 'limit' => (int) $limit );
        // empty
        else
            $qryParams = false;

        $listContentObjectId = (int) $listContentObjectId;

        // query for fetch user data of one list, with optional limit parameter
        // u.id, u.email, u.first_name, u.last_name, u.salutation
        $qryGetData = "SELECT s.id as s_id,
                              u.email,
                              u.first_name,
                              u.last_name,
                              u.salutation,
                              u.status as u_status,
                              u.custom_data_text_1,
                              u.custom_data_text_2,
                              u.custom_data_text_3,
                              u.custom_data_text_4,
                              s.status as s_status,
                              s.created as s_created,
                              s.modified as s_modified,
                              s.confirmed as s_confirmed,
                              s.approved as s_approved,
                              s.removed as s_removed,
                              s.newsletter_user_id,
                              s.output_format_array_string

                       FROM cjwnl_subscription s, cjwnl_user u
                       WHERE s.list_contentobject_id=$listContentObjectId
                       AND s.newsletter_user_id=u.id";
        // the preview shows the newest subscriptions, the file has them in the order they were made
        $qryGetData .= $qryParams ? ' ORDER BY s.id DESC' : ' ORDER BY s.id';

        // execute query
        $resQryGetData = $db->arrayQuery( $qryGetData, $qryParams );

        // exists results ?
        if ( is_array( $resQryGetData ) && count( $resQryGetData ) > 0 )
        {
            // array keys for csv title's
            $arrKeys = array_keys( $resQryGetData[ 0 ] );

            // set keys at the begin of array => first array element => title's
            array_unshift( $resQryGetData,$arrKeys );

            return $resQryGetData;
        }
        // error
        else
        {
          return false;
        }
    }
}
}
}

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SubscriptionListCsvexport extends \Exponential\Runnable\ModuleView
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

        $module  = $Params['Module'];
        $http    = \eZHTTPTool::instance();
        $nodeId  = (int) $Params['NodeId'];

        $arrCsvData          = array();
        $arrPreviewCsvData   = array();
        $arrTables           = array();
        $arrDisplayItems     = array();
        $strPreviewCsvData   = 'Csv preview not possible';
        $delimiter           = ';';
        $exportCsvFile       = false;

        // array with csv identifier ( sorted )
        $arrDisplayItems = array(
                                   'email',
                                   'first_name',
                                   'last_name',
                                   'salutation',
                                   'custom_data_text_1',
                                   'custom_data_text_2',
                                   'custom_data_text_3',
                                   'custom_data_text_4',
                                   's_status',
                                   's_created',
                                   's_confirmed',
                                   's_approved',
                                   's_removed',
                                   's_id',
                                   'newsletter_user_id',
                                   //'output_format_array_string'
                                );

        /**
         * Node
         */
        // fetch node for contentobject id
        $listNode = \eZContentObjectTreeNode::fetch( $nodeId );

        // check ListNode
        if ( !$listNode )
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        else
            $listContentObjectId = $listNode->attribute( 'contentobject_id' );

        $systemNode = $listNode->attribute( 'parent' );


        /**
         * Delimiter
         */
        // get delimiter
        if ( $http->hasVariable( 'CsvDelimiter' ) && $http->variable( 'CsvDelimiter' ) != '' )
            $delimiter = substr( (string)$http->variable( 'CsvDelimiter' ), 0, 1 );

        /**
         * CSV Preview
         */
        // preview for csv data export, fetch data with limit
        $arrPreviewCsvData = getDataForCsv( $listContentObjectId, 10, $module );

        // create csv string for preview

        if ( $arrPreviewCsvData !== FALSE )
        {
            $objCjwNLCsvPreview = new \CjwNewsletterCsvExport( $arrPreviewCsvData, $delimiter, $arrDisplayItems );

            // write csv string
            $resWrite = $objCjwNLCsvPreview->writeCsv();

            // save csv string ( if exists ) in local var
            if ( isset( $objCjwNLCsvPreview->CsvResult ) && $objCjwNLCsvPreview->CsvResult != '' )
            {
                $strPreviewCsvData = $objCjwNLCsvPreview->CsvResult;
            }
        }

        /**
         * Actions
         */
        // cancel
        if ( $http->hasPostVariable( 'CancelButton' ) )
        {
            $csvFilePath = '';

            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->redirectToView( 'subscription_list', array( $nodeId ) ) );
        }
        // export
        elseif ( $http->hasPostVariable( 'ExportButton' ) )
        {
            $exportCsvFile = true;

            /**
             * Export
             */
            // fetch data
            $arrCsvData = getDataForCsv( $listContentObjectId, 0, $module );

            if ( $arrCsvData !== FALSE )
            {
                //export data in csv format for download in webbrowser
                $objCjwNLCsvExport = new \CjwNewsletterCsvExport( $arrCsvData, $delimiter, $arrDisplayItems );

                // write csv string => $objCjwNLCsvExport->CsvResult
                $resWrite = $objCjwNLCsvExport->writeCsv();

                // create download csv file
                if ( $objCjwNLCsvExport->CsvResult != '' )
                    $resExport = $objCjwNLCsvExport->downloadCsvFile( $listContentObjectId );
                else
                    return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_ACCESS_DENIED, 'kernel' ) );
            }
            else
            {
                return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_ACCESS_DENIED, 'kernel' ) );
            }
        }

        $viewParameters = array(
                     'offset'       => 0,
                     'namefilter'   => ''
                     );

        $userParameters = $Params['UserParameters'];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $tpl = templateInit();
        $tpl->setVariable( 'view_parameters', $viewParameters );
        $tpl->setVariable( 'list_node', $listNode );
        $tpl->setVariable( 'csv_delimiter', $delimiter );
        $tpl->setVariable( 'arr_tables', $arrTables );
        $tpl->setVariable( 'str_preview_csv_data', $strPreviewCsvData );

        $Result = array();
        $Result[ 'content' ] = $tpl->fetch( 'design:newsletter/subscription_list_csvexport.tpl' );
        $Result[ 'path' ]    = array( array( 'url' => false,
                                             'text' => ezi18n( 'cjw_newsletter/subscription_list_csvexport', 'Subscription list CSV export' ) ) );

        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),

                                  array( 'url'  => $systemNode->attribute( 'url_alias' ),
                                         'text' => $systemNode->attribute( 'name' ) ),

                                  array( 'url'  => $listNode->attribute( 'url_alias' ),
                                         'text' => $listNode->attribute( 'name' ) ),

                                  array( 'url'  => 'newsletter/subscription_list/' . $nodeId,
                                         'text' => ezi18n( 'cjw_newsletter/subscription_list', 'Subscriptions' ) ),

                                  array( 'url'  => false,
                                         'text' => ezi18n( 'cjw_newsletter/subscription_list_csvexport', 'CSV export' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
