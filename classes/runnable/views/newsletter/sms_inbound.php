<?php
/**
 * The endpoint an SMS provider calls with an incoming SMS: newsletter/sms_inbound/<transport>.
 *
 * The request is checked by the transport (a Twilio-style signature, an HMAC of the body, or a shared token; see
 * CjwNewsletterSmsTransportBase); a request that fails the check is answered 403 and changes nothing. A verified
 * SMS is stored in cjwnl_sms_inbound, and a STOP keyword stops the SMS of the number. No session and no login are
 * needed: the anonymous role needs the policy newsletter/sms_public.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SmsInbound extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $transportName = isset( $Params['Transport'] ) ? preg_replace( '/[^A-Za-z0-9_]/', '', (string)$Params['Transport'] ) : '';
        if ( $transportName === '' )
            $transportName = \CjwNewsletterSms::transportName();
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string)$_SERVER['REQUEST_METHOD'] ) : 'GET';

        if ( !\CjwNewsletterSms::enabled() || !in_array( $method, array( 'POST', 'GET' ), true ) )
            $result = array( 'response' => array( 'status' => 404, 'content_type' => 'application/json; charset=utf-8', 'body' => json_encode( array( 'ok' => false ) ) ) );
        else
            $result = \CjwNewsletterSms::handleInbound( $transportName, self::request() );

        $response = $result['response'];
        if ( empty( $result['verified'] ) && $response['status'] === 403 )
            \eZDebug::writeWarning( 'An inbound SMS request for the transport ' . $transportName . ' failed the signature check.', __METHOD__ );

        $status = (int)$response['status'];
        $texts = array( 200 => 'OK', 403 => 'Forbidden', 404 => 'Not Found' );
        header( 'HTTP/1.1 ' . $status . ' ' . ( isset( $texts[$status] ) ? $texts[$status] : 'OK' ) );
        header( 'Content-Type: ' . $response['content_type'] );
        header( 'Cache-Control: no-store' );
        header( 'X-Robots-Tag: noindex' );
        echo $response['body'];
        \eZExecution::cleanExit();
    }

    /**
     * @return array hash( url, params (POST and GET), post (POST only), headers, body ) of the current request
     */
    public static function request()
    {
        $https = ( !empty( $_SERVER['HTTPS'] ) && strtolower( (string)$_SERVER['HTTPS'] ) !== 'off' )
                 || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strtolower( (string)$_SERVER['HTTP_X_FORWARDED_PROTO'] ) === 'https' );
        $host = isset( $_SERVER['HTTP_HOST'] ) ? (string)$_SERVER['HTTP_HOST'] : ( isset( $_SERVER['SERVER_NAME'] ) ? (string)$_SERVER['SERVER_NAME'] : '' );
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string)$_SERVER['REQUEST_URI'] : '';
        $headers = array();
        foreach ( $_SERVER as $key => $value )
        {
            if ( strncmp( $key, 'HTTP_', 5 ) === 0 )
                $headers[strtolower( str_replace( '_', '-', substr( $key, 5 ) ) )] = (string)$value;
        }
        if ( isset( $_SERVER['CONTENT_TYPE'] ) )
            $headers['content-type'] = (string)$_SERVER['CONTENT_TYPE'];
        $body = (string)@file_get_contents( 'php://input' );
        $post = $_POST ? $_POST : array();
        if ( !$post && $body !== '' && isset( $headers['content-type'] ) && stripos( $headers['content-type'], 'application/x-www-form-urlencoded' ) !== false )
            parse_str( $body, $post );
        $params = $post;
        foreach ( $_GET as $key => $value )
            if ( !isset( $params[$key] ) )
                $params[$key] = $value;
        return array( 'url' => ( $https ? 'https' : 'http' ) . '://' . $host . $uri, 'params' => $params, 'post' => $post, 'headers' => $headers, 'body' => $body );
    }
}

}
