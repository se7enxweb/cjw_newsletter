<?php
/**
 * File containing the CjwNewsletterSmsHooks class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * The handler of area N5 (SMS) at the extension points (CjwNewsletterExtensionPoints), registered in
 * cjw_newsletter.ini [ExtensionPointSettings] Handlers[]=CjwNewsletterSmsHooks.
 *
 *  - sendQueueCreated: an SMS send (channel sms) gets one cjwnl_sms_message per subscriber who allows SMS.
 *  - sendProcessAllowed: the mail runner never mails an SMS send.
 *  - queueProcessBefore: the SMS queue is sent, within N1's throttle (transport name "sms").
 *  - listAttributeInput: the list's sms_enabled and sms_sender.
 *  - userInput / userStored, subscribeValidate / subscribeInput: the mobile number on the admin user page and in the
 *    subscribe form.
 *  - dashboardSummary: the SMS block of the dashboard.
 *  - userRemoved: a removed subscriber's codes and SMS go with him.
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSmsHooks
{
    const I18N = 'cjw_newsletter/sms';

    public static function sendQueueCreated( $sendObject, $cli )
    {
        if ( !CjwNewsletterSms::isSmsSend( $sendObject ) )
            return;
        $totals = CjwNewsletterSms::createMessages( $sendObject );
        if ( $cli )
            $cli->output( 'SMS send ' . $sendObject->attribute( 'id' ) . ': ' . $totals['messages'] . ' SMS queued, ' . $totals['skipped'] . ' subscribers without a confirmed number or SMS consent.' );
    }

    /** A subscriber is removed (CjwNewsletterUser::remove()): his codes, his SMS and the SMS from him go too. */
    public static function userRemoved( $newsletterUserId )
    {
        CjwNewsletterSms::forgetUser( $newsletterUserId, false );
    }

    public static function sendProcessAllowed( $sendObject )
    {
        return !CjwNewsletterSms::isSmsSend( $sendObject );
    }

    public static function queueProcessBefore( $cli )
    {
        $totals = CjwNewsletterSms::processQueue( $cli );
        if ( $cli && ( $totals['sent'] || $totals['failed'] || $totals['deferred'] ) )
            $cli->output( 'SMS: ' . $totals['sent'] . ' sent, ' . $totals['failed'] . ' failed, ' . $totals['deferred'] . ' left for a later run.' );
    }

    /**
     * The fields of design:newsletter/sms/list_edit_part.tpl.
     *
     * @return string[] errors
     */
    public static function listAttributeInput( $list, $http, $prefix, $postfix, $contentObjectAttribute )
    {
        // the part is only in the form when it is listed; without its marker the stored values stay
        if ( !$http->hasPostVariable( $prefix . 'SmsPart' . $postfix ) )
            return array();
        $list->setAttribute( 'sms_enabled', $http->hasPostVariable( $prefix . 'SmsEnabled' . $postfix ) ? 1 : 0 );
        $sender = $http->hasPostVariable( $prefix . 'SmsSender' . $postfix ) ? trim( (string)$http->postVariable( $prefix . 'SmsSender' . $postfix ) ) : '';
        $error = self::senderError( $sender );
        if ( $error !== '' )
            return array( $error );
        $list->setAttribute( 'sms_sender', $sender );
        return array();
    }

    /**
     * @return string '' or what is wrong with a sender: a number in E.164 form or an alphanumeric id of 3-11 characters
     */
    public static function senderError( $sender )
    {
        if ( $sender === '' )
            return '';
        if ( $sender[0] === '+' ? CjwNewsletterSms::validPhone( $sender ) : preg_match( '/^[A-Za-z0-9 ]{3,11}$/', $sender ) && preg_match( '/[A-Za-z]/', $sender ) )
            return '';
        return ezpI18n::tr( self::I18N, 'The SMS sender must be a number like +4915123456789 or a name of 3 to 11 letters and digits.' );
    }

    // ------------------------------------------------------------------ admin user page

    /**
     * Admin user edit: SmsPhone (the number, set as confirmed only by the person with the code: an admin may
     * enter or remove a number, which then waits for the person's code).
     *
     * @return string[] errors
     */
    public static function userInput( $user, $http )
    {
        if ( !$http->hasPostVariable( 'CjwNewsletterSmsPart' ) )
            return array();
        $raw = $http->hasPostVariable( 'CjwNewsletterSmsPhone' ) ? trim( (string)$http->postVariable( 'CjwNewsletterSmsPhone' ) ) : '';
        if ( $raw === '' )
        {
            if ( (string)$user->attribute( 'phone_number' ) !== '' )
            {
                $user->setAttribute( 'phone_number', '' );
                $user->setAttribute( 'phone_status', CjwNewsletterSms::PHONE_NONE );
                $user->setAttribute( 'phone_confirmed', 0 );
            }
            return array();
        }
        $phone = CjwNewsletterSms::normalisePhone( $raw );
        if ( $phone === '' )
            return array( ezpI18n::tr( self::I18N, 'This is not a valid mobile number. Please enter it with the country code, for example +49 151 23456789.' ) );
        if ( $phone !== (string)$user->attribute( 'phone_number' ) )
        {
            $user->setAttribute( 'phone_number', $phone );
            $user->setAttribute( 'phone_status', CjwNewsletterSms::PHONE_NONE );
            $user->setAttribute( 'phone_confirmed', 0 );
        }
        return array();
    }

    /** After the admin stored the user: "send the code" was ticked. */
    public static function userStored( $user, $http )
    {
        if ( $http->hasPostVariable( 'CjwNewsletterSmsSendCode' ) && CjwNewsletterSms::enabled()
             && (string)$user->attribute( 'phone_number' ) !== '' && (int)$user->attribute( 'phone_status' ) !== CjwNewsletterSms::PHONE_CONFIRMED )
        {
            $result = CjwNewsletterSms::requestCode( $user, (string)$user->attribute( 'phone_number' ) );
            if ( class_exists( 'CjwNewsletterUI' ) )
                CjwNewsletterUI::notice( $result['ok'] ? 'feedback' : 'warning', $result['ok']
                    ? ezpI18n::tr( self::I18N, 'The confirmation code was sent to %phone.', null, array( '%phone' => CjwNewsletterSms::maskPhone( $result['phone'] ) ) )
                    : $result['error'] );
        }
    }

    // ------------------------------------------------------------------ public subscribe form

    /** @return string[] errors of the optional mobile number */
    public static function subscribeValidate( $http )
    {
        if ( !CjwNewsletterSms::enabled() || !$http->hasPostVariable( 'CjwNewsletterSmsPhone' ) )
            return array();
        $raw = trim( (string)$http->postVariable( 'CjwNewsletterSmsPhone' ) );
        if ( $raw === '' )
            return array();
        if ( CjwNewsletterSms::normalisePhone( $raw ) === '' )
            return array( ezpI18n::tr( self::I18N, 'This is not a valid mobile number. Please enter it with the country code, for example +49 151 23456789.' ) );
        return array();
    }

    /** A new subscriber who gave a number gets the confirmation code by SMS. */
    public static function subscribeInput( $user, $http )
    {
        if ( !CjwNewsletterSms::enabled() || !$http->hasPostVariable( 'CjwNewsletterSmsPhone' ) )
            return;
        $raw = trim( (string)$http->postVariable( 'CjwNewsletterSmsPhone' ) );
        if ( $raw === '' || CjwNewsletterSms::normalisePhone( $raw ) === '' )
            return;
        $result = CjwNewsletterSms::requestCode( $user, $raw );
        if ( !$result['ok'] )
            eZDebug::writeWarning( 'The SMS confirmation code of a new subscriber was not sent: ' . $result['error'], __METHOD__ );
    }

    // ------------------------------------------------------------------ dashboard

    public static function dashboardSummary( $summary )
    {
        return CjwNewsletterSms::summary();
    }
}
