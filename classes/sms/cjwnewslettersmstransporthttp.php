<?php
/**
 * File containing the CjwNewsletterSmsTransportHttp class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * The generic SMS transport: one HTTP request per SMS to any provider API, mapped in the INI group. JSON or form
 * bodies, GET or POST, basic, bearer or header authentication, and the success read from the HTTP status and,
 * optionally, a value in the JSON answer. doc/sms.md has a full example per kind of provider.
 *
 * Settings ([SmsTransport_<name>], credentials only in settings/override/cjw_newsletter.ini.append.php):
 *   Class=CjwNewsletterSmsTransportHttp
 *   Url=https://sms.example.org/v1/messages   {Key} is replaced by the setting Key (e.g. {AccountSid})
 *   Method=POST                               POST, PUT or GET (GET sends the fields as the query)
 *   Format=json                               json or form
 *   Auth=none                                 none, basic (AuthUser, AuthPassword), bearer (AuthToken),
 *                                             header (AuthHeader: AuthHeaderValue)
 *   FieldTo=to  FieldFrom=from  FieldText=text   names of the fields; a dotted name builds nested JSON (message.to)
 *   ExtraFields[<name>]=<value>               more fixed fields
 *   Headers[]=X-Name: value                   more request headers
 *   From=                                     the sender when the list has none
 *   SuccessStatus=2xx                         a comma list of codes, 2xx = 200-299
 *   SuccessJsonPath=                          optional: this value of the JSON answer must be truthy ...
 *   SuccessJsonValue=                         ... or equal this value
 *   MessageIdPath=id                          the provider's message id in the JSON answer
 *   ErrorJsonPath=error                       the error text in the JSON answer
 *   Timeout=10
 *   AllowInsecureHttp=disabled                plain http only to 127.0.0.1/localhost unless enabled
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSmsTransportHttp extends CjwNewsletterSmsTransportBase
{
    /** @var array|null the last request and answer (tests and the admin's test send) */
    public $lastExchange = null;

    protected function preset()
    {
        return array_merge( parent::preset(), array(
            'Url' => '', 'Method' => 'POST', 'Format' => 'json', 'Auth' => 'none', 'AuthUser' => '', 'AuthPassword' => '',
            'AuthToken' => '', 'AuthHeader' => 'Authorization', 'AuthHeaderValue' => '',
            'FieldTo' => 'to', 'FieldFrom' => 'from', 'FieldText' => 'text', 'ExtraFields' => array(), 'Headers' => array(),
            'SuccessStatus' => '2xx', 'SuccessJsonPath' => '', 'SuccessJsonValue' => '', 'MessageIdPath' => 'id',
            'ErrorJsonPath' => 'error', 'Timeout' => 10, 'AllowInsecureHttp' => 'disabled' ) );
    }

    /**
     * The request that would be sent (nothing is sent).
     *
     * @return array hash( method, url, headers: string[], body ) or hash( error )
     */
    public function buildRequest( $to, $from, $text )
    {
        $url = $this->expand( (string)$this->setting( 'Url' ) );
        if ( $url === '' )
            return array( 'error' => 'no Url is set for the SMS transport ' . $this->name );
        $parts = parse_url( $url );
        if ( !$parts || empty( $parts['host'] ) || !in_array( strtolower( isset( $parts['scheme'] ) ? $parts['scheme'] : '' ), array( 'http', 'https' ), true ) )
            return array( 'error' => 'the Url of the SMS transport ' . $this->name . ' is not an http(s) address' );
        if ( strtolower( $parts['scheme'] ) === 'http' && !self::isLoopback( $parts['host'] ) && !self::enabled( $this->setting( 'AllowInsecureHttp' ) ) )
            return array( 'error' => 'the SMS transport ' . $this->name . ' refuses plain http to ' . $parts['host'] . ' (AllowInsecureHttp)' );

        $from = (string)$from !== '' ? (string)$from : (string)$this->setting( 'From' );
        $fields = array();
        foreach ( (array)$this->setting( 'ExtraFields', array() ) as $key => $value )
            self::put( $fields, (string)$key, $this->expand( (string)$value ) );
        self::put( $fields, (string)$this->setting( 'FieldTo', 'to' ), (string)$to );
        if ( (string)$this->setting( 'FieldFrom', 'from' ) !== '' && $from !== '' )
            self::put( $fields, (string)$this->setting( 'FieldFrom', 'from' ), $from );
        self::put( $fields, (string)$this->setting( 'FieldText', 'text' ), (string)$text );

        $method = strtoupper( (string)$this->setting( 'Method', 'POST' ) );
        if ( !in_array( $method, array( 'POST', 'PUT', 'GET' ), true ) )
            $method = 'POST';
        $headers = array();
        $body = '';
        if ( $method === 'GET' )
            $url .= ( strpos( $url, '?' ) === false ? '?' : '&' ) . http_build_query( $fields );
        else if ( strtolower( (string)$this->setting( 'Format', 'json' ) ) === 'form' )
        {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $body = http_build_query( $fields );
        }
        else
        {
            $headers[] = 'Content-Type: application/json';
            $body = json_encode( $fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        }
        $headers[] = 'Accept: application/json';
        switch ( strtolower( (string)$this->setting( 'Auth', 'none' ) ) )
        {
            case 'basic':
                $headers[] = 'Authorization: Basic ' . base64_encode( $this->setting( 'AuthUser' ) . ':' . $this->setting( 'AuthPassword' ) );
                break;
            case 'bearer':
                $headers[] = 'Authorization: Bearer ' . $this->setting( 'AuthToken' );
                break;
            case 'header':
                $name = preg_replace( '/[^A-Za-z0-9-]/', '', (string)$this->setting( 'AuthHeader', 'Authorization' ) );
                if ( $name !== '' )
                    $headers[] = $name . ': ' . str_replace( array( "\r", "\n" ), '', $this->expand( (string)$this->setting( 'AuthHeaderValue' ) ) );
                break;
        }
        foreach ( (array)$this->setting( 'Headers', array() ) as $header )
        {
            $header = str_replace( array( "\r", "\n" ), '', (string)$header );
            if ( strpos( $header, ':' ) > 0 )
                $headers[] = $header;
        }
        return array( 'method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body );
    }

    public function send( $to, $from, $text )
    {
        $request = $this->buildRequest( $to, $from, $text );
        if ( isset( $request['error'] ) )
            return array( 'ok' => false, 'id' => '', 'error' => $request['error'] );
        $response = $this->request( $request );
        $this->lastExchange = array( 'request' => self::withoutSecrets( $request ), 'response' => $response );
        return $this->interpret( $response );
    }

    /**
     * Reads the answer of the provider.
     *
     * @param array $response hash( status, body, error )
     * @return array hash( ok, id, error )
     */
    public function interpret( array $response )
    {
        if ( !empty( $response['error'] ) )
            return array( 'ok' => false, 'id' => '', 'error' => (string)$response['error'] );
        $status = (int)$response['status'];
        $json = json_decode( (string)$response['body'], true );
        $ok = self::statusMatches( $status, (string)$this->setting( 'SuccessStatus', '2xx' ) );
        $path = (string)$this->setting( 'SuccessJsonPath' );
        if ( $ok && $path !== '' )
        {
            $value = is_array( $json ) ? self::path( $json, $path ) : null;
            $want = (string)$this->setting( 'SuccessJsonValue' );
            $ok = $want === '' ? !empty( $value ) : ( is_scalar( $value ) && (string)$value === $want ) || ( is_bool( $value ) && ( $value ? 'true' : 'false' ) === strtolower( $want ) );
        }
        $id = is_array( $json ) ? self::path( $json, (string)$this->setting( 'MessageIdPath', 'id' ) ) : null;
        if ( $ok )
            return array( 'ok' => true, 'id' => is_scalar( $id ) ? (string)$id : '', 'error' => '' );
        $error = is_array( $json ) ? self::path( $json, (string)$this->setting( 'ErrorJsonPath', 'error' ) ) : null;
        if ( is_array( $error ) )
            $error = json_encode( $error );
        $error = is_scalar( $error ) && (string)$error !== '' ? (string)$error : 'HTTP ' . $status;
        return array( 'ok' => false, 'id' => is_scalar( $id ) ? (string)$id : '', 'error' => mb_substr( $error, 0, 500 ) );
    }

    /**
     * Performs the HTTP request (curl, or the PHP streams without curl).
     *
     * @param array $request hash( method, url, headers, body )
     * @return array hash( status, body, error )
     */
    protected function request( array $request )
    {
        $timeout = max( 1, (int)$this->setting( 'Timeout', 10 ) );
        if ( function_exists( 'curl_init' ) )
        {
            $ch = curl_init( $request['url'] );
            curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $request['method'],
                CURLOPT_HTTPHEADER => $request['headers'], CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS ) );
            if ( $request['method'] !== 'GET' )
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $request['body'] );
            $body = curl_exec( $ch );
            $error = $body === false ? curl_error( $ch ) : '';
            $status = (int)curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
            if ( PHP_VERSION_ID < 80000 )
                if ( PHP_VERSION_ID < 80000 ) curl_close( $ch ); // no effect since PHP 8.0, deprecated in 8.5
            unset( $ch );
            return array( 'status' => $status, 'body' => $body === false ? '' : (string)$body, 'error' => $error );
        }
        $context = stream_context_create( array( 'http' => array( 'method' => $request['method'], 'header' => implode( "\r\n", $request['headers'] ),
            'content' => $request['method'] === 'GET' ? '' : $request['body'], 'timeout' => $timeout, 'ignore_errors' => true,
            'follow_location' => 0 ) ) );
        $body = @file_get_contents( $request['url'], false, $context );
        $status = 0;
        if ( function_exists( 'http_get_last_response_headers' ) )
            $responseHeaders = http_get_last_response_headers();
        else
        {
            // PHP before 8.4: the headers are in the variable the stream wrapper sets in this scope
            $scope = get_defined_vars();
            $responseHeaders = isset( $scope['http_response_header'] ) ? $scope['http_response_header'] : array();
        }
        foreach ( (array)$responseHeaders as $line )
            if ( preg_match( '#^HTTP/\S+\s+(\d{3})#', $line, $m ) )
                $status = (int)$m[1];
        return array( 'status' => $status, 'body' => $body === false ? '' : (string)$body, 'error' => $body === false && $status === 0 ? 'no answer from ' . parse_url( $request['url'], PHP_URL_HOST ) : '' );
    }

    /** {Key} in a value is replaced by the setting Key (a scalar). */
    protected function expand( $value )
    {
        $settings = $this->settings;
        return preg_replace_callback( '/\{([A-Za-z][A-Za-z0-9_]*)\}/', function ( $m ) use ( $settings ) {
            return isset( $settings[$m[1]] ) && is_scalar( $settings[$m[1]] ) ? rawurlencode( (string)$settings[$m[1]] ) : $m[0];
        }, (string)$value );
    }

    /** @return bool the code matches a list like "200,201" or "2xx" */
    public static function statusMatches( $status, $list )
    {
        foreach ( explode( ',', $list ) as $item )
        {
            $item = strtolower( trim( $item ) );
            if ( $item === '' )
                continue;
            if ( preg_match( '/^([1-5])xx$/', $item, $m ) && (int)floor( $status / 100 ) === (int)$m[1] )
                return true;
            if ( ctype_digit( $item ) && (int)$item === (int)$status )
                return true;
        }
        return false;
    }

    /** Sets $data[a][b] for the name 'a.b'. */
    protected static function put( array &$data, $name, $value )
    {
        if ( $name === '' )
            return;
        $ref =& $data;
        $keys = explode( '.', $name );
        $last = array_pop( $keys );
        foreach ( $keys as $key )
        {
            if ( !isset( $ref[$key] ) || !is_array( $ref[$key] ) )
                $ref[$key] = array();
            $ref =& $ref[$key];
        }
        $ref[$last] = $value;
    }

    protected static function isLoopback( $host )
    {
        $host = strtolower( trim( $host, '[]' ) );
        return $host === 'localhost' || $host === '::1' || strncmp( $host, '127.', 4 ) === 0;
    }

    protected static function enabled( $value )
    {
        return in_array( strtolower( (string)$value ), array( 'enabled', 'true', '1', 'yes' ), true );
    }

    /** The request with the Authorization header masked. */
    protected static function withoutSecrets( array $request )
    {
        foreach ( $request['headers'] as $i => $header )
            if ( preg_match( '/^(authorization|x-api-key|api-key)\s*:/i', $header, $m ) )
                $request['headers'][$i] = $m[1] . ': ***';
        return $request;
    }
}
