<?php
/**
 * newsletter/subscriber_export/<list content object id>: the subscribers of a list as a CSV file, with filters
 * (status, a date range) and a choice of columns. Policy function newsletter/import_export; every download is
 * recorded in the audit trail (data.export.csv).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SubscriberExport extends \Exponential\Runnable\ModuleView
{
    const PREVIEW_ROWS = 10;

    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );
        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $listId = (int)$Params['ListContentObjectId'];
        if ( !\CjwNewsletterEznewsletterMigration::isListObject( $listId ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $listObject = \eZContentObject::fetch( $listId );
        $listNode = $listObject->attribute( 'main_node' );

        if ( $http->hasPostVariable( 'CancelButton' ) )
            return $this->viewResult( null, $module->redirectTo( 'newsletter/subscription_list/' . (int)$listObject->attribute( 'main_node_id' ) ) );

        $posted = $http->hasPostVariable( 'ExportButton' ) || $http->hasPostVariable( 'PreviewButton' );
        $input = array();
        if ( $posted )
        {
            $input = array( 'statuses' => $http->hasPostVariable( 'Statuses' ) ? (array)$http->postVariable( 'Statuses' ) : array(),
                            'columns' => $http->hasPostVariable( 'Columns' ) ? (array)$http->postVariable( 'Columns' ) : array(),
                            'date_field' => (string)$http->postVariable( 'DateField', 'subscribed' ),
                            'date_from' => (string)$http->postVariable( 'DateFrom', '' ),
                            'date_to' => (string)$http->postVariable( 'DateTo', '' ),
                            'delimiter' => (string)$http->postVariable( 'CsvDelimiter', 'semicolon' ) );
        }
        else
        {
            // the default: the active subscribers
            $input = array( 'statuses' => array( \CjwNewsletterSubscription::STATUS_CONFIRMED, \CjwNewsletterSubscription::STATUS_APPROVED ) );
        }
        $filters = \CjwNewsletterSubscriberExport::cleanFilters( $input );

        if ( $http->hasPostVariable( 'ExportButton' ) )
        {
            $count = \CjwNewsletterSubscriberExport::count( $listId, $filters );
            \CjwNewsletterSubscriberExport::audit( $listId, $filters, $count, 'web' );
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . \CjwNewsletterSubscriberExport::fileName( $listId ) . '"' );
            header( 'Cache-Control: no-store' );
            header( 'X-Content-Type-Options: nosniff' );
            $out = fopen( 'php://output', 'w' );
            \CjwNewsletterSubscriberExport::write( $out, $listId, $filters );
            fclose( $out );
            \eZExecution::cleanExit();
        }

        $labels = array( 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'approved' => 'Approved', 'removed_self' => 'Unsubscribed',
                         'removed_admin' => 'Removed by an administrator', 'bounced_soft' => 'Soft bounce', 'bounced_hard' => 'Hard bounce',
                         'blacklisted' => 'Blacklisted' );
        $statusNames = array();
        foreach ( \CjwNewsletterSubscriberExport::statusNames() as $id => $name )
            $statusNames[$id] = \ezpI18n::tr( 'cjw_newsletter/importexport', isset( $labels[$name] ) ? $labels[$name] : $name );
        $columnNames = array();
        $fieldNames = \CjwNewsletterCsvMapper::fieldNames();
        $extra = array( 'subscription_status' => 'Subscription status', 'output_formats' => 'Output formats', 'subscribed' => 'Subscribed',
                        'confirmed' => 'Confirmed', 'approved' => 'Approved', 'removed' => 'Removed', 'user_status' => 'User status',
                        'subscription_id' => 'Subscription ID', 'newsletter_user_id' => 'Newsletter user ID', 'import_id' => 'Import ID' );
        foreach ( array_keys( \CjwNewsletterSubscriberExport::$columns ) as $column )
            $columnNames[$column] = isset( $fieldNames[$column] ) ? $fieldNames[$column]
                : \ezpI18n::tr( 'cjw_newsletter/importexport', isset( $extra[$column] ) ? $extra[$column] : $column );

        $tpl = templateInit();
        $tpl->setVariable( 'list_object', $listObject );
        $tpl->setVariable( 'list_node', $listNode );
        $tpl->setVariable( 'filters', $filters );
        $tpl->setVariable( 'filter_date_from', $filters['date_from'] ? date( 'Y-m-d', $filters['date_from'] ) : '' );
        $tpl->setVariable( 'filter_date_to', $filters['date_to'] ? date( 'Y-m-d', $filters['date_to'] ) : '' );
        $tpl->setVariable( 'delimiter_name', \CjwNewsletterCsvMapper::delimiterName( $filters['delimiter'] ) );
        $tpl->setVariable( 'delimiters', array_keys( \CjwNewsletterCsvMapper::$delimiters ) );
        $tpl->setVariable( 'status_names', $statusNames );
        $tpl->setVariable( 'column_names', $columnNames );
        $tpl->setVariable( 'date_fields', array_keys( \CjwNewsletterSubscriberExport::$dateFields ) );
        $tpl->setVariable( 'count', \CjwNewsletterSubscriberExport::count( $listId, $filters ) );
        $tpl->setVariable( 'preview', \CjwNewsletterSubscriberExport::fetchRows( $listId, $filters, self::PREVIEW_ROWS ) );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/importexport/subscriber_export.tpl' );
        $path = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ) );
        if ( $listNode )
        {
            $path[] = array( 'url' => $listNode->attribute( 'url_alias' ), 'text' => $listNode->attribute( 'name' ) );
            $path[] = array( 'url' => 'newsletter/subscription_list/' . $listNode->attribute( 'node_id' ), 'text' => \ezpI18n::tr( 'cjw_newsletter/subscription_list', 'Subscriptions' ) );
        }
        $path[] = array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/importexport', 'Subscriber export' ) );
        $Result['path'] = $path;
        return $this->viewResult( $Result, null );
    }
}

}
