<?php
/**
 * File containing the CjwNewsletterArticlePoolFinder class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * Finds the articles of an article pool: the published content under the pool's nodes that matches its classes,
 * sections, tags, states and age, with the editor's or the caller's filters on top.
 *
 * Every filter is a content fetch filter (the parameters of fetch( content, tree ), applied by
 * eZContentObjectTreeNode::subTreeByNodeID()); nothing is put into SQL by hand, and every value is cast first.
 * Tags use the extended attribute filter of eztags when that extension is active, and are ignored otherwise.
 *
 * Used by the auto-fill of the recurring sends, by the article picker of the editors, and by the block "articles
 * for your interests" of the rendering (tag_ids and language per subscriber).
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterArticlePoolFinder
{
    /** the most articles one call returns */
    const MAX_LIMIT = 200;

    /**
     * @param CjwNewsletterArticlePool $pool
     * @param array $options all optional:
     *   since               int Unix time: only content published after it (on top of the pool's max_age_days)
     *   until               int Unix time: only content published before it
     *   limit               int how many (default: the pool's max_items; at most MAX_LIMIT)
     *   offset              int
     *   tag_ids             int[] only content with at least one of these tags (and, if the pool has tags, one of those)
     *   language            string locale the content is fetched in (e.g. ger-DE); its translation when it has one
     *   exclude_object_ids  int[] content object ids to leave out (articles an edition carries already)
     *   class_identifiers   string[] narrows the pool's classes (an editor's filter); a class outside the pool is ignored
     *   section_ids         int[] narrows the pool's sections
     *   state_ids           int[] narrows the pool's object states
     *   sort_by             published | modified | priority | name (default: the pool's sort_by)
     *   anonymous           bool true = only content the anonymous user may read (what a newsletter may show); default
     *                       false = the permissions of the current user
     * @return eZContentObjectTreeNode[] main nodes, newest (or by sort_by) first
     */
    static function find( $pool, $options = array() )
    {
        $params = self::fetchParameters( $pool, $options );
        if ( $params === false )
            return array();
        $parents = $params['__parents'];
        unset( $params['__parents'] );
        $nodes = eZContentObjectTreeNode::subTreeByNodeID( $params, count( $parents ) === 1 ? $parents[0] : $parents );
        return self::unique( is_array( $nodes ) ? $nodes : array(), isset( $params['Limit'] ) ? $params['Limit'] : 0 );
    }

    /**
     * @return int how many articles find() would return without limit and offset
     */
    static function count( $pool, $options = array() )
    {
        $params = self::fetchParameters( $pool, $options );
        if ( $params === false )
            return 0;
        $parents = $params['__parents'];
        unset( $params['__parents'], $params['Limit'], $params['Offset'], $params['SortBy'] );
        return (int)eZContentObjectTreeNode::subTreeCountByNodeID( $params, count( $parents ) === 1 ? $parents[0] : $parents );
    }

    /**
     * The fetch parameters of the pool and the options, every value cast.
     *
     * @return array|false the parameters of subTreeByNodeID() plus __parents, false when nothing can match
     */
    static function fetchParameters( $pool, $options = array() )
    {
        if ( !$pool instanceof CjwNewsletterArticlePool )
            return false;
        $options = is_array( $options ) ? $options : array();

        $parents = $pool->parentNodeIds();
        if ( !$parents )
            return false;

        // classes: the pool's, narrowed by the option; the further filter of the pool takes some out
        $classes = $pool->classIdentifiers();
        if ( !empty( $options['class_identifiers'] ) )
        {
            $wanted = CjwNewsletterArticlePool::fromArrayString( CjwNewsletterArticlePool::toArrayString( (array)$options['class_identifiers'] ), false );
            $classes = $classes ? array_values( array_intersect( $classes, $wanted ) ) : $wanted;
            if ( !$classes )
                return false;
        }
        $extra = $pool->filterArray();
        if ( $extra['exclude_class_identifiers'] && $classes )
        {
            $classes = array_values( array_diff( $classes, $extra['exclude_class_identifiers'] ) );
            if ( !$classes )
                return false;
        }

        $filters = array();

        // age: the pool's max_age_days and the option since, the later one wins
        $since = isset( $options['since'] ) ? (int)$options['since'] : 0;
        if ( (int)$pool->attribute( 'max_age_days' ) > 0 )
            $since = max( $since, time() - (int)$pool->attribute( 'max_age_days' ) * 86400 );
        if ( $since > 0 )
            $filters[] = array( 'published', '>', $since );
        if ( !empty( $options['until'] ) && (int)$options['until'] > 0 )
            $filters[] = array( 'published', '<', (int)$options['until'] );

        $sections = self::narrow( $pool->sectionIds(), isset( $options['section_ids'] ) ? $options['section_ids'] : array() );
        if ( $sections === false )
            return false;
        if ( $sections )
            $filters[] = array( 'section', 'in', $sections );

        $states = self::narrow( $pool->stateIds(), isset( $options['state_ids'] ) ? $options['state_ids'] : array() );
        if ( $states === false )
            return false;
        if ( $states )
            $filters[] = array( 'state', 'in', $states );

        $exclude = self::integers( isset( $options['exclude_object_ids'] ) ? $options['exclude_object_ids'] : array() );
        if ( $exclude )
            $filters[] = array( 'contentobject_id', 'not_in', $exclude );

        $limit = isset( $options['limit'] ) ? (int)$options['limit'] : (int)$pool->attribute( 'max_items' );
        $limit = max( 1, min( self::MAX_LIMIT, $limit > 0 ? $limit : 10 ) );
        $offset = isset( $options['offset'] ) ? max( 0, (int)$options['offset'] ) : 0;

        $sortBy = isset( $options['sort_by'] ) && in_array( $options['sort_by'], CjwNewsletterArticlePool::$sortFields, true )
            ? $options['sort_by'] : (string)$pool->attribute( 'sort_by' );
        switch ( $sortBy )
        {
            case 'modified': $sort = array( 'modified', false ); break;
            case 'priority': $sort = array( 'priority', true ); break;
            case 'name': $sort = array( 'name', true ); break;
            default: $sort = array( 'published', false );
        }

        $params = array( 'Depth' => false,
                         'MainNodeOnly' => true,
                         'IgnoreVisibility' => false,
                         'Limit' => $limit,
                         'Offset' => $offset,
                         'SortBy' => array( $sort, array( 'node_id', false ) ),
                         'AsObject' => true,
                         '__parents' => $parents );
        if ( $classes )
        {
            $params['ClassFilterType'] = 'include';
            $params['ClassFilterArray'] = $classes;
        }
        if ( $filters )
            $params['AttributeFilter'] = array_merge( array( 'and' ), $filters );

        // tags: one group for the pool's tags and one for the option's; a node needs a tag of each group
        $tagGroups = array();
        if ( $pool->tagIds() )
            $tagGroups[] = $pool->tagIds();
        $optionTags = self::integers( isset( $options['tag_ids'] ) ? $options['tag_ids'] : array() );
        if ( $optionTags )
            $tagGroups[] = $optionTags;
        if ( $tagGroups )
        {
            if ( !class_exists( 'eZTagsAttributeFilter' ) )
            {
                // asked for tags, and there is no tag system: nothing can match an interest
                if ( $optionTags )
                    return false;
            }
            else
            {
                $params['ExtendedAttributeFilter'] = array( 'id' => 'TagsAttributeAndMultipleFilter', 'params' => array( 'tag_id' => $tagGroups ) );
            }
        }

        if ( !empty( $options['language'] ) && preg_match( '/^[a-z]{3}-[A-Z]{2}(@[a-z]+)?$/', (string)$options['language'] ) )
            $params['Language'] = (string)$options['language'];

        if ( !empty( $options['anonymous'] ) )
        {
            $limitation = self::anonymousLimitation();
            if ( $limitation === false )
                return false;
            $params['Limitation'] = $limitation;
        }
        return $params;
    }

    /**
     * The read permission of the anonymous user, in the form the fetch takes as Limitation.
     *
     * @return array|false array() = no limitation, the policies otherwise, false = may read nothing
     */
    static function anonymousLimitation()
    {
        $id = (int)eZINI::instance()->variable( 'UserSettings', 'AnonymousUserID' );
        $user = eZUser::fetch( $id );
        if ( !$user instanceof eZUser )
            return false;
        $access = $user->hasAccessTo( 'content', 'read' );
        if ( $access['accessWord'] === 'yes' )
            return array();
        if ( $access['accessWord'] === 'limited' && isset( $access['policies'] ) )
            return $access['policies'];
        return false;
    }

    /**
     * @return int[]|false the pool's values narrowed by the option's ($pool empty = any, so the option's); false when
     *                     the option asks for values the pool does not allow
     */
    protected static function narrow( $poolValues, $optionValues )
    {
        $optionValues = self::integers( $optionValues );
        if ( !$optionValues )
            return $poolValues;
        if ( !$poolValues )
            return $optionValues;
        $both = array_values( array_intersect( $poolValues, $optionValues ) );
        return $both ? $both : false;
    }

    /** @return int[] the values > 0, cast, without repeats */
    protected static function integers( $values )
    {
        $result = array();
        foreach ( (array)$values as $value )
            if ( is_scalar( $value ) && (int)$value > 0 && !in_array( (int)$value, $result, true ) )
                $result[] = (int)$value;
        return $result;
    }

    /** A node may come twice through the joins of the tag filter: each once, at most $limit. */
    protected static function unique( $nodes, $limit )
    {
        $seen = array();
        $result = array();
        foreach ( $nodes as $node )
        {
            if ( !$node instanceof eZContentObjectTreeNode )
                continue;
            $id = (int)$node->attribute( 'node_id' );
            if ( isset( $seen[$id] ) )
                continue;
            $seen[$id] = true;
            $result[] = $node;
            if ( $limit > 0 && count( $result ) >= $limit )
                break;
        }
        return $result;
    }
}
