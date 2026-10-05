<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/mailbox_list.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/mailbox_list.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/mailbox_list.php:
 *
 *
 * File mailbox_list.php
 *
 * List all stored mailboxes.
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

class MailboxList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $templateFile = "design:newsletter/mailbox_list.tpl";

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $mailboxObject = new \CjwNewsletterMailbox( true );

        $listMailboxesCount = 0;
        $listMailboxes = array();

        // return array with mailbox objects
        // TODO result check (is array or object etc )
        if ( is_object( $mailboxObject ) )
        {
            // null when there are no mailboxes, which PHP 8's count() refuses
            $listMailboxes = $mailboxObject->fetchAllMailboxes();
            if ( !is_array( $listMailboxes ) )
                $listMailboxes = array();
            $listMailboxesCount = count( $listMailboxes );
        }

        $tpl = \eZTemplate::factory();

        $viewParameters = array( 'offset' => 0,
                                 'namefilter' => '',
                                 'redirect_uri' => $module->currentRedirectionURI() );

        $userParameters = $Params['UserParameters'];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $tpl->setVariable( 'view_parameters', $viewParameters );

        $tpl->setVariable( 'mailbox_list', $listMailboxes );
        $tpl->setVariable( 'mailbox_list_count', $listMailboxesCount );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();

        $Result['content'] = $tpl->fetch( $templateFile );
        $Result['path'] = array( array( 'url'  => 'newsletter/index',
                                        'text' => \ezpI18n::tr( 'cjw_newsletter', 'Newsletter' ) ),
                                 array( 'url'  => false,
                                        'text' => \ezpI18n::tr( 'cjw_newsletter/mailbox_item_list', 'Mail accounts' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
