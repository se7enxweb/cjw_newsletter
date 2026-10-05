<?php
/**
 * File containing the CjwNewsletterInterestBlock class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The edition block "articles for your interests": a skin prints {cjwnl_interests_block( 5, $heading, $accent )}, the
 * output keeps the marker [[cjwnl:interests:<limit>:<settings>]], and when the mail of a subscriber is made the
 * marker becomes the articles of the list's article pool (N2's CjwNewsletterArticlePoolFinder) that carry a tag of
 * the subscriber's interests, without the articles the edition already has. Only content anonymous users may read
 * goes into a mail. A subscriber without interests, or without matching articles, gets nothing (not even the
 * heading).
 *
 * @package cjw_newsletter
 */
class CjwNewsletterInterestBlock
{
    const PATTERN = '#\[\[cjwnl:interests:(\d{1,2}):([A-Za-z0-9_-]*)\]\]#';

    /** @var array cache key => nodes */
    protected static $cache = array();
    /** @var array edition object id => object ids of its articles */
    protected static $editionArticles = array();

    /**
     * @param int $limit 0 = [InterestSettings] MaxArticlesPerBlock
     * @param string $heading the heading in the skin's language
     * @param string $accent a colour #rrggbb for the HTML part
     * @return string
     */
    static function marker( $limit = 0, $heading = '', $accent = '' )
    {
        $settings = array( 'h' => (string)$heading, 'a' => preg_match( '/^#[0-9a-f]{6}$/i', (string)$accent ) ? (string)$accent : '' );
        return '[[cjwnl:interests:' . max( 0, min( 20, (int)$limit ) ) . ':' . rtrim( strtr( base64_encode( json_encode( $settings ) ), '+/', '-_' ), '=' ) . ']]';
    }

    /** @return bool */
    static function hasMarker( $text )
    {
        return strpos( (string)$text, '[[cjwnl:interests:' ) !== false;
    }

    static function clearCache()
    {
        self::$cache = array();
        self::$editionArticles = array();
    }

    /**
     * Replaces the markers of a body.
     *
     * @param string $body
     * @param bool $html
     * @param CjwNewsletterUser|null $user null: the block is left out (archive), or shown with sample articles in a preview
     * @param array $options hash( edition_object_id, list_id, language, preview )
     * @return string
     */
    static function resolve( $body, $html, $user, $options = array() )
    {
        return preg_replace_callback( self::PATTERN, function ( $m ) use ( $html, $user, $options ) {
            $json = base64_decode( strtr( $m[2], '-_', '+/' ), true );
            $settings = $json === false ? null : json_decode( $json, true );
            $settings = is_array( $settings ) ? $settings : array();
            $limit = (int)$m[1] > 0 ? (int)$m[1] : CjwNewsletterInterestBlock::defaultLimit();
            $nodes = CjwNewsletterInterestBlock::articles( $user, $options, $limit );
            if ( !$nodes )
                return '';
            $heading = isset( $settings['h'] ) ? (string)$settings['h'] : '';
            $accent = isset( $settings['a'] ) && $settings['a'] !== '' ? (string)$settings['a'] : '#1f5f8b';
            return $html ? CjwNewsletterInterestBlock::renderHtml( $nodes, $heading, $accent ) : CjwNewsletterInterestBlock::renderText( $nodes, $heading );
        }, (string)$body );
    }

    /** @return int [InterestSettings] MaxArticlesPerBlock */
    static function defaultLimit()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'InterestSettings', 'MaxArticlesPerBlock' ) ? max( 1, min( 20, (int)$ini->variable( 'InterestSettings', 'MaxArticlesPerBlock' ) ) ) : 5;
    }

    /**
     * The articles of a subscriber.
     *
     * @param CjwNewsletterUser|null $user
     * @param array $options hash( edition_object_id, list_id, language, preview )
     * @param int $limit
     * @return eZContentObjectTreeNode[]
     */
    static function articles( $user, $options, $limit )
    {
        if ( !class_exists( 'CjwNewsletterArticlePool' ) || !class_exists( 'CjwNewsletterArticlePoolFinder' ) )
            return array();
        $listId = isset( $options['list_id'] ) ? (int)$options['list_id'] : 0;
        $preview = !empty( $options['preview'] ) && !is_object( $user );
        if ( !is_object( $user ) && !$preview )
            return array();
        $interests = is_object( $user ) ? CjwNewsletterInterests::forUser( $user->attribute( 'id' ) ) : array();
        if ( !$interests && !$preview )
            return array();
        $tagIds = array();
        $topics = array();
        foreach ( $interests as $interest )
        {
            if ( (int)$interest->attribute( 'eztags_id' ) > 0 )
                $tagIds[] = (int)$interest->attribute( 'eztags_id' );
            else
                $topics[] = strtolower( (string)$interest->attribute( 'identifier' ) );
        }
        $editionObjectId = isset( $options['edition_object_id'] ) ? (int)$options['edition_object_id'] : 0;
        $exclude = self::editionArticleObjectIds( $editionObjectId );
        if ( $editionObjectId > 0 )
            $exclude[] = $editionObjectId;
        $language = isset( $options['language'] ) ? (string)$options['language'] : '';
        sort( $tagIds );
        sort( $topics );
        $key = md5( json_encode( array( $listId, $tagIds, $topics, $exclude, $language, $limit, $preview ) ) );
        if ( isset( self::$cache[$key] ) )
            return self::$cache[$key];
        $nodes = array();
        try
        {
            $pool = CjwNewsletterArticlePool::forList( $listId );
            $find = array( 'limit' => $topics ? $limit * 4 : $limit, 'exclude_object_ids' => array_values( array_unique( $exclude ) ),
                           'anonymous' => true );
            if ( $language !== '' )
                $find['language'] = $language;
            if ( $tagIds && !$topics )
                $find['tag_ids'] = $tagIds;
            foreach ( (array)CjwNewsletterArticlePoolFinder::find( $pool, $find ) as $node )
            {
                if ( !$node instanceof eZContentObjectTreeNode )
                    continue;
                if ( !$preview && $topics && !self::matchesTopics( $node, $topics, $tagIds ) )
                    continue;
                $nodes[] = $node;
                if ( count( $nodes ) >= $limit )
                    break;
            }
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( $e->getMessage(), __METHOD__ );
            $nodes = array();
        }
        if ( count( self::$cache ) > 200 )
            self::$cache = array();
        return self::$cache[$key] = $nodes;
    }

    /**
     * An article matches a topic without a tag when its keywords (ezkeyword) or its tags (eztags keywords) name the
     * topic's identifier, or when it carries one of the tags of the other interests.
     */
    static function matchesTopics( $node, $topics, $tagIds )
    {
        foreach ( $node->attribute( 'data_map' ) as $attribute )
        {
            $type = $attribute->attribute( 'data_type_string' );
            if ( $type === 'ezkeyword' || $type === 'eztags' )
            {
                $words = array();
                foreach ( preg_split( '/[,|]+/', (string)$attribute->attribute( 'data_text' ) ) as $word )
                {
                    $word = strtolower( trim( $word ) );
                    if ( $word !== '' )
                    {
                        $words[] = $word;
                        $words[] = trim( preg_replace( '/[^a-z0-9]+/', '_', $word ), '_' );
                    }
                }
                if ( array_intersect( $topics, $words ) )
                    return true;
                if ( $type === 'eztags' && $tagIds && preg_match_all( '/\d+/', (string)$attribute->attribute( 'data_text' ), $m ) && array_intersect( $tagIds, array_map( 'intval', $m[0] ) ) )
                    return true;
            }
        }
        return false;
    }

    /** @return int[] the object ids of the articles under an edition */
    static function editionArticleObjectIds( $editionObjectId )
    {
        $editionObjectId = (int)$editionObjectId;
        if ( $editionObjectId <= 0 )
            return array();
        if ( isset( self::$editionArticles[$editionObjectId] ) )
            return self::$editionArticles[$editionObjectId];
        $ids = array();
        $object = eZContentObject::fetch( $editionObjectId );
        $mainNodeId = $object ? (int)$object->attribute( 'main_node_id' ) : 0;
        if ( $mainNodeId > 0 )
            foreach ( (array)eZContentObjectTreeNode::subTreeByNodeID( array( 'Depth' => 1, 'DepthOperator' => 'eq' ), $mainNodeId ) as $child )
                $ids[] = (int)$child->attribute( 'contentobject_id' );
        return self::$editionArticles[$editionObjectId] = $ids;
    }

    /** @return array hash( title, intro, url ) of an article */
    static function articleData( $node )
    {
        $map = $node->attribute( 'data_map' );
        $title = '';
        foreach ( array( 'title', 'short_title', 'name' ) as $identifier )
            if ( isset( $map[$identifier] ) && $map[$identifier]->attribute( 'has_content' ) && trim( (string)$map[$identifier]->attribute( 'data_text' ) ) !== '' )
            {
                $title = trim( (string)$map[$identifier]->attribute( 'data_text' ) );
                break;
            }
        if ( $title === '' )
            $title = (string)$node->attribute( 'name' );
        $intro = '';
        foreach ( array( 'intro', 'short_description', 'teaser_intro', 'description', 'summary' ) as $identifier )
            if ( isset( $map[$identifier] ) && $map[$identifier]->attribute( 'has_content' ) )
            {
                $attribute = $map[$identifier];
                $intro = in_array( $attribute->attribute( 'data_type_string' ), array( 'ezxmltext', 'ezrichtext' ), true )
                    ? CjwNewsletterPlainText::xmlText( $attribute ) : trim( (string)$attribute->attribute( 'data_text' ) );
                break;
            }
        $intro = preg_replace( '/\s+/u', ' ', $intro );
        if ( mb_strlen( $intro, 'UTF-8' ) > 220 )
            $intro = rtrim( mb_substr( $intro, 0, 217, 'UTF-8' ) ) . '...';
        return array( 'title' => $title, 'intro' => $intro,
                      'url' => CjwNewsletterRenderingOperators::absoluteUrl( '/' . ltrim( (string)$node->attribute( 'url_alias' ), '/' ) ) );
    }

    /** @return string the block as an e-mail table (inline styles) */
    static function renderHtml( $nodes, $heading, $accent )
    {
        $h = function ( $s ) { return htmlspecialchars( (string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); };
        $out = '<table role="presentation" class="cjwnl-interests" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 16px 0;">';
        if ( $heading !== '' )
            $out .= '<tr><td style="padding:0 0 8px 0;border-bottom:2px solid ' . $h( $accent ) . ';font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:24px;font-weight:bold;color:#222222;">' . $h( $heading ) . '</td></tr>';
        foreach ( $nodes as $node )
        {
            $a = self::articleData( $node );
            $out .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e5e5e5;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;color:#333333;">'
                  . '<a href="' . $h( $a['url'] ) . '" style="color:' . $h( $accent ) . ';font-weight:bold;text-decoration:none;">' . $h( $a['title'] ) . '</a>'
                  . ( $a['intro'] !== '' ? '<br />' . $h( $a['intro'] ) : '' ) . '</td></tr>';
        }
        return $out . '</table>';
    }

    /** @return string the block as plain text */
    static function renderText( $nodes, $heading )
    {
        $lines = array();
        if ( $heading !== '' )
        {
            $lines[] = $heading;
            $lines[] = str_repeat( '-', max( 3, min( 72, mb_strlen( $heading, 'UTF-8' ) ) ) );
            $lines[] = '';
        }
        foreach ( $nodes as $node )
        {
            $a = self::articleData( $node );
            $lines[] = '* ' . $a['title'];
            if ( $a['intro'] !== '' )
                foreach ( explode( "\n", CjwNewsletterPlainText::wrap( $a['intro'], 70 ) ) as $line )
                    $lines[] = '  ' . $line;
            $lines[] = '  ' . $a['url'];
            $lines[] = '';
        }
        return "\n" . implode( "\n", $lines ) . "\n";
    }
}

?>
