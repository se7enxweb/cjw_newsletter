<?php
/**
 * The click redirect of a tracked newsletter (cjw_newsletter 4.2.0, area N4 Statistics):
 * newsletter/r/<link id>/<key>/<signature>.
 *
 * Public, no login, no session. It only ever redirects to the URL stored for the link id when the edition was queued
 * (never a URL of the request), and only when the key and the signature are valid and belong to the same send; a bad
 * signature, a key of another send or an unknown link answer 404. A link without key and signature (the web archive
 * of the edition shows the links before the recipient's part is filled in) goes to the stored URL and counts nothing.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class R extends \Exponential\Runnable\ModuleView
{
    /**
     * Counts the click, without output (the code of the view, also used by the tests).
     *
     * @return array status, url ('' = 404)
     */
    public static function handle( $linkId, $key, $signature )
    {
        if ( !preg_match( '/^[0-9]{1,10}$/', (string)$linkId ) )
            return array( 'status' => 'not_found', 'url' => '' );
        try
        {
            return \CjwNewsletterTracking::recordClick( (int)$linkId, (string)$key, (string)$signature );
        }
        catch ( \Exception $e )
        {
            \eZDebug::writeError( 'Click redirect: ' . $e->getMessage(), __METHOD__ );
            return array( 'status' => 'error', 'url' => '' );
        }
    }

    public function run( array $scope )
    {
        $Params = $scope['Params'];
        $r = self::handle( isset( $Params['LinkId'] ) ? $Params['LinkId'] : '', isset( $Params['Key'] ) ? $Params['Key'] : '',
                           isset( $Params['Signature'] ) ? $Params['Signature'] : '' );
        header( 'Cache-Control: no-store, max-age=0' );
        header( 'X-Robots-Tag: noindex, nofollow' );
        header( 'Referrer-Policy: no-referrer' );
        if ( $r['url'] === '' )
        {
            header( 'HTTP/1.1 404 Not Found' );
            header( 'Content-Type: text/plain; charset=utf-8' );
            echo "This link is not known.\n";
        }
        else
        {
            header( 'Location: ' . $r['url'], true, 302 );
            header( 'Content-Type: text/plain; charset=utf-8' );
        }
        \eZExecution::cleanExit();
    }
}

}
