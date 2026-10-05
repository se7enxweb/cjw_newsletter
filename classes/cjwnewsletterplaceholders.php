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
 * @version //autogentag//
 * @package cjw_newsletter
 */
class CjwNewsletterPlaceholders
{
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
        if ( $personalize )
        {
            $values['[[name]]'] = (string)$user->attribute( 'name' );
            $values['[[salutation_name]]'] = (string)$user->attribute( 'salutation_name' );
            $values['[[first_name]]'] = (string)$user->attribute( 'first_name' );
            $values['[[last_name]]'] = (string)$user->attribute( 'last_name' );
        }
        return $values;
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
