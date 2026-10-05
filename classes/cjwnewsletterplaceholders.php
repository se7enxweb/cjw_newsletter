<?php
/**
 * File containing the CjwNewsletterPlaceholders class
 *
 * @copyright Copyright (C) 2007-2026 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur, 7x. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2.0 (or any later version)
 * @version //autogentag//
 * @package cjw_newsletter
 * @filesource
 */
/**
 * Replaces the placeholders of an edition ([[name]], [[first_name]] ..., #_hash_unsubscribe_# ...) for one recipient.
 *
 * A value goes into the HTML part escaped (htmlspecialchars, UTF-8): a subscriber who calls himself
 * "<script>" or "Smith & Sons" gets that text in the mail, not markup. The text part and the subject are plain
 * text and get the value as it is.
 *
 * The placeholders (4.2.0):
 *   always              #_hash_unsubscribe_#, #_hash_configure_#, #_hash_item_#, #_hash_edition_#,
 *                       [[list_name]], [[unsubscribe_url]], [[configure_url]], [[manage_url]]
 *   personalised lists  [[name]], [[salutation_name]], [[first_name]], [[last_name]], [[email]], [[organisation]],
 *                       [[custom_1]] .. [[custom_4]], and [[<key>]] of cjw_newsletter.ini [PlaceholderSettings]
 *                       Placeholders[<key>]=<subscriber attribute>
 *
 * Other channels (the SMS text) use valuesForSubscriber() and replaceInText().
 *
 * @version //autogentag//
 * @package cjw_newsletter
 */
class CjwNewsletterPlaceholders
{
    /** @var array list object id => name, for one process */
    protected static $listNames = array();

    /**
     * The placeholders of a recipient and their values.
     *
     * @param CjwNewsletterEditionSendItem $sendItem
     * @param CjwNewsletterEditionSend $sendObject
     * @param string $unsubscribeHash the hash of the subscription
     * @param CjwNewsletterUser $user
     * @param bool $personalize true when the list or send personalises the content (the [[...]] names)
     * @return array placeholder => value
     */
    static function valuesForRecipient( $sendItem, $sendObject, $unsubscribeHash, $user, $personalize )
    {
        $values = array(
            '#_hash_unsubscribe_#' => (string)$unsubscribeHash,
            '#_hash_configure_#' => (string)$user->attribute( 'hash' ),
            '#_hash_item_#' => (string)$sendItem->attribute( 'hash' ),
            '#_hash_edition_#' => (string)$sendObject->attribute( 'hash' ),
        );
        $listObjectId = is_object( $sendObject ) ? (int)$sendObject->attribute( 'list_contentobject_id' ) : 0;
        return array_merge( $values, self::valuesForSubscriber( $user, $listObjectId, $personalize, (string)$unsubscribeHash ) );
    }

    /**
     * The placeholders of a subscriber without a send (another channel, a preview): the links, the list name and,
     * when personalised, the subscriber's fields. Raw values: escape them for HTML with escapeForHtml().
     *
     * @param CjwNewsletterUser $user
     * @param int $listObjectId the list (its name, the unsubscribe link of the subscription); 0 = none
     * @param bool $personalize the subscriber's fields too
     * @param string|null $unsubscribeHash the hash of the subscription, null = looked up
     * @return array placeholder => raw value
     */
    static function valuesForSubscriber( $user, $listObjectId = 0, $personalize = true, $unsubscribeHash = null )
    {
        $values = array();
        $listObjectId = (int)$listObjectId;
        if ( $unsubscribeHash === null && $listObjectId > 0 && is_object( $user ) )
        {
            $subscription = CjwNewsletterSubscription::fetchByListIdAndNewsletterUserId( $listObjectId, $user->attribute( 'id' ) );
            $unsubscribeHash = is_object( $subscription ) ? (string)$subscription->attribute( 'hash' ) : '';
        }
        $base = self::siteBaseUrl();
        $configureUrl = is_object( $user ) && (string)$user->attribute( 'hash' ) !== '' ? $base . '/newsletter/configure/' . $user->attribute( 'hash' ) : '';
        $values['[[list_name]]'] = self::listName( $listObjectId );
        $values['[[unsubscribe_url]]'] = (string)$unsubscribeHash !== '' ? $base . '/newsletter/unsubscribe/' . $unsubscribeHash : $configureUrl;
        $values['[[configure_url]]'] = $configureUrl;
        $values['[[manage_url]]'] = self::manageUrl( $user, $configureUrl );
        if ( $personalize && is_object( $user ) )
        {
            $values['[[name]]'] = (string)$user->attribute( 'name' );
            $values['[[salutation_name]]'] = (string)$user->attribute( 'salutation_name' );
            $values['[[first_name]]'] = (string)$user->attribute( 'first_name' );
            $values['[[last_name]]'] = (string)$user->attribute( 'last_name' );
            $values['[[email]]'] = (string)$user->attribute( 'email' );
            $values['[[organisation]]'] = (string)$user->attribute( 'organisation' );
            for ( $i = 1; $i <= 4; $i++ )
                $values['[[custom_' . $i . ']]'] = (string)$user->attribute( 'custom_data_text_' . $i );
            foreach ( self::iniPlaceholders() as $key => $attribute )
            {
                if ( isset( $values['[[' . $key . ']]'] ) || !$user->hasAttribute( $attribute ) )
                    continue;
                $value = $user->attribute( $attribute );
                if ( !is_object( $value ) && !is_array( $value ) )
                    $values['[[' . $key . ']]'] = (string)$value;
            }
        }
        return $values;
    }

    /** @return string[] key => subscriber attribute, from [PlaceholderSettings] Placeholders[] */
    static function iniPlaceholders()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $out = array();
        if ( $ini->hasVariable( 'PlaceholderSettings', 'Placeholders' ) )
            foreach ( (array)$ini->variable( 'PlaceholderSettings', 'Placeholders' ) as $key => $attribute )
                if ( preg_match( '/^[a-z0-9_]{1,40}$/', (string)$key ) && preg_match( '/^[a-z0-9_]{1,60}$/', (string)$attribute )
                     && !in_array( (string)$attribute, array( 'hash', 'data_xml', 'note', 'remote_id' ), true ) )
                    $out[(string)$key] = (string)$attribute;
        return $out;
    }

    /** @return string[] the placeholder names an editor can use, for the documentation and the admin pages */
    static function names()
    {
        $names = array( '[[name]]', '[[salutation_name]]', '[[first_name]]', '[[last_name]]', '[[email]]', '[[organisation]]',
                        '[[custom_1]]', '[[custom_2]]', '[[custom_3]]', '[[custom_4]]', '[[list_name]]', '[[unsubscribe_url]]',
                        '[[configure_url]]', '[[manage_url]]' );
        foreach ( array_keys( self::iniPlaceholders() ) as $key )
            if ( !in_array( '[[' . $key . ']]', $names, true ) )
                $names[] = '[[' . $key . ']]';
        return $names;
    }

    /** @return string the name of a list ('' without one) */
    static function listName( $listObjectId )
    {
        $listObjectId = (int)$listObjectId;
        if ( $listObjectId <= 0 )
            return '';
        if ( !isset( self::$listNames[$listObjectId] ) )
        {
            $object = eZContentObject::fetch( $listObjectId );
            self::$listNames[$listObjectId] = $object instanceof eZContentObject ? (string)$object->attribute( 'name' ) : '';
        }
        return self::$listNames[$listObjectId];
    }

    /** @return string scheme, host and siteaccess path of the links in a mail, no trailing slash */
    static function siteBaseUrl()
    {
        if ( class_exists( 'CjwNewsletterRenderingOperators' ) && CjwNewsletterRenderingOperators::$baseUrl !== '' )
            return rtrim( CjwNewsletterRenderingOperators::$baseUrl, '/' );
        if ( class_exists( 'expMailToken' ) )
            return expMailToken::baseURL();
        $siteURL = trim( (string)eZINI::instance()->variable( 'SiteSettings', 'SiteURL' ) );
        return rtrim( preg_match( '#^https?://#i', $siteURL ) ? $siteURL : 'https://' . $siteURL, '/' );
    }

    /** @return string the personal link of the kernel preference page, else the configure page of the newsletter */
    static function manageUrl( $user, $fallback )
    {
        if ( !is_object( $user ) || !class_exists( 'expMailPreferencesService' ) || !class_exists( 'expMailRecipient' ) )
            return $fallback;
        try
        {
            $email = (string)$user->attribute( 'email' );
            if ( $email === '' )
                return $fallback;
            $recipient = (int)$user->attribute( 'ez_user_id' ) > 0 ? expMailRecipient::fromUserId( (int)$user->attribute( 'ez_user_id' ) ) : expMailRecipient::fromAddress( $email );
            return $recipient ? expMailPreferencesService::manageURL( $recipient ) : $fallback;
        }
        catch ( Throwable $e )
        {
            return $fallback;
        }
    }

    /**
     * @param array $values placeholder => raw value
     * @return array placeholder => value escaped for HTML
     */
    static function escapeForHtml( $values )
    {
        $escaped = array();
        foreach ( $values as $placeholder => $value )
            $escaped[$placeholder] = htmlspecialchars( (string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
        return $escaped;
    }

    /**
     * Replaces the placeholders in one text.
     *
     * @param string $text
     * @param array $values placeholder => raw value
     * @param bool $html true: the text is HTML, the values are escaped
     * @return string
     */
    static function replaceInText( $text, $values, $html = false )
    {
        return str_replace( array_keys( $values ), array_values( $html ? self::escapeForHtml( $values ) : $values ), (string)$text );
    }

    /**
     * Replaces the placeholders in the bodies of a mail.
     *
     * @param array $bodies output format => content, e.g. array( 'html' => ..., 'text' => ... )
     * @param array $values placeholder => raw value
     * @return array the bodies with 'html' and 'text' always set; html escaped, every other part raw
     */
    static function replaceInBodies( $bodies, $values )
    {
        $result = array( 'html' => '', 'text' => '' );
        $search = array_keys( $values );
        $raw = array_values( $values );
        $html = array_values( self::escapeForHtml( $values ) );
        foreach ( $bodies as $index => $string )
            $result[$index] = str_replace( $search, $index === 'html' ? $html : $raw, (string)$string );
        return $result;
    }

    /**
     * Replaces the placeholders in a subject (plain text, a mail header: raw values).
     *
     * @param string $subject
     * @param array $values placeholder => raw value
     * @return string
     */
    static function replaceInSubject( $subject, $values )
    {
        return str_replace( array_keys( $values ), array_values( $values ), (string)$subject );
    }
}
