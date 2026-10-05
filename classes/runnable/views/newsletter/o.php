<?php
/**
 * The open pixel of a tracked newsletter (cjw_newsletter 4.2.0, area N4 Statistics): newsletter/o/<key>/<signature>.
 *
 * Public, no login, no session. It always answers the 1x1 GIF with no-store caching, also for a bad or missing
 * signature (a mail client must never show a broken image); only a valid signature is counted (CjwNewsletterTracking).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class O extends \Exponential\Runnable\ModuleView
{
    /**
     * Counts the open, without output (the code of the view, also used by the tests).
     *
     * @return string the status of CjwNewsletterTracking::recordOpen()
     */
    public static function handle( $key, $signature )
    {
        try
        {
            return \CjwNewsletterTracking::recordOpen( (string)$key, (string)$signature );
        }
        catch ( \Exception $e )
        {
            \eZDebug::writeError( 'Open pixel: ' . $e->getMessage(), __METHOD__ );
            return 'error';
        }
    }

    public function run( array $scope )
    {
        $Params = $scope['Params'];
        self::handle( isset( $Params['Key'] ) ? $Params['Key'] : '', isset( $Params['Signature'] ) ? $Params['Signature'] : '' );
        $gif = \CjwNewsletterTracking::pixel();
        header( 'Content-Type: image/gif' );
        header( 'Content-Length: ' . strlen( $gif ) );
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        header( 'X-Robots-Tag: noindex, nofollow' );
        header( 'Referrer-Policy: no-referrer' );
        echo $gif;
        \eZExecution::cleanExit();
    }
}

}
