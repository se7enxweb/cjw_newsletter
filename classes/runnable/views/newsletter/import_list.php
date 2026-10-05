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

        $tpl = templateInit();

        $userParameters = isset( $Params['UserParameters'] ) && is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();
        $viewParameters = array_merge( array( 'offset' => 0, 'namefilter' => '' ), $userParameters );
        $viewParameters['offset'] = max( 0, (int)$viewParameters['offset'] );
        $limit = isset( $userParameters['limit'] ) && in_array( (int)$userParameters['limit'], array( 10, 25, 50, 100 ) ) ? (int)$userParameters['limit'] : 25;

        // the newest imports first
        $importList = \CjwNewsletterImport::fetchAllImportItems( $limit, $viewParameters['offset'], array( 'id' => 'desc' ) );
        $importListCount = \CjwNewsletterImport::fetchAllImportItemsCount( );

        $tpl->setVariable( 'view_parameters', array( 'offset' => $viewParameters['offset'], 'limit' => $limit ) );
        $tpl->setVariable( 'import_list', is_array( $importList ) ? $importList : array() );
        $tpl->setVariable( 'import_list_count', $importListCount );
        $tpl->setVariable( 'limit', $limit );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

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
