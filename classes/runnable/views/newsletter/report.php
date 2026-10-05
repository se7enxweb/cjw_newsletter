<?php
/**
 * The statistics of the newsletter sends (cjw_newsletter 4.2.0, area N4 Statistics):
 * newsletter/report lists the sends with their numbers, newsletter/report/<edition send id> is the report of one send
 * (sent, delivered, bounced, opens, clicks per link, unsubscribes, opens and clicks over time, the A/B test).
 * Only totals: no page shows a person.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class Report extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );
        $module = $Params['Module'];
        $sendId = isset( $Params['EditionSendId'] ) ? (int)$Params['EditionSendId'] : 0;
        $userParameters = isset( $Params['UserParameters'] ) && is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();
        $tpl = templateInit();
        $tpl->setVariable( 'tracking_enabled', \CjwNewsletterTracking::enabled() );
        $path = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter', 'Newsletter' ) ),
                       array( 'url' => $sendId ? 'newsletter/report' : false, 'text' => \ezpI18n::tr( 'cjw_newsletter/statistics', 'Statistics' ) ) );
        if ( $sendId > 0 )
        {
            $send = \CjwNewsletterEditionSend::fetch( $sendId );
            if ( !is_object( $send ) )
                return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
            $report = \CjwNewsletterStatisticsReport::send( $send );
            $tpl->setVariable( 'report', $report );
            $path[] = array( 'url' => false, 'text' => $report['edition']['name'] !== '' ? $report['edition']['name'] : '#' . $sendId );
            $Result = array( 'content' => $tpl->fetch( 'design:newsletter/statistics/report.tpl' ), 'path' => $path );
            return $this->viewResult( $Result, null );
        }
        $limit = 25;
        $offset = isset( $userParameters['offset'] ) ? max( 0, (int)$userParameters['offset'] ) : 0;
        $tpl->setVariable( 'sends', \CjwNewsletterStatisticsReport::sends( $limit, $offset ) );
        $tpl->setVariable( 'send_count', \CjwNewsletterStatisticsReport::sendCount() );
        $tpl->setVariable( 'limit', $limit );
        $tpl->setVariable( 'view_parameters', array( 'offset' => $offset ) );
        $tpl->setVariable( 'dashboard', \CjwNewsletterStatisticsReport::dashboard() );
        $Result = array( 'content' => $tpl->fetch( 'design:newsletter/statistics/report_list.tpl' ), 'path' => $path );
        return $this->viewResult( $Result, null );
    }
}

}
