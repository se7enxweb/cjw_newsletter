<?php
/**
 * File containing the CjwNewsletterSmsTransportTwilio class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * A preset of the generic HTTP transport for APIs in the style of Twilio's Messages resource: a form POST to
 * .../Accounts/{AccountSid}/Messages.json with To, From and Body, basic authentication with the account id and its
 * token, success on 201 with the message id in "sid", and inbound requests signed with the header
 * X-Twilio-Signature (base64 of HMAC-SHA1 over the full URL followed by the POST fields sorted by name, each name
 * directly followed by its value, keyed with the auth token). The inbound endpoint answers an empty TwiML document.
 *
 * Only the account settings are needed, in settings/override/cjw_newsletter.ini.append.php:
 *   [SmsTransport_twilio]
 *   AccountSid=AC...
 *   AuthToken=...
 *   From=+1...                 or ExtraFields[MessagingServiceSid]=MG... with From empty
 *   InboundUrl=https://www.example.org/newsletter/sms_inbound/twilio   the exact URL set at the provider
 * Every setting of CjwNewsletterSmsTransportHttp can still be changed (Url for a compatible provider, Timeout ...).
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSmsTransportTwilio extends CjwNewsletterSmsTransportHttp
{
    public function __construct( $name, array $settings )
    {
        parent::__construct( $name, $settings );
        if ( (string)$this->setting( 'AuthUser' ) === '' )
            $this->settings['AuthUser'] = (string)$this->setting( 'AccountSid' );
        if ( (string)$this->setting( 'AuthPassword' ) === '' )
            $this->settings['AuthPassword'] = (string)$this->setting( 'AuthToken' );
        if ( (string)$this->setting( 'InboundSecret' ) === '' )
            $this->settings['InboundSecret'] = (string)$this->setting( 'AuthToken' );
    }

    protected function preset()
    {
        return array_merge( parent::preset(), array(
            'Url' => 'https://api.twilio.com/2010-04-01/Accounts/{AccountSid}/Messages.json', 'Method' => 'POST', 'Format' => 'form',
            'Auth' => 'basic', 'AccountSid' => '', 'AuthToken' => '', 'FieldTo' => 'To', 'FieldFrom' => 'From', 'FieldText' => 'Body',
            'SuccessStatus' => '200,201', 'MessageIdPath' => 'sid', 'ErrorJsonPath' => 'message',
            'InboundSignature' => 'twilio', 'InboundSignatureHeader' => 'X-Twilio-Signature',
            'InboundFromField' => 'From', 'InboundTextField' => 'Body', 'InboundIdField' => 'MessageSid' ) );
    }

    public function verifyInbound( array $request )
    {
        if ( strtolower( (string)$this->setting( 'InboundSignature' ) ) !== 'twilio' )
            return parent::verifyInbound( $request );
        $secret = (string)$this->setting( 'InboundSecret' );
        $header = strtolower( (string)$this->setting( 'InboundSignatureHeader', 'X-Twilio-Signature' ) );
        $given = isset( $request['headers'][$header] ) ? trim( (string)$request['headers'][$header] ) : '';
        if ( $secret === '' || $given === '' )
            return false;
        $url = (string)$this->setting( 'InboundUrl' ) !== '' ? (string)$this->setting( 'InboundUrl' ) : ( isset( $request['url'] ) ? (string)$request['url'] : '' );
        $fields = isset( $request['post'] ) ? (array)$request['post'] : ( isset( $request['params'] ) ? (array)$request['params'] : array() );
        $expected = self::signature( $url, $fields, $secret );
        return hash_equals( $expected, $given );
    }

    /**
     * The signature a Twilio-style provider sends for a request.
     *
     * @param string $url the full URL the provider calls
     * @param array $params the POST fields
     * @param string $token the auth token
     * @return string base64
     */
    public static function signature( $url, array $params, $token )
    {
        ksort( $params, SORT_STRING );
        $data = (string)$url;
        foreach ( $params as $key => $value )
            $data .= $key . ( is_array( $value ) ? implode( '', $value ) : (string)$value );
        return base64_encode( hash_hmac( 'sha1', $data, (string)$token, true ) );
    }

    public function inboundResponse( $ok )
    {
        return array( 'status' => $ok ? 200 : 403, 'content_type' => 'text/xml; charset=utf-8',
                      'body' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<Response></Response>' . "\n" );
    }
}
