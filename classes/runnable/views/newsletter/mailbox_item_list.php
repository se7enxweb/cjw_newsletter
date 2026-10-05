<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/mailbox_item_list.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/mailbox_item_list.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/mailbox_item_list.php:
 *
 *
 * File mailbox_item_list.php
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

class MailboxItemList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $templateFile = "design:newsletter/mailbox_item_list.tpl";

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $http = \eZHTTPTool::instance();
        $tpl = \eZTemplate::factory();

        // "Collect all mails" and "Parse mails" go on in the background (a mail server can take minutes) and show their progress here
        foreach ( array( 'ConnectMailboxButton' => '--collect-only', 'BounceMailItemButton' => '--parse-only' ) as $button => $argument )
        {
            if ( $http->hasPostVariable( $button ) )
            {
                $error = '';
                $jobID = \CjwNewsletterJob::start( 'mailbox', array( $argument ), $error );
                if ( !$jobID )
                {
                    \CjwNewsletterUI::notice( 'error', \ezpI18n::tr( 'extension/cjw_newsletter', 'The run could not be started: %reason', null, array( '%reason' => $error ) ) );
                    return $this->viewResult( null, $module->redirectToView( 'mailbox_item_list' ) );
                }
                return $this->viewResult( null, $module->redirectToView( 'mailbox_item_list', array(), array(), array( 'job' => $jobID ) ) );
            }
        }

        $userParameters = isset( $Params['UserParameters'] ) && is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();
        $viewParameters = array_merge( array( 'offset' => 0, 'namefilter' => '' ), $userParameters );
        $limit = isset( $userParameters['limit'] ) && in_array( (int)$userParameters['limit'], array( 10, 25, 50, 100 ) ) ? (int)$userParameters['limit'] : 25;
        $viewParameters['offset'] = max( 0, (int)$viewParameters['offset'] );

        // the newest mails first
        $mailboxItemList = \CjwNewsletterMailboxItem::fetchAllMailboxItems( $limit, $viewParameters['offset'], array( 'id' => 'desc' ) );
        $mailboxItemListCount = \CjwNewsletterMailboxItem::fetchAllMailboxItemsCount( );

        $tpl->setVariable( 'view_parameters', array( 'offset' => $viewParameters['offset'], 'limit' => $limit ) );
        $tpl->setVariable( 'mailbox_item_list', is_array( $mailboxItemList ) ? $mailboxItemList : array() );
        $tpl->setVariable( 'mailbox_item_list_count', $mailboxItemListCount );
        $tpl->setVariable( 'limit', $limit );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );
        $tpl->setVariable( 'job_id', isset( $userParameters['job'] ) && \CjwNewsletterJob::isID( $userParameters['job'] ) ? $userParameters['job'] : '' );
        $tpl->setVariable( 'active_mailboxes', count( (array)\CjwNewsletterMailbox::fetchAllActiveMailboxes() ) );

        $Result = array();
        $Result['content'] = $tpl->fetch( $templateFile );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                  array( 'url'  => false,
                                         'text' => \ezpI18n::tr( 'cjw_newsletter/mailbox_item_list', 'Bounces' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
