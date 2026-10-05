<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/settings.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/settings.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/settings.php:
 *
 *
 * File settings.php
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

class Settings extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );

        $module = $Params["Module"];
        $http = \eZHTTPTool::instance();

        $viewParameters = array();


        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'view_parameters', $viewParameters );

        //http://admin.eldorado-templin.info.jac400.in-mv.com/settings/view/eldorado-templin_admin/cjw_newsletter.ini

        $tpl->setVariable( 'current_siteaccess', $viewParameters );

        //$tpl->setVariable( 'link_array', $data['result']);

        //$tpl->setVariable( 'csv_data_not_ok', $invalidLinien );


        $currentSiteAccess = $GLOBALS['eZCurrentAccess'];
        $currentSiteAccessName = $currentSiteAccess['name'];

        $redirectUri = "/settings/view/$currentSiteAccessName/cjw_newsletter.ini";
        return $this->viewResult( isset( $Result ) ? $Result : null,  $module->redirectTo( $redirectUri ) );

        /*
        $Result = array();
        $Result['content'] = $tpl->fetch( "design:newsletter/index.tpl" );
        $Result['path'] = array( array( 'url' => false,
                                            'text' => 'newsletter' ),
                                     array( 'url' => false,
                                            'text' => 'index' ) );
        */

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
