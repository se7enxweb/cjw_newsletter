<?php
/**
 * File containing the CjwNewsletterTransportFile class
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @filesource
 */
/**
 * Storing a mail to a file
 *
 * @version //autogentag//
 * @package cjw_newsletter
 */
class CjwNewsletterTransportFile implements ezcMailTransport
{
    /**
     *
     * @var string
     */
    public $mailDir = 'var/log/mail';

    /**
     * Constructs a new CjwNewsletterTransportFile
     *
     * @param string $mailDir
     * @return void
     */
    public function __construct( $mailDir = 'var/log/mail' )
    {
        $mailDir = self::resolveMailDir( $mailDir );
        if ( is_dir( $mailDir ) or eZDir::mkdir( $mailDir, false, true ) )
        {
            $this->mailDir = $mailDir;
        }
        else
        {
            throw new ezcMailTransportException( "The mail directory '$mailDir' does not exist and cannot be created." );
        }
    }

    /**
     * A relative directory is relative to the installation, not to whatever the current directory of the process is
     * (a cron job and a web request differ in that).
     *
     * @param string $mailDir
     * @return string
     */
    public static function resolveMailDir( $mailDir )
    {
        $mailDir = rtrim( (string)$mailDir, '/\\' );
        if ( $mailDir === '' )
        {
            $mailDir = 'var/log/mail';
        }
        if ( $mailDir[0] !== '/' && !preg_match( '#^[A-Za-z]:[/\\\\]#', $mailDir ) )
        {
            $base = rtrim( (string)eZSys::siteDir(), '/\\' );
            // the installation's directory; where the process does not know it (siteDir is empty), the current directory is
            if ( $base === '' || $base[0] !== '/' || !is_dir( $base ) )
            {
                $base = rtrim( getcwd(), '/\\' );
            }
            $mailDir = $base . '/' . $mailDir;
        }
        return $mailDir;
    }

    /**
     * Stores the mail $mail to the filesystem
     *
     * @throws ezcMailTransportException
     *         if the mail was not accepted for delivery by the MTA.
     * @param ezcMail $mail
     * @return void
     */
    public function send( ezcMail $mail )
    {
        $mail->appendExcludeHeaders( array( 'to', 'subject' ) );
        $headers = rtrim( $mail->generateHeaders() ); // rtrim removes the linebreak at the end, mail doesn't want it.

        if ( ( count( $mail->to ) + count( $mail->cc ) + count( $mail->bcc ) ) < 1 )
        {
            throw new ezcMailTransportException( 'No recipient addresses found in message header.' );
        }
        $emailReturnPath = '';
        if ( isset( $mail->returnPath ) )
        {
            $emailReturnPath = $mail->returnPath->email;
        }


        $firstRecipient = '';
        foreach ( array( $mail->to, $mail->cc, $mail->bcc ) as $addressList )
        {
            if ( count( $addressList ) > 0 )
            {
                $firstRecipient = $addressList[0]->email;
                break;
            }
        }

        $success = $this->createMailFile( ezcMailTools::composeEmailAddresses( $mail->to ),
                         $mail->getHeader( 'Subject' ),
                         $mail->generateBody(),
                         $headers,
                         $emailReturnPath,
                         $firstRecipient );
        if ( $success === false )
        {
            throw new ezcMailTransportException( 'The email could not be written to ' . $this->mailDir );
        }
    }

    /**
     *
     * @param unknown_type $receiver
     * @param unknown_type $subject
     * @param unknown_type $message
     * @param unknown_type $extraHeaders
     * @param unknown_type $emailReturnPath
     * @return file
     */
    function createMailFile( $receiver, $subject, $message, $extraHeaders, $emailReturnPath = '', $recipientAddress = '' )
    {
        $sys = eZSys::instance();
        $lineBreak =  ($sys->osType() == 'win32' ? "\r\n" : "\n" );
        // $separator =  ($sys->osType() == 'win32' ? "\\" : "/" );
        // $fileName = date("Ymd") .'-' .date("His").'-'.rand().'.mail';
        // unique even when many mails are written in the same second: time, random id and the recipient
        $recipient = preg_replace( '/[^A-Za-z0-9._@-]+/', '_', substr( $recipientAddress !== '' ? (string)$recipientAddress : strip_tags( (string)$receiver ), 0, 80 ) );
        $fileName = gmdate( 'Ymd-His' ) . '-' . uniqid( '', true ) . '-' . $recipient . '.eml';
        // $mailDir = eZSys::siteDir().eZSys::varDirectory().'/log/mail';
        // $mailDir = eZSys::siteDir().'var/log/mail';

        $data = $extraHeaders.$lineBreak;
        if ( $emailReturnPath != '')
            $data .= "Return-Path: <".$emailReturnPath.">".$lineBreak;

        $data .= "To: ".$receiver.$lineBreak;
        $data .= "Subject: ".$subject.$lineBreak;
        // $data .= "From: ".$emailSender.$lineBreak;
        $data .=  $lineBreak;
        $data .= $message;

        $data = preg_replace('/(\r\n|\r|\n)/', "\r\n", $data);

        return eZFile::create( $fileName, $this->mailDir, $data );
    }
}
?>