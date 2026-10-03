<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/mailbox_edit.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/mailbox_edit.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/mailbox_edit.php:
 *
 *
 * File mailbox_edit.php
 *
 * Add or edit mailboxes
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

class MailboxEdit extends \Exponential\Runnable\ModuleView
{
    /** A redirect target from a form: a path of this site, never another host. */
    private function localPath( $path )
    {
        $path = ltrim( (string)$path, '/\\' );
        if ( $path === '' || strpos( $path, ':' ) !== false )
        {
            $path = 'newsletter/mailbox_list';
        }
        return '/' . $path;
    }

    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $templateFile = "design:newsletter/mailbox_edit.tpl";

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $http = \eZHTTPTool::instance();

        // create new CjwNewsletterMailbox object
        $mailboxObject = new \CjwNewsletterMailbox();

        if ( isset( $Params[ 'MailboxId' ] ) )
        {
            // if id > 0 => edit view | 0 = add view
            if ( $Params[ 'MailboxId' ] > 0 )
            {
                if ( is_object( $mailboxObject ) )
                {
                    // fetch mailbox data to edit by id
                    $mailboxObject = $mailboxObject->fetchMailboxDataForEdit( $Params[ 'MailboxId' ] );
                }
            }

            // an id that does not exist is an error, not a mailbox to fill
            if ( !is_object( $mailboxObject ) )
            {
                return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
            }

            // set data from edit/add view
            $mailboxData = array();
            if ( $http->hasPostVariable( 'edit' ) )
            {
                foreach ( array( 'email' => '', 'server' => '', 'port' => 0, 'user_name' => '', 'password' => '', 'type' => 'imap',
                                 'is_activated' => 0, 'is_ssl' => 0, 'delete_mails_from_server' => 0 ) as $field => $default )
                {
                    $mailboxData[$field] = $http->hasPostVariable( $field ) ? $http->postVariable( $field ) : $default;
                }
                if ( !in_array( $mailboxData['type'], array( 'imap', 'pop3' ) ) )
                {
                    $mailboxData['type'] = 'imap';
                }
            }

            // if PublishButton was pushed than store new data
            if ( $http->hasPostVariable( 'PublishButton' ) && $mailboxData )
            {
                // save data
                $resultStoreData = $mailboxObject->storeMailboxData( $Params[ 'MailboxId' ], $mailboxData );

                // positiv return, redirect to maibox list
                if ( $resultStoreData )
                {
                    $module->redirectTo( $this->localPath( $http->hasPostVariable( 'redirect' ) ? $http->postVariable( 'redirect' ) : '' ) );
                }
            }
            // Cancel
            elseif( $http->hasPostVariable( 'DiscardButton' ) )
            {
                $module->redirectTo( $this->localPath( $http->hasPostVariable( 'redirect' ) ? $http->postVariable( 'redirect' ) : '' ) );
            }
        }

        $tpl = templateInit();

        $viewParameters = array( 'offset'     => 0,
                                 'namefilter' => '' );

        $userParameters = $Params[ 'UserParameters' ];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $tpl->setVariable( 'view_parameters', $viewParameters );
        $tpl->setVariable( 'mailbox', $mailboxObject );

        $Result = array();

        $Result[ 'content' ] = $tpl->fetch( $templateFile );
        //$Result[ 'ui_context' ] = 'edit';
        $Result['path'] = array( array( 'url'  => false,
                                        'text' => ezi18n( 'cjw_newsletter', 'Newsletter' ) ),
                                 array( 'url'  => false,
                                        'text' => ezi18n( 'cjw_newsletter/mailbox_item_list', 'Mail accounts' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
