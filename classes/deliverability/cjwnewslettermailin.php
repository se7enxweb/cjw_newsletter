<?php
/**
 * File containing the CjwNewsletterMailin class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * Subscribe and unsubscribe by e-mail (cjw_newsletter.ini [MailInSettings], the addresses in cjwnl_mailin_address,
 * every message handled in cjwnl_mailin_message).
 *
 * A message reaches this class from the kernel's mailbox reader ([BounceSettings] MessageListeners[] of
 * mailpreferences.ini names this class: mailMessage()) or from a newsletter mail account
 * (CjwNewsletterMailboxItem). It is routed by the address it was sent to (Delivered-To, X-Original-To, To, Cc;
 * list+tag@... with PlusAddressing=enabled). The word that asks for something is the tag of a plus-address, the
 * fixed action of the address, or a keyword in the subject or the first line of the text.
 *
 * The From header is never trusted alone:
 *  - subscribe: a new address gets a pending subscription and the newsletter's confirmation mail, whose link must
 *    be opened (double opt-in); a known address gets the mail with the link to its own settings page, where the
 *    person subscribes. Nothing is confirmed by the message itself.
 *  - unsubscribe: honoured at once only when the message proves the address (it carries the newsletter's own headers
 *    of that person, quoted in a reply, or the code of the subscription or of the person); otherwise the address on
 *    record gets a mail with the unsubscribe links.
 *
 * Automatic messages (Auto-Submitted, Precedence bulk/junk/list, [MailInSettings] IgnoreSenders[]), suppressed and
 * blacklisted addresses and senders over MaxRequestsPerSenderPerDay are rejected. A message is handled once (by its
 * Message-Id). The sender address is kept masked once the message is handled.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterMailin
{
    const STATUS_NEW = 0;
    const STATUS_PENDING = 1;
    const STATUS_DONE = 2;
    const STATUS_REJECTED = 9;

    const UNSUBSCRIBE_TEMPLATE = 'design:newsletter/deliverability/mail/mailin_unsubscribe.tpl';

    /** @var int|null test override of the clock */
    static $now = null;

    static function now()
    {
        return self::$now !== null ? (int)self::$now : time();
    }

    /** @return bool [MailInSettings] MailIn=enabled */
    static function enabled()
    {
        return self::setting( 'MailIn', 'disabled' ) === 'enabled';
    }

    /** @return bool [MailInSettings] PlusAddressing=enabled */
    static function plusAddressing()
    {
        return self::setting( 'PlusAddressing', 'disabled' ) === 'enabled';
    }

    // ------------------------------------------------------------------ entry points

    /**
     * Listener of the kernel's mailbox reader ([BounceSettings] MessageListeners[] of mailpreferences.ini): a message
     * of the bounce mailbox. A bounce of a newsletter mail is acted on (the kernel has already suppressed what it
     * reports as hard or complaint), any other message is routed to the mail-in addresses.
     *
     * @param string $raw the whole message
     * @param array $classification expMailBounceReader::classify()
     * @param bool $dryRun change nothing
     * @return bool the message was for the newsletter (the reader may delete it)
     */
    static function mailMessage( $raw, $classification, $dryRun = false )
    {
        $kind = is_array( $classification ) && isset( $classification['kind'] ) ? (string)$classification['kind'] : CjwNewsletterBounce::NONE;
        if ( $kind !== CjwNewsletterBounce::NONE )
        {
            $headers = CjwNewsletterBounce::cjwHeaders( $raw );
            $sendItem = isset( $headers['x-cjwnl-senditem'] ) ? CjwNewsletterEditionSendItem::fetchByHash( $headers['x-cjwnl-senditem'], true ) : null;
            $user = ( !is_object( $sendItem ) && isset( $headers['x-cjwnl-user'] ) ) ? CjwNewsletterUser::fetchByHash( $headers['x-cjwnl-user'], true ) : null;
            if ( !is_object( $sendItem ) && !is_object( $user ) )
                return false;
            CjwNewsletterBounce::handle( $classification, is_object( $sendItem ) ? $sendItem : null, is_object( $user ) ? $user : null, $dryRun );
            return true;
        }
        $result = self::handleRawMessage( $raw, 0, $dryRun );
        return is_array( $result );
    }

    /**
     * Routes and acts on a message that is no bounce.
     *
     * @param string $raw
     * @param int $mailboxId the newsletter mail account it came from, 0 = the kernel's bounce mailbox
     * @param bool $dryRun only say what would be done
     * @return array|null null: not for a mail-in address (or mail-in is off); else action (subscribe, unsubscribe, ''),
     *                    status (pending, done, rejected, duplicate), note, address_id, message_id (0 in a dry run)
     */
    static function handleRawMessage( $raw, $mailboxId = 0, $dryRun = false )
    {
        if ( !self::enabled() )
            return null;
        $message = self::readMessage( $raw );
        $route = self::route( $message['recipients'], (int)$mailboxId );
        if ( $route === null )
            return null;
        $address = $route['address'];
        $result = array( 'action' => '', 'status' => 'rejected', 'note' => '', 'address_id' => (int)$address->attribute( 'id' ), 'message_id' => 0 );
        $identifier = $message['message_id'] !== '' ? $message['message_id'] : 'sha1:' . sha1( (string)$raw );
        if ( CjwNewsletterMailinMessage::fetchListByMessageIdentifier( mb_substr( $identifier, 0, 255 ), 1 ) )
        {
            $result['status'] = 'duplicate';
            $result['note'] = 'Handled before.';
            return $result;
        }
        $action = self::actionOf( $address, $route['tag'], $message );
        $result['action'] = $action;
        $from = $message['from'];
        $row = null;
        if ( !$dryRun )
        {
            $row = CjwNewsletterMailinMessage::create( array( 'mailin_address_id' => (int)$address->attribute( 'id' ),
                'message_identifier' => mb_substr( $identifier, 0, 255 ), 'email_from' => mb_substr( $from, 0, 255 ),
                'action' => $action, 'status' => self::STATUS_NEW, 'created' => self::now() ) );
            $row->store();
            $result['message_id'] = (int)$row->attribute( 'id' );
        }
        $reject = self::rejectReason( $message, $action, $from, $address );
        if ( $reject !== '' )
        {
            $result['note'] = $reject;
            return self::finish( $row, $result, self::STATUS_REJECTED, 0 );
        }
        $listId = (int)$address->attribute( 'list_contentobject_id' );
        if ( $action === 'subscribe' )
            return self::subscribe( $row, $result, $from, $listId, $dryRun );
        return self::unsubscribe( $row, $result, $from, $listId, $message, $dryRun );
    }

    // ------------------------------------------------------------------ the two actions

    protected static function subscribe( $row, array $result, $from, $listId, $dryRun )
    {
        $user = CjwNewsletterUser::fetchByEmail( $from );
        if ( is_object( $user ) )
        {
            $subscription = CjwNewsletterSubscription::fetchByListIdAndNewsletterUserId( $listId, $user->attribute( 'id' ) );
            if ( is_object( $subscription ) && in_array( (int)$subscription->attribute( 'status' ),
                     array( CjwNewsletterSubscription::STATUS_CONFIRMED, CjwNewsletterSubscription::STATUS_APPROVED ), true ) )
            {
                $result['note'] = 'Already subscribed.';
                return self::finish( $row, $result, self::STATUS_DONE, (int)$user->attribute( 'id' ) );
            }
            // a known address: the link to its own settings page, where the person subscribes (no change here)
            $result['note'] = 'Known address: the link to the settings page was sent.';
            if ( !$dryRun )
            {
                $sent = $user->sendSubcriptionInformationMail();
                if ( !is_array( $sent ) || $sent['send_result'] !== true )
                {
                    $result['note'] = 'The mail with the link to the settings page could not be sent.';
                    return self::finish( $row, $result, self::STATUS_REJECTED, (int)$user->attribute( 'id' ) );
                }
            }
            return self::finish( $row, $result, self::STATUS_PENDING, (int)$user->attribute( 'id' ) );
        }
        $result['note'] = 'New address: pending subscription, the confirmation mail was sent.';
        if ( $dryRun )
            return self::finish( $row, $result, self::STATUS_PENDING, 0 );
        $data = array( 'email' => $from, 'first_name' => '', 'last_name' => '', 'salutation' => null, 'id_array' => array(),
                       'list_array' => array( $listId ), 'list_output_format_array' => array( $listId => array( 0 ) ) );
        $created = CjwNewsletterSubscription::createSubscriptionByArray( $data, CjwNewsletterUser::STATUS_PENDING, true, 'subscribe' );
        $user = CjwNewsletterUser::fetchByEmail( $from );
        if ( !is_array( $created ) || !is_object( $user ) || !empty( $created['errors'] ) )
        {
            $result['note'] = 'The subscription could not be made.';
            return self::finish( $row, $result, self::STATUS_REJECTED, is_object( $user ) ? (int)$user->attribute( 'id' ) : 0 );
        }
        $sent = $user->sendSubcriptionConfirmationMail();
        if ( !is_array( $sent ) || $sent['send_result'] !== true )
            $result['note'] = 'Pending subscription made, but the confirmation mail could not be sent.';
        return self::finish( $row, $result, self::STATUS_PENDING, (int)$user->attribute( 'id' ) );
    }

    protected static function unsubscribe( $row, array $result, $from, $listId, array $message, $dryRun )
    {
        $user = CjwNewsletterUser::fetchByEmail( $from );
        $subscriptions = array();
        if ( is_object( $user ) )
        {
            foreach ( (array)CjwNewsletterSubscription::fetchSubscriptionListByNewsletterUserId( $user->attribute( 'id' ) ) as $subscription )
            {
                if ( $listId > 0 && (int)$subscription->attribute( 'list_contentobject_id' ) !== $listId )
                    continue;
                if ( in_array( (int)$subscription->attribute( 'status' ), array( CjwNewsletterSubscription::STATUS_PENDING,
                         CjwNewsletterSubscription::STATUS_CONFIRMED, CjwNewsletterSubscription::STATUS_APPROVED ), true ) )
                    $subscriptions[] = $subscription;
            }
        }
        if ( !$subscriptions )
        {
            // nothing to do; the answer does not tell whether the address is known
            $result['note'] = 'No subscription of this address.';
            return self::finish( $row, $result, self::STATUS_DONE, is_object( $user ) ? (int)$user->attribute( 'id' ) : 0 );
        }
        if ( self::provesAddress( $message, $user, $subscriptions ) )
        {
            $result['note'] = 'Unsubscribed (' . count( $subscriptions ) . ').';
            if ( !$dryRun )
                foreach ( $subscriptions as $subscription )
                    $subscription->unsubscribe();
            return self::finish( $row, $result, self::STATUS_DONE, (int)$user->attribute( 'id' ) );
        }
        $result['note'] = 'The unsubscribe link was sent to the address.';
        if ( !$dryRun )
        {
            $sent = self::sendUnsubscribeLinks( $user, $subscriptions );
            if ( !is_array( $sent ) || $sent['send_result'] !== true )
            {
                $result['note'] = 'The mail with the unsubscribe link could not be sent.';
                return self::finish( $row, $result, self::STATUS_REJECTED, (int)$user->attribute( 'id' ) );
            }
        }
        return self::finish( $row, $result, self::STATUS_PENDING, (int)$user->attribute( 'id' ) );
    }

    /**
     * @return bool the message carries something only the owner of the address has: the newsletter's own headers
     *              of this person (quoted in a reply to an edition), or the code of the person or of a subscription
     */
    static function provesAddress( array $message, $user, array $subscriptions )
    {
        $codes = array( strtolower( (string)$user->attribute( 'hash' ) ) );
        foreach ( $subscriptions as $subscription )
            $codes[] = strtolower( (string)$subscription->attribute( 'hash' ) );
        $codes = array_filter( $codes, function ( $code ) { return strlen( $code ) >= 16; } );
        foreach ( array( 'x-cjwnl-user', 'x-cjwnl-subscription' ) as $header )
            if ( isset( $message['cjw_headers'][$header] ) && in_array( strtolower( $message['cjw_headers'][$header] ), $codes, true ) )
                return true;
        $haystack = strtolower( $message['subject'] . "\n" . $message['text'] );
        foreach ( $codes as $code )
            if ( preg_match( '/(^|[^0-9a-z])' . preg_quote( $code, '/' ) . '([^0-9a-z]|$)/', $haystack ) )
                return true;
        return false;
    }

    /**
     * Sends the address on record the unsubscribe links of its subscriptions.
     *
     * @return array CjwNewsletterMail::sendEmail()
     */
    static function sendUnsubscribeLinks( $user, array $subscriptions )
    {
        include_once( 'kernel/common/template.php' );
        $tpl = eZTemplate::factory();
        $links = array();
        $base = class_exists( 'expMailToken' ) ? expMailToken::baseURL() : 'http://' . eZSys::hostname();
        foreach ( $subscriptions as $subscription )
        {
            $path = 'newsletter/unsubscribe/' . $subscription->attribute( 'hash' );
            eZURI::transformURI( $path, false, 'relative' );
            $links[] = array( 'name' => CjwNewsletterMailPreferences::listName( $subscription ), 'url' => $base . $path );
        }
        $tpl->setVariable( 'newsletter_user', $user );
        $tpl->setVariable( 'links', $links );
        $body = $tpl->fetch( self::UNSUBSCRIBE_TEMPLATE );
        $subject = $tpl->hasVariable( 'subject' ) ? (string)$tpl->variable( 'subject' ) : 'Newsletter';
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $mail = new CjwNewsletterMail();
        $mail->setExtraMailHeadersByNewsletterUser( $user );
        $mail->setTransportMethodDirectlyFromIni();
        return $mail->sendEmail( $ini->variable( 'NewsletterMailSettings', 'EmailSender' ), $ini->variable( 'NewsletterMailSettings', 'EmailSenderName' ),
                                 (string)$user->attribute( 'email' ), '', $subject, array( 'text' => $body ), false, 'utf-8',
                                 $ini->variable( 'NewsletterMailSettings', 'EmailReplyTo' ), $ini->variable( 'NewsletterMailSettings', 'EmailReturnPath' ) );
    }

    // ------------------------------------------------------------------ reading a message

    /**
     * @param string $raw
     * @return array headers (lower case name => values), from (lower case address or ''), recipients (lower case
     *               addresses, the envelope headers first), subject, text (the first text/plain part, up to 4000
     *               characters), message_id, cjw_headers
     */
    static function readMessage( $raw )
    {
        $raw = str_replace( array( "\r\n", "\r" ), "\n", (string)$raw );
        $pos = strpos( $raw, "\n\n" );
        $headerText = $pos === false ? $raw : substr( $raw, 0, $pos );
        $headers = self::parseHeaders( $headerText );
        $first = function ( $name ) use ( $headers ) { return isset( $headers[$name][0] ) ? (string)$headers[$name][0] : ''; };
        $recipients = array();
        foreach ( array( 'delivered-to', 'x-original-to', 'envelope-to', 'to', 'cc' ) as $name )
            if ( isset( $headers[$name] ) )
                foreach ( $headers[$name] as $value )
                    $recipients = array_merge( $recipients, self::addresses( $value ) );
        $from = self::addresses( $first( 'from' ) );
        $text = '';
        if ( class_exists( 'expMailBounceReader' ) )
        {
            foreach ( expMailBounceReader::parts( $raw ) as $part )
                if ( $part['type'] === 'text/plain' )
                {
                    $text = (string)$part['body'];
                    break;
                }
        }
        else if ( $pos !== false )
        {
            $text = substr( $raw, $pos + 2 );
        }
        $subject = $first( 'subject' );
        if ( function_exists( 'iconv_mime_decode' ) && strpos( $subject, '=?' ) !== false )
        {
            $decoded = @iconv_mime_decode( $subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8' );
            if ( is_string( $decoded ) )
                $subject = $decoded;
        }
        return array( 'headers' => $headers, 'from' => $from ? $from[0] : '', 'recipients' => array_values( array_unique( $recipients ) ),
                      'subject' => trim( $subject ), 'text' => mb_substr( $text, 0, 4000 ), 'message_id' => trim( $first( 'message-id' ) ),
                      'cjw_headers' => CjwNewsletterBounce::cjwHeaders( $raw ) );
    }

    /**
     * The mail-in address a message was sent to.
     *
     * @param string[] $recipients
     * @param int $mailboxId
     * @return array|null address (CjwNewsletterMailinAddress), tag (of a plus-address, '' else)
     */
    static function route( array $recipients, $mailboxId = 0 )
    {
        foreach ( $recipients as $email )
        {
            $email = strtolower( $email );
            // ( address, plus_tag of the row, tag passed on as the action word )
            $candidates = array( array( $email, '', '' ) );
            if ( self::plusAddressing() && preg_match( '/^([^+@]+)\+([^@]+)@(.+)$/', $email, $m ) )
            {
                $candidates[] = array( $m[1] . '@' . $m[3], $m[2], '' );
                $candidates[] = array( $m[1] . '@' . $m[3], '', $m[2] );
            }
            foreach ( $candidates as $candidate )
            {
                $address = CjwNewsletterMailinAddress::fetchByEmailAndPlusTag( $candidate[0], $candidate[1] );
                if ( !$address || !(int)$address->attribute( 'is_active' ) || (int)$address->attribute( 'mailbox_id' ) !== (int)$mailboxId )
                    continue;
                return array( 'address' => $address, 'tag' => $candidate[2] );
            }
        }
        return null;
    }

    /**
     * @return string subscribe, unsubscribe or '' (not understood)
     */
    static function actionOf( $address, $tag, array $message )
    {
        $fixed = (string)$address->attribute( 'action' );
        if ( $fixed === 'subscribe' || $fixed === 'unsubscribe' )
            return $fixed;
        foreach ( array( $tag, $message['subject'], self::firstLine( $message['text'] ) ) as $text )
        {
            $found = self::keywordIn( $text );
            if ( $found !== '' )
                return $found;
        }
        return '';
    }

    /**
     * @return string subscribe, unsubscribe or '' (none, or both)
     */
    static function keywordIn( $text )
    {
        $text = mb_strtolower( trim( (string)$text ) );
        if ( $text === '' )
            return '';
        $hit = array();
        foreach ( array( 'unsubscribe' => 'UnsubscribeKeywords', 'subscribe' => 'SubscribeKeywords' ) as $action => $setting )
        {
            foreach ( (array)self::setting( $setting, array() ) as $word )
            {
                $word = mb_strtolower( trim( (string)$word ) );
                if ( $word !== '' && preg_match( '/(^|[^\p{L}\p{N}])' . preg_quote( $word, '/' ) . '($|[^\p{L}\p{N}])/u', $text ) )
                {
                    $hit[$action] = true;
                    break;
                }
            }
        }
        return count( $hit ) === 1 ? key( $hit ) : '';
    }

    /**
     * @return string why the message is not acted on, '' = it is
     */
    static function rejectReason( array $message, $action, $from, $address )
    {
        if ( $action === '' )
            return 'No subscribe or unsubscribe asked for.';
        if ( $from === '' )
            return 'No sender address.';
        $auto = isset( $message['headers']['auto-submitted'][0] ) ? strtolower( trim( $message['headers']['auto-submitted'][0] ) ) : '';
        $precedence = isset( $message['headers']['precedence'][0] ) ? strtolower( trim( $message['headers']['precedence'][0] ) ) : '';
        if ( ( $auto !== '' && $auto !== 'no' ) || in_array( $precedence, array( 'bulk', 'junk', 'list' ), true ) )
            return 'An automatic message.';
        foreach ( (array)self::setting( 'IgnoreSenders', array() ) as $pattern )
        {
            $pattern = strtolower( trim( (string)$pattern ) );
            if ( $pattern !== '' && fnmatch( $pattern, $from ) )
                return 'A sender that is ignored.';
        }
        // the site's own addresses never subscribe
        $own = array( strtolower( (string)$address->attribute( 'email' ) ) );
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $own[] = strtolower( trim( (string)$ini->variable( 'NewsletterMailSettings', 'EmailSender' ) ) );
        if ( in_array( preg_replace( '/\+[^@]*@/', '@', $from ), $own, true ) )
            return 'A sender that is ignored.';
        if ( $action === 'subscribe' )
        {
            if ( (int)$address->attribute( 'list_contentobject_id' ) <= 0 || !CjwNewsletterSubscription::isNewsletterListObject( (int)$address->attribute( 'list_contentobject_id' ) ) )
                return 'The address belongs to no list.';
            if ( class_exists( 'expMailSuppression' ) && CjwNewsletterMailPreferences::available() && expMailSuppression::isSuppressed( $from ) )
                return 'The address is suppressed.';
            if ( CjwNewsletterBlacklistItem::fetchByEmail( $from ) )
                return 'The address is on the blacklist.';
        }
        $max = (int)self::setting( 'MaxRequestsPerSenderPerDay', 5 );
        if ( $max > 0 && self::requestsToday( $from ) > $max )
            return 'Too many requests from this sender today.';
        return '';
    }

    /**
     * @return int the messages of this sender in the last 24 hours (this one included once it is stored)
     */
    static function requestsToday( $from )
    {
        $db = eZDB::instance();
        $since = self::now() - 86400;
        $masked = self::mask( $from );
        $rows = $db->arrayQuery( "SELECT COUNT(*) AS c FROM cjwnl_mailin_message WHERE created >= " . (int)$since
                                 . " AND ( email_from = '" . $db->escapeString( $from ) . "' OR email_from = '" . $db->escapeString( $masked ) . "' )" );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }

    /**
     * @return string the address as it is kept after the message is handled
     */
    static function mask( $email )
    {
        if ( class_exists( 'expMailPreferencesService' ) )
            return expMailPreferencesService::maskAddress( $email );
        $at = strpos( (string)$email, '@' );
        return $at === false ? '***' : substr( $email, 0, 1 ) . '***' . substr( $email, $at );
    }

    // ------------------------------------------------------------------ admin

    /**
     * Checks and stores the form of a mail-in address.
     *
     * @param CjwNewsletterMailinAddress $address
     * @param array $input email, plus_tag, list_contentobject_id, action, mailbox_id, is_active
     * @return array field => error text (empty: stored)
     */
    static function storeAddress( $address, array $input )
    {
        $errors = array();
        $email = strtolower( trim( (string)( isset( $input['email'] ) ? $input['email'] : '' ) ) );
        $tag = strtolower( trim( (string)( isset( $input['plus_tag'] ) ? $input['plus_tag'] : '' ) ) );
        $action = isset( $input['action'] ) ? (string)$input['action'] : 'both';
        if ( !in_array( $action, array( 'subscribe', 'unsubscribe', 'both' ), true ) )
            $action = 'both';
        $listId = isset( $input['list_contentobject_id'] ) ? (int)$input['list_contentobject_id'] : 0;
        $mailboxId = isset( $input['mailbox_id'] ) ? max( 0, (int)$input['mailbox_id'] ) : 0;
        if ( !eZMail::validate( $email ) || strpos( $email, '+' ) !== false )
            $errors['email'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'Enter a valid address without a "+" part (the tag goes into its own field).' );
        if ( $tag !== '' && !preg_match( '/^[a-z0-9._-]{1,100}$/', $tag ) )
            $errors['plus_tag'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'A tag has only letters, digits, ".", "_" and "-".' );
        if ( $listId > 0 && !CjwNewsletterSubscription::isNewsletterListObject( $listId ) )
            $errors['list_contentobject_id'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'Choose a newsletter list.' );
        if ( $listId <= 0 && $action !== 'unsubscribe' )
            $errors['list_contentobject_id'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'An address for every list can only take unsubscribe mails.' );
        if ( $mailboxId > 0 && !eZPersistentObject::fetchObject( CjwNewsletterMailbox::definition(), null, array( 'id' => $mailboxId ), true ) )
            $errors['mailbox_id'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'Choose a mail account.' );
        if ( !isset( $errors['email'] ) )
        {
            $other = CjwNewsletterMailinAddress::fetchByEmailAndPlusTag( $email, $tag );
            if ( $other && (int)$other->attribute( 'id' ) !== (int)$address->attribute( 'id' ) )
                $errors['email'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'This address is already in use.' );
        }
        $address->setAttribute( 'email', mb_substr( $email, 0, 255 ) );
        $address->setAttribute( 'plus_tag', mb_substr( $tag, 0, 100 ) );
        $address->setAttribute( 'action', $action );
        $address->setAttribute( 'list_contentobject_id', max( 0, $listId ) );
        $address->setAttribute( 'mailbox_id', $mailboxId );
        $address->setAttribute( 'is_active', empty( $input['is_active'] ) ? 0 : 1 );
        if ( $errors )
            return $errors;
        $now = time();
        if ( !(int)$address->attribute( 'id' ) )
            $address->setAttribute( 'created', $now );
        $address->setAttribute( 'modified', $now );
        $address->store();
        return array();
    }

    /**
     * @return string the address a person writes to ("news+subscribe@..." for a tag)
     */
    static function displayAddress( $address )
    {
        $email = (string)$address->attribute( 'email' );
        $tag = (string)$address->attribute( 'plus_tag' );
        if ( $tag === '' )
            return $email;
        $at = strpos( $email, '@' );
        return $at === false ? $email : substr( $email, 0, $at ) . '+' . $tag . substr( $email, $at );
    }

    // ------------------------------------------------------------------ internals

    protected static function finish( $row, array $result, $status, $userId )
    {
        $names = array( self::STATUS_PENDING => 'pending', self::STATUS_DONE => 'done', self::STATUS_REJECTED => 'rejected', self::STATUS_NEW => 'new' );
        $result['status'] = $names[$status];
        if ( is_object( $row ) )
        {
            $row->setAttribute( 'status', $status );
            $row->setAttribute( 'newsletter_user_id', (int)$userId );
            $row->setAttribute( 'note', mb_substr( $result['note'], 0, 1000 ) );
            $row->setAttribute( 'email_from', self::mask( (string)$row->attribute( 'email_from' ) ) );
            $row->setAttribute( 'processed', self::now() );
            $row->store();
        }
        return $result;
    }

    protected static function firstLine( $text )
    {
        foreach ( preg_split( "/\r\n|\n|\r/", (string)$text ) as $line )
        {
            $line = trim( $line );
            if ( $line !== '' && $line[0] !== '>' )
                return mb_substr( $line, 0, 200 );
        }
        return '';
    }

    /** @return array lower case name => values (folded lines joined) */
    protected static function parseHeaders( $text )
    {
        if ( class_exists( 'expMailBounceReader' ) )
            return expMailBounceReader::parseHeaders( $text );
        $text = preg_replace( "/\n[ \t]+/", ' ', (string)$text );
        $out = array();
        foreach ( explode( "\n", $text ) as $line )
        {
            $pos = strpos( $line, ':' );
            if ( $pos > 0 )
                $out[strtolower( trim( substr( $line, 0, $pos ) ) )][] = trim( substr( $line, $pos + 1 ) );
        }
        return $out;
    }

    /** @return string[] the valid addresses in a header value, lower case */
    protected static function addresses( $value )
    {
        $out = array();
        if ( preg_match_all( '/[A-Za-z0-9._%+\'=-]+@[A-Za-z0-9.-]+\.[A-Za-z0-9-]{2,}/', (string)$value, $m ) )
            foreach ( $m[0] as $email )
                if ( eZMail::validate( $email ) )
                    $out[] = strtolower( $email );
        return $out;
    }

    protected static function setting( $name, $default )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'MailInSettings', $name ) ? $ini->variable( 'MailInSettings', $name ) : $default;
    }
}

?>
