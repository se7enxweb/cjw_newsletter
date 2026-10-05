<?php
/**
 * File containing the CjwNewsletterSmsTransportFile class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * The simulated SMS sending: nothing leaves the server, every SMS is written as one JSON file
 * <date>-<id>.sms.json (to, from, text, segments, time) into Dir. Used by the tests and to try the SMS channel
 * without a provider. A relative Dir is relative to the installation.
 *
 * Settings ([SmsTransport_file]): Dir, From, and for tests FailPattern (a regular expression: a number that matches
 * fails with "simulated failure"). Inbound requests are checked like every transport (see
 * CjwNewsletterSmsTransportBase); without an InboundSecret they are refused.
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSmsTransportFile extends CjwNewsletterSmsTransportBase
{
    protected function preset()
    {
        return array_merge( parent::preset(), array( 'Dir' => 'var/tmp/cjwnl-sms-outbox', 'FailPattern' => '', 'From' => 'Newsletter' ) );
    }

    /** @return string the absolute directory */
    public function dir()
    {
        $dir = (string)$this->setting( 'Dir', 'var/tmp/cjwnl-sms-outbox' );
        if ( $dir === '' )
            $dir = 'var/tmp/cjwnl-sms-outbox';
        if ( $dir[0] !== '/' )
            $dir = rtrim( class_exists( 'eZSys' ) ? eZSys::rootDir() : getcwd(), '/' ) . '/' . $dir;
        return rtrim( $dir, '/' );
    }

    public function send( $to, $from, $text )
    {
        $pattern = (string)$this->setting( 'FailPattern' );
        if ( $pattern !== '' && @preg_match( $pattern, (string)$to ) )
            return array( 'ok' => false, 'id' => '', 'error' => 'simulated failure' );
        $dir = $this->dir();
        if ( !is_dir( $dir ) && !@mkdir( $dir, 0770, true ) && !is_dir( $dir ) )
            return array( 'ok' => false, 'id' => '', 'error' => 'the directory ' . $dir . ' cannot be created' );
        $id = 'file-' . bin2hex( random_bytes( 8 ) );
        $from = (string)$from !== '' ? (string)$from : (string)$this->setting( 'From' );
        $data = array( 'id' => $id, 'to' => (string)$to, 'from' => $from, 'text' => (string)$text,
                       'segments' => CjwNewsletterSms::segments( $text ), 'transport' => $this->name, 'time' => gmdate( 'Y-m-d\TH:i:s\Z' ) );
        $file = $dir . '/' . gmdate( 'Ymd-His' ) . '-' . $id . '.sms.json';
        if ( @file_put_contents( $file, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n", LOCK_EX ) === false )
            return array( 'ok' => false, 'id' => '', 'error' => 'the file ' . $file . ' cannot be written' );
        @chmod( $file, 0660 );
        return array( 'ok' => true, 'id' => $id, 'error' => '' );
    }

    /**
     * @return array[] the SMS written so far, oldest first (each the stored hash plus file)
     */
    public function outbox()
    {
        $files = glob( $this->dir() . '/*.sms.json' );
        $files = $files ? $files : array();
        sort( $files );
        $out = array();
        foreach ( $files as $file )
        {
            $data = json_decode( (string)file_get_contents( $file ), true );
            if ( is_array( $data ) )
                $out[] = $data + array( 'file' => $file );
        }
        return $out;
    }
}
