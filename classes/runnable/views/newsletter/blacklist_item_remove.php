<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/blacklist_item_remove.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/blacklist_item_remove.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/blacklist_item_remove.php:
 *
 *
 * File blacklist_item_remove.php
 *
 * Removes a blacklist item
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

class BlacklistItemRemove extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $templateFile = 'design:newsletter/blacklist_item_remove.tpl';

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $http = \eZHTTPTool::instance();
        $tpl = templateInit();

        $redirect = $http->hasVariable( 'RedirectURI' )
            ? \CjwNewsletterUtils::localRedirectPath( $http->variable( 'RedirectURI' ), '/newsletter/blacklist_item_list' )
            : '/newsletter/blacklist_item_list';

        $items = array();
        $email = $http->hasVariable( 'Email' ) ? trim( (string)$http->variable( 'Email' ) ) : '';
        if ( $email !== '' )
        {
            $itemByEmail = \CjwNewsletterBlacklistItem::fetchByEmail( $email );
            if ( is_object( $itemByEmail ) )
            {
                $items[$itemByEmail->attribute( 'id' )] = $itemByEmail;
            }
        }
        $ids = $http->hasVariable( 'BlacklistIDArray' ) ? (array)$http->variable( 'BlacklistIDArray' ) : array();
        foreach ( $ids as $id )
        {
            $itemByID = \CjwNewsletterBlacklistItem::fetch( (int)$id );
            if ( is_object( $itemByID ) )
            {
                $items[$itemByID->attribute( 'id' )] = $itemByID;
            }
        }

        if ( $http->hasVariable( 'CancelButton' ) )
        {
            return $this->viewResult( null, $module->redirectTo( $redirect ) );
        }
        if ( !$items )
        {
            \CjwNewsletterUI::notice( 'warning', ezi18n( 'cjw_newsletter/blacklist_item_remove', 'Select at least one address to remove from the blacklist.' ) );
            return $this->viewResult( null, $module->redirectTo( $redirect ) );
        }
        if ( $http->hasVariable( 'ConfirmRemoveButton' ) )
        {
            foreach ( $items as $item )
            {
                $item->remove();
            }
            \CjwNewsletterUI::notice( 'feedback', ezi18n( 'cjw_newsletter/blacklist_item_remove', '%count addresses were removed from the blacklist.', '', array( '%count' => count( $items ) ) ) );
            return $this->viewResult( null, $module->redirectTo( $redirect ) );
        }

        // step one: show what would go, remove nothing
        $tpl->setVariable( 'items', array_values( $items ) );
        $tpl->setVariable( 'redirect_uri', ltrim( $redirect, '/' ) );

        $Result = array();
        $Result['content'] = $tpl->fetch( $templateFile );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),
                                  array( 'url'  => 'newsletter/blacklist_item_list',
                                         'text' => ezi18n( 'cjw_newsletter/blacklist_item_list', 'Blacklists' ) ),
                                  array( 'url'  => false,
                                         'text' => ezi18n( 'cjw_newsletter/blacklist_item_remove', 'Remove' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
