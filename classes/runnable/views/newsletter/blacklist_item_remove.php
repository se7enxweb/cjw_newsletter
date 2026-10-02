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

        require_once( 'kernel/common/i18n.php' );

        $http = \eZHTTPTool::instance();
        $blackListItemArray = array();
        $deleteIDArray = $http->hasVariable( 'BlacklistIDArray' ) ? $http->variable( 'BlacklistIDArray' ) : array();
        $email = $http->hasVariable( 'Email' ) ? trim( $http->variable( 'Email' ) ) : '';

        if ( $email )
        {
            $itemByEmail = \CjwNewsletterBlacklistItem::fetchByEmail( $email );
            if( !is_object( $itemByEmail ) )
            {
                \eZDebug::writeError( "Given email ($email) isn't blacklisted", 'newsletter/blacklist_item_remove' );
                return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
            }
            $blackListItemArray[] = $itemByEmail;
        }

        if ( $deleteIDArray )
        {
            foreach ( $deleteIDArray as $id )
            {
                $itemByID = \CjwNewsletterBlacklistItem::fetch( $id );
                if( !is_object( $itemByID ) )
                {
                    \eZDebug::writeError( "Given id ($id) isn't blacklisted", 'newsletter/blacklist_item_remove' );
                    return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
                }
                $blackListItemArray[] = $itemByID;
            }
        }

        foreach ( $blackListItemArray as $blackListItem )
        {
            $blackListItem->remove();
        }

        if ( $http->hasVariable( 'RedirectURI' ) )
            $module->redirectTo( trim( $http->variable( 'RedirectURI' ) ) );
        elseif ( $http->hasSessionVariable( 'LastAccessesURI' ) )
            $module->redirectTo( $http->sessionVariable( 'LastAccessesURI' ) );
        else
            $module->redirectToView( 'blacklist_item_list' );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
