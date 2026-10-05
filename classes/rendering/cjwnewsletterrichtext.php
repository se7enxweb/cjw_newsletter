<?php
/**
 * File containing the CjwNewsletterRichText class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * Rich text in the DocBook format of ezrichtext (sections, para, emphasis, link, title, lists, tables, literallayout,
 * programlisting, blockquote, ezembed, eztemplate) for a newsletter: as HTML with inline styles for the HTML part and
 * as plain text for the text part. The eztemplate "newsletter_condition" becomes a condition (CjwNewsletterConditions)
 * in both, from the same settings (ezconfig/ezvalue).
 *
 * Every text is escaped in the HTML output; links are taken only with http, https, mailto and the ez* schemes, and a
 * link to content becomes the absolute address of its node.
 *
 * @package cjw_newsletter
 */
class CjwNewsletterRichText
{
    const DOCBOOK = 'http://docbook.org/ns/docbook';
    const XLINK = 'http://www.w3.org/1999/xlink';

    /** @var int the depth of the lists while converting */
    protected $listDepth = 0;
    /** @var string text or html */
    protected $format = 'text';

    /** @return bool the XML is DocBook (ezrichtext) */
    static function isRichText( $xml )
    {
        return strpos( (string)$xml, self::DOCBOOK ) !== false;
    }

    /** @return string the rich text as plain text */
    static function toText( $xml )
    {
        $converter = new self();
        $converter->format = 'text';
        $root = $converter->load( $xml );
        return $root ? CjwNewsletterPlainText::tidy( $converter->children( $root ) ) : '';
    }

    /** @return string the rich text as HTML with inline styles */
    static function toHtml( $xml )
    {
        $converter = new self();
        $converter->format = 'html';
        $root = $converter->load( $xml );
        return $root ? trim( $converter->children( $root ) ) : '';
    }

    /** @return DOMElement|null */
    protected function load( $xml )
    {
        $xml = trim( (string)$xml );
        if ( $xml === '' )
            return null;
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $old = libxml_use_internal_errors( true );
        $ok = $doc->loadXML( $xml, LIBXML_NONET );
        libxml_clear_errors();
        libxml_use_internal_errors( $old );
        return $ok ? $doc->documentElement : null;
    }

    protected function h( $text )
    {
        return htmlspecialchars( (string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
    }

    protected function children( DOMNode $node )
    {
        $out = '';
        foreach ( $node->childNodes as $child )
            $out .= $this->node( $child );
        return $out;
    }

    protected function node( DOMNode $node )
    {
        if ( $node instanceof DOMText )
        {
            $parent = $node->parentNode ? $node->parentNode->localName : '';
            $text = $node->textContent;
            if ( !in_array( $parent, array( 'literallayout', 'programlisting' ), true ) )
                $text = preg_replace( '/\s+/u', ' ', $text );
            return $this->format === 'html' ? $this->h( $text ) : $text;
        }
        if ( !$node instanceof DOMElement )
            return '';
        $html = $this->format === 'html';
        switch ( $node->localName )
        {
            case 'section':
                return $this->children( $node );
            case 'para':
                $inner = trim( $this->children( $node ) );
                if ( $inner === '' )
                    return '';
                return $html ? '<p style="margin:0 0 1em 0;">' . $inner . "</p>\n" : $inner . "\n\n";
            case 'title':
                $level = max( 1, min( 6, (int)$this->attr( $node, 'level', 2 ) ) );
                $inner = trim( $this->children( $node ) );
                if ( $html )
                    return '<h' . $level . ' style="margin:1em 0 .5em 0;">' . $inner . '</h' . $level . ">\n";
                $plain = trim( preg_replace( '/\s+/u', ' ', $inner ) );
                return "\n" . $plain . "\n" . str_repeat( $level <= 1 ? '=' : '-', max( 3, min( 72, mb_strlen( $plain, 'UTF-8' ) ) ) ) . "\n\n";
            case 'emphasis':
                $role = (string)$node->getAttribute( 'role' );
                $inner = $this->children( $node );
                if ( !$html )
                    return $role === 'strong' ? '*' . $inner . '*' : ( $role === 'underlined' ? '_' . $inner . '_' : $inner );
                if ( $role === 'strong' )
                    return '<strong>' . $inner . '</strong>';
                if ( $role === 'underlined' )
                    return '<u>' . $inner . '</u>';
                if ( $role === 'strikedout' )
                    return '<s>' . $inner . '</s>';
                return '<em>' . $inner . '</em>';
            case 'subscript':
                return $html ? '<sub>' . $this->children( $node ) . '</sub>' : $this->children( $node );
            case 'superscript':
                return $html ? '<sup>' . $this->children( $node ) . '</sup>' : $this->children( $node );
            case 'link':
                $href = self::resolveHref( (string)$node->getAttributeNS( self::XLINK, 'href' ) );
                $inner = $this->children( $node );
                if ( $href === '' )
                    return $inner;
                if ( $html )
                    return '<a href="' . $this->h( $href ) . '">' . $inner . '</a>';
                $label = trim( $inner );
                return ( $label === '' || $label === $href || 'mailto:' . $label === $href ) ? $href : $label . ' (' . $href . ')';
            case 'itemizedlist':
            case 'orderedlist':
                return $this->listing( $node, $node->localName === 'orderedlist' );
            case 'listitem':
                return $this->children( $node );
            case 'blockquote':
                $inner = trim( $this->children( $node ) );
                if ( $html )
                    return '<blockquote style="margin:0 0 1em 0;padding:0 0 0 1em;border-left:3px solid #cccccc;">' . $inner . "</blockquote>\n";
                return implode( "\n", array_map( function ( $l ) { return '> ' . $l; }, explode( "\n", CjwNewsletterPlainText::tidy( $inner ) ) ) ) . "\n\n";
            case 'literallayout':
            case 'programlisting':
                $inner = $this->children( $node );
                if ( $html )
                    return '<pre style="margin:0 0 1em 0;white-space:pre-wrap;font-family:Consolas,Menlo,monospace;">' . $inner . "</pre>\n";
                return $inner . "\n\n";
            case 'informaltable':
            case 'table':
                return $this->table( $node );
            case 'caption':
                return '';
            case 'anchor':
                return '';
            case 'ezembed':
            case 'ezembedinline':
                return $this->embed( $node );
            case 'eztemplate':
            case 'eztemplateinline':
                return $this->template( $node );
            case 'ezconfig':
            case 'ezattribute':
                return '';
            default:
                return $this->children( $node );
        }
    }

    protected function attr( DOMElement $node, $localName, $default = '' )
    {
        foreach ( $node->attributes as $attribute )
            if ( $attribute->localName === $localName )
                return $attribute->value;
        return $default;
    }

    protected function listing( DOMElement $node, $ordered )
    {
        $this->listDepth++;
        $items = array();
        $n = 0;
        foreach ( $node->childNodes as $child )
        {
            if ( !$child instanceof DOMElement || $child->localName !== 'listitem' )
                continue;
            $n++;
            $items[] = array( $n, trim( $this->children( $child ) ) );
        }
        $this->listDepth--;
        if ( $this->format === 'html' )
        {
            $tag = $ordered ? 'ol' : 'ul';
            $out = '<' . $tag . ' style="margin:0 0 1em 0;padding:0 0 0 1.5em;">';
            foreach ( $items as $item )
                $out .= '<li style="margin:0 0 .3em 0;">' . preg_replace( '#^<p style="margin:0 0 1em 0;">(.*)</p>\s*$#s', '$1', $item[1] ) . '</li>';
            return $out . '</' . $tag . ">\n";
        }
        $indent = str_repeat( '  ', $this->listDepth );
        $lines = array();
        foreach ( $items as $item )
        {
            $bullet = $ordered ? $item[0] . '. ' : '- ';
            $body = explode( "\n", CjwNewsletterPlainText::tidy( $item[1] ) );
            $first = array_shift( $body );
            $lines[] = $indent . $bullet . $first;
            foreach ( $body as $line )
                $lines[] = $line === '' ? '' : $indent . str_repeat( ' ', strlen( $bullet ) ) . $line;
        }
        return "\n" . implode( "\n", $lines ) . "\n\n";
    }

    protected function table( DOMElement $node )
    {
        $rows = array();
        $header = false;
        foreach ( $node->getElementsByTagNameNS( self::DOCBOOK, 'tr' ) as $tr )
        {
            $cells = array();
            foreach ( $tr->childNodes as $cell )
            {
                if ( !$cell instanceof DOMElement || !in_array( $cell->localName, array( 'td', 'th' ), true ) )
                    continue;
                if ( $cell->localName === 'th' && !$rows )
                    $header = true;
                $cells[] = array( $cell->localName, trim( $this->children( $cell ) ) );
            }
            $rows[] = $cells;
        }
        if ( $this->format === 'html' )
        {
            $out = '<table cellpadding="4" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 1em 0;">';
            foreach ( $rows as $cells )
            {
                $out .= '<tr>';
                foreach ( $cells as $cell )
                    $out .= '<' . $cell[0] . ' style="border:1px solid #dddddd;text-align:left;vertical-align:top;">' . $cell[1] . '</' . $cell[0] . '>';
                $out .= '</tr>';
            }
            return $out . "</table>\n";
        }
        $plain = array();
        foreach ( $rows as $cells )
        {
            $row = array();
            foreach ( $cells as $cell )
                $row[] = preg_replace( '/\s+/u', ' ', $cell[1] );
            $plain[] = $row;
        }
        return "\n" . CjwNewsletterPlainText::table( $plain, $header ) . "\n\n";
    }

    protected function embed( DOMElement $node )
    {
        $href = (string)$node->getAttributeNS( self::XLINK, 'href' );
        $object = null;
        if ( preg_match( '#^ezcontent://(\d+)#', $href, $m ) )
            $object = eZContentObject::fetch( (int)$m[1] );
        else if ( preg_match( '#^ezlocation://(\d+)#', $href, $m ) )
        {
            $treeNode = eZContentObjectTreeNode::fetch( (int)$m[1] );
            $object = $treeNode ? $treeNode->attribute( 'object' ) : null;
        }
        if ( !$object instanceof eZContentObject || !$object->attribute( 'can_read' ) )
            return '';
        $name = (string)$object->attribute( 'name' );
        $url = self::resolveHref( 'ezcontent://' . $object->attribute( 'id' ) );
        $inline = $node->localName === 'ezembedinline';
        if ( $this->format === 'html' )
        {
            $image = self::imageOf( $object );
            if ( $image )
                return '<img src="' . $this->h( $image['url'] ) . '" alt="' . $this->h( $image['alt'] !== '' ? $image['alt'] : $name ) . '" width="' . (int)$image['width'] . '" style="display:block;max-width:100%;height:auto;border:0;" />' . ( $inline ? '' : "\n" );
            return $url !== '' ? '<a href="' . $this->h( $url ) . '">' . $this->h( $name ) . '</a>' . ( $inline ? '' : "\n" ) : $this->h( $name );
        }
        return '[' . $name . ']' . ( $url !== '' && !self::imageOf( $object ) ? ' (' . $url . ')' : '' ) . ( $inline ? '' : "\n\n" );
    }

    /** @return array|false hash( url, alt, width ) of the image of an image object */
    static function imageOf( eZContentObject $object )
    {
        $map = $object->dataMap();
        foreach ( $map as $attribute )
        {
            if ( $attribute->attribute( 'data_type_string' ) !== 'ezimage' || !$attribute->attribute( 'has_content' ) )
                continue;
            $content = $attribute->attribute( 'content' );
            $alias = $content ? $content->attribute( 'medium' ) : null;
            if ( !is_array( $alias ) || empty( $alias['url'] ) )
                continue;
            return array( 'url' => CjwNewsletterRenderingOperators::absoluteUrl( '/' . ltrim( $alias['url'], '/' ) ),
                          'alt' => isset( $alias['alternative_text'] ) ? (string)$alias['alternative_text'] : '',
                          'width' => isset( $alias['width'] ) ? (int)$alias['width'] : 300 );
        }
        return false;
    }

    protected function template( DOMElement $node )
    {
        $name = (string)$node->getAttribute( 'name' );
        $content = '';
        $settings = array();
        foreach ( $node->childNodes as $child )
        {
            if ( !$child instanceof DOMElement )
                continue;
            if ( $child->localName === 'ezcontent' )
                $content .= $this->children( $child );
            else if ( $child->localName === 'ezconfig' )
                foreach ( $child->getElementsByTagNameNS( '*', 'ezvalue' ) as $value )
                    $settings[(string)$value->getAttribute( 'key' )] = trim( $value->textContent );
        }
        if ( $name === 'newsletter_condition' && self::conditionTagEnabled() )
            return CjwNewsletterConditions::wrap( $settings, $content );
        return $content;
    }

    /** @return bool [ConditionTagSettings] ConditionTag is enabled */
    static function conditionTagEnabled()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return !$ini->hasVariable( 'ConditionTagSettings', 'ConditionTag' ) || $ini->variable( 'ConditionTagSettings', 'ConditionTag' ) !== 'disabled';
    }

    /**
     * An address of a rich text link for a mail: content links become the absolute address of their node, only safe
     * schemes are kept.
     *
     * @param string $href
     * @return string '' when the link is dropped
     */
    static function resolveHref( $href )
    {
        $href = trim( $href );
        if ( $href === '' )
            return '';
        $anchor = '';
        if ( ( $pos = strpos( $href, '#' ) ) !== false )
        {
            $anchor = substr( $href, $pos );
            $href = substr( $href, 0, $pos );
        }
        $node = null;
        if ( preg_match( '#^ezlocation://(\d+)$#', $href, $m ) )
            $node = eZContentObjectTreeNode::fetch( (int)$m[1] );
        else if ( preg_match( '#^ezcontent://(\d+)$#', $href, $m ) )
        {
            $object = eZContentObject::fetch( (int)$m[1] );
            $node = $object ? $object->attribute( 'main_node' ) : null;
        }
        else if ( preg_match( '#^ezurl://(\d+)$#', $href, $m ) && class_exists( 'eZURL' ) )
        {
            $url = eZURL::url( (int)$m[1] );
            return is_string( $url ) && $url !== '' ? self::resolveHref( $url ) : '';
        }
        if ( $node instanceof eZContentObjectTreeNode )
            return CjwNewsletterRenderingOperators::absoluteUrl( '/' . $node->attribute( 'url_alias' ) ) . $anchor;
        if ( preg_match( '#^ez[a-z]+://#i', $href ) )
            return '';
        if ( $href === '' && $anchor !== '' )
            return '';
        if ( preg_match( '#^(https?:|mailto:)#i', $href ) )
            return $href . $anchor;
        if ( strpos( $href, '/' ) === 0 )
            return CjwNewsletterRenderingOperators::absoluteUrl( $href ) . $anchor;
        return '';
    }
}

?>
