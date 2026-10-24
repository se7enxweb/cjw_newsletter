<?php
/**
 * File containing the CjwNewsletterTransport class
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @filesource
 */
/**
 * Generate output and mail
 *
 * @version //autogentag//
 * @package cjw_newsletter
 */
class CjwNewsletterTransport
{

    /**
    * Holds the properties of this class.
    *
    * @var array(string=>mixed)
    */
    protected $properties = array();

    /**
     * Set all default values
     *
     * @param string $transportMethod
     * @return void
     */
    function __construct( $transportMethod = 'file' )
    {
        $this->transportMethod = $transportMethod;
    }

    /**
     * Choose opportuneness send-logic
     *
     * @param object $ezcMailComposerObject
     * @return error message / boolean
     */
    public function send( ezcMail $ezcMailComposerObject )
    {
        $iniTransport = $this->transportMethod;
        $cjwNewsletterINI = eZINI::instance( 'cjw_newsletter.ini' );
        $transport = null;

        try
        {
        switch ( $iniTransport )
        {
            case 'smtp':
            {
                // read smtp settings from ini
                $smtpTransportServer = $cjwNewsletterINI->variable( 'NewsletterMailSettings', 'SmtpTransportServer' );
                $smtpTransportPort = $cjwNewsletterINI->variable( 'NewsletterMailSettings', 'SmtpTransportPort' );
                $smtpTransportUser = $cjwNewsletterINI->variable( 'NewsletterMailSettings', 'SmtpTransportUser' );
                $smtpTransportPassword = $cjwNewsletterINI->variable( 'NewsletterMailSettings', 'SmtpTransportPassword' );
                $smtpTransportConnectionType = $cjwNewsletterINI->variable( 'NewsletterMailSettings', 'SmtpTransportConnectionType' );

                $options = new ezcMailSmtpTransportOptions();
                if( $smtpTransportConnectionType )
                {
                    $options->connectionType = $smtpTransportConnectionType;
                }
                $transport = new ezcMailSmtpTransport(  $smtpTransportServer,
                                                        $smtpTransportUser,
                                                        $smtpTransportPassword,
                                                        $smtpTransportPort,
                                                        $options );

            } break;
            case 'file':
            {
                // var/log/mail
                $mailDir = $cjwNewsletterINI->variable( 'NewsletterMailSettings', 'FileTransportMailDir' );
                $transport = new CjwNewsletterTransportFile( $mailDir );
            } break;
            case 'sendmail':
            case 'mta':
            {
                $transport = new ezcMailMtaTransport();
            } break;
            default:
            {
                throw new ezcMailTransportException( 'Unknown transport method ' . $iniTransport );
            }
        }

            // zetacomponents/mail neutralises no line break: a CR/LF in an address, a name, the subject or a
            // header would start a header of its own, and in an address also an SMTP command of its own
            $problem = self::secureMail( $ezcMailComposerObject );
            if ( $problem !== null )
            {
                throw new ezcMailTransportException( 'Mail refused: ' . $problem );
            }

            if ( $iniTransport === 'smtp' )
            {
                // ezcMailSmtpTransport escapes a "." at the start of a line (RFC 5321, 4.5.2) only after CRLF.
                // With the line break of HeaderLineEnding (LF by default) a line of the newsletter starting
                // with "." was sent unescaped: SMTP smuggling, or the end of DATA in mid-message. SMTP is CRLF.
                $lineBreak = ezcMailTools::lineBreak();
                ezcMailTools::setLineBreak( "\r\n" );
                try
                {
                    $transport->send( $ezcMailComposerObject );
                }
                finally
                {
                    ezcMailTools::setLineBreak( $lineBreak );
                }
            }
            else
            {
                $transport->send( $ezcMailComposerObject );
            }
            // CjwNewsletterLog::writeInfo( 'email send ok', 'CjwNewsletterTransport', 'send' );

            return true;
        }
        catch ( Exception $e )
        {
            // error by transport with tracking
            eZDebug::writeError( 'CjwNewsletterTransport:send: ' . $e->getMessage()  );
            return $e;
        }
    }

    /**
     * $text without CR, LF, NUL and the other control characters (a tab stays), for a mail header.
     *
     * @param mixed $text
     * @return string
     */
    public static function cleanHeaderText( $text )
    {
        return trim( (string)preg_replace( '/[\x00-\x08\x0A-\x1F\x7F]+/', ' ', (string)$text ) );
    }

    /**
     * Whether $email is an address that may go into a mail header and an SMTP command.
     *
     * ezcMailTools::validateEmailAddress() alone is not enough: its pattern ends with "$" without the D
     * modifier, so "someone@example.com\n" is valid to it, and its quoted local part allows most control
     * characters.
     *
     * @param mixed $email
     * @return bool
     */
    public static function isValidAddress( $email )
    {
        return is_string( $email )
            && $email !== ''
            && strlen( $email ) <= 254
            && !preg_match( '/[\x00-\x1F\x7F]/', $email )
            && ezcMailTools::validateEmailAddress( $email );
    }

    /**
     * Checks every address of $mail (from, return path, to, cc, bcc and the addresses in headers such as
     * Reply-To) and takes control characters out of names, the subject and the other header values.
     *
     * @param ezcMail $mail
     * @return string|null why the mail must not be sent, or null
     */
    public static function secureMail( ezcMail $mail )
    {
        $addresses = array();
        foreach ( array( 'from', 'returnPath' ) as $field )
        {
            if ( $mail->$field !== null )
                $addresses[$field] = array( $mail->$field );
        }
        foreach ( array( 'to', 'cc', 'bcc' ) as $field )
        {
            $addresses[$field] = (array)$mail->$field;
        }
        foreach ( $addresses as $field => $list )
        {
            foreach ( $list as $address )
            {
                if ( !$address instanceof ezcMailAddress || !self::isValidAddress( $address->email ) )
                    return "invalid address in $field";
                $address->name = self::cleanHeaderText( $address->name );
            }
        }
        $mail->subject = self::cleanHeaderText( $mail->subject );

        $headers = $mail->headers;
        foreach ( $headers->getCaseSensitiveArray() as $name => $value )
        {
            if ( !preg_match( '/^[\x21-\x39\x3B-\x7E]+$/', (string)$name ) )
                return 'invalid header name';
            $values = is_array( $value ) ? $value : array( $value );
            foreach ( $values as $index => $item )
            {
                if ( $item instanceof ezcMailAddress )
                {
                    if ( !self::isValidAddress( $item->email ) )
                        return "invalid address in header $name";
                    $item->name = self::cleanHeaderText( $item->name );
                }
                else if ( is_scalar( $item ) )
                {
                    $values[$index] = self::cleanHeaderText( $item );
                }
            }
            $headers[$name] = is_array( $value ) ? $values : $values[0];
        }
        return null;
    }

    /**
     * Sets the property $name to $value.
     *
     * @param string $name
     * @param string $value
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name does not exist
     * @throws ezcBaseValueException
     *         if $value is not accepted for the property $name
     * @param string $name
     * @param mixed $value
     * @return void
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'transportMethod':
                if ( !(  in_array($value, array( 'file', 'smtp', 'sendmail', 'mta' ) ) ) )
                {
                    throw new ezcBaseValueException( 'transportMethod', $value, 'file, smtp, sendmail, mta' );
                }
                $this->properties[$name] = $value;
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Returns the value of the property $name.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name does not exist
     *
     * @param string $name
     * @return mixed
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'transportMethod':
                return $this->properties[$name];
            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Returns true if the property $name is set, otherwise false.
     *
     * @param string $name
     * @return boolean
     * @ignore
     */
    public function __isset( $name )
    {
        switch ( $name )
        {
            case 'transportMethod':
                return isset( $this->properties[$name] );

            default:
                return false;
        }
    }

}

?>
