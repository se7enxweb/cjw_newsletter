<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/mailbox_item_view.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/mailbox_item_view.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/mailbox_item_view.php:
 *
 *
 * File mailbox_item_view.php
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage modules
 * @filesource
 *
 */

namespace
{
if ( !function_exists( 'downloadFile' ) ) {
// helpfunction
/**
 * Passthrough file, and exit cleanly
*/
function downloadFile( $filePath )
{

    if( !file_exists( $filePath ) )
    {
        header("HTTP/1.1 404 Not Found");
        eZExecution::cleanExit();
    }

    ob_clean();

    header("Pragma: public");
    header("Expires: 0"); // set expiration time
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    header("Content-Disposition: attachment; filename=" . basename( $filePath ) );

    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize( $filePath ));

    ob_end_clean();

    @readfile( $filePath );
    eZExecution::cleanExit();

}
}
}

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class MailboxItemView extends \Exponential\Runnable\ModuleView
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
        $templateFile = 'design:newsletter/mailbox_item_view.tpl';

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $mailboxItemId = (int) $Params['MailboxItemId'];

        $mailboxItemObject = \CjwNewsletterMailboxItem::fetch( $mailboxItemId );

        if( !is_object( $mailboxItemObject ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        if ( $http->hasVariable( 'GetRawMailContent' ) )
        {
            header('Content-Type: text/plain');
            echo $mailboxItemObject->getRawMailMessageContent();
            \eZExecution::cleanExit();
        }
        elseif ( $http->hasVariable( 'DownloadRawMailContent' ) )
        {
            downloadFile( $mailboxItemObject->getFilePath() );
        }
        else
        {
            $cjwNewsletterMailParserObject = new \CjwNewsletterMailParser( $mailboxItemObject );

            if ( is_object( $cjwNewsletterMailParserObject ) )
            {
                $parseHeaderArray = $cjwNewsletterMailParserObject->parse();
            }

            $tpl = \eZTemplate::factory();

            $tpl->setVariable( 'mailbox_item', $mailboxItemObject );
            $tpl->setVariable( 'mailbox_item_raw_content', $mailboxItemObject->getRawMailMessageContent() );
            $tpl->setVariable( 'mailbox_header_hash', $parseHeaderArray );

            $Result = array();

            $Result['content'] = $tpl->fetch( $templateFile );
            $Result['path'] = array( array( 'url' => 'newsletter/mailbox_item_list',
                                            'text' => \ezpI18n::tr( 'cjw_newsletter/mailbox_item_view', 'Mailbox item list' ) ),
                                        array( 'url' => false,
                                            'text' => \ezpI18n::tr( 'cjw_newsletter/mailbox_item_view', 'Mailbox item view' ) ) );
        }

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
