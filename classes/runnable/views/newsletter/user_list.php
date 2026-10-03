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
        $db   = \eZDB::instance();

        $searchUserEmail = false;
        $offset          = 0;
        $limit           = 10;

        $filterArray = array();
        //
        // filter examples
        //
        //$filterArray[] = array( 'cjwnl_user.email' => array( 'OR', array( 'like', '%@%' ), array( 'like', '%abc@%' ) ) );
        //$filterArray[] = array( 'cjwnl_user.email' => array( 'OR', array( 'AND', 'woldt', 'acd'  ), array( 'like', '%abc@%' ) ) );
        //$filterArray[] = array( 'cjwnl_user.last_name' => array( array( 'woldt', 'acd' ) ) );
        //$filterArray[] = array( 'cjwnl_user.last_name' => array( 'AND', 'woldt', 'acd'  ) );
        //$filterArray[] = array( 'cjwnl_subscription.list_contentobject_id' => array(  array( 132 , 109 ) ) );
        //$filterArray[] = array( 'cjwnl_subscription.status' => CjwNewsletterSubscription::STATUS_APPROVED );
        //$filterArray[] = array( 'cjwnl_user.email' =>  array( 'like', '%@%.de' ) );

        // get wanted user email and filter by itself
        if( $http->hasVariable( 'SearchUserEmail' ) )
        {
            // the filter escapes the value for SQL; the template washes it for HTML
            $searchUserEmail = trim( (string)$http->variable( 'SearchUserEmail' ) );
            if ( $searchUserEmail !== '' )
            {
                $filterArray[]   = array( 'cjwnl_user.email' =>  array( 'like', '%' . $searchUserEmail . '%' ) );
            }
        }

        // AND - all filter should match
        // OR - 1 one the filter should be match
        // AND-NOT - none of the filter should be matched

        $userListSearch = \CjwNewsletterUser::fetchUserListByFilter( $filterArray,
                                                                    $limit,
                                                                    $offset );

        $tpl->setVariable( 'user_list', $userListSearch );
        $tpl->setVariable( 'user_list_count', count( $userListSearch ) );

        $viewParameters = array( 'offset'     => 0,
                                 'namefilter' => '' );

        $searchParameters = array( 'search_user_email' => $searchUserEmail );

        $userParameters = $Params['UserParameters'];
        $viewParameters = array_merge( $viewParameters, $userParameters );
        $viewParameters = array_merge( $viewParameters, $searchParameters );

        $tpl->setVariable( 'view_parameters', $viewParameters );

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
