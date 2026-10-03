<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/preview_archive.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/preview_archive.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/preview_archive.php:
 *
 *
 * File preview_archive.php
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

class PreviewArchive extends \Exponential\Runnable\ModuleView
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

        $module = $Params["Module"];
        $http = \eZHTTPTool::instance();

        $editionSendId = (int) $Params['EditionSendId'];

        $outputFormatId = 0;
        $newsletterUserId = 0;

        if( $Params['OutputFormat'] )
            $outputFormatId = (int) $Params['OutputFormat'];

        if( $Params['NewsletterUserId'] )
            $newsletterUserId = $Params['NewsletterUserId'];


        $editionSendObject = \CjwNewsletterEditionSend::fetch( $editionSendId );

        if( !is_object( $editionSendObject  ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        $newsletterDataArray = $editionSendObject->getParsedOutputXml();
        $newsletterContent = false;

        if( is_array( $newsletterDataArray ) && isset( $newsletterDataArray[ $outputFormatId ]['body'] ) )
        {
            $newsletterContentArray = $newsletterDataArray[ $outputFormatId ];
        }
        else
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
        }

        // html / text  - multipart/alternative
        if( $outputFormatId === 0 )
        {
            $newsletterContent .= $newsletterContentArray['body']['html'];
            $textContent = "<hr /><pre>" . $newsletterContentArray['body']['text'] . "</pre></body>";
            $newsletterContent = preg_replace( array('%</body>%'), array( $textContent ), $newsletterContent);
        }
        // plain/text
        elseif( $outputFormatId === 1 )
        {

            $newsletterContent .= '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>newsletter - outputformat - text</title></head><body><pre>'. $newsletterContentArray['body']['text'] .'</pre></body></html>';
        }
        else
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
        }


        $mailSubjectLabel = ezi18n( 'cjw_newsletter/preview', 'Email subject' );

        $subjectStyle = 'style="background-color:#dddddd;border-color: #cccccc;border-width: 0 0 1px 0;border-style: solid;color:#333333;"';

        $mailSubject = '<body${1}><!-- email subject preview start --><table width="100%" cellpadding="5" cellspacing="0" border="0" bgcolor="#dddddd" class="newsletter-skin-preview-email-subject" '. $subjectStyle .'><tr><th width="1%" nowrap>' . $mailSubjectLabel . ':</th><td width="99%">' . $newsletterContentArray['subject'] . '</td></tr></table></span><!-- email subject preview end -->';

        $newsletterContent = preg_replace( "%<body(.*)>%", $mailSubject, $newsletterContent );

        /*$mailSubject = "<body><b>Email subject:</b> ". $newsletterContentArray['subject'] . "<br />";
        $newsletterContent = preg_replace( array('%<body>%'), array( $mailSubject ), $newsletterContent);*/

        $debug = ( $http->hasVariable( 'Debug' ) && (int)$http->variable( 'Debug' ) == 1 ) ? 1 : 0;

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
