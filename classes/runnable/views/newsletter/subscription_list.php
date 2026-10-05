<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/subscription_list.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/subscription_list.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/subscription_list.php:
 *
 *
 * File subscription_list.php
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

class SubscriptionList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $tpl = \eZTemplate::factory();

        $nodeId = (int) $Params['NodeId'];

        $node = \eZContentObjectTreeNode::fetch( $nodeId );
        if( !is_object( $node ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        if( !$node->attribute( 'can_read' ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_ACCESS_DENIED, 'kernel' ) );
        }

        $viewParameters = array( 'offset' => 0,
                                 'namefilter' => '' );

        if( is_array( $Params['UserParameters'] ) )
        {
            $viewParameters = array_merge( $viewParameters, $Params['UserParameters'] );
        }

        $tpl->setVariable( 'view_parameters', $viewParameters );
        $tpl->setVariable( 'node', $node );

        $systemNode = $node->attribute( 'parent' );

        $Result = array();

        if ( $node->attribute( 'class_identifier' ) == 'cjw_newsletter_list_virtual' )
        {
            $templateFile = 'design:newsletter/subscription_list_virtual.tpl';
        }
        else
        {
            $templateFile = 'design:newsletter/subscription_list.tpl';
        }

        $Result['node_id'] = $nodeId;
        $Result['content'] = $tpl->fetch( $templateFile );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),

                                  array( 'url'  => $systemNode->attribute( 'url_alias' ),
                                         'text' => $systemNode->attribute( 'name' ) ),

                                  array( 'url'  => $node->attribute( 'url_alias' ),
                                         'text' => $node->attribute( 'name' ) ),

                                  array( 'url'  => false,
                                         'text' => \ezpI18n::tr( 'cjw_newsletter/subscription_list', 'Subscriptions' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
