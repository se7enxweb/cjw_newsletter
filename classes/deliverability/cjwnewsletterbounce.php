<?php
/**
 * File containing the CjwNewsletterBounce class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * What a bounce means for the newsletter, and what is done about it (cjw_newsletter.ini [DeliverabilitySettings]).
 *
 *  - The kind of a returned message comes from the kernel's classifier (expMailBounceReader::classify(), delivery
 *    status notifications and feedback loop reports); a message the kernel cannot read is judged by the SMTP code
 *    the newsletter's mail parser found in it.
 *  - hard (a permanent failure of the address, [BounceSettings] HardStatusCodes[] of mailpreferences.ini) and
 *    complaint: the address goes on the kernel suppression list with the reason "bounce" or "complaint"
 *    ([DeliverabilitySettings] SuppressHardBounces), which blocks every optional mail of the site, not only the
 *    newsletter. The suppression listener of the newsletter then puts the address on its blacklist.
 *  - soft (a full mailbox, a server that is down, a delay): the item of the send is queued again after
 *    SoftBounceRetryDelay seconds (doubled for each further try), at most SoftBounceMaxRetries times and only while
 *    the send is younger than SoftBounceRetryMaxAge days; the newsletter user's bounce count rises as before
 *    ([BounceSettings] BounceThresholdValue).
 *
 * The same is done for a mail the transport refused at once (sendFailed()): a temporary refusal is tried again
 * later, a permanent one is a hard bounce.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterBounce
{
    const HARD = 'hard';
    const SOFT = 'soft';
    const COMPLAINT = 'complaint';
    const NONE = 'none';

    /** @var int|null test override of the clock */
    static $now = null;

    static function now()
    {
        return self::$now !== null ? (int)self::$now : time();
    }

    // ------------------------------------------------------------------ settings

    /** @return bool hard bounces and complaints go on the kernel suppression list */
    static function suppressHardBounces()
    {
        return self::setting( 'SuppressHardBounces', 'enabled' ) !== 'disabled';
    }

    /** @return int how often a soft-bounced item is sent again, 0 = never */
    static function maxRetries()
    {
        return max( 0, min( 9, (int)self::setting( 'SoftBounceMaxRetries', 2 ) ) );
    }

    /** @return int seconds before the first retry (each further retry waits twice as long) */
    static function retryDelay()
    {
        return max( 60, (int)self::setting( 'SoftBounceRetryDelay', 3600 ) );
    }

    /** @return int a send older than this many days is not opened again for a retry */
    static function retryMaxAgeDays()
    {
        return max( 1, (int)self::setting( 'SoftBounceRetryMaxAge', 3 ) );
    }

    // ------------------------------------------------------------------ understanding a message

    /**
     * The kind of a returned message.
     *
     * @param string $raw the whole message
     * @param string $code the SMTP code the newsletter's parser found (CjwNewsletterMailParser), '' or '0' for none
     * @return array kind (hard, soft, complaint, none), detail (status code or feedback type), addresses (failed
     *               recipients the kernel read, lower case), source (kernel or code)
     */
    static function classify( $raw, $code = '' )
    {
        if ( class_exists( 'expMailBounceReader' ) )
        {
            $c = expMailBounceReader::classify( (string)$raw );
            if ( is_array( $c ) && isset( $c['kind'] ) && $c['kind'] !== self::NONE )
                return array( 'kind' => (string)$c['kind'], 'detail' => (string)$c['detail'], 'addresses' => (array)$c['addresses'], 'source' => 'kernel' );
            // a feedback report marked not-spam is no complaint, whatever codes the text has
            if ( is_array( $c ) && isset( $c['detail'] ) && $c['detail'] === 'not-spam' )
                return array( 'kind' => self::NONE, 'detail' => 'not-spam', 'addresses' => array(), 'source' => 'kernel' );
        }
        $kind = self::kindOfCode( $code );
        return array( 'kind' => $kind, 'detail' => $kind === self::NONE ? '' : trim( (string)$code ), 'addresses' => array(), 'source' => 'code' );
    }

    /**
     * The kind of an SMTP reply or a status code such as "550 5.1.1", "5.2.2", "(#4.4.1)" or "421".
     *
     * @param string $code
     * @return string hard, soft or none
     */
    static function kindOfCode( $code )
    {
        $code = trim( (string)$code );
        if ( $code === '' || $code === '0' )
            return self::NONE;
        if ( preg_match( '/\b([245])\.(\d{1,3})\.(\d{1,3})\b/', $code, $m ) )
        {
            $status = $m[1] . '.' . $m[2] . '.' . $m[3];
            if ( $m[1] === '2' )
                return self::NONE;
            return self::isHardStatus( $status ) ? self::HARD : self::SOFT;
        }
        if ( preg_match( '/\b([45])\d\d\b/', $code ) )
            return self::SOFT; // a basic code alone does not say that the address is gone
        return self::NONE;
    }

    /**
     * @param string $status an enhanced status code x.y.z
     * @return bool a permanent failure of the address (the kernel's HardStatusCodes[], or its defaults)
     */
    static function isHardStatus( $status )
    {
        if ( class_exists( 'expMailBounceReader' ) )
            return expMailBounceReader::isHardStatus( (string)$status );
        foreach ( array( '5.1.', '5.2.1', '5.4.4' ) as $hard )
            if ( substr( $hard, -1 ) === '.' ? strpos( $status, $hard ) === 0 : $status === $hard )
                return true;
        return false;
    }

    /**
     * The newsletter's own headers in a message or in the copy a mail server quotes (any case, quoted with "> ").
     *
     * @param string $raw
     * @return array lower case header => value (x-cjwnl-senditem, x-cjwnl-user, ...)
     */
    static function cjwHeaders( $raw )
    {
        $out = array();
        $lines = preg_split( "/\r\n|\n|\r/", (string)$raw );
        foreach ( array_slice( $lines, 0, 400 ) as $line )
        {
            $line = ltrim( $line, "> \t" );
            if ( stripos( $line, 'x-cjwnl-' ) !== 0 )
                continue;
            $pieces = explode( ':', $line, 2 );
            if ( count( $pieces ) === 2 )
            {
                $key = strtolower( trim( $pieces[0] ) );
                if ( !isset( $out[$key] ) )
                    $out[$key] = trim( $pieces[1] );
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ acting on a bounce

    /**
     * Acts on a bounce of a newsletter mail.
     *
     * @param array $classification classify()
     * @param CjwNewsletterEditionSendItem|null $sendItem the item the mail was sent for, when known
     * @param CjwNewsletterUser|null $user the newsletter user, when known (else the one of the item)
     * @param bool $dryRun only say what would be done
     * @return array kind, action (none, suppressed, retry, bounced, ignored), detail, retry_at
     */
    static function handle( array $classification, $sendItem = null, $user = null, $dryRun = false )
    {
        $kind = isset( $classification['kind'] ) ? (string)$classification['kind'] : self::NONE;
        $detail = isset( $classification['detail'] ) ? (string)$classification['detail'] : '';
        $result = array( 'kind' => $kind, 'action' => 'none', 'detail' => $detail, 'retry_at' => 0 );
        if ( !is_object( $user ) && is_object( $sendItem ) )
            $user = $sendItem->attribute( 'newsletter_user_object' );
        if ( $kind === self::NONE || ( !is_object( $user ) && !is_object( $sendItem ) ) )
        {
            $result['action'] = $kind === self::NONE ? 'none' : 'ignored';
            return $result;
        }
        if ( $dryRun )
        {
            $result['action'] = $kind === self::SOFT ? ( self::canRetry( $sendItem, $user ) ? 'retry' : 'bounced' )
                                                     : ( self::suppressHardBounces() ? 'suppressed' : 'bounced' );
            return $result;
        }
        $now = self::now();
        if ( is_object( $sendItem ) )
            $sendItem->setBounced();
        if ( is_object( $user ) )
        {
            $user->setAttribute( 'last_bounce', $now );
            if ( $kind === self::SOFT )
                $user->setAttribute( 'soft_bounce_count', min( 255, (int)$user->attribute( 'soft_bounce_count' ) + 1 ) );
            // setBounced() stores the user
            if ( $kind !== self::COMPLAINT )
                $user->setBounced( $kind === self::HARD );
            else
                $user->store();
        }
        if ( $kind === self::HARD || $kind === self::COMPLAINT )
        {
            $result['action'] = 'bounced';
            if ( self::suppressHardBounces() && is_object( $user ) )
            {
                $reason = $kind === self::HARD ? 'bounce' : 'complaint';
                if ( CjwNewsletterMailPreferences::suppressForBounce( (string)$user->attribute( 'email' ), $reason, $detail ) )
                    $result['action'] = 'suppressed';
            }
            return $result;
        }
        // soft
        $result['action'] = 'bounced';
        if ( is_object( $sendItem ) )
        {
            $at = self::scheduleRetry( $sendItem, $user );
            if ( $at > 0 )
            {
                $result['action'] = 'retry';
                $result['retry_at'] = $at;
            }
        }
        return $result;
    }

    /**
     * A mail the transport refused at once (the runner's queue). A temporary refusal is queued again, a permanent
     * failure of the address is a hard bounce.
     *
     * @param CjwNewsletterEditionSendItem $sendItem
     * @param array $sendResult CjwNewsletterMail::sendEmail()
     * @return bool true: handled here (the item waits for its retry, or was closed as a hard bounce); false: the
     *              caller closes the item as before
     */
    static function sendFailed( $sendItem, $sendResult )
    {
        if ( !is_object( $sendItem ) )
            return false;
        $error = '';
        if ( isset( $sendResult['send_error'] ) )
            $error = (string)$sendResult['send_error'];
        else if ( isset( $sendResult['send_result'] ) && $sendResult['send_result'] instanceof Exception )
            $error = $sendResult['send_result']->getMessage();
        $kind = self::kindOfCode( $error );
        $user = $sendItem->attribute( 'newsletter_user_object' );
        if ( $kind === self::HARD )
        {
            $sendItem->setAttribute( 'status', CjwNewsletterEditionSendItem::STATUS_ABORT );
            $sendItem->store();
            self::handle( array( 'kind' => self::HARD, 'detail' => self::statusOf( $error ) ), $sendItem, $user );
            return true;
        }
        // a temporary refusal (4xx), or a mail server that could not be reached: try again later. Any other error
        // (a file that could not be written, a broken mail) is not helped by waiting: the caller closes the item
        $temporary = $kind === self::SOFT || preg_match( '/could not connect|connection (refused|timed out|reset|closed)|timed out|temporar/i', $error );
        if ( $temporary && self::scheduleRetry( $sendItem, $user ) > 0 )
            return true;
        return false;
    }

    /**
     * @return bool the item may be sent again: retries left, a user who still gets mail, a send not too old
     */
    static function canRetry( $sendItem, $user = null )
    {
        if ( !is_object( $sendItem ) || self::maxRetries() === 0 )
            return false;
        if ( (int)$sendItem->attribute( 'retry_count' ) >= self::maxRetries() )
            return false;
        if ( !is_object( $user ) )
            $user = $sendItem->attribute( 'newsletter_user_object' );
        if ( !is_object( $user ) )
            return false;
        $status = (int)$user->attribute( 'status' );
        if ( in_array( $status, array( CjwNewsletterUser::STATUS_BOUNCED_HARD, CjwNewsletterUser::STATUS_BOUNCED_SOFT,
                                       CjwNewsletterUser::STATUS_BLACKLISTED, CjwNewsletterUser::STATUS_REMOVED_SELF,
                                       CjwNewsletterUser::STATUS_REMOVED_ADMIN ), true ) )
            return false;
        if ( class_exists( 'expMailSuppression' ) && CjwNewsletterMailPreferences::available()
             && expMailSuppression::isSuppressed( (string)$user->attribute( 'email' ) ) )
            return false;
        $send = CjwNewsletterEditionSend::fetch( (int)$sendItem->attribute( 'edition_send_id' ) );
        if ( !is_object( $send ) || (int)$send->attribute( 'status' ) === CjwNewsletterEditionSend::STATUS_ABORT )
            return false;
        $created = (int)$send->attribute( 'created' );
        if ( $created > 0 && $created < self::now() - self::retryMaxAgeDays() * 86400 )
            return false;
        return true;
    }

    /**
     * Queues an item again for a later run (status new, next_retry), and opens its send again when it had finished.
     *
     * @return int when the item is sent again, 0 = no retry
     */
    static function scheduleRetry( $sendItem, $user = null )
    {
        if ( !self::canRetry( $sendItem, $user ) )
            return 0;
        $count = (int)$sendItem->attribute( 'retry_count' );
        $at = self::now() + self::retryDelay() * (int)pow( 2, $count );
        $sendItem->setAttribute( 'retry_count', $count + 1 );
        $sendItem->setAttribute( 'next_retry', $at );
        $sendItem->setAttribute( 'status', CjwNewsletterEditionSendItem::STATUS_NEW );
        $sendItem->store();
        $send = CjwNewsletterEditionSend::fetch( (int)$sendItem->attribute( 'edition_send_id' ) );
        if ( is_object( $send ) && (int)$send->attribute( 'status' ) === CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_FINISHED )
        {
            $send->setAttribute( 'status', CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_STARTED );
            $send->store();
        }
        return $at;
    }

    /**
     * @return int items that wait for a retry (all sends, or one)
     */
    static function waitingRetryCount( $editionSendId = 0 )
    {
        $db = eZDB::instance();
        $sql = 'SELECT COUNT(*) AS c FROM cjwnl_edition_send_item WHERE status = ' . CjwNewsletterEditionSendItem::STATUS_NEW . ' AND next_retry > ' . self::now();
        if ( (int)$editionSendId > 0 )
            $sql .= ' AND edition_send_id = ' . (int)$editionSendId;
        $rows = $db->arrayQuery( $sql );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }

    /**
     * A message of a newsletter mail account (CjwNewsletterMailboxItem) after the parser read it: the bounce is
     * judged and acted on.
     *
     * @param CjwNewsletterMailboxItem $item
     * @param array $parsed CjwNewsletterMailParser::parse()
     * @return array handle(), plus classification
     */
    static function handleMailboxItem( $item, array $parsed )
    {
        $raw = (string)$item->getRawMailMessageContent();
        $c = self::classify( $raw, isset( $parsed['error_code'] ) ? $parsed['error_code'] : '' );
        $sendItem = null;
        $user = null;
        if ( isset( $parsed['x-cjwnl-senditem'] ) )
            $sendItem = CjwNewsletterEditionSendItem::fetchByHash( (string)$parsed['x-cjwnl-senditem'], true );
        if ( !is_object( $sendItem ) )
            $sendItem = null;
        if ( $sendItem === null && isset( $parsed['x-cjwnl-user'] ) )
        {
            $user = CjwNewsletterUser::fetchByHash( (string)$parsed['x-cjwnl-user'], true );
            if ( !is_object( $user ) )
                $user = null;
        }
        $result = self::handle( $c, $sendItem, $user );
        $result['classification'] = $c;
        return $result;
    }

    /**
     * @return string the enhanced status code in a text, or its basic code
     */
    static function statusOf( $text )
    {
        if ( preg_match( '/\b[245]\.\d{1,3}\.\d{1,3}\b/', (string)$text, $m ) )
            return $m[0];
        if ( preg_match( '/\b[245]\d\d\b/', (string)$text, $m ) )
            return $m[0];
        return '';
    }

    protected static function setting( $name, $default )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'DeliverabilitySettings', $name ) ? $ini->variable( 'DeliverabilitySettings', $name ) : $default;
    }
}

?>
