<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/import_view.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/import_view.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/import_view.php:
 *
 *
 * File import_view.php
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

class ImportView extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $http = \eZHTTPTool::instance();
        $module = $Params['Module'];
        $templateFile = 'design:newsletter/import_view.tpl';

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $importId = (int) $Params['ImportId'];
        $importObject = \CjwNewsletterImport::fetch( $importId );

        $viewParameters = array( 'offset' => 0,
                                 'namefilter' => '' );

        $userParameters = $Params['UserParameters'];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        if( !is_object( $importObject ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        if( $http->hasPostVariable( 'RemoveSubsciptionsByAdminButton' ) )
        {
            $removeResult = $importObject->removeActiveSubscriptionsByAdmin();
        }

        $tpl = templateInit();

        $tpl->setVariable( 'import_object', $importObject );
        $tpl->setVariable( 'view_parameters', $viewParameters );

        $Result = array();

        $Result['content'] = $tpl->fetch( $templateFile );


        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),
                                  array( 'url'  => 'newsletter/import_list',
                                         'text' => ezi18n( 'cjw_newsletter/import_view', 'Imports' ) ),
                                  array( 'url'  => false,
                                         'text' => ezi18n( 'cjw_newsletter/import_view', 'Import details' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
