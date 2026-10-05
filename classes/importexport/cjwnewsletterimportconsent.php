<?php
/**
 * File containing the CjwNewsletterImportConsent class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage importexport
 */

/**
 * The consent of imported subscriptions in the kernel's consent log, with the source "import".
 *
 * A subscription stored by an import would otherwise be recorded by the bridge (CjwNewsletterMailPreferences) as
 * "approved by an administrator" with the source "bridge". The import stores it with the bridge quiet and records
 * the consent itself: source "import", and a wording that names the import, the consent source the admin gave
 * (for example "Sign-up form at the trade fair 2025") and, for a migration, the original opt-in date.
 *
 * It also answers whether an address may be imported at all: not when it is on the suppression list, on the
 * newsletter blacklist, or when the person switched the newsletter category (or all optional mail) off.
 *
 * @package cjw_newsletter
 * @subpackage importexport
 */
class CjwNewsletterImportConsent extends CjwNewsletterMailPreferences
{
    /**
     * Why an address must not be imported.
     *
     * @param string $email
     * @return string|null null = it may be imported; else suppressed, blacklisted or opted_out
     */
    static function blockReason( $email )
    {
        $email = trim( (string)$email );
        if ( class_exists( 'expMailSuppression' ) && self::available() )
        {
            try
            {
                if ( expMailSuppression::isSuppressed( $email ) )
                    return 'suppressed';
            }
            catch ( Throwable $e )
            {
                eZDebug::writeError( 'Import consent: ' . $e->getMessage(), __METHOD__ );
            }
        }
        if ( class_exists( 'CjwNewsletterBlacklistItem' ) && is_object( CjwNewsletterBlacklistItem::fetchByEmail( $email ) ) )
            return 'blacklisted';
        if ( self::available() )
        {
            try
            {
                $recipient = expMailRecipient::fromAddress( $email );
                if ( $recipient !== null )
                {
                    $prefs = expMailPreferences::forRecipient( $recipient );
                    if ( !$prefs->masterOn() )
                        return 'opted_out';
                    if ( $prefs->isStored( self::CATEGORY ) && $prefs->state( self::CATEGORY ) === expMailPreferences::OFF )
                        return 'opted_out';
                }
            }
            catch ( Throwable $e )
            {
                eZDebug::writeError( 'Import consent: ' . $e->getMessage(), __METHOD__ );
            }
        }
        return null;
    }

    /**
     * Stores a subscription without the bridge recording it (the import records it itself).
     *
     * @param CjwNewsletterSubscription $subscription
     */
    static function storeQuietly( $subscription )
    {
        $was = self::$busy;
        self::$busy = true;
        try
        {
            $subscription->store();
        }
        finally
        {
            self::$busy = $was;
        }
    }

    /**
     * The context of an import: source "import", the wording, the acting admin and the siteaccess (no IP: the
     * consent was not given in this request).
     *
     * @param string $wording
     * @return expConsentContext|null
     */
    static function importContext( $wording )
    {
        if ( !class_exists( 'expConsentContext' ) )
            return null;
        $context = expConsentContext::system( $wording, 'import' );
        $context->sendConfirmation = false;
        return $context;
    }

    /**
     * Records that a person was subscribed by an import: the category "newsletter" is switched on with the source
     * "import" (no double opt-in mail: the consent was given where the addresses were collected), or, when it is
     * on already, an "on" row says that this import confirmed it.
     *
     * @param CjwNewsletterUser $newsletterUser
     * @param string $wording
     * @return bool a row was written
     */
    static function recordOn( $newsletterUser, $wording )
    {
        return self::record( $newsletterUser, true, $wording );
    }

    /**
     * Records an opt-out an import carried over (an old unsubscription): the category is switched off unless the
     * person still has an active subscription.
     *
     * @param CjwNewsletterUser $newsletterUser
     * @param string $wording
     * @return bool a row was written
     */
    static function recordOff( $newsletterUser, $wording )
    {
        return self::record( $newsletterUser, false, $wording );
    }

    protected static function record( $newsletterUser, $on, $wording )
    {
        if ( !self::available() || !is_object( $newsletterUser ) )
            return false;
        $was = self::$busy;
        self::$busy = true;
        try
        {
            $recipient = self::recipientForNewsletterUser( $newsletterUser );
            $context = self::importContext( $wording );
            if ( $recipient === null || $context === null )
                return false;
            $prefs = expMailPreferences::forRecipient( $recipient );
            $state = $prefs->state( self::CATEGORY );
            if ( $on )
            {
                if ( $state === expMailPreferences::ON && $prefs->isStored( self::CATEGORY ) )
                    expConsentLog::record( $recipient, self::CATEGORY, 'on', $state, $state, $context );
                else
                    $prefs->set( self::CATEGORY, true, $context );
                return true;
            }
            if ( self::activeSubscriptionCount( $newsletterUser->attribute( 'id' ) ) > 0 )
                return false;
            if ( $state !== expMailPreferences::OFF || !$prefs->isStored( self::CATEGORY ) )
                $prefs->set( self::CATEGORY, false, $context );
            else
                expConsentLog::record( $recipient, self::CATEGORY, 'off', $state, $state, $context );
            return true;
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Import consent: ' . $e->getMessage(), __METHOD__ );
            return false;
        }
        finally
        {
            self::$busy = $was;
        }
    }
}

?>
