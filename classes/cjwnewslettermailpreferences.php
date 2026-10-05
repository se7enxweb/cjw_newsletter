<?php
/**
 * File containing the CjwNewsletterMailPreferences class
 *
 * The bridge between the newsletter and the e-mail preferences of Exponential 6.0.15 and later
 * (kernel/classes/mailpreferences). Every method does nothing on an installation without them, so the extension
 * keeps working on older versions.
 *
 *  - Sending: the mails of an edition go through the mail gate as category "newsletter": the master switch
 *    "all optional e-mail off", a "newsletter" switched off on the preference page and a suppressed address block
 *    them; the gate adds the footer and the List-Unsubscribe headers (sendThroughGate()).
 *  - Consent log: subscribing, confirming, approving and unsubscribing (by the person or an administrator) are
 *    recorded with the source "bridge"; a confirmed subscription switches the category on, the last one
 *    removed switches it off (subscriptionChanged()).
 *  - Blacklist and suppression list: an address put on the blacklist is suppressed, one taken off is lifted,
 *    and the other way round ([SuppressionSettings] Listeners[] of mailpreferences.ini names this class).
 *
 * The lists themselves appear on the preference page through CjwNewsletterMailCategoryHandler.
 *
 * @copyright Copyright (C) 2007-2026 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage classes
 */
class CjwNewsletterMailPreferences
{
    const CATEGORY = 'newsletter';

    /** @var bool a change this class makes is not mirrored back */
    protected static $busy = false;

    /**
     * @return bool the e-mail preferences exist (classes and tables)
     */
    public static function available()
    {
        // only a yes is kept: a long running process may see the tables appear
        static $available = false;
        if ( !$available )
            $available = class_exists( 'expMailPreferences' ) && class_exists( 'expMailGate' )
                         && class_exists( 'expMailPreferencesService' ) && expMailPreferencesService::tableExists( 'expmail_preference' );
        return $available;
    }

    /**
     * The person of a newsletter user: the account when there is one, else the address.
     *
     * @param CjwNewsletterUser $newsletterUser
     * @return expMailRecipient|null
     */
    public static function recipientForNewsletterUser( $newsletterUser )
    {
        if ( !is_object( $newsletterUser ) )
            return null;
        $userId = (int)$newsletterUser->attribute( 'ez_user_id' );
        if ( $userId > 0 )
        {
            $recipient = expMailRecipient::fromUserId( $userId );
            if ( $recipient !== null )
                return $recipient;
        }
        return expMailRecipient::fromAddress( (string)$newsletterUser->attribute( 'email' ) );
    }

    // ------------------------------------------------------------------ sending

    /**
     * Sends a built newsletter mail through the mail gate.
     *
     * @param ezcMail $mail the built mail (CjwNewsletterMailComposer)
     * @param CjwNewsletterTransport $transport
     * @param string $category
     * @return array handled (false: no gate here, send as before), result (true, an Exception of the transport, or
     *               false), blocked (true: the gate sent it to nobody), reasons (why it was blocked)
     */
    public static function sendThroughGate( ezcMail $mail, CjwNewsletterTransport $transport, $category = self::CATEGORY )
    {
        if ( !self::available() )
            return array( 'handled' => false, 'result' => null, 'blocked' => false, 'reasons' => array() );
        $ezMail = new eZMail();
        $ezMail->Mail = $mail;
        // the gate reads the recipients from eZMail's own lists
        $lists = array( 'to' => array(), 'cc' => array(), 'bcc' => array() );
        foreach ( array_keys( $lists ) as $field )
            foreach ( (array)$mail->$field as $address )
                if ( $address instanceof ezcMailAddress )
                    $lists[$field][] = array( 'email' => $address->email, 'name' => $address->name !== '' ? $address->name : false );
        $ezMail->setReceiverElements( $lists['to'] );
        $ezMail->setCcElements( $lists['cc'] );
        $ezMail->setBccElements( $lists['bcc'] );
        $ezMail->setCategory( $category );
        $gateTransport = new CjwNewsletterGateTransport( $transport );
        $ok = expMailGate::dispatch( $ezMail, $gateTransport );
        $last = expMailGate::lastResult();
        if ( is_array( $last ) && $last['decision'] === 'blocked' )
            return array( 'handled' => true, 'result' => false, 'blocked' => true, 'reasons' => (array)$last['blocked'] );
        $result = $gateTransport->lastResult !== null ? $gateTransport->lastResult : (bool)$ok;
        return array( 'handled' => true, 'result' => $result, 'blocked' => false, 'reasons' => array() );
    }

    // ------------------------------------------------------------------ consent log

    /**
     * The status a subscription has in the database (before it is stored again).
     *
     * @param CjwNewsletterSubscription $subscription
     * @return int|null null for a new subscription
     */
    public static function storedStatus( $subscription )
    {
        if ( !self::available() || !(int)$subscription->attribute( 'id' ) )
            return null;
        $db = eZDB::instance();
        $rows = $db->arrayQuery( 'SELECT status FROM cjwnl_subscription WHERE id = ' . (int)$subscription->attribute( 'id' ) );
        return isset( $rows[0]['status'] ) ? (int)$rows[0]['status'] : null;
    }

    /**
     * Records a change of a subscription's status in the consent log and keeps the category "newsletter" of the
     * person in step: on with a confirmed or approved subscription, off when the last one is removed.
     *
     * @param CjwNewsletterSubscription $subscription
     * @param int|null $oldStatus
     */
    public static function subscriptionChanged( $subscription, $oldStatus )
    {
        if ( self::$busy || !self::available() )
            return;
        $new = (int)$subscription->attribute( 'status' );
        if ( $oldStatus !== null && (int)$oldStatus === $new )
            return;
        $active = array( CjwNewsletterSubscription::STATUS_CONFIRMED, CjwNewsletterSubscription::STATUS_APPROVED );
        $removed = array( CjwNewsletterSubscription::STATUS_REMOVED_SELF, CjwNewsletterSubscription::STATUS_REMOVED_ADMIN );
        if ( in_array( $new, $active, true ) )
            $kind = in_array( (int)$oldStatus, $active, true ) ? null : 'on';
        else if ( in_array( $new, $removed, true ) )
            $kind = in_array( (int)$oldStatus, $removed, true ) ? null : 'off';
        else if ( $new === CjwNewsletterSubscription::STATUS_PENDING )
            $kind = 'pending';
        else
            $kind = null; // bounced and blacklisted: the suppression list speaks for them
        // back from the blacklist or a bounce: no new consent was given, the earlier one stands
        if ( $kind === 'on' && in_array( (int)$oldStatus, array( CjwNewsletterSubscription::STATUS_BLACKLISTED, CjwNewsletterSubscription::STATUS_BOUNCED_SOFT,
                                                                  CjwNewsletterSubscription::STATUS_BOUNCED_HARD ), true ) )
            return;
        if ( $kind === null )
            return;
        self::$busy = true;
        try
        {
            $recipient = self::recipientForNewsletterUser( $subscription->attribute( 'newsletter_user' ) );
            if ( $recipient === null )
                return;
            $list = self::listName( $subscription );
            $byAdmin = $new === CjwNewsletterSubscription::STATUS_REMOVED_ADMIN || $new === CjwNewsletterSubscription::STATUS_APPROVED;
            $prefs = expMailPreferences::forRecipient( $recipient );
            $state = $prefs->state( self::CATEGORY );
            if ( $kind === 'pending' )
            {
                $context = self::context( self::tr( 'Newsletter "%list": subscribed, waiting for the confirmation of the e-mail address', $list ) );
                expConsentLog::record( $recipient, self::CATEGORY, 'pending', $state, $state, $context );
            }
            else if ( $kind === 'on' )
            {
                $context = self::context( $byAdmin ? self::tr( 'Newsletter "%list": subscription approved by an administrator', $list )
                                                   : self::tr( 'Newsletter "%list": subscription confirmed', $list ) );
                if ( $state === expMailPreferences::ON && $prefs->isStored( self::CATEGORY ) )
                    expConsentLog::record( $recipient, self::CATEGORY, 'on', $state, $state, $context );
                else
                    $prefs->set( self::CATEGORY, true, $context );
            }
            else
            {
                $context = self::context( $byAdmin ? self::tr( 'Newsletter "%list": subscription removed by an administrator', $list )
                                                   : self::tr( 'Newsletter "%list": unsubscribed', $list ) );
                if ( self::activeSubscriptionCount( $subscription->attribute( 'newsletter_user_id' ) ) === 0 && $state !== expMailPreferences::OFF )
                    $prefs->set( self::CATEGORY, false, $context );
                else
                    expConsentLog::record( $recipient, self::CATEGORY, 'off', $state, $state, $context );
            }
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Newsletter consent bridge: ' . $e->getMessage(), __METHOD__ );
        }
        finally
        {
            self::$busy = false;
        }
    }

    /**
     * @param int $newsletterUserId
     * @return int subscriptions that are confirmed or approved
     */
    public static function activeSubscriptionCount( $newsletterUserId )
    {
        $db = eZDB::instance();
        $rows = $db->arrayQuery( 'SELECT COUNT(*) AS c FROM cjwnl_subscription WHERE newsletter_user_id = ' . (int)$newsletterUserId
                                 . ' AND status IN ( ' . CjwNewsletterSubscription::STATUS_CONFIRMED . ', ' . CjwNewsletterSubscription::STATUS_APPROVED . ' )' );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }

    /**
     * @param CjwNewsletterSubscription $subscription
     * @return string the name of its list
     */
    public static function listName( $subscription )
    {
        $object = eZContentObject::fetch( (int)$subscription->attribute( 'list_contentobject_id' ) );
        return $object instanceof eZContentObject ? (string)$object->attribute( 'name' ) : '#' . (int)$subscription->attribute( 'list_contentobject_id' );
    }

    // ------------------------------------------------------------------ blacklist and suppression list

    /**
     * An address was put on the newsletter blacklist: it is suppressed (reason "bridge").
     *
     * @param string $email
     */
    public static function blacklisted( $email )
    {
        if ( self::$busy || !self::available() || trim( (string)$email ) === '' )
            return;
        self::$busy = true;
        try
        {
            if ( !expMailSuppression::isSuppressed( $email ) )
            {
                expMailSuppression::add( $email, 'bridge', 'Newsletter blacklist' );
                $recipient = expMailRecipient::fromAddress( $email );
                if ( $recipient !== null )
                    expConsentLog::record( $recipient, '', 'suppress', '', 'bridge', self::context( self::tr( 'Put on the newsletter blacklist', '' ) ) );
            }
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Newsletter blacklist bridge: ' . $e->getMessage(), __METHOD__ );
        }
        finally
        {
            self::$busy = false;
        }
    }

    /**
     * A hard bounce or a complaint of a newsletter mail: the address goes on the suppression list with the reason
     * "bounce" or "complaint", and the consent log records it (source system). The suppression listener of this
     * class then puts the address on the newsletter blacklist, as for every other suppression.
     *
     * @param string $email
     * @param string $reason bounce or complaint
     * @param string $detail the status code or the feedback type
     * @return bool the address is suppressed now (false: no e-mail preferences here, or no address)
     */
    public static function suppressForBounce( $email, $reason, $detail = '' )
    {
        $email = trim( (string)$email );
        if ( !self::available() || $email === '' || !in_array( $reason, array( 'bounce', 'complaint' ), true ) )
            return false;
        try
        {
            if ( expMailSuppression::isSuppressed( $email ) )
                return true;
            $wording = $reason === 'bounce' ? 'Newsletter: hard bounce, status ' . $detail : 'Newsletter: complaint, feedback type ' . $detail;
            expMailSuppression::add( $email, $reason, $wording, 0 );
            $recipient = expMailRecipient::fromAddress( $email );
            if ( $recipient !== null )
                expConsentLog::record( $recipient, '', 'suppress', '', $reason, expConsentContext::system( $wording ) );
            return true;
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Newsletter bounce suppression: ' . $e->getMessage(), __METHOD__ );
            return false;
        }
    }

    /**
     * An address was taken off the newsletter blacklist: its suppression is lifted.
     *
     * @param string $email
     */
    public static function unblacklisted( $email )
    {
        if ( self::$busy || !self::available() || trim( (string)$email ) === '' )
            return;
        self::$busy = true;
        try
        {
            $reason = expMailSuppression::reason( $email );
            if ( $reason !== null && expMailSuppression::lift( $email ) )
            {
                $recipient = expMailRecipient::fromAddress( $email );
                if ( $recipient !== null )
                    expConsentLog::record( $recipient, '', 'unsuppress', $reason, '', self::context( self::tr( 'Taken off the newsletter blacklist', '' ) ) );
            }
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Newsletter blacklist bridge: ' . $e->getMessage(), __METHOD__ );
        }
        finally
        {
            self::$busy = false;
        }
    }

    /**
     * Listener of the suppression list ([SuppressionSettings] Listeners[]): the address goes on the blacklist.
     *
     * @param string $email
     * @param string $hash
     * @param string $reason
     */
    public static function suppressionAdded( $email, $hash, $reason )
    {
        if ( self::$busy || trim( (string)$email ) === '' || !class_exists( 'CjwNewsletterBlacklistItem' ) )
            return;
        self::$busy = true;
        try
        {
            if ( !CjwNewsletterBlacklistItem::fetchByEmail( $email ) )
            {
                $item = CjwNewsletterBlacklistItem::create( $email, 'Exponential e-mail preferences: suppressed (' . $reason . ')' );
                $item->store();
            }
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Newsletter blacklist bridge: ' . $e->getMessage(), __METHOD__ );
        }
        finally
        {
            self::$busy = false;
        }
    }

    /**
     * Listener of the suppression list: the address comes off the blacklist. A lift by hash alone finds the
     * blacklist entry whose address has that hash.
     *
     * @param string $hash
     * @param string|null $email
     * @param string $reason
     */
    public static function suppressionLifted( $hash, $email, $reason )
    {
        if ( self::$busy || !class_exists( 'CjwNewsletterBlacklistItem' ) )
            return;
        self::$busy = true;
        try
        {
            $item = false;
            if ( $email !== null && $email !== '' )
                $item = CjwNewsletterBlacklistItem::fetchByEmail( $email );
            else
            {
                $db = eZDB::instance();
                foreach ( (array)$db->arrayQuery( 'SELECT id, email FROM cjwnl_blacklist_item' ) as $row )
                {
                    if ( expMailSuppression::hash( $row['email'] ) === $hash )
                    {
                        $item = CjwNewsletterBlacklistItem::fetch( (int)$row['id'] );
                        break;
                    }
                }
            }
            if ( is_object( $item ) )
                $item->remove();
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Newsletter blacklist bridge: ' . $e->getMessage(), __METHOD__ );
        }
        finally
        {
            self::$busy = false;
        }
    }

    // ------------------------------------------------------------------ internals

    protected static function context( $wording )
    {
        return expConsentContext::fromRequest( 'bridge', $wording );
    }

    protected static function tr( $text, $list )
    {
        return ezpI18n::tr( 'cjw_newsletter/mailpreferences', $text, null, array( '%list' => $list ) );
    }
}

?>
