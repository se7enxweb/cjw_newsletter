<?php
/**
 * newsletter/throttle: the rate limits, pauses and batches of the transports, and the items that wait for a
 * soft-bounce retry (cjw_newsletter 4.2.0, area deliverability).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class Throttle extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        if ( $http->hasPostVariable( 'PauseButton' ) || $http->hasPostVariable( 'ResumeButton' ) )
        {
            $transport = \CjwNewsletterThrottle::name( $http->hasPostVariable( 'Transport' ) ? (string)$http->postVariable( 'Transport' ) : '' );
            if ( $http->hasPostVariable( 'ResumeButton' ) )
            {
                \CjwNewsletterThrottle::resume( $transport );
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/deliverability', 'The transport "%transport" sends again.', null, array( '%transport' => $transport ) ) );
            }
            else
            {
                $minutes = $http->hasPostVariable( 'Minutes' ) ? (int)$http->postVariable( 'Minutes' ) : 60;
                $minutes = max( 1, min( 10080, $minutes ) );
                \CjwNewsletterThrottle::pause( $transport, time() + $minutes * 60 );
                \CjwNewsletterUI::notice( 'warning', \ezpI18n::tr( 'cjw_newsletter/deliverability', 'The transport "%transport" is paused for %minutes minutes.', null,
                                                                  array( '%transport' => $transport, '%minutes' => $minutes ) ) );
            }
            if ( class_exists( 'expAudit' ) )
                \expAudit::event( 'system.cjw_newsletter.throttle', array( 'object' => 'cjw_newsletter:throttle',
                    'after' => array( 'transport' => $transport, 'paused_until' => \CjwNewsletterThrottle::pausedUntil( $transport ) ) ) );
            return $this->viewResult( null, $module->redirectTo( '/newsletter/throttle' ) );
        }

        include_once( 'kernel/common/template.php' );
        $tpl = templateInit();
        $batches = array();
        $names = array( 0 => 'new', 1 => 'running', 2 => 'done', 3 => 'paused', 9 => 'failed' );
        foreach ( \CjwNewsletterDeliverability::recentBatches( 20 ) as $batch )
        {
            $status = (int)$batch->attribute( 'status' );
            $batches[] = array( 'id' => (int)$batch->attribute( 'id' ), 'send_id' => (int)$batch->attribute( 'edition_send_id' ),
                                'channel' => (string)$batch->attribute( 'channel' ), 'number' => (int)$batch->attribute( 'batch_number' ),
                                'items' => (int)$batch->attribute( 'item_count' ), 'sent' => (int)$batch->attribute( 'sent_count' ),
                                'failed' => (int)$batch->attribute( 'failed_count' ), 'status' => isset( $names[$status] ) ? $names[$status] : (string)$status,
                                'started' => (int)$batch->attribute( 'started' ), 'finished' => (int)$batch->attribute( 'finished' ) );
        }
        $tpl->setVariable( 'enabled', \CjwNewsletterThrottle::enabled() );
        $tpl->setVariable( 'batch_size', \CjwNewsletterThrottle::batchSize() );
        $tpl->setVariable( 'pause_between', \CjwNewsletterThrottle::pauseBetweenBatches() );
        $tpl->setVariable( 'transports', array_values( \CjwNewsletterThrottle::states() ) );
        $tpl->setVariable( 'batches', $batches );
        $tpl->setVariable( 'retries_waiting', \CjwNewsletterBounce::waitingRetryCount() );
        $tpl->setVariable( 'max_retries', \CjwNewsletterBounce::maxRetries() );
        $tpl->setVariable( 'retry_delay', \CjwNewsletterBounce::retryDelay() );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/deliverability/throttle.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/deliverability', 'Rate limits and batches' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
