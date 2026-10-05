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
        $mailboxId = isset( $Params['MailboxId'] ) ? (int)$Params['MailboxId'] : 0;

        // the mail account: a new one for 0, an existing one for an id
        $mailboxObject = new \CjwNewsletterMailbox();
        if ( $mailboxId > 0 )
        {
            $mailboxObject = $mailboxObject->fetchMailboxDataForEdit( $mailboxId );
            // an id that does not exist is an error, not a mail account to fill
            if ( !is_object( $mailboxObject ) )
            {
                return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
            }
        }
        $redirect = $this->localPath( $http->hasPostVariable( 'redirect' ) ? $http->postVariable( 'redirect' ) : '' );
        $errors = array();
        $confirmRemove = false;

        if ( $http->hasPostVariable( 'DiscardButton' ) || $http->hasPostVariable( 'CancelButton' ) )
        {
            return $this->viewResult( null, $module->redirectTo( $redirect ) );
        }
        elseif ( $http->hasPostVariable( 'RemoveButton' ) && $mailboxId > 0 )
        {
            // step one: ask
            $confirmRemove = true;
        }
        elseif ( $http->hasPostVariable( 'ConfirmRemoveButton' ) && $mailboxId > 0 )
        {
            $mailboxObject->remove();
            \CjwNewsletterUI::notice( 'feedback', ezi18n( 'cjw_newsletter/mailbox_edit', 'The mail account was removed. The mails it collected stay.' ) );
            return $this->viewResult( null, $module->redirectTo( $redirect ) );
        }
        elseif ( $http->hasPostVariable( 'PublishButton' ) )
        {
            $data = array();
            foreach ( array( 'email' => '', 'server' => '', 'port' => '', 'user_name' => '', 'password' => '', 'type' => 'imap',
                             'is_activated' => 0, 'is_ssl' => 0, 'delete_mails_from_server' => 0 ) as $field => $default )
            {
                $data[$field] = $http->hasPostVariable( $field ) ? trim( (string)$http->postVariable( $field ) ) : $default;
            }
            if ( !in_array( $data['type'], array( 'imap', 'pop3' ) ) )
            {
                $data['type'] = 'imap';
            }
            foreach ( array( 'is_activated', 'is_ssl', 'delete_mails_from_server' ) as $flag )
            {
                $data[$flag] = (int)$data[$flag] ? 1 : 0;
            }
            if ( $data['email'] === '' || !\eZMail::validate( $data['email'] ) )
            {
                $errors['email'] = ezi18n( 'cjw_newsletter/mailbox_edit', 'Enter a valid email address.' );
            }
            if ( $data['server'] === '' || !preg_match( '/^[A-Za-z0-9]([A-Za-z0-9.\-]*[A-Za-z0-9])?$/', $data['server'] ) )
            {
                $errors['server'] = ezi18n( 'cjw_newsletter/mailbox_edit', 'Enter the name of the mail server, for example mail.example.com.' );
            }
            if ( $data['port'] !== '' && ( !ctype_digit( $data['port'] ) || (int)$data['port'] > 65535 ) )
            {
                $errors['port'] = ezi18n( 'cjw_newsletter/mailbox_edit', 'The port is a number from 1 to 65535. Leave it empty for the default of the type.' );
            }
            $data['port'] = $data['port'] === '' ? 0 : (int)$data['port'];
            if ( $data['user_name'] === '' )
            {
                $errors['user_name'] = ezi18n( 'cjw_newsletter/mailbox_edit', 'Enter the user name.' );
            }
            if ( $data['password'] === '' )
            {
                if ( $mailboxId > 0 )
                {
                    // the form never shows the password; empty keeps the stored one
                    unset( $data['password'] );
                }
                else
                {
                    $errors['password'] = ezi18n( 'cjw_newsletter/mailbox_edit', 'Enter the password.' );
                }
            }
            if ( !$errors )
            {
                $mailboxObject->storeMailboxData( $mailboxId, $data );
                \CjwNewsletterUI::notice( 'feedback', ezi18n( 'cjw_newsletter/mailbox_edit', 'The mail account %email was saved.', '', array( '%email' => $data['email'] ) ) );
                return $this->viewResult( null, $module->redirectTo( $redirect ) );
            }
            // show what was typed, not what is stored
            foreach ( $data as $field => $value )
            {
                $mailboxObject->setAttribute( $field, $value );
            }
        }

        $tpl = templateInit();

        $viewParameters = array( 'offset'     => 0,
                                 'namefilter' => '' );

        $userParameters = $Params[ 'UserParameters' ];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $tpl->setVariable( 'view_parameters', $viewParameters );
        $tpl->setVariable( 'mailbox', $mailboxObject );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'confirm_remove', $confirmRemove );
        $tpl->setVariable( 'redirect_uri', ltrim( $redirect, '/' ) );

        $Result = array();

        $Result[ 'content' ] = $tpl->fetch( $templateFile );
        $Result['path'] = array( array( 'url'  => 'newsletter/index',
                                        'text' => ezi18n( 'cjw_newsletter', 'Newsletter' ) ),
                                 array( 'url'  => 'newsletter/mailbox_list',
                                        'text' => ezi18n( 'cjw_newsletter/mailbox_item_list', 'Mail accounts' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
