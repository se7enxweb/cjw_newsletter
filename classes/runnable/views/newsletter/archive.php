<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/archive.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/archive.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/archive.php:
 *
 *
 * File archive.php
 *
 * -get the stored content of newsletter edition<br>
 * -may be parse user content<br>
 * -newsletter / archive / $edition_send_hash<br>
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

class Archive extends \Exponential\Runnable\ModuleView
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

        $editionSendHash = $Params['EditionSendHash'];
        $outputFormatId = 0;
        $subscriptionHash = false;

        if( $Params['OutputFormatId'] )
            $outputFormatId = (int) $Params['OutputFormatId'];

        if( $Params['SubscriptionHash'] )
            $subscriptionHash = $Params['SubscriptionHash'];

        $editionSendObject = \CjwNewsletterEditionSend::fetchByHash( $editionSendHash );

        if( !is_object( $editionSendObject  ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        $newsletterDataArray = $editionSendObject->getParsedOutputXml();
        $newsletterContent = false;

        if( isset( $newsletterDataArray[ $outputFormatId ]) )
        {
            $newsletterContentArray = $newsletterDataArray[ $outputFormatId ];
        }

        switch( $outputFormatId )
        {
            // html
            case 0:
                $newsletterContent = $newsletterContentArray['body']['html'];
                break;
                // text
            case 1:

                $newsletterContent .= '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>newsletter - outputformat - text</title></head><body><pre>'. $newsletterContentArray['body']['text'] .'</pre></body></html>';

                break;
            default:
                return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        $debug = 0;

        if( $debug == 0 )
        {
            header( "Content-type: text/html" );
            echo $newsletterContent;
            \eZExecution::cleanExit();
        }
        else
        {
            $Result = array();
            $Result['content'] = '<code>'.$newsletterContent.'</code>';
          //  header( "Content-type: text/html" );
        }

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
