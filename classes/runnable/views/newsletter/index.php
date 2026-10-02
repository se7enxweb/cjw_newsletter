<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/index.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/index.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/index.php:
 *
 *
 * File index.php
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

class Index extends \Exponential\Runnable\ModuleView
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

        $module = $Params[ 'Module' ];
        $http = \eZHTTPTool::instance();

        $viewParameters = array( 'offset' => 0,
                                 'namefilter' => '' );

        $userParameters = $Params['UserParameters'];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $tpl = templateInit();
        $tpl->setVariable( 'view_parameters', $viewParameters );

        $tpl->setVariable( 'current_siteaccess', $viewParameters );
        $Result = array();
        $Result['content'] = $tpl->fetch( "design:newsletter/index.tpl" );
        $Result['path'] = array( array( 'url'  => false,
                                        'text' => ezi18n( 'cjw_newsletter', 'Newsletter' ) ),
                                 array( 'url'  => false,
                                        'text' => ezi18n( 'cjw_newsletter/index', 'Dashboard' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
