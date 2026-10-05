<?php
/**
 * File containing the CjwNewsletterSms class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * The SMS channel of cjw_newsletter: settings, the transports, phone numbers (E.164), the confirmation code (the
 * double opt-in of a number), the STOP keyword, and the SMS queue of an SMS send.
 *
 * Who gets an SMS: a subscriber of the list whose number is confirmed with the code (cjwnl_user.phone_status = 2)
 * AND whose kernel mail-preference category "sms" is on (expMailPreferences::allows( 'sms' )). The confirmation
 * code is the double opt-in; the consent log records it as 'confirm' of the category sms with the wording shown.
 *
 * The text of an SMS send is kept as one row of cjwnl_sms_message with newsletter_user_id 0 and status
 * STATUS_TEMPLATE; the rows of the recipients are made from it when the queue is created.
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSms
{
    const PHONE_NONE = 0;
    const PHONE_PENDING = 1;
    const PHONE_CONFIRMED = 2;
    const PHONE_STOPPED = 3;

    const STATUS_NEW = 0;
    const STATUS_SENT = 1;
    const STATUS_FAILED = 2;
    const STATUS_TEMPLATE = 8;
    const STATUS_ABORTED = 9;

    const CHANNEL = 'sms';
    const I18N = 'cjw_newsletter/sms';

    /** the GSM 03.38 basic character set (one septet each) */
    const GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
    /** the GSM 03.38 extension table (two septets each) */
    const GSM_EXTENDED = "^{}\\[~]|€\f";

    /** @var array|null transports made in this request, by name */
    protected static $transports = array();

    // ------------------------------------------------------------------ settings

    /** @return eZINI */
    public static function ini()
    {
        return eZINI::instance( 'cjw_newsletter.ini' );
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed a setting of [SmsSettings]
     */
    public static function setting( $name, $default = null )
    {
        $ini = self::ini();
        return $ini->hasVariable( 'SmsSettings', $name ) ? $ini->variable( 'SmsSettings', $name ) : $default;
    }

    /** @return bool [SmsSettings] Sms=enabled */
    public static function enabled()
    {
        return self::setting( 'Sms', 'disabled' ) === 'enabled';
    }

    /** @return string the kernel category of the SMS consent */
    public static function category()
    {
        return (string)self::setting( 'ConsentCategory', 'sms' );
    }

    /** @return string the name of the transport in use */
    public static function transportName()
    {
        $name = preg_replace( '/[^A-Za-z0-9_]/', '', (string)self::setting( 'Transport', 'file' ) );
        return $name === '' ? 'file' : $name;
    }

    /** @return string the name N1's throttle counts the SMS under */
    public static function throttleName()
    {
        $name = (string)self::setting( 'ThrottleTransport', '' );
        return $name !== '' ? $name : 'sms';
    }

    /**
     * The transport object of an INI group [SmsTransport_<name>].
     *
     * @param string|null $name null = [SmsSettings] Transport
     * @return CjwNewsletterSmsTransport|null null when the group or its class is missing
     */
    public static function transport( $name = null )
    {
        $name = $name === null ? self::transportName() : preg_replace( '/[^A-Za-z0-9_]/', '', (string)$name );
        if ( isset( self::$transports[$name] ) )
            return self::$transports[$name];
        $ini = self::ini();
        $group = 'SmsTransport_' . $name;
        if ( $name === '' || !$ini->hasGroup( $group ) )
            return null;
        $settings = $ini->group( $group );
        $class = isset( $settings['Class'] ) ? trim( (string)$settings['Class'] ) : '';
        if ( $class === '' || !class_exists( $class ) || !in_array( 'CjwNewsletterSmsTransport', class_implements( $class ), true ) )
        {
            eZDebug::writeError( "SMS transport $name: the class '$class' does not exist or is no CjwNewsletterSmsTransport", __METHOD__ );
            return null;
        }
        return self::$transports[$name] = new $class( $name, $settings );
    }

    /** Forgets the transports made so far (after a change of the settings, in tests). */
    public static function resetTransports()
    {
        self::$transports = array();
    }

    // ------------------------------------------------------------------ numbers and texts

    /**
     * A phone number in E.164 form: +, the country code and the number, without blanks. "00" at the start is "+";
     * a number with one leading 0 gets [SmsSettings] DefaultCountryCode (e.g. 49).
     *
     * @param string $raw
     * @param string|null $countryCode digits; null = the setting
     * @return string the number, '' when it is not a valid number
     */
    public static function normalisePhone( $raw, $countryCode = null )
    {
        $number = trim( (string)$raw );
        if ( $number === '' || mb_strlen( $number ) > 40 )
            return '';
        // blanks, dashes, dots, slashes and brackets are only formatting; "(0)" after a country code is dropped
        $number = preg_replace( '/\(0\)/', '', $number );
        $number = preg_replace( '/[\s\-\.\/\(\)\x{00A0}]+/u', '', $number );
        if ( strncmp( $number, '00', 2 ) === 0 )
            $number = '+' . substr( $number, 2 );
        if ( $number !== '' && $number[0] !== '+' )
        {
            $countryCode = preg_replace( '/\D/', '', (string)( $countryCode === null ? self::setting( 'DefaultCountryCode', '' ) : $countryCode ) );
            if ( $countryCode === '' || $number[0] !== '0' )
                return '';
            $number = '+' . $countryCode . substr( $number, 1 );
        }
        return self::validPhone( $number ) ? $number : '';
    }

    /** @return bool the number is in E.164 form */
    public static function validPhone( $number )
    {
        return is_string( $number ) && preg_match( '/^\+[1-9][0-9]{6,14}$/', $number ) === 1;
    }

    /** @return string the number with all but the last four digits hidden (+49 ••• 1234) */
    public static function maskPhone( $number )
    {
        $number = (string)$number;
        if ( strlen( $number ) < 6 )
            return $number === '' ? '' : '•••';
        return substr( $number, 0, 3 ) . ' ••• ' . substr( $number, -4 );
    }

    /**
     * Length and parts of an SMS text: GSM 03.38 (160 per SMS, 153 per part of a long one; the extension
     * characters count twice) or, with any other character, UCS-2 (70, 67 per part; a character outside the
     * basic plane counts twice).
     *
     * @param string $text
     * @return array hash( encoding: gsm|ucs2, units, segments, per_segment, remaining )
     */
    public static function segments( $text )
    {
        $text = (string)$text;
        $units = 0;
        $gsm = true;
        $chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
        $chars = $chars ? $chars : array();
        foreach ( $chars as $char )
        {
            if ( mb_strpos( self::GSM_BASIC, $char ) !== false )
                $units += 1;
            else if ( mb_strpos( self::GSM_EXTENDED, $char ) !== false )
                $units += 2;
            else
            {
                $gsm = false;
                break;
            }
        }
        if ( !$gsm )
        {
            $units = 0;
            foreach ( $chars as $char )
                $units += strlen( mb_convert_encoding( $char, 'UTF-16BE', 'UTF-8' ) ) / 2;
        }
        $single = $gsm ? 160 : 70;
        $part = $gsm ? 153 : 67;
        if ( $units === 0 )
            return array( 'encoding' => $gsm ? 'gsm' : 'ucs2', 'units' => 0, 'segments' => 0, 'per_segment' => $single, 'remaining' => $single );
        $segments = $units <= $single ? 1 : (int)ceil( $units / $part );
        $per = $segments === 1 ? $single : $part;
        return array( 'encoding' => $gsm ? 'gsm' : 'ucs2', 'units' => (int)$units, 'segments' => $segments, 'per_segment' => $per,
                      'remaining' => $segments * $per - (int)$units );
    }

    /** @return int [SmsSettings] MaxSegments (an SMS edition may not be longer) */
    public static function maxSegments()
    {
        return max( 1, (int)self::setting( 'MaxSegments', 3 ) );
    }

    /** @return string the hint appended to every SMS of an edition ('' = none) */
    public static function stopHint()
    {
        if ( self::setting( 'AppendStopHint', 'enabled' ) !== 'enabled' )
            return '';
        $keywords = self::stopKeywords();
        return ezpI18n::tr( self::I18N, 'Reply %keyword to stop.', null, array( '%keyword' => $keywords ? $keywords[0] : 'STOP' ) );
    }

    /** @return string[] the STOP keywords, upper case */
    public static function stopKeywords()
    {
        $out = array();
        foreach ( (array)self::setting( 'StopKeywords', array( 'STOP' ) ) as $keyword )
        {
            $keyword = mb_strtoupper( trim( (string)$keyword ) );
            if ( $keyword !== '' && !in_array( $keyword, $out, true ) )
                $out[] = $keyword;
        }
        return $out ? $out : array( 'STOP' );
    }

    // ------------------------------------------------------------------ people

    /**
     * @param expMailRecipient $recipient
     * @return CjwNewsletterUser|null the newsletter user of a person (by account, then by address)
     */
    public static function newsletterUserFor( $recipient )
    {
        $user = null;
        if ( $recipient->userId() > 0 )
            $user = CjwNewsletterUser::fetchByEzUserId( $recipient->userId() );
        if ( !is_object( $user ) && $recipient->email() !== '' )
            $user = CjwNewsletterUser::fetchByEmail( $recipient->email() );
        if ( is_array( $user ) )
            $user = $user ? $user[0] : null;
        return is_object( $user ) ? $user : null;
    }

    /**
     * @param CjwNewsletterUser $user
     * @return expMailRecipient|null the person of the kernel's preferences (null before Exponential 6.0.15)
     */
    public static function recipientFor( $user )
    {
        if ( !class_exists( 'expMailRecipient' ) )
            return null;
        $recipient = null;
        if ( (int)$user->attribute( 'ez_user_id' ) > 0 )
            $recipient = expMailRecipient::fromUserId( (int)$user->attribute( 'ez_user_id' ) );
        if ( $recipient === null && (string)$user->attribute( 'email' ) !== '' )
            $recipient = expMailRecipient::fromAddress( (string)$user->attribute( 'email' ) );
        return $recipient;
    }

    /** @return bool the category sms exists in the kernel's registry */
    public static function categoryAvailable()
    {
        return class_exists( 'expMailCategoryRegistry' ) && expMailCategoryRegistry::instance()->get( self::category() ) !== null;
    }

    /**
     * May an edition SMS go to this newsletter user?
     *
     * @param CjwNewsletterUser $user
     * @return string 'allow', or the reason: no_phone, not_confirmed, stopped, no_category, and the reasons of
     *                expMailPreferences::decision() (off, pending, master_off, suppressed)
     */
    public static function decision( $user )
    {
        if ( !self::validPhone( (string)$user->attribute( 'phone_number' ) ) )
            return 'no_phone';
        $status = (int)$user->attribute( 'phone_status' );
        if ( $status === self::PHONE_STOPPED )
            return 'stopped';
        if ( $status !== self::PHONE_CONFIRMED )
            return 'not_confirmed';
        if ( !self::categoryAvailable() )
            return 'no_category';
        $recipient = self::recipientFor( $user );
        if ( $recipient === null )
            return 'no_category';
        return expMailPreferences::forRecipient( $recipient )->decision( self::category() );
    }

    /** @return bool */
    public static function allows( $user )
    {
        return self::decision( $user ) === 'allow';
    }

    // ------------------------------------------------------------------ the confirmation code

    /** @return string the key the codes are hashed with (derived from the site secret of the mail preferences) */
    protected static function codeKey()
    {
        if ( class_exists( 'expMailSecret' ) )
            return expMailSecret::derive( 'cjwnl-sms-code' );
        return hash( 'sha256', 'cjwnl-sms-code:' . eZINI::instance()->variable( 'SiteSettings', 'SiteName' ) . ':' . __FILE__, true );
    }

    /** @return string the stored hash of a code */
    public static function codeHash( $userId, $phone, $code )
    {
        return hash_hmac( 'sha256', (int)$userId . '|' . (string)$phone . '|' . (string)$code, self::codeKey() );
    }

    /**
     * Stores a number for a newsletter user and sends the confirmation code to it. Until the code is entered the
     * number is pending and gets no edition SMS.
     *
     * @param CjwNewsletterUser $user
     * @param string $rawPhone as typed
     * @return array hash( ok: bool, error: string, phone: the E.164 number, code: the code (only in tests: $returnCode) )
     */
    public static function requestCode( $user, $rawPhone, $returnCode = false )
    {
        $phone = self::normalisePhone( $rawPhone );
        if ( $phone === '' )
            return array( 'ok' => false, 'phone' => '', 'error' => ezpI18n::tr( self::I18N, 'This is not a valid mobile number. Please enter it with the country code, for example +49 151 23456789.' ) );
        $userId = (int)$user->attribute( 'id' );
        $hour = time() - 3600;
        $recent = CjwNewsletterSmsCode::fetchListCount( array( 'newsletter_user_id' => $userId, 'purpose' => 'confirm', 'created' => array( '>', $hour ) ) );
        if ( $recent >= max( 1, (int)self::setting( 'CodeMaxPerHour', 3 ) ) )
            return array( 'ok' => false, 'phone' => $phone, 'error' => ezpI18n::tr( self::I18N, 'Too many codes were requested. Please wait an hour and try again.' ) );
        $transport = self::transport();
        if ( !$transport )
            return array( 'ok' => false, 'phone' => $phone, 'error' => ezpI18n::tr( self::I18N, 'SMS cannot be sent at the moment. Please try again later.' ) );
        if ( self::throttleAcquire( 1 ) < 1 )
            return array( 'ok' => false, 'phone' => $phone, 'error' => ezpI18n::tr( self::I18N, 'SMS cannot be sent at the moment. Please try again in a few minutes.' ) );

        $length = min( 10, max( 4, (int)self::setting( 'CodeLength', 6 ) ) );
        $code = str_pad( (string)random_int( 0, (int)pow( 10, $length ) - 1 ), $length, '0', STR_PAD_LEFT );
        $now = time();
        $db = eZDB::instance();
        $db->begin();
        // an older code of the person is no longer valid
        foreach ( CjwNewsletterSmsCode::fetchList( array( 'newsletter_user_id' => $userId, 'purpose' => 'confirm', 'used' => 0 ) ) as $old )
        {
            $old->setAttribute( 'expires', min( (int)$old->attribute( 'expires' ), $now - 1 ) );
            $old->store();
        }
        $row = CjwNewsletterSmsCode::create( array( 'newsletter_user_id' => $userId, 'phone_number' => $phone,
            'code_hash' => self::codeHash( $userId, $phone, $code ), 'purpose' => 'confirm', 'attempts' => 0,
            'expires' => $now + max( 60, (int)self::setting( 'CodeTTL', 900 ) ), 'used' => 0, 'created' => $now ) );
        $row->store();
        $user->setAttribute( 'phone_number', $phone );
        $user->setAttribute( 'phone_status', self::PHONE_PENDING );
        $user->setAttribute( 'phone_confirmed', 0 );
        $user->store();
        $db->commit();

        $siteName = (string)eZINI::instance()->variable( 'SiteSettings', 'SiteName' );
        $text = ezpI18n::tr( self::I18N, 'Your confirmation code for the newsletters of %site: %code', null, array( '%site' => $siteName, '%code' => $code ) );
        $link = self::confirmURL( $user );
        if ( $link !== '' )
            $text .= "\n" . ezpI18n::tr( self::I18N, 'Enter it at %url', null, array( '%url' => $link ) );
        $result = $transport->send( $phone, (string)self::setting( 'CodeSender', '' ), $text );
        self::throttleRecord( 1 );
        if ( !$result['ok'] )
        {
            $row->setAttribute( 'expires', $now - 1 );
            $row->store();
            eZDebug::writeError( 'The confirmation SMS could not be sent: ' . $result['error'], __METHOD__ );
            return array( 'ok' => false, 'phone' => $phone, 'error' => ezpI18n::tr( self::I18N, 'The code could not be sent. Please check the number or try again later.' ) );
        }
        $out = array( 'ok' => true, 'phone' => $phone, 'error' => '' );
        if ( $returnCode )
            $out['code'] = $code;
        return $out;
    }

    /**
     * Checks a confirmation code. The right code confirms the number and records the consent ('confirm' of the
     * category sms, with $wording); the category is switched on when it was not.
     *
     * @param CjwNewsletterUser $user
     * @param string $code as typed
     * @param expConsentContext|null $context the context of the request (null: from the request, source confirm)
     * @return array hash( ok: bool, error: string )
     */
    public static function confirmCode( $user, $code, $context = null )
    {
        $code = preg_replace( '/\s+/', '', (string)$code );
        $userId = (int)$user->attribute( 'id' );
        $rows = CjwNewsletterSmsCode::fetchList( array( 'newsletter_user_id' => $userId, 'purpose' => 'confirm', 'used' => 0 ), 1 );
        $row = $rows ? $rows[0] : null;
        $now = time();
        if ( !$row || (int)$row->attribute( 'expires' ) < $now || (string)$row->attribute( 'phone_number' ) !== (string)$user->attribute( 'phone_number' ) )
            return array( 'ok' => false, 'error' => ezpI18n::tr( self::I18N, 'The code has expired. Please ask for a new code.' ) );
        $max = max( 1, (int)self::setting( 'CodeMaxAttempts', 5 ) );
        if ( (int)$row->attribute( 'attempts' ) >= $max )
            return array( 'ok' => false, 'error' => ezpI18n::tr( self::I18N, 'The code was entered wrongly too often. Please ask for a new code.' ) );
        if ( $code === '' || !ctype_digit( $code ) || !hash_equals( (string)$row->attribute( 'code_hash' ), self::codeHash( $userId, $row->attribute( 'phone_number' ), $code ) ) )
        {
            $row->setAttribute( 'attempts', (int)$row->attribute( 'attempts' ) + 1 );
            $row->store();
            return array( 'ok' => false, 'error' => ezpI18n::tr( self::I18N, 'The code is not right. Please check it and try again.' ) );
        }
        $db = eZDB::instance();
        $db->begin();
        $row->setAttribute( 'used', $now );
        $row->store();
        $user->setAttribute( 'phone_status', self::PHONE_CONFIRMED );
        $user->setAttribute( 'phone_confirmed', $now );
        $user->store();
        $recipient = self::recipientFor( $user );
        if ( $recipient !== null && self::categoryAvailable() )
        {
            $wording = self::consentWording();
            if ( $context === null )
                $context = expConsentContext::fromRequest( 'confirm', $wording );
            else if ( $context->wording === '' )
                $context->wording = $wording;
            $confirmContext = new expConsentContext( 'confirm', $context->wording, $context->ip, $context->actorUserId, $context->siteaccess );
            $prefs = expMailPreferences::forRecipient( $recipient );
            $old = $prefs->state( self::category() );
            if ( $old !== expMailPreferences::ON || !$prefs->isStored( self::category() ) )
                $prefs->set( self::category(), true, $confirmContext );       // writes the 'confirm' row itself
            else
                expConsentLog::record( $recipient, self::category(), 'confirm', 'pending', 'on', $confirmContext );
        }
        $db->commit();
        return array( 'ok' => true, 'error' => '' );
    }

    /**
     * @param CjwNewsletterUser $user
     * @return string the public page where the code is entered ('' with [SmsSettings] CodeLink=disabled)
     */
    public static function confirmURL( $user )
    {
        if ( self::setting( 'CodeLink', 'enabled' ) !== 'enabled' || (string)$user->attribute( 'hash' ) === '' )
            return '';
        $base = class_exists( 'expMailToken' ) ? expMailToken::baseURL() : rtrim( 'https://' . eZINI::instance()->variable( 'SiteSettings', 'SiteURL' ), '/' );
        return $base . '/newsletter/sms_confirm/' . rawurlencode( (string)$user->attribute( 'hash' ) );
    }

    /** @return string the wording of the consent, as the preference page and the confirmation page show it */
    public static function consentWording()
    {
        return ezpI18n::tr( self::I18N, 'I confirm my mobile number with the code and want to receive newsletters by SMS. I can stop them at any time by replying STOP or on the preference page.' );
    }

    /**
     * Removes the number of a newsletter user (and with it every pending SMS to it).
     *
     * @param CjwNewsletterUser $user
     */
    public static function removePhone( $user )
    {
        $db = eZDB::instance();
        $db->begin();
        $user->setAttribute( 'phone_number', '' );
        $user->setAttribute( 'phone_status', self::PHONE_NONE );
        $user->setAttribute( 'phone_confirmed', 0 );
        $user->store();
        self::abortPendingMessagesOf( (int)$user->attribute( 'id' ), 'number removed' );
        $db->commit();
    }

    // ------------------------------------------------------------------ inbound (STOP)

    /**
     * Handles a request to the inbound endpoint: checks it with the transport, reads the SMS, stores it, and a
     * STOP keyword stops the SMS of the number.
     *
     * @param string $transportName
     * @param array $request hash( url, params, headers, body )
     * @return array hash( verified: bool, action: stop|ignored|none, inbound_id, response: the transport's answer )
     */
    public static function handleInbound( $transportName, array $request )
    {
        $transport = self::transport( $transportName );
        if ( !$transport || !$transport->verifyInbound( $request ) )
        {
            $fallback = $transport ? $transport : new CjwNewsletterSmsTransportFile( 'none', array() );
            return array( 'verified' => false, 'action' => 'none', 'inbound_id' => 0, 'response' => $fallback->inboundResponse( false ) );
        }
        $sms = $transport->parseInbound( $request );
        if ( $sms === null )
            return array( 'verified' => true, 'action' => 'none', 'inbound_id' => 0, 'response' => $transport->inboundResponse( true ) );
        $result = self::receive( $sms['from'], $sms['text'], $sms['id'] );
        $result['verified'] = true;
        $result['response'] = $transport->inboundResponse( true );
        return $result;
    }

    /**
     * An incoming SMS (verified): stored, and STOP acted on.
     *
     * @param string $from
     * @param string $text
     * @param string $providerId
     * @return array hash( action, inbound_id, users )
     */
    public static function receive( $from, $text, $providerId = '' )
    {
        $phone = self::normalisePhone( $from );
        $text = mb_substr( trim( (string)$text ), 0, 1600 );
        $words = preg_split( '/[\s,.!;:]+/u', $text, 2, PREG_SPLIT_NO_EMPTY );
        $keyword = $words ? mb_substr( mb_strtoupper( $words[0] ), 0, 50 ) : '';
        $now = time();
        if ( $providerId !== '' )
        {
            $seen = CjwNewsletterSmsInbound::fetchList( array( 'provider_message_id' => mb_substr( (string)$providerId, 0, 255 ) ), 1 );
            if ( $seen )
                return array( 'action' => (string)$seen[0]->attribute( 'action' ), 'inbound_id' => (int)$seen[0]->attribute( 'id' ), 'users' => 0, 'duplicate' => true );
        }
        $users = $phone !== '' ? self::usersByPhone( $phone ) : array();
        $action = 'ignored';
        $db = eZDB::instance();
        $db->begin();
        if ( $phone !== '' && in_array( $keyword, self::stopKeywords(), true ) )
        {
            $action = 'stop';
            foreach ( $users as $user )
                self::stopUser( $user );
        }
        $row = CjwNewsletterSmsInbound::create( array( 'phone_number' => $phone !== '' ? $phone : mb_substr( (string)$from, 0, 50 ),
            'keyword' => $keyword, 'body' => $text, 'newsletter_user_id' => $users ? (int)$users[0]->attribute( 'id' ) : 0,
            'action' => $action, 'provider_message_id' => mb_substr( (string)$providerId, 0, 255 ), 'created' => $now, 'processed' => $now ) );
        $row->store();
        $db->commit();
        return array( 'action' => $action, 'inbound_id' => (int)$row->attribute( 'id' ), 'users' => count( $users ) );
    }

    /** @return CjwNewsletterUser[] the newsletter users with this number */
    public static function usersByPhone( $phone )
    {
        $list = eZPersistentObject::fetchObjectList( CjwNewsletterUser::definition(), null, array( 'phone_number' => (string)$phone ), array( 'id' => 'asc' ), null, true );
        return is_array( $list ) ? $list : array();
    }

    /**
     * STOP: the number gets no more SMS, the category sms is switched off (consent log 'off', source system).
     *
     * @param CjwNewsletterUser $user
     */
    public static function stopUser( $user )
    {
        $user->setAttribute( 'phone_status', self::PHONE_STOPPED );
        $user->store();
        self::abortPendingMessagesOf( (int)$user->attribute( 'id' ), 'STOP' );
        $recipient = self::recipientFor( $user );
        if ( $recipient !== null && self::categoryAvailable() )
        {
            $prefs = expMailPreferences::forRecipient( $recipient );
            if ( $prefs->state( self::category() ) !== expMailPreferences::OFF || !$prefs->isStored( self::category() ) )
                $prefs->set( self::category(), false, expConsentContext::system( ezpI18n::tr( self::I18N, 'The person replied STOP by SMS.' ) ) );
        }
    }

    protected static function abortPendingMessagesOf( $userId, $reason )
    {
        foreach ( CjwNewsletterSmsMessage::fetchList( array( 'newsletter_user_id' => (int)$userId, 'status' => self::STATUS_NEW ) ) as $message )
        {
            $message->setAttribute( 'status', self::STATUS_ABORTED );
            $message->setAttribute( 'error', $reason );
            $message->setAttribute( 'processed', time() );
            $message->store();
            self::closeItems( (int)$message->attribute( 'edition_send_id' ), $userId, CjwNewsletterEditionSendItem::STATUS_ABORT );
        }
    }

    // ------------------------------------------------------------------ SMS sends

    /**
     * Makes an SMS send of an edition (it waits for the next queue run like a mail send).
     *
     * @param CjwNewsletterEdition $edition the edition content of the published version
     * @param string $text the text of the SMS, placeholders allowed
     * @return CjwNewsletterEditionSend|string the send, or an error string
     */
    public static function createSend( $edition, $text )
    {
        $errors = self::validateText( $text );
        if ( $errors )
            return $errors[0];
        $list = $edition->attribute( 'list_attribute_content' );
        if ( !is_object( $list ) || !(int)$list->attribute( 'sms_enabled' ) )
            return ezpI18n::tr( self::I18N, 'SMS are not switched on for this list.' );
        // the output of the edition is rendered by a child process that writes to the database: never inside a
        // transaction of this process
        $send = CjwNewsletterEditionSend::create( $edition );
        $db = eZDB::instance();
        $db->begin();
        $send->setAttribute( 'channel', self::CHANNEL );
        $send->setAttribute( 'throttle_transport', self::throttleName() );
        $send->store();
        $template = CjwNewsletterSmsMessage::create( array( 'edition_send_id' => (int)$send->attribute( 'id' ), 'newsletter_user_id' => 0,
            'phone_number' => '', 'body' => (string)$text, 'status' => self::STATUS_TEMPLATE, 'transport' => self::transportName(),
            'created' => time() ) );
        $template->store();
        $db->commit();
        return $send;
    }

    /**
     * @param string $text
     * @return string[] what is wrong with the text of an SMS edition
     */
    public static function validateText( $text )
    {
        $text = trim( (string)$text );
        if ( $text === '' )
            return array( ezpI18n::tr( self::I18N, 'Please write the text of the SMS.' ) );
        $hint = self::stopHint();
        $info = self::segments( $text . ( $hint !== '' ? "\n" . $hint : '' ) );
        if ( $info['segments'] > self::maxSegments() )
            return array( ezpI18n::tr( self::I18N, 'The SMS would need %count parts; at most %max are allowed. Please shorten the text.', null,
                                       array( '%count' => $info['segments'], '%max' => self::maxSegments() ) ) );
        return array();
    }

    /** @return string the text of an SMS send ('' for none) */
    public static function sendText( $sendId )
    {
        $rows = CjwNewsletterSmsMessage::fetchList( array( 'edition_send_id' => (int)$sendId, 'status' => self::STATUS_TEMPLATE ), 1 );
        return $rows ? (string)$rows[0]->attribute( 'body' ) : '';
    }

    /** @return bool the send goes out by SMS */
    public static function isSmsSend( $sendObject )
    {
        return is_object( $sendObject ) && (string)$sendObject->attribute( 'channel' ) === self::CHANNEL;
    }

    /**
     * The personal text of one recipient: the placeholders of the edition (raw: an SMS is plain text) and the STOP hint.
     *
     * @return string
     */
    public static function personalise( $text, $sendItem, $sendObject, $user )
    {
        $subscription = $sendItem->attribute( 'newsletter_subscription_object' );
        $unsubscribeHash = is_object( $subscription ) ? (string)$subscription->attribute( 'hash' ) : null;
        // the placeholders of area N3 (raw values: an SMS is plain text), else those of 4.1
        if ( method_exists( 'CjwNewsletterPlaceholders', 'valuesForSubscriber' ) && method_exists( 'CjwNewsletterPlaceholders', 'replaceInText' ) )
        {
            $values = (array)CjwNewsletterPlaceholders::valuesForSubscriber( $user, (int)$sendObject->attribute( 'list_contentobject_id' ), true, $unsubscribeHash );
            $text = (string)CjwNewsletterPlaceholders::replaceInText( $text, $values, false );
        }
        else
        {
            $values = CjwNewsletterPlaceholders::valuesForRecipient( $sendItem, $sendObject, (string)$unsubscribeHash, $user, true );
            $text = CjwNewsletterPlaceholders::replaceInSubject( $text, $values );
        }
        $hint = self::stopHint();
        return trim( $text ) . ( $hint !== '' ? "\n" . $hint : '' );
    }

    /**
     * After the queue run made the items of an SMS send: one SMS per subscriber who allows it; the items of the
     * others are closed.
     *
     * @param CjwNewsletterEditionSend $sendObject
     * @return array hash( messages, skipped )
     */
    public static function createMessages( $sendObject )
    {
        $sendId = (int)$sendObject->attribute( 'id' );
        $text = self::sendText( $sendId );
        $totals = array( 'messages' => 0, 'skipped' => 0 );
        $list = CjwNewsletterList::fetchByListObjectVersion( (int)$sendObject->attribute( 'list_contentobject_id' ), (int)$sendObject->attribute( 'list_contentobject_version' ) );
        $sender = is_object( $list ) ? (string)$list->attribute( 'sms_sender' ) : '';
        $done = array();
        foreach ( CjwNewsletterSmsMessage::fetchList( array( 'edition_send_id' => $sendId ) ) as $existing )
            $done[(int)$existing->attribute( 'newsletter_user_id' )] = true;
        $offset = 0;
        $db = eZDB::instance();
        while ( true )
        {
            $items = CjwNewsletterEditionSendItem::fetchListSendIdAndStatus( $sendId, CjwNewsletterEditionSendItem::STATUS_NEW, 200, $offset );
            if ( !$items )
                break;
            $offset += count( $items );
            $db->begin();
            foreach ( $items as $item )
            {
                $userId = (int)$item->attribute( 'newsletter_user_id' );
                if ( isset( $done[$userId] ) )
                    continue;
                $done[$userId] = true;
                $user = CjwNewsletterUser::fetch( $userId );
                $decision = is_object( $user ) && $text !== '' ? self::decision( $user ) : 'no_user';
                if ( $decision !== 'allow' )
                {
                    $totals['skipped']++;
                    continue;
                }
                $message = CjwNewsletterSmsMessage::create( array( 'edition_send_id' => $sendId, 'newsletter_user_id' => $userId,
                    'phone_number' => (string)$user->attribute( 'phone_number' ), 'body' => self::personalise( $text, $item, $sendObject, $user ),
                    'status' => self::STATUS_NEW, 'transport' => self::transportName(), 'created' => time() ) );
                $message->store();
                $totals['messages']++;
            }
            $db->commit();
        }
        // the items of people who get no SMS are closed
        foreach ( $db->arrayQuery( 'SELECT DISTINCT newsletter_user_id FROM cjwnl_edition_send_item WHERE edition_send_id = ' . $sendId . ' AND status = ' . (int)CjwNewsletterEditionSendItem::STATUS_NEW ) as $row )
        {
            $userId = (int)$row['newsletter_user_id'];
            if ( CjwNewsletterSmsMessage::fetchListCount( array( 'edition_send_id' => $sendId, 'newsletter_user_id' => $userId ) ) === 0 )
                self::closeItems( $sendId, $userId, CjwNewsletterEditionSendItem::STATUS_ABORT );
        }
        return $totals;
    }

    /**
     * Sends the waiting SMS of every SMS send, as many as the throttle allows; a send with none left is finished.
     *
     * @param eZCLI|null $cli
     * @return array hash( sent, failed, deferred, finished )
     */
    public static function processQueue( $cli = null )
    {
        $totals = array( 'sent' => 0, 'failed' => 0, 'deferred' => 0, 'finished' => 0 );
        if ( !self::enabled() )
            return $totals;
        $sends = CjwNewsletterEditionSend::fetchEditionSendListByStatus( array( CjwNewsletterEditionSend::STATUS_MAILQUEUE_CREATED, CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_STARTED ) );
        $budget = max( 1, (int)self::setting( 'MaxPerRun', 500 ) );
        foreach ( $sends as $sendObject )
        {
            if ( !self::isSmsSend( $sendObject ) )
                continue;
            if ( (int)$sendObject->attribute( 'status' ) === CjwNewsletterEditionSend::STATUS_MAILQUEUE_CREATED )
            {
                $sendObject->setAttribute( 'status', CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_STARTED );
                $sendObject->store();
            }
            $sendId = (int)$sendObject->attribute( 'id' );
            while ( $budget > 0 )
            {
                $messages = CjwNewsletterSmsMessage::fetchList( array( 'edition_send_id' => $sendId, 'status' => self::STATUS_NEW ), min( 50, $budget ), 0, array( 'id' => 'asc' ) );
                if ( !$messages )
                    break;
                $allowed = self::throttleAcquire( count( $messages ) );
                if ( $allowed < 1 )
                {
                    $totals['deferred'] += count( $messages );
                    $budget = 0;
                    if ( $cli )
                        $cli->output( 'SMS send ' . $sendId . ': the rate limit is reached, the rest goes in a later run.' );
                    break;
                }
                $sent = self::sendMessages( array_slice( $messages, 0, $allowed ), $totals );
                self::throttleRecord( $sent );
                $budget -= $allowed;
                if ( $allowed < count( $messages ) )
                {
                    $totals['deferred'] += count( $messages ) - $allowed;
                    $budget = 0;
                }
            }
            if ( CjwNewsletterSmsMessage::fetchListCount( array( 'edition_send_id' => $sendId, 'status' => self::STATUS_NEW ) ) === 0 )
            {
                foreach ( CjwNewsletterEditionSendItem::fetchListSendIdAndStatus( $sendId, CjwNewsletterEditionSendItem::STATUS_NEW, 0, 0 ) as $item )
                {
                    $item->setAttribute( 'status', CjwNewsletterEditionSendItem::STATUS_ABORT );
                    $item->store();
                }
                $sendObject->setAttribute( 'status', CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_FINISHED );
                $sendObject->store();
                $totals['finished']++;
                if ( $cli )
                    $cli->output( 'SMS send ' . $sendId . ' finished.' );
            }
            if ( $budget <= 0 )
                break;
        }
        return $totals;
    }

    /**
     * @param CjwNewsletterSmsMessage[] $messages
     * @param array $totals
     * @return int how many went out
     */
    protected static function sendMessages( array $messages, array &$totals )
    {
        $sent = 0;
        foreach ( $messages as $message )
        {
            $sendId = (int)$message->attribute( 'edition_send_id' );
            $userId = (int)$message->attribute( 'newsletter_user_id' );
            // the person may have stopped or withdrawn since the queue was made
            $user = CjwNewsletterUser::fetch( $userId );
            if ( !is_object( $user ) || !self::allows( $user ) || (string)$user->attribute( 'phone_number' ) !== (string)$message->attribute( 'phone_number' ) )
            {
                $message->setAttribute( 'status', self::STATUS_ABORTED );
                $message->setAttribute( 'error', 'no longer allowed' );
                $message->setAttribute( 'processed', time() );
                $message->store();
                self::closeItems( $sendId, $userId, CjwNewsletterEditionSendItem::STATUS_ABORT );
                continue;
            }
            $transport = self::transport( (string)$message->attribute( 'transport' ) !== '' ? (string)$message->attribute( 'transport' ) : null );
            if ( !$transport )
                $transport = self::transport();
            $list = null;
            $sendObject = CjwNewsletterEditionSend::fetch( $sendId );
            if ( is_object( $sendObject ) )
                $list = CjwNewsletterList::fetchByListObjectVersion( (int)$sendObject->attribute( 'list_contentobject_id' ), (int)$sendObject->attribute( 'list_contentobject_version' ) );
            $result = $transport ? $transport->send( (string)$message->attribute( 'phone_number' ), is_object( $list ) ? (string)$list->attribute( 'sms_sender' ) : '', (string)$message->attribute( 'body' ) )
                                 : array( 'ok' => false, 'id' => '', 'error' => 'no SMS transport' );
            $message->setAttribute( 'transport', $transport ? $transport->name() : '' );
            $message->setAttribute( 'processed', time() );
            if ( $result['ok'] )
            {
                $message->setAttribute( 'status', self::STATUS_SENT );
                $message->setAttribute( 'provider_message_id', mb_substr( (string)$result['id'], 0, 255 ) );
                $message->store();
                self::closeItems( $sendId, $userId, CjwNewsletterEditionSendItem::STATUS_SEND );
                $totals['sent']++;
                $sent++;
            }
            else
            {
                $message->setAttribute( 'status', self::STATUS_FAILED );
                $message->setAttribute( 'error', mb_substr( (string)$result['error'], 0, 2000 ) );
                $message->store();
                self::closeItems( $sendId, $userId, CjwNewsletterEditionSendItem::STATUS_ABORT );
                $totals['failed']++;
                // a provider error still used a request of the rate
                $sent++;
            }
        }
        return $sent;
    }

    /** Sets the open items of a person in a send to $status. */
    protected static function closeItems( $sendId, $userId, $status )
    {
        $db = eZDB::instance();
        $rows = $db->arrayQuery( 'SELECT id FROM cjwnl_edition_send_item WHERE edition_send_id = ' . (int)$sendId . ' AND newsletter_user_id = ' . (int)$userId
                                 . ' AND status = ' . (int)CjwNewsletterEditionSendItem::STATUS_NEW );
        foreach ( (array)$rows as $row )
        {
            $item = eZPersistentObject::fetchObject( CjwNewsletterEditionSendItem::definition(), null, array( 'id' => (int)$row['id'] ), true );
            if ( is_object( $item ) )
            {
                $item->setAttribute( 'status', $status );
                $item->store();
            }
        }
    }

    /**
     * A test SMS from the admin (to a number typed there, not stored).
     *
     * @return array hash( ok, error, phone )
     */
    public static function sendTest( $rawPhone, $text )
    {
        $phone = self::normalisePhone( $rawPhone );
        if ( $phone === '' )
            return array( 'ok' => false, 'phone' => '', 'error' => ezpI18n::tr( self::I18N, 'This is not a valid mobile number. Please enter it with the country code, for example +49 151 23456789.' ) );
        $transport = self::transport();
        if ( !$transport )
            return array( 'ok' => false, 'phone' => $phone, 'error' => ezpI18n::tr( self::I18N, 'No SMS transport is set up.' ) );
        if ( self::throttleAcquire( 1 ) < 1 )
            return array( 'ok' => false, 'phone' => $phone, 'error' => ezpI18n::tr( self::I18N, 'SMS cannot be sent at the moment. Please try again in a few minutes.' ) );
        $hint = self::stopHint();
        $result = $transport->send( $phone, '', trim( (string)$text ) . ( $hint !== '' ? "\n" . $hint : '' ) );
        self::throttleRecord( 1 );
        return array( 'ok' => (bool)$result['ok'], 'phone' => $phone, 'error' => (string)$result['error'] );
    }

    // ------------------------------------------------------------------ the throttle of area N1

    /** @return int how many SMS may go now (N1's CjwNewsletterThrottle; all of them without it) */
    public static function throttleAcquire( $wanted )
    {
        if ( class_exists( 'CjwNewsletterThrottle' ) && method_exists( 'CjwNewsletterThrottle', 'acquire' ) )
            return max( 0, min( (int)$wanted, (int)CjwNewsletterThrottle::acquire( self::throttleName(), (int)$wanted ) ) );
        return (int)$wanted;
    }

    public static function throttleRecord( $sent )
    {
        if ( $sent > 0 && class_exists( 'CjwNewsletterThrottle' ) && method_exists( 'CjwNewsletterThrottle', 'record' ) )
            CjwNewsletterThrottle::record( self::throttleName(), (int)$sent );
    }

    // ------------------------------------------------------------------ the dashboard

    /** @return array what the dashboard block shows */
    public static function summary()
    {
        $db = eZDB::instance();
        $count = function ( $sql ) use ( $db ) {
            $r = $db->arrayQuery( $sql );
            return isset( $r[0]['c'] ) ? (int)$r[0]['c'] : 0;
        };
        $messages = array();
        foreach ( array( 'new' => self::STATUS_NEW, 'sent' => self::STATUS_SENT, 'failed' => self::STATUS_FAILED, 'aborted' => self::STATUS_ABORTED ) as $key => $status )
            $messages[$key] = $count( 'SELECT COUNT(*) AS c FROM cjwnl_sms_message WHERE newsletter_user_id > 0 AND status = ' . (int)$status );
        $phones = array();
        foreach ( array( 'pending' => self::PHONE_PENDING, 'confirmed' => self::PHONE_CONFIRMED, 'stopped' => self::PHONE_STOPPED ) as $key => $status )
            $phones[$key] = $count( 'SELECT COUNT(*) AS c FROM cjwnl_user WHERE phone_status = ' . (int)$status );
        $consents = class_exists( 'expMailPreferenceRow' ) && self::categoryAvailable()
            ? $count( "SELECT COUNT(*) AS c FROM expmail_preference WHERE category = '" . $db->escapeString( self::category() ) . "' AND state = 'on'" ) : 0;
        $stops = $count( "SELECT COUNT(*) AS c FROM cjwnl_sms_inbound WHERE action = 'stop'" );
        $stops30 = $count( "SELECT COUNT(*) AS c FROM cjwnl_sms_inbound WHERE action = 'stop' AND created > " . ( time() - 30 * 86400 ) );
        $sends = array();
        foreach ( (array)$db->arrayQuery( "SELECT id, edition_contentobject_id, status, created FROM cjwnl_edition_send WHERE channel = 'sms' ORDER BY id DESC", array( 'limit' => 5 ) ) as $row )
        {
            $object = eZContentObject::fetch( (int)$row['edition_contentobject_id'] );
            $sends[] = array( 'id' => (int)$row['id'], 'name' => $object ? $object->attribute( 'name' ) : '#' . (int)$row['edition_contentobject_id'],
                              'node_id' => $object ? (int)$object->attribute( 'main_node_id' ) : 0, 'status' => (int)$row['status'], 'created' => (int)$row['created'],
                              'sent' => $count( 'SELECT COUNT(*) AS c FROM cjwnl_sms_message WHERE edition_send_id = ' . (int)$row['id'] . ' AND status = ' . self::STATUS_SENT ),
                              'waiting' => $count( 'SELECT COUNT(*) AS c FROM cjwnl_sms_message WHERE edition_send_id = ' . (int)$row['id'] . ' AND status = ' . self::STATUS_NEW ),
                              'failed' => $count( 'SELECT COUNT(*) AS c FROM cjwnl_sms_message WHERE edition_send_id = ' . (int)$row['id'] . ' AND status = ' . self::STATUS_FAILED ) );
        }
        $transport = self::transport();
        $problems = array();
        if ( self::enabled() && !$transport )
            $problems[] = array( 'level' => 'error', 'code' => 'sms_transport', 'url' => false,
                                 'text' => ezpI18n::tr( self::I18N, 'SMS are switched on, but the SMS transport %name is not set up.', null, array( '%name' => self::transportName() ) ) );
        if ( self::enabled() && !self::categoryAvailable() )
            $problems[] = array( 'level' => 'warning', 'code' => 'sms_category', 'url' => false,
                                 'text' => ezpI18n::tr( self::I18N, 'The mail-preference category %category is not switched on, so nobody can agree to SMS.', null, array( '%category' => self::category() ) ) );
        if ( $messages['failed'] > 0 )
            $problems[] = array( 'level' => 'warning', 'code' => 'sms_failed', 'url' => false,
                                 'text' => ezpI18n::tr( self::I18N, '%count SMS could not be sent.', null, array( '%count' => $messages['failed'] ) ) );
        return array( 'enabled' => self::enabled(), 'transport' => self::transportName(), 'transport_ok' => (bool)$transport,
                      'transport_class' => $transport ? get_class( $transport ) : '', 'simulated' => $transport instanceof CjwNewsletterSmsTransportFile,
                      'outbox' => $transport instanceof CjwNewsletterSmsTransportFile ? $transport->dir() : '',
                      'category' => self::category(), 'category_ok' => self::categoryAvailable(), 'consents' => $consents,
                      'messages' => $messages, 'phones' => $phones, 'stops' => $stops, 'stops_30' => $stops30, 'sends' => $sends,
                      'problems' => $problems );
    }
}
