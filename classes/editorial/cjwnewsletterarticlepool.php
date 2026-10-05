<?php
/**
 * File containing the CjwNewsletterArticlePool class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * Where articles for editions come from (nodes, classes, sections, tags, states, age), per list or the global default.
 *
 * Table cjwnl_article_pool (cjw_newsletter 4.2.0, area N2 Editorial; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterArticlePool extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'list_contentobject_id' => array( 'name' => 'ListContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'name' => array( 'name' => 'Name', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'is_default' => array( 'name' => 'IsDefault', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'parent_node_id_array_string' => array( 'name' => 'ParentNodeIdArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'class_identifier_array_string' => array( 'name' => 'ClassIdentifierArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'section_id_array_string' => array( 'name' => 'SectionIdArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'tag_id_array_string' => array( 'name' => 'TagIdArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'state_id_array_string' => array( 'name' => 'StateIdArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'max_age_days' => array( 'name' => 'MaxAgeDays', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'max_items' => array( 'name' => 'MaxItems', 'datatype' => 'integer', 'default' => 10, 'required' => false ),
                'sort_by' => array( 'name' => 'SortBy', 'datatype' => 'string', 'default' => 'published', 'required' => false ),
                'filter_data' => array( 'name' => 'FilterData', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'creator_contentobject_id' => array( 'name' => 'CreatorContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterArticlePool',
            'name' => 'cjwnl_article_pool' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterArticlePool
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterArticlePool( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterArticlePool|null
     */
    static function fetch( $id )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'id' => (int)$id ), true );
        return $object ? $object : null;
    }

    /**
     * @param array|null $conditions field => value (eZPersistentObject conditions)
     * @param int $limit 0 = all
     * @param int $offset
     * @param array|null $sorts field => asc|desc, null = newest id first
     * @return CjwNewsletterArticlePool[]
     */
    static function fetchList( $conditions = null, $limit = 0, $offset = 0, $sorts = null )
    {
        $limits = (int)$limit > 0 ? array( 'limit' => (int)$limit, 'offset' => (int)$offset ) : null;
        $list = eZPersistentObject::fetchObjectList( self::definition(), null, $conditions,
            $sorts === null ? array( 'id' => 'desc' ) : $sorts, $limits, true );
        return is_array( $list ) ? $list : array();
    }

    /**
     * @param array|null $conditions
     * @return int
     */
    static function fetchListCount( $conditions = null )
    {
        return (int)eZPersistentObject::count( self::definition(), $conditions );
    }

    /**
     * @return CjwNewsletterArticlePool[] the rows with these values (index cjwnl_article_pool_list), newest id first
     */
    static function fetchListByListContentobjectId( $listContentobjectId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'list_contentobject_id' => (int)$listContentobjectId ), $limit, $offset );
    }
}
