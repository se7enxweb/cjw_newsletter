<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/unsubscribe.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/unsubscribe.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/unsubscribe.php:
 *
 *
 * File unsubscribe.php
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

class Unsubscribe extends \Exponential\Runnable\ModuleView
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
        $subscription = \CjwNewsletterSubscription::fetchByHash( $Params['Hash'] );

        if ( !$subscription )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        $newsletterUser = $subscription->attribute( 'newsletter_user' );
        if ( !is_object( $newsletterUser ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }
        if ( $newsletterUser->isOnBlacklist() )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        if( $subscription->isRemoved() )
        {
            $tplTemplate = 'design:newsletter/unsubscribe_already_done.tpl';
        }
        elseif ( $module->isCurrentAction( 'Unsubscribe' ) )
        {
            $unsubscribeResult = $subscription->unsubscribe();
            $tpl->setVariable( 'unsubscribe_result', $unsubscribeResult );


            $tplTemplate = 'design:newsletter/unsubscribe_success.tpl';
        }
        else if ( $module->isCurrentAction( 'Cancel' ) )
        {
            $cancelUri = '/';
            if ( $module->hasActionParameter( 'CancelUri' ) )
            {
                $cancelUri = $module->actionParameter( 'CancelUri' );
                // only a path of this site, never another host
                if ( !is_string( $cancelUri ) || strpos( $cancelUri, '//' ) !== false || strpos( $cancelUri, ':' ) !== false || strpos( $cancelUri, '\\' ) !== false )
                {
                    $cancelUri = '/';
                }
            }

            return $this->viewResult( isset( $Result ) ? $Result : null, $module->redirectTo( $cancelUri ) );
        }
        else
        {
            $tplTemplate = 'design:newsletter/unsubscribe.tpl';
        }

        $tpl->setVariable( 'newsletter_user', $newsletterUser );
        $tpl->setVariable( 'subscription', $subscription );

        $Result = array();
        $Result['content'] = $tpl->fetch( $tplTemplate );
        $Result['path'] = array( array( 'url' => false,
                                        'text' => \ezpI18n::tr( 'cjw_newsletter/unsubscribe', 'Unsubscribe' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
