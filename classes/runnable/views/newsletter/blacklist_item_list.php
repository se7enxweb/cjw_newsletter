<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/blacklist_item_list.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/blacklist_item_list.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/blacklist_item_list.php:
 *
 *
 * File blacklist_item_list.php
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

class BlacklistItemList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $templateFile = 'design:newsletter/blacklist_item_list.tpl';

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $http = \eZHTTPTool::instance();
        $tpl = templateInit();

        $vp = \CjwNewsletterUI::listParameters( $Params, array( 'created', 'email', 'id' ), 25 );
        $user = isset( $Params['UserParameters'] ) && is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();
        if ( !isset( $user['order'] ) )
        {
            $vp['order'] = 'desc';
        }
        $vp['limit'] = isset( $user['limit'] ) && in_array( (int)$user['limit'], array( 10, 25, 50, 100 ) ) ? (int)$user['limit'] : 25;

        if ( $http->hasPostVariable( 'FilterButton' ) )
        {
            $vp['q'] = mb_substr( trim( (string)$http->postVariable( 'Filter' ) ), 0, 100 );
            return $this->viewResult( null, $module->redirectTo( \CjwNewsletterUI::listURL( 'blacklist_item_list', $vp, array( 'offset' => 0 ) ) ) );
        }

        $total = 0;
        $blacklistItemList = \CjwNewsletterBlacklistItem::fetchPage( $vp['q'], $vp['sort'], $vp['order'], $vp['limit'], $vp['offset'], $total );

        $tpl->setVariable( 'view_parameters', array( 'offset' => $vp['offset'], 'q' => rawurlencode( $vp['q'] ), 'sort' => $vp['sort'], 'order' => $vp['order'] ) );
        $tpl->setVariable( 'blacklist_item_list', $blacklistItemList );
        $tpl->setVariable( 'blacklist_item_list_count', $total );
        $tpl->setVariable( 'all_count', \CjwNewsletterBlacklistItem::fetchAllBlacklistItemsCount() );
        $tpl->setVariable( 'limit', $vp['limit'] );
        $tpl->setVariable( 'vp', $vp );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( $templateFile );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),
                                  array( 'url'  => false,
                                         'text' => ezi18n( 'cjw_newsletter/blacklist_item_list', 'Blacklists' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
