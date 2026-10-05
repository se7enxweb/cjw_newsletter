<?php
/**
 * File containing the CjwNewsletterPlainText class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The text part of a newsletter: rich text as plain text, wrapping, and the tidying of a rendered text part.
 *
 * @package cjw_newsletter
 */
class CjwNewsletterPlainText
{
    /**
     * Rich text of an attribute as plain text: ezxmltext through the plaintext tag views, imported DocBook
     * (ezrichtext) through CjwNewsletterRichText.
     *
     * @param eZContentObjectAttribute|string $attribute an attribute, or the stored XML
     * @return string
     */
    static function xmlText( $attribute )
    {
        $xml = is_object( $attribute ) ? (string)$attribute->attribute( 'data_text' ) : (string)$attribute;
        if ( trim( $xml ) === '' )
            return '';
        if ( CjwNewsletterRichText::isRichText( $xml ) )
            return CjwNewsletterRichText::toText( $xml );
        if ( !class_exists( 'eZXHTMLXMLOutput' ) )
            return self::tidy( strip_tags( $xml ) );
        $handler = new CjwNewsletterPlainTextXMLOutput( $xml, false, is_object( $attribute ) ? $attribute : null );
        $text = $handler->outputText();
        return self::tidy( (string)$text );
    }

    /**
     * Tidies a text part: no tabs or spaces at line ends, at most one empty line in a row, no empty lines at the
     * start and the end.
     *
     * @param string $text
     * @return string
     */
    static function tidy( $text )
    {
        $text = str_replace( array( "\r\n", "\r" ), "\n", (string)$text );
        $text = preg_replace( '/[ \t]+\n/', "\n", $text );
        $text = preg_replace( "/\n{3,}/", "\n\n", $text );
        return trim( $text, "\n" );
    }

    /**
     * The final text part of a skin that writes plain text (TextFormat=plain): entities of the templates undone,
     * markup a template still left removed, tidied.
     *
     * @param string $text
     * @return string
     */
    static function finish( $text )
    {
        $text = (string)$text;
        // a template may still have printed an HTML comment or a stray tag: never in a text part
        $text = preg_replace( '#<!--.*?-->#s', '', $text );
        $text = preg_replace( '#</?(?:p|div|span|br|b|strong|i|em|table|tr|td|th|tbody|thead|font|center)\b[^>]*>#i', '', $text );
        $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $lines = array();
        foreach ( explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $text ) ) as $line )
            $lines[] = rtrim( $line );
        return self::tidy( implode( "\n", $lines ) ) . "\n";
    }

    /**
     * Wraps text at $width columns, line by line; a line that starts with spaces, "- " or "1. " keeps its indent on
     * the following lines. Words longer than the width (links) are not broken.
     *
     * @param string $text
     * @param int $width
     * @return string
     */
    static function wrap( $text, $width = 72 )
    {
        $out = array();
        foreach ( explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", (string)$text ) ) as $line )
        {
            if ( mb_strlen( $line, 'UTF-8' ) <= $width )
            {
                $out[] = $line;
                continue;
            }
            $indent = preg_match( '/^(\s*(?:[-*]|\d+\.)?\s*)/u', $line, $m ) ? str_repeat( ' ', mb_strlen( $m[1], 'UTF-8' ) ) : '';
            $words = preg_split( '/ +/u', trim( $line ) );
            $current = mb_substr( $line, 0, mb_strlen( $line, 'UTF-8' ) - mb_strlen( ltrim( $line ), 'UTF-8' ), 'UTF-8' );
            $first = true;
            foreach ( $words as $word )
            {
                $candidate = ( $first ? $current : $current . ' ' ) . $word;
                if ( !$first && mb_strlen( $candidate, 'UTF-8' ) > $width )
                {
                    $out[] = rtrim( $current );
                    $current = $indent . $word;
                }
                else
                    $current = $candidate;
                $first = false;
            }
            $out[] = rtrim( $current );
        }
        return implode( "\n", $out );
    }

    /**
     * Rows as aligned text columns (the plain view of a table or a matrix).
     *
     * @param array[] $rows rows of cell strings, the first may be the header
     * @param bool $header the first row is a header (a line of dashes under it)
     * @return string
     */
    static function table( $rows, $header = false )
    {
        $widths = array();
        foreach ( $rows as $row )
            foreach ( array_values( (array)$row ) as $i => $cell )
                $widths[$i] = max( isset( $widths[$i] ) ? $widths[$i] : 0, min( 40, mb_strlen( trim( (string)$cell ), 'UTF-8' ) ) );
        $lines = array();
        foreach ( array_values( $rows ) as $r => $row )
        {
            $cells = array();
            foreach ( array_values( (array)$row ) as $i => $cell )
            {
                $cell = preg_replace( '/\s+/u', ' ', trim( (string)$cell ) );
                $cells[] = $cell . str_repeat( ' ', max( 0, $widths[$i] - mb_strlen( $cell, 'UTF-8' ) ) );
            }
            $lines[] = rtrim( implode( ' | ', $cells ) );
            if ( $header && $r === 0 )
            {
                $rule = array();
                foreach ( $widths as $w )
                    $rule[] = str_repeat( '-', max( 1, $w ) );
                $lines[] = implode( '-+-', $rule );
            }
        }
        return implode( "\n", $lines );
    }
}

?>
