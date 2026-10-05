<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/user_list.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/user_list.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/user_list.php:
 *
 *
 * File user_list.php
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

class UserList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $templateFile = 'design:newsletter/user_list.tpl';

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $tpl  = templateInit();
        $http = \eZHTTPTool::instance();

        // the parameters of the list: (q) text, (status) group, (list) list object id, (sort), (order), (offset), (limit)
        $vp = \CjwNewsletterUI::listParameters( $Params, array( 'email', 'name', 'status', 'created', 'id' ), 25 );
        $user = isset( $Params['UserParameters'] ) && is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();
        $groups = array( 'confirmed' => array( \CjwNewsletterUser::STATUS_CONFIRMED ),
                         'pending' => array( \CjwNewsletterUser::STATUS_PENDING, \CjwNewsletterUser::STATUS_PENDING_EZ_USER_REGISTER ),
                         'removed' => array( \CjwNewsletterUser::STATUS_REMOVED_SELF, \CjwNewsletterUser::STATUS_REMOVED_ADMIN ),
                         'bounced' => array( \CjwNewsletterUser::STATUS_BOUNCED_SOFT, \CjwNewsletterUser::STATUS_BOUNCED_HARD ),
                         'blacklisted' => array( \CjwNewsletterUser::STATUS_BLACKLISTED ) );
        $vp['status'] = isset( $user['status'] ) && isset( $groups[$user['status']] ) ? $user['status'] : '';
        $vp['list'] = isset( $user['list'] ) ? (int)$user['list'] : 0;
        $vp['limit'] = isset( $user['limit'] ) && in_array( (int)$user['limit'], array( 10, 25, 50, 100 ) ) ? (int)$user['limit'] : 25;

        $url = function ( $override = array() ) use ( $vp )
        {
            $vp2 = array_merge( $vp, $override );
            $path = 'newsletter/user_list';
            foreach ( array( 'offset' => 0, 'limit' => 25 ) as $key => $default )
            {
                if ( $vp2[$key] != $default ) { $path .= '/(' . $key . ')/' . (int)$vp2[$key]; }
            }
            if ( $vp2['q'] !== '' ) { $path .= '/(q)/' . rawurlencode( $vp2['q'] ); }
            if ( $vp2['status'] !== '' ) { $path .= '/(status)/' . $vp2['status']; }
            if ( $vp2['list'] ) { $path .= '/(list)/' . (int)$vp2['list']; }
            return $path . '/(sort)/' . $vp2['sort'] . '/(order)/' . $vp2['order'];
        };

        // a search from the form (and the old GET variable) becomes a URL, so that the page can be bookmarked and paged
        if ( $http->hasVariable( 'SearchUserEmail' ) || $http->hasPostVariable( 'SubmitUserSearch' ) )
        {
            $status = $http->hasVariable( 'StatusFilter' ) ? (string)$http->variable( 'StatusFilter' ) : '';
            return $this->viewResult( null, $module->redirectTo( $url( array( 'offset' => 0,
                'q' => mb_substr( trim( (string)$http->variable( 'SearchUserEmail' ) ), 0, 100 ),
                'status' => isset( $groups[$status] ) ? $status : '',
                'list' => $http->hasVariable( 'ListFilter' ) ? (int)$http->variable( 'ListFilter' ) : 0 ) ) ) );
        }

        $total = 0;
        $userList = \CjwNewsletterUser::fetchUserPage( $vp['q'], $vp['status'] !== '' ? $groups[$vp['status']] : array(), $vp['list'],
                                                       $vp['sort'], $vp['order'], $vp['limit'], $vp['offset'], $total );
        $summary = \CjwNewsletterDashboard::summary();

        $tpl->setVariable( 'user_list', $userList );
        $tpl->setVariable( 'user_list_count', $total );
        $tpl->setVariable( 'all_count', $summary['users']['total'] );
        $tpl->setVariable( 'lists', $summary['lists'] );
        $tpl->setVariable( 'vp', $vp );
        $tpl->setVariable( 'urls', array( 'base' => $url( array( 'offset' => 0 ) ),
                                          'sort_email' => $url( array( 'sort' => 'email', 'order' => $vp['sort'] == 'email' && $vp['order'] == 'asc' ? 'desc' : 'asc', 'offset' => 0 ) ),
                                          'sort_name' => $url( array( 'sort' => 'name', 'order' => $vp['sort'] == 'name' && $vp['order'] == 'asc' ? 'desc' : 'asc', 'offset' => 0 ) ),
                                          'sort_status' => $url( array( 'sort' => 'status', 'order' => $vp['sort'] == 'status' && $vp['order'] == 'asc' ? 'desc' : 'asc', 'offset' => 0 ) ),
                                          'sort_created' => $url( array( 'sort' => 'created', 'order' => $vp['sort'] == 'created' && $vp['order'] == 'asc' ? 'desc' : 'asc', 'offset' => 0 ) ),
                                          'limit_10' => $url( array( 'limit' => 10, 'offset' => 0 ) ), 'limit_25' => $url( array( 'limit' => 25, 'offset' => 0 ) ),
                                          'limit_50' => $url( array( 'limit' => 50, 'offset' => 0 ) ), 'limit_100' => $url( array( 'limit' => 100, 'offset' => 0 ) ) ) );
        // the navigator builds its links from the view parameters
        $tpl->setVariable( 'view_parameters', array( 'offset' => $vp['offset'], 'limit' => $vp['limit'], 'q' => rawurlencode( $vp['q'] ),
                                                     'status' => $vp['status'], 'list' => $vp['list'], 'sort' => $vp['sort'], 'order' => $vp['order'] ) );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );
        $tpl->setVariable( 'search_user_email', $vp['q'] );

        $Result = array();
        $Result['content'] = $tpl->fetch( $templateFile );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),
                                  array( 'url'  => false,
                                         'text' => ezi18n( 'cjw_newsletter/user_list', 'Users' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
