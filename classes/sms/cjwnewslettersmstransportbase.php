<?php
/**
 * File containing the CjwNewsletterSmsTransportBase class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * What the shipped SMS transports share: the settings with their preset, the generic check of an inbound request
 * (an HMAC of the body with a shared secret, or a secret token) and the generic reading of an inbound SMS through a
 * field mapping, which also reads JSON bodies by a dotted path.
 *
 * Inbound settings of a transport group (secrets only in settings/override):
 *   InboundSignature=hmac-sha256      hmac-sha256: header InboundSignatureHeader carries hex(HMAC-SHA256(body, secret));
 *                                     token: the request carries InboundTokenField=<secret> (a GET or POST value)
 *   InboundSignatureHeader=X-Signature
 *   InboundTokenField=token
 *   InboundSecret=                    without a secret every inbound request is refused
 *   InboundFromField=from             POST field or JSON path of the sender number
 *   InboundTextField=text
 *   InboundIdField=id
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
abstract class CjwNewsletterSmsTransportBase implements CjwNewsletterSmsTransport
{
    /** @var string */
    protected $name;

    /** @var array */
    protected $settings;

    public function __construct( $name, array $settings )
    {
        $this->name = (string)$name;
        $this->settings = array_merge( $this->preset(), $settings );
    }

    /** @return array the defaults of the class, overridden by the INI group */
    protected function preset()
    {
        return array( 'InboundSignature' => 'hmac-sha256', 'InboundSignatureHeader' => 'X-Signature', 'InboundTokenField' => 'token',
                      'InboundSecret' => '', 'InboundFromField' => 'from', 'InboundTextField' => 'text', 'InboundIdField' => 'id',
                      'From' => '' );
    }

    public function name()
    {
        return $this->name;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function setting( $key, $default = '' )
    {
        return array_key_exists( $key, $this->settings ) ? $this->settings[$key] : $default;
    }

    /** @return array every setting, secrets replaced by '***' (for the dashboard and the logs) */
    public function publicSettings()
    {
        $out = array();
        foreach ( $this->settings as $key => $value )
            $out[$key] = preg_match( '/(secret|token|password|key)$/i', $key ) && $value !== '' && !is_array( $value ) ? '***' : $value;
        return $out;
    }

    public function verifyInbound( array $request )
    {
        $secret = (string)$this->setting( 'InboundSecret' );
        if ( $secret === '' )
            return false;
        $mode = strtolower( (string)$this->setting( 'InboundSignature' ) );
        if ( $mode === 'token' )
        {
            $field = (string)$this->setting( 'InboundTokenField', 'token' );
            $given = isset( $request['params'][$field] ) ? (string)$request['params'][$field] : '';
            return $given !== '' && hash_equals( $secret, $given );
        }
        if ( $mode === 'hmac-sha256' )
        {
            $header = strtolower( (string)$this->setting( 'InboundSignatureHeader', 'X-Signature' ) );
            $given = isset( $request['headers'][$header] ) ? strtolower( trim( (string)$request['headers'][$header] ) ) : '';
            if ( strncmp( $given, 'sha256=', 7 ) === 0 )
                $given = substr( $given, 7 );
            $expected = hash_hmac( 'sha256', isset( $request['body'] ) ? (string)$request['body'] : '', $secret );
            return $given !== '' && hash_equals( $expected, $given );
        }
        return false;
    }

    public function parseInbound( array $request )
    {
        $data = isset( $request['params'] ) && is_array( $request['params'] ) ? $request['params'] : array();
        $body = isset( $request['body'] ) ? trim( (string)$request['body'] ) : '';
        if ( $body !== '' && ( $body[0] === '{' || $body[0] === '[' ) )
        {
            $json = json_decode( $body, true );
            if ( is_array( $json ) )
                $data = array_merge( $data, $json );
        }
        $from = self::path( $data, (string)$this->setting( 'InboundFromField', 'from' ) );
        $text = self::path( $data, (string)$this->setting( 'InboundTextField', 'text' ) );
        if ( !is_scalar( $from ) || (string)$from === '' || !is_scalar( $text ) )
            return null;
        $id = self::path( $data, (string)$this->setting( 'InboundIdField', 'id' ) );
        return array( 'from' => (string)$from, 'text' => (string)$text, 'id' => is_scalar( $id ) ? (string)$id : '' );
    }

    public function inboundResponse( $ok )
    {
        return array( 'status' => $ok ? 200 : 403, 'content_type' => 'application/json; charset=utf-8',
                      'body' => json_encode( array( 'ok' => (bool)$ok ) ) );
    }

    /**
     * A value of a nested array by a dotted path ('data.0.id'); a key that contains dots itself is found as it is.
     *
     * @param array $data
     * @param string $path
     * @return mixed|null
     */
    public static function path( $data, $path )
    {
        if ( $path === '' )
            return null;
        if ( is_array( $data ) && array_key_exists( $path, $data ) )
            return $data[$path];
        foreach ( explode( '.', $path ) as $key )
        {
            if ( !is_array( $data ) || !array_key_exists( $key, $data ) )
                return null;
            $data = $data[$key];
        }
        return $data;
    }
}
