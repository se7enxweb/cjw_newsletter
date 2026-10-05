<?php
/**
 * File containing the CjwNewsletterTracking class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * Open and click tracking of the newsletter editions (cjw_newsletter 4.2.0, area N4 Statistics).
 *
 * Privacy first:
 * - Nothing is tracked unless [TrackingSettings] Tracking=enabled and the list (or the send) chose a tracking mode:
 *   0 off (the default), 1 anonymous totals, 2 per person for the recipients who agreed.
 * - Per person means: the recipient switched on the e-mail preference category [TrackingSettings] ConsentCategory
 *   (newsletter_statistics, optional, off by default). Everybody else gets links and a pixel that carry only the send
 *   (and the A/B variant), never the person, so only anonymous totals can be counted for them.
 * - The consent is checked again on every open and click: a person who withdrew it is counted anonymously from then on.
 *
 * Links: when the mail queue of a send is made, every http(s) link of the edition (never mailto:, anchors, links with
 * placeholders or the unsubscribe and preference links) is stored as a cjwnl_link row and rewritten to
 *   <site>/newsletter/r/<link id>/#_cjwnl_track_#
 * and the open pixel <site>/newsletter/o/#_cjwnl_track_# is added before </body>. The placeholder becomes
 * "<key>/<signature>" for each recipient: key p<item hash> (per person) or a<send id>x<variant id> (anonymous), the
 * signature an HMAC of the key under a key derived from the site secret. The redirect only goes to the stored URL of
 * the link id, and only when the key belongs to the same send: there is no open redirect.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterTracking
{
    const PLACEHOLDER = '#_cjwnl_track_#';

    const MODE_OFF = 0;
    const MODE_ANONYMOUS = 1;
    const MODE_PERSON = 2;

    /** @var array per process: newsletter user id => consent (bool), for the queue run */
    protected static $consentCache = array();

    // ------------------------------------------------------------------ settings

    /**
     * @param string $block
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    static function setting( $block, $name, $default = null )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( $block, $name ) ? $ini->variable( $block, $name ) : $default;
    }

    /** @return bool [TrackingSettings] Tracking=enabled: the site allows tracking at all */
    static function enabled()
    {
        return self::setting( 'TrackingSettings', 'Tracking', 'disabled' ) === 'enabled';
    }

    /** @return bool the open pixel is added to tracked HTML mails */
    static function pixelEnabled()
    {
        return self::setting( 'TrackingSettings', 'OpenPixel', 'enabled' ) === 'enabled';
    }

    /** @return string the mail-preference category whose consent allows per-person counting */
    static function consentCategory()
    {
        $c = trim( (string)self::setting( 'TrackingSettings', 'ConsentCategory', 'newsletter_statistics' ) );
        return $c !== '' ? $c : 'newsletter_statistics';
    }

    /** @return int months per-person rows are kept */
    static function retentionMonths()
    {
        return max( 1, (int)self::setting( 'TrackingSettings', 'PersonRetentionMonths', 12 ) );
    }

    /** @return int[] the tracking modes, for forms */
    static function modes()
    {
        return array( self::MODE_OFF, self::MODE_ANONYMOUS, self::MODE_PERSON );
    }

    /** @return int a valid mode */
    static function cleanMode( $mode )
    {
        $mode = (int)$mode;
        return in_array( $mode, self::modes(), true ) ? $mode : self::MODE_OFF;
    }

    /** @return int the mode that applies to a send now (0 when the site switched tracking off, or for SMS and test sends) */
    static function sendMode( $send )
    {
        if ( !is_object( $send ) || !self::enabled() )
            return self::MODE_OFF;
        if ( (string)$send->attribute( 'channel' ) === 'sms' || (int)$send->attribute( 'test_group_id' ) > 0 )
            return self::MODE_OFF;
        return self::cleanMode( $send->attribute( 'tracking_mode' ) );
    }

    // ------------------------------------------------------------------ keys and signatures

    /** @return string the raw signing key, derived from the site secret */
    protected static function signingKey()
    {
        static $key = null;
        if ( $key !== null )
            return $key;
        if ( class_exists( 'expMailSecret' ) )
        {
            $key = expMailSecret::derive( 'cjw_newsletter:tracking' );
            return $key;
        }
        // Exponential before 6.0.15: a secret of its own, made once
        $row = eZSiteData::fetchByName( 'cjw_newsletter_tracking_secret' );
        if ( !$row )
        {
            $row = eZSiteData::create( 'cjw_newsletter_tracking_secret', bin2hex( random_bytes( 32 ) ) );
            $row->store();
        }
        $key = hex2bin( (string)$row->attribute( 'value' ) );
        return $key;
    }

    /** @return string 24 hex characters */
    static function sign( $key )
    {
        return substr( hash_hmac( 'sha256', 'cjwnl-track:' . (string)$key, self::signingKey() ), 0, 24 );
    }

    /** @return bool */
    static function verify( $key, $signature )
    {
        $key = (string)$key;
        $signature = (string)$signature;
        if ( self::parseKey( $key ) === null || !preg_match( '/^[0-9a-f]{24}$/', $signature ) )
            return false;
        return hash_equals( self::sign( $key ), $signature );
    }

    /** @return string */
    static function personKey( $sendItem )
    {
        return 'p' . (string)$sendItem->attribute( 'hash' );
    }

    /** @return string */
    static function anonymousKey( $sendId, $variantId = 0 )
    {
        return 'a' . (int)$sendId . 'x' . (int)$variantId;
    }

    /**
     * @param string $key
     * @return array|null type person (hash) or anonymous (send_id, variant_id); null for anything else
     */
    static function parseKey( $key )
    {
        $key = (string)$key;
        if ( preg_match( '/^p([0-9a-f]{32})\z/', $key, $m ) )
            return array( 'type' => 'person', 'hash' => $m[1] );
        if ( preg_match( '/^a([1-9][0-9]{0,9})x([0-9]{1,10})\z/', $key, $m ) )
            return array( 'type' => 'anonymous', 'send_id' => (int)$m[1], 'variant_id' => (int)$m[2] );
        return null;
    }

    /** @return string what the placeholder becomes: "<key>/<signature>" */
    static function token( $key )
    {
        return $key . '/' . self::sign( $key );
    }

    // ------------------------------------------------------------------ consent

    /**
     * @param CjwNewsletterUser $newsletterUser
     * @param bool $cached use the cache of this process (the queue run)
     * @return bool the person switched the statistics category on (and confirmed it)
     */
    static function hasConsent( $newsletterUser, $cached = false )
    {
        if ( !is_object( $newsletterUser ) )
            return false;
        $id = (int)$newsletterUser->attribute( 'id' );
        if ( $cached && isset( self::$consentCache[$id] ) )
            return self::$consentCache[$id];
        $consent = false;
        if ( class_exists( 'CjwNewsletterMailPreferences' ) && CjwNewsletterMailPreferences::available()
             && class_exists( 'expMailCategoryRegistry' ) && expMailCategoryRegistry::instance()->get( self::consentCategory() ) )
        {
            try
            {
                $recipient = CjwNewsletterMailPreferences::recipientForNewsletterUser( $newsletterUser );
                if ( $recipient !== null )
                    $consent = expMailPreferences::forRecipient( $recipient )->state( self::consentCategory() ) === expMailPreferences::ON;
            }
            catch ( Exception $e )
            {
                eZDebug::writeError( 'Consent check: ' . $e->getMessage(), __METHOD__ );
                $consent = false;
            }
        }
        if ( $cached )
            self::$consentCache[$id] = $consent;
        return $consent;
    }

    /** Forgets the consent cache (between queue runs of a persistent process, and in tests). */
    static function resetCache()
    {
        self::$consentCache = array();
    }

    /**
     * The tracking key of a recipient: per person when the send counts per person and the person agreed, else anonymous.
     *
     * @return string
     */
    static function keyForItem( $sendItem, $send, $newsletterUser )
    {
        if ( self::sendMode( $send ) === self::MODE_PERSON && self::hasConsent( $newsletterUser, true ) )
            return self::personKey( $sendItem );
        return self::anonymousKey( $send->attribute( 'id' ), $sendItem->attribute( 'ab_variant_id' ) );
    }

    // ------------------------------------------------------------------ link rewriting

    /**
     * @param string $url an absolute URL as it is in the mail (entities decoded)
     * @return bool the link is rewritten to the click redirect
     */
    static function trackable( $url )
    {
        $url = trim( (string)$url );
        if ( $url === '' || strlen( $url ) > 2000 || !preg_match( '#^https?://[^/\s]+#i', $url ) )
            return false;
        // placeholders: personal links (unsubscribe, configure), filled per recipient
        if ( strpos( $url, '#_' ) !== false || strpos( $url, '[[' ) !== false || strpos( $url, '{{' ) !== false )
            return false;
        $patterns = (array)self::setting( 'TrackingSettings', 'NoTrackPatterns', array() );
        $patterns = array_merge( array( '#/newsletter/(unsubscribe|configure|subscribe|r|o)(/|$)#', '#/mailpreferences/#', '#/user/(login|logout|register|activate)#' ), $patterns );
        foreach ( $patterns as $pattern )
        {
            $pattern = trim( (string)$pattern );
            if ( $pattern !== '' && @preg_match( $pattern, $url ) )
                return false;
        }
        return true;
    }

    /**
     * Rewrites the links of a send's output XML (cjwnl_edition_send.output_xml or a cjwnl_edition_send_output row)
     * and adds the open pixel. The links are stored as cjwnl_link rows of the send.
     *
     * @param string $xml
     * @param int $sendId
     * @param array $state shared between the outputs of one send: url hash => link, position
     * @return string|false the new XML, false when it could not be read
     */
    static function rewriteOutputXml( $xml, $sendId, &$state )
    {
        if ( trim( (string)$xml ) === '' )
            return false;
        $doc = new DOMDocument();
        if ( !@$doc->loadXML( $xml ) )
            return false;
        if ( !isset( $state['links'] ) )
            $state = array( 'links' => array(), 'position' => 0, 'rewritten' => 0 );
        foreach ( $doc->getElementsByTagName( 'output_format' ) as $format )
        {
            $base = self::baseUrl( $format->getAttribute( 'ez_url' ) );
            foreach ( $format->getElementsByTagName( 'type' ) as $typeNode )
            {
                $name = $typeNode->getAttribute( 'name' );
                if ( $name !== 'html' && $name !== 'text' )
                    continue;
                $body = $typeNode->nodeValue;
                if ( strpos( $body, self::PLACEHOLDER ) !== false )
                    continue;   // already rewritten
                $context = array( 'ez_url' => rtrim( (string)$format->getAttribute( 'ez_url' ), '/' ), 'ez_root' => rtrim( (string)$format->getAttribute( 'ez_root' ), '/' ) );
                $new = $name === 'html' ? self::rewriteHtml( $body, $base, $sendId, $state, $context ) : self::rewriteText( $body, $base, $sendId, $state, $context );
                if ( $new === $body )
                    continue;
                while ( $typeNode->firstChild )
                    $typeNode->removeChild( $typeNode->firstChild );
                $typeNode->appendChild( $doc->createCDATASection( $new ) );
            }
        }
        return $doc->saveXML();
    }

    /** @return string the base of the tracking URLs: [TrackingSettings] BaseURL, else the output format's site URL */
    static function baseUrl( $ezUrl )
    {
        $base = trim( (string)self::setting( 'TrackingSettings', 'BaseURL', '' ) );
        if ( $base === '' )
            $base = trim( (string)$ezUrl );
        if ( $base === '' && class_exists( 'expMailToken' ) )
            $base = expMailToken::baseURL();
        return rtrim( $base, '/' );
    }

    /** @return string the HTML part with tracked links and the pixel */
    static function rewriteHtml( $html, $base, $sendId, &$state, $context = array() )
    {
        $self = __CLASS__;
        $html = preg_replace_callback( '#(<a\b[^>]*?\bhref\s*=\s*)(["\'])(.*?)\2#is', function ( $m ) use ( $base, $sendId, &$state, $context, $self ) {
            $url = html_entity_decode( $m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            if ( !$self::trackable( $url ) )
                return $m[0];
            $link = $self::linkFor( $sendId, $url, $state, $context );
            if ( !$link )
                return $m[0];
            $state['rewritten']++;
            return $m[1] . $m[2] . htmlspecialchars( $base . '/newsletter/r/' . $link->attribute( 'id' ) . '/' . $self::PLACEHOLDER, ENT_QUOTES, 'UTF-8' ) . $m[2];
        }, (string)$html );
        if ( self::pixelEnabled() && $base !== '' )
        {
            $pixel = '<img src="' . htmlspecialchars( $base . '/newsletter/o/' . self::PLACEHOLDER, ENT_QUOTES, 'UTF-8' ) . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;margin:0;padding:0" />';
            if ( stripos( $html, '</body>' ) !== false )
                $html = preg_replace( '#</body>#i', $pixel . '</body>', $html, 1 );
            else
                $html .= $pixel;
        }
        return $html;
    }

    /** @return string the text part with tracked links */
    static function rewriteText( $text, $base, $sendId, &$state, $context = array() )
    {
        $self = __CLASS__;
        return preg_replace_callback( '#https?://[^\s<>"\'\)\]]+#i', function ( $m ) use ( $base, $sendId, &$state, $context, $self ) {
            $url = rtrim( $m[0], '.,;:!?' );
            $tail = substr( $m[0], strlen( $url ) );
            if ( !$self::trackable( $url ) )
                return $m[0];
            $link = $self::linkFor( $sendId, $url, $state, $context );
            if ( !$link )
                return $m[0];
            $state['rewritten']++;
            return $base . '/newsletter/r/' . $link->attribute( 'id' ) . '/' . $self::PLACEHOLDER . $tail;
        }, (string)$text );
    }

    /**
     * The stored link of a URL in a send (made when it is new).
     *
     * @return CjwNewsletterLink|null
     */
    static function linkFor( $sendId, $url, &$state, $context = array() )
    {
        $hash = hash( 'sha256', $url );
        if ( isset( $state['links'][$hash] ) )
            return $state['links'][$hash];
        $link = CjwNewsletterLink::fetchByEditionSendIdAndUrlHash( $sendId, $hash );
        if ( !$link )
        {
            $link = CjwNewsletterLink::create( array( 'edition_send_id' => (int)$sendId, 'url_hash' => $hash, 'url' => $url,
                'contentobject_id' => self::contentObjectIdForUrl( $url, $context ), 'position' => ++$state['position'], 'created' => time() ) );
            $link->store();
        }
        $state['links'][$hash] = $link;
        return $link;
    }

    /**
     * The content object a site URL shows (article statistics), 0 for other URLs.
     *
     * @param string $url
     * @param array $context ez_url, ez_root of the output format
     * @return int
     */
    static function contentObjectIdForUrl( $url, $context = array() )
    {
        $path = null;
        foreach ( array( isset( $context['ez_url'] ) ? $context['ez_url'] : '', isset( $context['ez_root'] ) ? $context['ez_root'] : '' ) as $prefix )
        {
            if ( $prefix !== '' && stripos( $url, $prefix . '/' ) === 0 )
            {
                $path = substr( $url, strlen( $prefix ) + 1 );
                break;
            }
        }
        if ( $path === null )
            return 0;
        $path = trim( preg_replace( '/[?#].*$/', '', $path ), '/' );
        if ( $path === '' )
            return 0;
        $nodeId = 0;
        if ( preg_match( '#^content/view/[a-z_]+/([0-9]+)#', $path, $m ) )
            $nodeId = (int)$m[1];
        else
        {
            $found = eZURLAliasML::fetchNodeIDByPath( urldecode( $path ) );
            $nodeId = $found ? (int)$found : 0;
        }
        if ( $nodeId <= 0 )
            return 0;
        $node = eZContentObjectTreeNode::fetch( $nodeId, false, false );
        return is_array( $node ) && isset( $node['contentobject_id'] ) ? (int)$node['contentobject_id'] : 0;
    }

    // ------------------------------------------------------------------ counting

    /**
     * Adds to a total of cjwnl_stat_total.
     *
     * @param int $sendId
     * @param int $linkId 0 for send-level counts
     * @param string $type open, unique_open, click, unique_click, ...
     * @param int $time
     * @param int $n
     */
    static function addTotal( $sendId, $linkId, $type, $time = null, $n = 1 )
    {
        $db = eZDB::instance();
        $day = (int)date( 'Ymd', $time === null ? time() : (int)$time );
        $where = 'edition_send_id = ' . (int)$sendId . ' AND link_id = ' . (int)$linkId . " AND stat_type = '" . $db->escapeString( $type ) . "' AND stat_day = $day";
        $rows = $db->arrayQuery( "SELECT id FROM cjwnl_stat_total WHERE $where" );
        if ( isset( $rows[0]['id'] ) )
        {
            $db->query( 'UPDATE cjwnl_stat_total SET total = total + ' . (int)$n . ' WHERE id = ' . (int)$rows[0]['id'] );
            return;
        }
        $row = CjwNewsletterStatTotal::create( array( 'edition_send_id' => (int)$sendId, 'link_id' => (int)$linkId,
            'stat_type' => (string)$type, 'stat_day' => $day, 'total' => (int)$n ) );
        $row->store();
    }

    /**
     * An open (the pixel was loaded).
     *
     * @param string $key
     * @param string $signature
     * @param int|null $now
     * @return string what was counted: invalid, off, anonymous, person
     */
    static function recordOpen( $key, $signature, $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        if ( !self::verify( $key, $signature ) )
            return 'invalid';
        $resolved = self::resolve( $key );
        if ( !$resolved )
            return 'invalid';
        $send = $resolved['send'];
        $mode = self::sendMode( $send );
        if ( $mode === self::MODE_OFF )
            return 'off';
        $db = eZDB::instance();
        $sendId = (int)$send->attribute( 'id' );
        self::addTotal( $sendId, 0, 'open', $now );
        $item = $resolved['item'];
        if ( $item && $mode === self::MODE_PERSON && self::hasConsent( $item->attribute( 'newsletter_user_object' ) ) )
        {
            $itemId = (int)$item->attribute( 'id' );
            $first = (int)$item->attribute( 'first_opened' ) === 0 && (int)$item->attribute( 'open_count' ) === 0;
            $open = CjwNewsletterOpen::create( array( 'edition_send_id' => $sendId, 'edition_send_item_id' => $itemId, 'created' => $now ) );
            $open->store();
            $db->query( 'UPDATE cjwnl_edition_send_item SET open_count = open_count + 1' . ( $first ? ', first_opened = ' . $now : '' ) . ' WHERE id = ' . $itemId );
            if ( $first )
            {
                self::addTotal( $sendId, 0, 'unique_open', $now );
                if ( (int)$item->attribute( 'ab_variant_id' ) > 0 )
                    $db->query( 'UPDATE cjwnl_ab_variant SET open_count = open_count + 1 WHERE id = ' . (int)$item->attribute( 'ab_variant_id' ) );
            }
            return 'person';
        }
        return 'anonymous';
    }

    /**
     * A click.
     *
     * @param int $linkId
     * @param string $key '' for a link without a key (the web archive of the edition): redirected, not counted
     * @param string $signature
     * @param int|null $now
     * @return array status (not_found, invalid, uncounted, off, anonymous, person), url (where to go, '' = 404)
     */
    static function recordClick( $linkId, $key, $signature, $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        $link = (int)$linkId > 0 ? CjwNewsletterLink::fetch( (int)$linkId ) : null;
        if ( !$link || !self::safeUrl( $link->attribute( 'url' ) ) )
            return array( 'status' => 'not_found', 'url' => '' );
        $url = (string)$link->attribute( 'url' );
        if ( (string)$key === '' && (string)$signature === '' )
            return array( 'status' => 'uncounted', 'url' => $url );
        if ( !self::verify( $key, $signature ) )
            return array( 'status' => 'invalid', 'url' => '' );
        $resolved = self::resolve( $key );
        if ( !$resolved || (int)$resolved['send']->attribute( 'id' ) !== (int)$link->attribute( 'edition_send_id' ) )
            return array( 'status' => 'invalid', 'url' => '' );
        $send = $resolved['send'];
        $mode = self::sendMode( $send );
        if ( $mode === self::MODE_OFF )
            return array( 'status' => 'off', 'url' => $url );
        $db = eZDB::instance();
        $sendId = (int)$send->attribute( 'id' );
        $db->query( 'UPDATE cjwnl_link SET click_count = click_count + 1 WHERE id = ' . (int)$link->attribute( 'id' ) );
        self::addTotal( $sendId, (int)$link->attribute( 'id' ), 'click', $now );
        $item = $resolved['item'];
        if ( $item && $mode === self::MODE_PERSON && self::hasConsent( $item->attribute( 'newsletter_user_object' ) ) )
        {
            $itemId = (int)$item->attribute( 'id' );
            $firstOnLink = CjwNewsletterLinkClick::fetchListCount( array( 'link_id' => (int)$link->attribute( 'id' ), 'edition_send_item_id' => $itemId ) ) === 0;
            $firstAtAll = (int)$item->attribute( 'click_count' ) === 0;
            $click = CjwNewsletterLinkClick::create( array( 'link_id' => (int)$link->attribute( 'id' ), 'edition_send_item_id' => $itemId, 'created' => $now ) );
            $click->store();
            $db->query( 'UPDATE cjwnl_edition_send_item SET click_count = click_count + 1 WHERE id = ' . $itemId );
            if ( $firstOnLink )
                self::addTotal( $sendId, (int)$link->attribute( 'id' ), 'unique_click', $now );
            if ( $firstAtAll )
            {
                self::addTotal( $sendId, 0, 'unique_click', $now );
                if ( (int)$item->attribute( 'ab_variant_id' ) > 0 )
                    $db->query( 'UPDATE cjwnl_ab_variant SET click_count = click_count + 1 WHERE id = ' . (int)$item->attribute( 'ab_variant_id' ) );
            }
            return array( 'status' => 'person', 'url' => $url );
        }
        // anonymous: the variant of the key (clicks decide an A/B test when only totals are kept)
        $variantId = $resolved['variant_id'];
        if ( $variantId > 0 )
            $db->query( 'UPDATE cjwnl_ab_variant SET click_count = click_count + 1 WHERE id = ' . (int)$variantId . ' AND ab_test_id = ' . (int)$send->attribute( 'ab_test_id' ) );
        return array( 'status' => 'anonymous', 'url' => $url );
    }

    /** @return bool the stored URL may be redirected to (http or https) */
    static function safeUrl( $url )
    {
        return (bool)preg_match( '#^https?://[^/\s]+#i', (string)$url ) && !preg_match( '/[\r\n]/', (string)$url );
    }

    /**
     * @param string $key a valid key
     * @return array|null send, item (per person, else null), variant_id
     */
    static function resolve( $key )
    {
        $parsed = self::parseKey( $key );
        if ( !$parsed )
            return null;
        if ( $parsed['type'] === 'person' )
        {
            $item = CjwNewsletterEditionSendItem::fetchByHash( $parsed['hash'] );
            if ( !is_object( $item ) )
                return null;
            $send = CjwNewsletterEditionSend::fetch( $item->attribute( 'edition_send_id' ) );
            return is_object( $send ) ? array( 'send' => $send, 'item' => $item, 'variant_id' => (int)$item->attribute( 'ab_variant_id' ) ) : null;
        }
        $send = CjwNewsletterEditionSend::fetch( $parsed['send_id'] );
        return is_object( $send ) ? array( 'send' => $send, 'item' => null, 'variant_id' => $parsed['variant_id'] ) : null;
    }

    /** @return string the 1x1 transparent GIF of the open pixel */
    static function pixel()
    {
        return base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
    }
}

?>
