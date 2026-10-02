<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/import_list.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/import_list.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/import_list.php:
 *
 *
 * File import_list.php
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

class ImportList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $templateFile = 'design:newsletter/import_list.tpl';

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $http = \eZHTTPTool::instance();
        $tpl = templateInit();

        $http = \eZHTTPTool::instance();
        $db = \eZDB::instance();

        $viewParameters = array( 'offset' => 0,
                                 'namefilter' => '' );

        $userParameters = $Params['UserParameters'];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $limit = 10;
        $limitArray = array( 10, 10, 25, 50 );
        $limitArrayKey = \eZPreferences::value( 'admin_import_list_limit' );

        // get user limit preference
        if ( isset( $limitArray[ $limitArrayKey ] ) )
        {
            $limit =  $limitArray[ $limitArrayKey ];
        }

        $importList = \CjwNewsletterImport::fetchAllImportItems( $limit, $viewParameters[ 'offset' ] );
        $importListCount = \CjwNewsletterImport::fetchAllImportItemsCount( );

        $tpl->setVariable( 'view_parameters', $viewParameters );

        $tpl->setVariable( 'import_list', $importList );
        $tpl->setVariable( 'import_list_count', $importListCount );

        $tpl->setVariable( 'limit', $limit );


        $Result = array();

        $Result['content'] = $tpl->fetch( $templateFile );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),

                                  array( 'url'  => false,
                                         'text' => ezi18n( 'cjw_newsletter/import_list', 'Imports' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
