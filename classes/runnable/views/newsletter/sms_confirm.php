<?php
/**
 * The public page where a subscriber enters the confirmation code of their mobile number:
 * newsletter/sms_confirm/<the subscriber's configure hash> (the link is in the code SMS).
 *
 * The right code confirms the number and records the consent to newsletters by SMS (kernel category sms, consent
 * log 'confirm' with the wording shown on the page). Wrong codes count against [SmsSettings] CodeMaxAttempts.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SmsConfirm extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $hash = isset( $Params['UserHash'] ) ? (string)$Params['UserHash'] : '';
        $user = preg_match( '/^[0-9a-f]{32}$/', $hash ) ? \CjwNewsletterUser::fetchByHash( $hash ) : null;
        if ( !\CjwNewsletterSms::enabled() || !is_object( $user ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );

        include_once( 'kernel/common/template.php' );
        $http = \eZHTTPTool::instance();
        $tpl = templateInit();
        $state = 'form';
        $error = '';
        $info = '';

        if ( $http->hasPostVariable( 'SmsConfirmButton' ) )
        {
            $context = class_exists( 'expConsentContext' ) ? \expConsentContext::fromRequest( 'confirm', \CjwNewsletterSms::consentWording() ) : null;
            $result = (int)$user->attribute( 'phone_status' ) === \CjwNewsletterSms::PHONE_PENDING
                ? \CjwNewsletterSms::confirmCode( $user, (string)$http->postVariable( 'SmsCode', '' ), $context )
                : array( 'ok' => false, 'error' => \ezpI18n::tr( 'cjw_newsletter/sms', 'There is no code waiting for this number. Please ask for a new code.' ) );
            if ( $result['ok'] )
                $state = 'confirmed';
            else
                $error = $result['error'];
        }
        elseif ( $http->hasPostVariable( 'SmsResendButton' ) )
        {
            if ( (string)$user->attribute( 'phone_number' ) !== '' && (int)$user->attribute( 'phone_status' ) !== \CjwNewsletterSms::PHONE_CONFIRMED )
            {
                $result = \CjwNewsletterSms::requestCode( $user, (string)$user->attribute( 'phone_number' ) );
                if ( $result['ok'] )
                    $info = \ezpI18n::tr( 'cjw_newsletter/sms', 'A new code was sent to %phone.', null, array( '%phone' => \CjwNewsletterSms::maskPhone( $result['phone'] ) ) );
                else
                    $error = $result['error'];
            }
        }
        $user = \CjwNewsletterUser::fetch( (int)$user->attribute( 'id' ) );
        if ( $state === 'form' && (int)$user->attribute( 'phone_status' ) === \CjwNewsletterSms::PHONE_CONFIRMED )
            $state = 'already';
        if ( $state === 'form' && (string)$user->attribute( 'phone_number' ) === '' )
            $state = 'no_phone';

        $tpl->setVariable( 'state', $state );
        $tpl->setVariable( 'error', $error );
        $tpl->setVariable( 'info', $info );
        $tpl->setVariable( 'user_hash', $hash );
        $tpl->setVariable( 'phone_masked', \CjwNewsletterSms::maskPhone( (string)$user->attribute( 'phone_number' ) ) );
        $tpl->setVariable( 'wording', \CjwNewsletterSms::consentWording() );
        $tpl->setVariable( 'code_length', min( 10, max( 4, (int)\CjwNewsletterSms::setting( 'CodeLength', 6 ) ) ) );
        $tpl->setVariable( 'configure_url', 'newsletter/configure/' . $hash );

        header( 'Cache-Control: no-store' );
        header( 'X-Robots-Tag: noindex' );
        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/sms/confirm.tpl' );
        $Result['path'] = array( array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/sms', 'Confirm your mobile number' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
