<?php
/**
 * The statistics as CSV (cjw_newsletter 4.2.0, area N4 Statistics):
 *   newsletter/statistics_export                     one row per send
 *   newsletter/statistics_export/<id>                the row of one send
 *   newsletter/statistics_export/<id>/(type)/links   the links of a send with their clicks
 *   newsletter/statistics_export/<id>/(type)/days    opens and clicks per day (no id: of every send)
 * Only totals, never a person. Cells that a spreadsheet would run as a formula are prefixed with '.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class StatisticsExport extends \Exponential\Runnable\ModuleView
{
    /** @return array filename, csv (the code of the view, also used by the tests) */
    public static function export( $sendId, $type )
    {
        $type = in_array( $type, array( 'sends', 'links', 'days' ), true ) ? $type : 'sends';
        $sendId = (int)$sendId;
        $csv = \CjwNewsletterStatisticsReport::csv( \CjwNewsletterStatisticsReport::exportRows( $type, $sendId ) );
        $name = 'newsletter-statistics' . ( $sendId > 0 ? '-' . $sendId : '' ) . '-' . $type . '-' . date( 'Ymd' ) . '.csv';
        return array( 'filename' => $name, 'csv' => "\xEF\xBB\xBF" . $csv );
    }

    public function run( array $scope )
    {
        $Params = $scope['Params'];
        $module = $Params['Module'];
        $sendId = isset( $Params['EditionSendId'] ) ? (int)$Params['EditionSendId'] : 0;
        $user = isset( $Params['UserParameters'] ) && is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();
        $type = isset( $user['type'] ) ? (string)$user['type'] : 'sends';
        if ( $sendId > 0 && !is_object( \CjwNewsletterEditionSend::fetch( $sendId ) ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
        if ( $type === 'links' && $sendId <= 0 )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
        $export = self::export( $sendId, $type );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $export['filename'] . '"' );
        header( 'Cache-Control: private, no-store, max-age=0' );
        header( 'X-Content-Type-Options: nosniff' );
        echo $export['csv'];
        \eZExecution::cleanExit();
    }
}

}
