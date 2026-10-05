<?php
/**
 * The state of a background run as JSON: status (starting, running, done, failed), the result and the last log lines.
 * The start page polls it.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class Job extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $jobID = (string)$Params['JobID'];
        $state = \CjwNewsletterJob::isID( $jobID ) ? \CjwNewsletterJob::status( $jobID ) : false;

        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Cache-Control: no-store' );
        if ( !$state )
        {
            header( 'HTTP/1.1 404 Not Found' );
            echo json_encode( array( 'status' => 'unknown' ) );
        }
        else
        {
            echo json_encode( $state );
        }
        \eZExecution::cleanExit();
    }
}

}
