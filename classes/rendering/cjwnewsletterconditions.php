<?php
/**
 * File containing the CjwNewsletterConditions class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The "newsletter condition" of an edition: a part of the text that only some subscribers get.
 *
 * The custom tag newsletter_condition (ezxmltext) and the eztemplate newsletter_condition (ezrichtext) render their
 * content between two markers when the edition's output is made:
 *
 *   [[cjwnl:if:<id>:<condition>]] ... [[cjwnl:else:<id>]] ... [[cjwnl:endif:<id>]]
 *
 * <condition> is the tag's settings as base64url JSON, <id> a token unique in the output, so that conditions can be
 * nested. The markers are plain text: they survive the HTML part and the text part alike, and the text conversion of
 * the old skins (which strips tags and comments) leaves them in place. resolve() then keeps or drops each part per
 * subscriber at send time, the same way in both parts.
 *
 * A condition has any of these settings; all that are set must match (and):
 *   field     a subscriber field: salutation, first_name, last_name, organisation, email, language,
 *             custom_data_text_1..4 (or custom_1..4)
 *   operator  eq (default when value is set), ne, contains, starts, empty, not_empty (default without value), in
 *   value     the value to compare with (case-insensitive); for "in" a list separated by commas
 *   list      content object ids of lists, separated by commas: the send is for one of them
 *   language  locales separated by commas (ger-DE,eng-GB): the language the subscriber gets
 *   interest  interest identifiers or eztags ids separated by commas: the subscriber picked one of them
 *   negate    1: the opposite
 *
 * Contexts (resolve()): mode "subscriber" evaluates for the subscriber, "all" keeps every if-part (preview without
 * a subscriber, test mails), "anonymous" evaluates without a subscriber (the web archive): a condition on a
 * subscriber field or an interest is false there, list and language are evaluated.
 *
 * @package cjw_newsletter
 */
class CjwNewsletterConditions
{
    const PATTERN = '#\[\[cjwnl:if:([a-z0-9]{6,40}):([A-Za-z0-9_-]*)\]\]#';

    /** @var string[] the open markers of the current rendering (template operators nest them) */
    static $stack = array();
    /** @var int */
    static $counter = 0;
    /** @var bool true while a newsletter output is rendered (the output command): only then the tags leave markers; on
     *  the web site the content of a condition is shown as it is */
    static $active = false;

    /** @return string[] the subscriber fields a condition can test */
    static function fields()
    {
        return array( 'salutation', 'first_name', 'last_name', 'organisation', 'email', 'language',
                      'custom_data_text_1', 'custom_data_text_2', 'custom_data_text_3', 'custom_data_text_4' );
    }

    /** @return string[] the operators */
    static function operators()
    {
        return array( 'eq', 'ne', 'contains', 'starts', 'empty', 'not_empty', 'in' );
    }

    /**
     * Cleans the settings of a tag: only known keys, plain strings.
     *
     * @param array $settings
     * @return array
     */
    static function normalize( $settings )
    {
        $out = array();
        foreach ( array( 'field', 'operator', 'value', 'list', 'language', 'interest', 'negate' ) as $key )
        {
            if ( !isset( $settings[$key] ) || is_array( $settings[$key] ) || is_object( $settings[$key] ) )
                continue;
            $value = trim( (string)$settings[$key] );
            if ( $value !== '' )
                $out[$key] = $value;
        }
        if ( isset( $out['field'] ) )
        {
            $field = strtolower( $out['field'] );
            if ( preg_match( '/^custom_([1-4])$/', $field, $m ) )
                $field = 'custom_data_text_' . $m[1];
            if ( in_array( $field, self::fields(), true ) )
                $out['field'] = $field;
            else
                unset( $out['field'] );
        }
        if ( isset( $out['operator'] ) && !in_array( strtolower( $out['operator'] ), self::operators(), true ) )
            unset( $out['operator'] );
        else if ( isset( $out['operator'] ) )
            $out['operator'] = strtolower( $out['operator'] );
        if ( isset( $out['negate'] ) )
            $out['negate'] = in_array( strtolower( $out['negate'] ), array( '1', 'true', 'yes', 'on' ), true ) ? '1' : '';
        if ( isset( $out['negate'] ) && $out['negate'] === '' )
            unset( $out['negate'] );
        return $out;
    }

    /** @return string the condition as it travels in a marker */
    static function encode( $settings )
    {
        $json = json_encode( self::normalize( $settings ) );
        return rtrim( strtr( base64_encode( (string)$json ), '+/', '-_' ), '=' );
    }

    /** @return array the condition of a marker ('' or damaged: array()) */
    static function decode( $encoded )
    {
        $json = base64_decode( strtr( (string)$encoded, '-_', '+/' ), true );
        $data = $json === false ? null : json_decode( $json, true );
        return is_array( $data ) ? self::normalize( $data ) : array();
    }

    /**
     * The opening marker of a condition; close() ends the innermost one.
     *
     * @param array $settings
     * @return string
     */
    static function open( $settings )
    {
        if ( !self::$active )
            return '';
        $id = substr( md5( uniqid( '', true ) . ( ++self::$counter ) ), 0, 12 );
        self::$stack[] = $id;
        return '[[cjwnl:if:' . $id . ':' . self::encode( $settings ) . ']]';
    }

    /** @return string the else marker of the innermost open condition, '' without one */
    static function elseMarker()
    {
        if ( !self::$active )
            return '';
        return self::$stack ? '[[cjwnl:else:' . end( self::$stack ) . ']]' : '';
    }

    /** @return string the closing marker of the innermost open condition, '' without one */
    static function close()
    {
        if ( !self::$active )
            return '';
        return self::$stack ? '[[cjwnl:endif:' . array_pop( self::$stack ) . ']]' : '';
    }

    /**
     * Wraps finished content in a condition (the converters of rich text, which see the whole content at once).
     */
    static function wrap( $settings, $content, $elseContent = '' )
    {
        $open = self::open( $settings );
        $else = $elseContent !== '' ? self::elseMarker() . $elseContent : '';
        return $open . $content . $else . self::close();
    }

    /** @return bool the text has condition markers */
    static function hasMarkers( $text )
    {
        return strpos( (string)$text, '[[cjwnl:if:' ) !== false;
    }

    /**
     * Keeps or drops every conditional part of a text.
     *
     * @param string $text
     * @param array $context hash( mode: subscriber|all|anonymous, user: CjwNewsletterUser|null, list_id: int,
     *                       language: string, interests: string[] identifiers and tag ids )
     * @return string
     */
    static function resolve( $text, $context )
    {
        $text = (string)$text;
        if ( !self::hasMarkers( $text ) )
            return $text;
        // innermost first: a condition whose content has no other opening marker
        $guard = 0;
        while ( $guard++ < 200 && preg_match_all( self::PATTERN, $text, $all, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) )
        {
            $changed = false;
            foreach ( array_reverse( $all ) as $match )
            {
                $id = $match[1][0];
                $start = $match[0][1];
                $openLength = strlen( $match[0][0] );
                $endMarker = '[[cjwnl:endif:' . $id . ']]';
                $end = strpos( $text, $endMarker, $start + $openLength );
                if ( $end === false )
                {
                    // a marker without its end: drop the marker, keep the text
                    $text = substr( $text, 0, $start ) . substr( $text, $start + $openLength );
                    $changed = true;
                    break;
                }
                $inner = substr( $text, $start + $openLength, $end - $start - $openLength );
                if ( preg_match( self::PATTERN, $inner ) )
                    continue;
                $elseMarker = '[[cjwnl:else:' . $id . ']]';
                $elsePos = strpos( $inner, $elseMarker );
                $ifPart = $elsePos === false ? $inner : substr( $inner, 0, $elsePos );
                $elsePart = $elsePos === false ? '' : substr( $inner, $elsePos + strlen( $elseMarker ) );
                $keep = self::evaluate( self::decode( $match[2][0] ), $context ) ? $ifPart : $elsePart;
                $text = substr( $text, 0, $start ) . $keep . substr( $text, $end + strlen( $endMarker ) );
                $changed = true;
                break;
            }
            if ( !$changed )
                break;
        }
        // stray markers (an else or end without its start)
        return preg_replace( '#\[\[cjwnl:(else|endif):[a-z0-9]{6,40}\]\]#', '', $text );
    }

    /**
     * @param array $condition normalised settings
     * @param array $context see resolve()
     * @return bool
     */
    static function evaluate( $condition, $context )
    {
        $mode = isset( $context['mode'] ) ? $context['mode'] : 'subscriber';
        if ( $mode === 'all' )
            return true;
        $result = true;
        if ( isset( $condition['list'] ) )
        {
            $listId = isset( $context['list_id'] ) ? (int)$context['list_id'] : 0;
            $result = $result && in_array( $listId, array_map( 'intval', self::split( $condition['list'] ) ), true );
        }
        if ( isset( $condition['language'] ) )
        {
            $language = isset( $context['language'] ) ? strtolower( (string)$context['language'] ) : '';
            $result = $result && in_array( $language, array_map( 'strtolower', self::split( $condition['language'] ) ), true );
        }
        $user = isset( $context['user'] ) && is_object( $context['user'] ) ? $context['user'] : null;
        if ( isset( $condition['interest'] ) )
        {
            if ( $user === null )
                $result = false;
            else
            {
                $have = array_map( 'strtolower', array_map( 'strval', isset( $context['interests'] ) ? (array)$context['interests'] : array() ) );
                $want = array_map( 'strtolower', self::split( $condition['interest'] ) );
                $result = $result && count( array_intersect( $want, $have ) ) > 0;
            }
        }
        if ( isset( $condition['field'] ) )
        {
            if ( $user === null )
                $result = false;
            else
            {
                $field = $condition['field'];
                $actual = $field === 'language' && isset( $context['language'] ) ? (string)$context['language'] : (string)$user->attribute( $field );
                $result = $result && self::compare( $actual, isset( $condition['operator'] ) ? $condition['operator'] : null,
                                                    isset( $condition['value'] ) ? $condition['value'] : null );
            }
        }
        if ( !isset( $condition['list'] ) && !isset( $condition['language'] ) && !isset( $condition['interest'] ) && !isset( $condition['field'] ) )
            $result = true;
        return isset( $condition['negate'] ) ? !$result : $result;
    }

    /**
     * @param string $actual
     * @param string|null $operator
     * @param string|null $value
     * @return bool
     */
    static function compare( $actual, $operator, $value )
    {
        $actual = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $actual ), 'UTF-8' ) : strtolower( trim( $actual ) );
        $value = $value === null ? null : ( function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $value ), 'UTF-8' ) : strtolower( trim( $value ) ) );
        if ( $operator === null )
            $operator = $value === null ? 'not_empty' : 'eq';
        switch ( $operator )
        {
            case 'empty':
                return $actual === '';
            case 'not_empty':
                return $actual !== '';
            case 'ne':
                return $actual !== (string)$value;
            case 'contains':
                return $value !== null && $value !== '' && strpos( $actual, $value ) !== false;
            case 'starts':
                return $value !== null && $value !== '' && strpos( $actual, $value ) === 0;
            case 'in':
                return in_array( $actual, self::split( (string)$value ), true );
            case 'eq':
            default:
                return $actual === (string)$value;
        }
    }

    /** @return string[] the values of a list separated by commas, trimmed, without empty ones */
    static function split( $string )
    {
        $out = array();
        foreach ( explode( ',', (string)$string ) as $part )
        {
            $part = trim( $part );
            if ( $part !== '' )
                $out[] = $part;
        }
        return $out;
    }

    /**
     * A short description of a condition, for the editor's preview.
     *
     * @return string
     */
    static function describe( $settings )
    {
        $c = self::normalize( $settings );
        $parts = array();
        if ( isset( $c['field'] ) )
            $parts[] = $c['field'] . ' ' . ( isset( $c['operator'] ) ? $c['operator'] : ( isset( $c['value'] ) ? 'eq' : 'not_empty' ) ) . ( isset( $c['value'] ) ? ' "' . $c['value'] . '"' : '' );
        foreach ( array( 'list', 'language', 'interest' ) as $key )
            if ( isset( $c[$key] ) )
                $parts[] = $key . ' ' . $c[$key];
        $text = $parts ? implode( ' and ', $parts ) : 'always';
        return isset( $c['negate'] ) ? 'not (' . $text . ')' : $text;
    }
}

?>
