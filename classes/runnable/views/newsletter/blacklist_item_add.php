<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/blacklist_item_add.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/blacklist_item_add.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/blacklist_item_add.php:
 *
 *
 * File blacklist_item_add.php
 *
 * Add an blacklist item
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

class BlacklistItemAdd extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $templateFile = 'design:newsletter/blacklist_item_add.tpl';

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $http = \eZHTTPTool::instance();
        $tpl = templateInit();

        $email = $http->hasVariable( 'Email' ) ? trim( (string)$http->variable( 'Email' ) ) : '';
        $note = $http->hasVariable( 'Note' ) ? trim( (string)$http->variable( 'Note' ) ) : '';
        $errors = array();

        if ( $http->hasVariable( 'DiscardButton' ) )
        {
            return $this->viewResult( null, $module->redirectTo( '/newsletter/blacklist_item_list' ) );
        }
        elseif ( $http->hasVariable( 'AddButton' ) )
        {
            if ( $email === '' )
            {
                $errors['email'] = ezi18n( 'cjw_newsletter/blacklist_item_add', 'Enter the email address.' );
            }
            elseif ( !\eZMail::validate( $email ) )
            {
                $errors['email'] = ezi18n( 'cjw_newsletter/blacklist_item_add', 'This is not a valid email address.' );
            }
            elseif ( mb_strlen( $email ) > 150 )
            {
                $errors['email'] = ezi18n( 'cjw_newsletter/blacklist_item_add', 'The email address is too long.' );
            }
            if ( mb_strlen( $note ) > 2000 )
            {
                $errors['note'] = ezi18n( 'cjw_newsletter/blacklist_item_add', 'The note is too long (2000 characters at most).' );
            }
            if ( !$errors )
            {
                $existing = \CjwNewsletterBlacklistItem::fetchByEmail( $email );
                if ( is_object( $existing ) )
                {
                    \CjwNewsletterUI::notice( 'warning', ezi18n( 'cjw_newsletter/blacklist_item_add', 'The address %email is on the blacklist already.', '', array( '%email' => $email ) ) );
                }
                else
                {
                    $item = \CjwNewsletterBlacklistItem::create( $email, $note );
                    // store() also blacklists the newsletter user of this address
                    $item->store();
                    $newsletterUser = $item->attribute( 'newsletter_user_object' );
                    \CjwNewsletterUI::notice( 'feedback', is_object( $newsletterUser )
                        ? ezi18n( 'cjw_newsletter/blacklist_item_add', 'Successfully adding newsletter user %nl_user_id with email %email to blacklist', '',
                                  array( '%nl_user_id' => $newsletterUser->attribute( 'id' ), '%email' => $newsletterUser->attribute( 'email' ) ) )
                        : ezi18n( 'cjw_newsletter/blacklist_item_add', 'Successfully adding email address %email to blacklist', '', array( '%email' => $email ) ) );
                }
                return $this->viewResult( null, $module->redirectTo( '/newsletter/blacklist_item_list' ) );
            }
        }

        $viewParameters = array( 'offset'     => 0,
                                 'namefilter' => '' );
        $userParameters = $Params[ 'UserParameters' ];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $tpl->setVariable( 'view_parameters', $viewParameters );
        $tpl->setVariable( 'email', $email );
        $tpl->setVariable( 'note', $note );
        $tpl->setVariable( 'errors', $errors );

        $Result = array();
        $Result[ 'content' ] = $tpl->fetch( $templateFile );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),
                                  array( 'url'  => 'newsletter/blacklist_item_list',
                                         'text' => ezi18n( 'cjw_newsletter/blacklist_item_list', 'Blacklists' ) ),
                                  array( 'url'  => false,
                                         'text' => ezi18n( 'cjw_newsletter/blacklist_item_add', 'Blacklist add' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
