<?php
/**
 * newsletter/suppression_import: a CSV file of addresses into the kernel suppression list, checked first (dry run)
 * (cjw_newsletter 4.2.0, area deliverability). The uploaded file is read and forgotten; it is never stored.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SuppressionImport extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $http = \eZHTTPTool::instance();
        $result = null;
        $error = '';
        $reason = $http->hasPostVariable( 'Reason' ) ? (string)$http->postVariable( 'Reason' ) : 'legal';
        $note = $http->hasPostVariable( 'Note' ) ? mb_substr( trim( (string)$http->postVariable( 'Note' ) ), 0, 200 ) : '';
        $dry = $http->hasPostVariable( 'CheckButton' );
        if ( $dry || $http->hasPostVariable( 'ImportButton' ) )
        {
            $csv = null;
            $name = '';
            if ( \eZHTTPFile::canFetch( 'SuppressionFile' ) )
            {
                $file = \eZHTTPFile::fetch( 'SuppressionFile' );
                if ( $file instanceof \eZHTTPFile )
                {
                    $csv = (string)file_get_contents( $file->attribute( 'filename' ) );
                    $name = (string)$file->attribute( 'original_filename' );
                }
            }
            else if ( $http->hasPostVariable( 'SuppressionText' ) && trim( (string)$http->postVariable( 'SuppressionText' ) ) !== '' )
            {
                $csv = (string)$http->postVariable( 'SuppressionText' );
                $name = 'pasted';
            }
            if ( $csv === null )
                $error = \ezpI18n::tr( 'cjw_newsletter/deliverability', 'Choose a CSV file or paste the addresses.' );
            else
            {
                $result = \CjwNewsletterSuppressionImport::import( $csv, $reason, $note, $dry, $name );
                $result['file'] = $name;
                if ( !$result['ok'] )
                    $error = $result['error'];
            }
        }

        include_once( 'kernel/common/template.php' );
        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'available', \CjwNewsletterSuppressionImport::available() );
        $tpl->setVariable( 'reasons', \CjwNewsletterSuppressionImport::$reasons );
        $tpl->setVariable( 'reason', $reason );
        $tpl->setVariable( 'note', $note );
        $tpl->setVariable( 'result', $result );
        $tpl->setVariable( 'error', $error );
        $tpl->setVariable( 'max_rows', \CjwNewsletterSuppressionImport::maxRows() );
        $tpl->setVariable( 'counts', \CjwNewsletterSuppressionImport::available()
            ? array( 'all' => \expMailSuppression::countList(), 'legal' => \expMailSuppression::countList( 'legal' ), 'bounce' => \expMailSuppression::countList( 'bounce' ) )
            : array( 'all' => 0, 'legal' => 0, 'bounce' => 0 ) );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/deliverability/suppression_import.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/deliverability', 'Suppression import' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
