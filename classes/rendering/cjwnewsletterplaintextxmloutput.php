<?php
/**
 * File containing the CjwNewsletterPlainTextXMLOutput class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * Renders ezxmltext as plain text for the text part of a newsletter: the same tag rules as the XHTML output, with
 * the tag templates of design:content/datatype/view/plaintext/ezxmltags/ and text that is not HTML-escaped (the text
 * part is not HTML). Use CjwNewsletterPlainText::xmlText(), which also tidies the result.
 *
 * @package cjw_newsletter
 */
class CjwNewsletterPlainTextXMLOutput extends eZXHTMLXMLOutput
{
    public $TemplatesPath = 'design:content/datatype/view/plaintext/ezxmltags/';

    public function __construct( $xmlData, $aliasedType, $contentObjectAttribute = null )
    {
        parent::__construct( $xmlData, $aliasedType, $contentObjectAttribute );
        $this->OutputTags['li']['initHandler'] = 'initHandlerPlainLi';
        $this->OutputTags['ol']['initHandler'] = 'initHandlerPlainList';
        $this->OutputTags['ul']['initHandler'] = 'initHandlerPlainList';
        $this->OutputTags['td']['initHandler'] = 'initHandlerPlainCell';
        $this->OutputTags['th']['initHandler'] = 'initHandlerPlainCell';
    }

    /** The number and the kind of list of an item, and how deep the list is. */
    function initHandlerPlainLi( $element, &$attributes, &$siblingParams, &$parentParams )
    {
        $siblingParams['list_count'] = isset( $siblingParams['list_count'] ) ? $siblingParams['list_count'] + 1 : 1;
        return array( 'tpl_vars' => array( 'list_count' => $siblingParams['list_count'],
                                           'list_type' => $element->parentNode ? $element->parentNode->nodeName : 'ul',
                                           'list_depth' => self::listDepth( $element ) ) );
    }

    function initHandlerPlainList( $element, &$attributes, &$siblingParams, &$parentParams )
    {
        return array( 'tpl_vars' => array( 'list_depth' => self::listDepth( $element ) ) );
    }

    function initHandlerPlainCell( $element, &$attributes, &$siblingParams, &$parentParams )
    {
        $siblingParams['cell_count'] = isset( $siblingParams['cell_count'] ) ? $siblingParams['cell_count'] + 1 : 1;
        return array( 'tpl_vars' => array( 'cell_count' => $siblingParams['cell_count'] ) );
    }

    /** @return int 1 for a top list, 2 for a list in a list ... */
    static function listDepth( $element )
    {
        $depth = 0;
        for ( $node = $element; $node; $node = $node->parentNode )
            if ( in_array( $node->nodeName, array( 'ul', 'ol' ), true ) )
                $depth++;
        return max( 1, $depth );
    }

    /** Text as it is: no HTML escaping; the spaces and line breaks of the stored XML are not text. */
    function renderText( $element, $childrenOutput, $vars )
    {
        if ( $element->parentNode && $element->parentNode->nodeName === 'literal' )
            return array( true, $element->textContent );
        if ( trim( $element->textContent ) === ''
             && ( ( $element->previousSibling && $element->previousSibling->nodeName === 'line' )
                  || ( $element->nextSibling && $element->nextSibling->nodeName === 'line' ) ) )
            return array( true, '' );
        $text = str_replace( "\xC2\xA0", ' ', $element->textContent );
        if ( $element->parentNode && $element->parentNode->localName === 'literallayout' )
            return array( true, $text );
        $text = str_replace( array( "\r", "\n", "\t" ), array( '', '', ' ' ), $text );
        return array( true, preg_replace( '/ +/', ' ', $text ) );
    }
}

?>
