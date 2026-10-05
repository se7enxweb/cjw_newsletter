<?php
/**
 * File containing the CjwNewsletterSmsCategoryHandler class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * The handler of the kernel mail-preference category "sms" ([Category_sms] HandlerClass in
 * settings/mailpreferences.ini.append.php of the extension).
 *
 * The category is the consent to newsletters by SMS; the double opt-in is the confirmation code sent to the number
 * (not the kernel's e-mail link, so the category has DoubleOptIn=false). On the preference page the handler adds a
 * part to the category's row (design:mailpreferences/category/sms.tpl): the mobile number, its state, and the
 * field for the code. An SMS of an edition only goes out when the category is on AND the number is confirmed.
 *
 * POST fields of the part: MailPreferencePart[sms][Phone], [Code], [Resend].
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSmsCategoryHandler implements expMailCategoryHandler
{
    /** @var array newsletter user id => number: codes to send once the page's transaction is committed */
    protected static $deferredCodes = array();

    public function stateFor( $recipient, $category )
    {
        return null;
    }

    public function frequencyFor( $recipient, $category )
    {
        return null;
    }

    public function changed( $recipient, $category, $state, $context )
    {
        // switched off: nothing waits to go out any more; the number stays (and stays confirmed) for later
        if ( $state === 'off' )
        {
            $user = CjwNewsletterSms::newsletterUserFor( $recipient );
            if ( $user )
                foreach ( CjwNewsletterSmsMessage::fetchList( array( 'newsletter_user_id' => (int)$user->attribute( 'id' ), 'status' => CjwNewsletterSms::STATUS_NEW ) ) as $message )
                {
                    $message->setAttribute( 'status', CjwNewsletterSms::STATUS_ABORTED );
                    $message->setAttribute( 'error', 'consent withdrawn' );
                    $message->setAttribute( 'processed', time() );
                    $message->store();
                }
        }
    }

    /** The newsletter lists of the person by SMS, under "Your subscriptions:". */
    public function subscriptions( $recipient, $category )
    {
        $user = CjwNewsletterSms::newsletterUserFor( $recipient );
        if ( !$user )
            return array();
        $out = array();
        foreach ( (array)$user->attribute( 'subscription_array' ) as $subscription )
        {
            if ( !is_object( $subscription ) || (int)$subscription->attribute( 'status' ) !== CjwNewsletterSubscription::STATUS_APPROVED )
                continue;
            $list = $subscription->attribute( 'newsletter_list_attribute_content' );
            if ( !is_object( $list ) || !$list->hasAttribute( 'sms_enabled' ) || !(int)$list->attribute( 'sms_enabled' ) )
                continue;
            $object = $subscription->attribute( 'newsletter_list' );
            if ( $object )
                $out[] = array( 'name' => $object->attribute( 'name' ), 'status' => '', 'active' => true, 'url' => '' );
        }
        return $out;
    }

    public function partTemplate( $recipient, $category, $mode )
    {
        return 'design:mailpreferences/category/sms.tpl';
    }

    public function partVariables( $recipient, $category, $mode )
    {
        $user = CjwNewsletterSms::newsletterUserFor( $recipient );
        $statusNames = array( CjwNewsletterSms::PHONE_NONE => 'none', CjwNewsletterSms::PHONE_PENDING => 'pending',
                              CjwNewsletterSms::PHONE_CONFIRMED => 'confirmed', CjwNewsletterSms::PHONE_STOPPED => 'stopped' );
        $vars = array( 'enabled' => CjwNewsletterSms::enabled(), 'has_user' => (bool)$user, 'phone' => '', 'phone_masked' => '',
                       'status' => 'none', 'confirmed' => 0, 'code_waiting' => false, 'code_expires' => 0,
                       'wording' => CjwNewsletterSms::consentWording(), 'stop_keyword' => implode( ', ', CjwNewsletterSms::stopKeywords() ),
                       'code_length' => min( 10, max( 4, (int)CjwNewsletterSms::setting( 'CodeLength', 6 ) ) ), 'mode' => $mode,
                       'code_sent' => false, 'code_error' => '' );
        if ( $user && isset( self::$deferredCodes[(int)$user->attribute( 'id' )] ) && eZDB::instance()->transactionCounter() === 0 )
        {
            list( $phone, $since ) = self::$deferredCodes[(int)$user->attribute( 'id' )];
            unset( self::$deferredCodes[(int)$user->attribute( 'id' )] );
            // a persistent worker keeps the static between requests: only what this request asked for
            if ( time() - $since > 120 )
                $phone = '';
            $result = $phone !== '' ? CjwNewsletterSms::requestCode( $user, $phone ) : array( 'ok' => false, 'error' => '' );
            $vars['code_sent'] = $result['ok'];
            $vars['code_error'] = $result['ok'] ? '' : $result['error'];
            $user = CjwNewsletterUser::fetch( (int)$user->attribute( 'id' ) );
        }
        if ( $user )
        {
            $phone = (string)$user->attribute( 'phone_number' );
            $status = (int)$user->attribute( 'phone_status' );
            $vars['phone'] = $phone;
            $vars['phone_masked'] = CjwNewsletterSms::maskPhone( $phone );
            $vars['status'] = isset( $statusNames[$status] ) ? $statusNames[$status] : 'none';
            $vars['confirmed'] = (int)$user->attribute( 'phone_confirmed' );
            if ( $status === CjwNewsletterSms::PHONE_PENDING )
            {
                $codes = CjwNewsletterSmsCode::fetchList( array( 'newsletter_user_id' => (int)$user->attribute( 'id' ), 'purpose' => 'confirm', 'used' => 0 ), 1 );
                if ( $codes && (int)$codes[0]->attribute( 'expires' ) >= time() )
                {
                    $vars['code_waiting'] = true;
                    $vars['code_expires'] = (int)$codes[0]->attribute( 'expires' );
                }
            }
        }
        return $vars;
    }

    public function storePart( $recipient, $category, $http, $context )
    {
        $posted = $http->hasPostVariable( 'MailPreferencePart' ) ? (array)$http->postVariable( 'MailPreferencePart' ) : array();
        $id = $category->identifier;
        $part = isset( $posted[$id] ) && is_array( $posted[$id] ) ? $posted[$id] : null;
        if ( $part === null )
            return array();
        $raw = isset( $part['Phone'] ) && is_string( $part['Phone'] ) ? trim( $part['Phone'] ) : '';
        $code = isset( $part['Code'] ) && is_string( $part['Code'] ) ? trim( $part['Code'] ) : '';
        $resend = !empty( $part['Resend'] );
        $user = CjwNewsletterSms::newsletterUserFor( $recipient );
        if ( !$user )
            return $raw !== '' ? array( ezpI18n::tr( CjwNewsletterSms::I18N, 'Please subscribe to a newsletter first; then you can add your mobile number here.' ) ) : array();

        $stored = (string)$user->attribute( 'phone_number' );
        $status = (int)$user->attribute( 'phone_status' );
        $on = expMailPreferences::forRecipient( $recipient )->state( $id ) === expMailPreferences::ON;

        if ( $raw === '' )
        {
            if ( $stored === '' )
                return array();
            CjwNewsletterSms::removePhone( $user );
            return array( 'changed' => 1 );
        }
        $phone = CjwNewsletterSms::normalisePhone( $raw );
        if ( $phone === '' )
            return array( ezpI18n::tr( CjwNewsletterSms::I18N, 'This is not a valid mobile number. Please enter it with the country code, for example +49 151 23456789.' ) );

        if ( $phone === $stored && $code !== '' && $status === CjwNewsletterSms::PHONE_PENDING )
        {
            $context->wording = CjwNewsletterSms::consentWording();
            $result = CjwNewsletterSms::confirmCode( $user, $code, $context );
            return $result['ok'] ? array( 'changed' => 1 ) : array( $result['error'] );
        }

        $changed = 0;
        if ( $phone !== $stored )
        {
            $user->setAttribute( 'phone_number', $phone );
            $user->setAttribute( 'phone_status', CjwNewsletterSms::PHONE_NONE );
            $user->setAttribute( 'phone_confirmed', 0 );
            $user->store();
            $status = CjwNewsletterSms::PHONE_NONE;
            $changed++;
        }
        // a code goes out when SMS are on and the number is not confirmed yet (or again on request)
        $needsCode = $status === CjwNewsletterSms::PHONE_NONE || $status === CjwNewsletterSms::PHONE_STOPPED
                     || ( $status === CjwNewsletterSms::PHONE_PENDING && $resend );
        if ( $on && $needsCode && CjwNewsletterSms::enabled() )
        {
            // the page stores the form inside a database transaction: the code SMS (a request to the provider) is
            // sent after it, when the page asks for the part's variables
            if ( eZDB::instance()->transactionCounter() > 0 )
            {
                self::$deferredCodes[(int)$user->attribute( 'id' )] = array( $phone, time() );
                return array( 'changed' => $changed + 1 );
            }
            $result = CjwNewsletterSms::requestCode( $user, $phone );
            if ( !$result['ok'] )
                return array_merge( array( $result['error'] ), $changed ? array( 'changed' => $changed ) : array() );
            $changed++;
        }
        return $changed ? array( 'changed' => $changed ) : array();
    }
}
