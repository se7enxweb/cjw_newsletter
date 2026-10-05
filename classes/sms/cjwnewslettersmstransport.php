<?php
/**
 * File containing the CjwNewsletterSmsTransport interface
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * A way to send SMS and to read the SMS a provider hands back (the provider-neutral part of the SMS channel).
 *
 * A transport is configured in cjw_newsletter.ini as a group [SmsTransport_<name>] with Class=<a class that
 * implements this interface>; [SmsSettings] Transport=<name> chooses the one in use. CjwNewsletterSms::transport()
 * makes the object and passes it its name and its settings (the group, merged over the preset of the class).
 *
 * Shipped: CjwNewsletterSmsTransportFile (writes files, for tests and simulated sending),
 * CjwNewsletterSmsTransportHttp (any HTTP/JSON or form API, mapped in the INI) and CjwNewsletterSmsTransportTwilio
 * (the HTTP transport with the settings of a Twilio-style API).
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
interface CjwNewsletterSmsTransport
{
    /**
     * @param string $name the name of the INI group (SmsTransport_<name>)
     * @param array $settings the settings of the group (name => value)
     */
    public function __construct( $name, array $settings );

    /** @return string the name of the transport (the throttle counts per name) */
    public function name();

    /**
     * Sends one SMS.
     *
     * @param string $to number in E.164 form (+4917...)
     * @param string $from sender id or number; '' = the transport's own
     * @param string $text the text, already personalised
     * @return array hash( ok: bool, id: provider message id, error: string )
     */
    public function send( $to, $from, $text );

    /**
     * Checks that a request to the inbound endpoint really comes from the provider (a signature, a shared secret).
     *
     * @param array $request hash( url: the full URL called, params: POST/GET values, headers: lower-case name => value,
     *                       body: the raw body )
     * @return bool
     */
    public function verifyInbound( array $request );

    /**
     * Reads an incoming SMS from a verified request.
     *
     * @param array $request as verifyInbound()
     * @return array|null hash( from, text, id ) or null when the request is no SMS (a delivery report)
     */
    public function parseInbound( array $request );

    /**
     * What the endpoint answers to the provider.
     *
     * @param bool $ok the request was verified and handled
     * @return array hash( status: HTTP status, content_type, body )
     */
    public function inboundResponse( $ok );
}
