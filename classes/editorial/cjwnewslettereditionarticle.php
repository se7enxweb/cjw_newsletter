<?php
/**
 * File containing the CjwNewsletterEditionArticle class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * An article taken into an edition from a pool, by an editor or by the auto-fill.
 *
 * Table cjwnl_edition_article (cjw_newsletter 4.2.0, area N2 Editorial; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterEditionArticle extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'edition_contentobject_id' => array( 'name' => 'EditionContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'contentobject_id' => array( 'name' => 'ContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'article_pool_id' => array( 'name' => 'ArticlePoolId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'position' => array( 'name' => 'Position', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'added_by' => array( 'name' => 'AddedBy', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'creator_contentobject_id' => array( 'name' => 'CreatorContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterEditionArticle',
            'name' => 'cjwnl_edition_article' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterEditionArticle
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterEditionArticle( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterEditionArticle|null
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
     * @return CjwNewsletterEditionArticle[]
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
     * @return CjwNewsletterEditionArticle|null the row with these values (unique index cjwnl_edition_article_edition)
     */
    static function fetchByEditionContentobjectIdAndContentobjectId( $editionContentobjectId, $contentobjectId )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'edition_contentobject_id' => (int)$editionContentobjectId, 'contentobject_id' => (int)$contentobjectId ), true );
        return $object ? $object : null;
    }

    /**
     * @return CjwNewsletterEditionArticle[] the rows with these values (index cjwnl_edition_article_object), newest id first
     */
    static function fetchListByContentobjectId( $contentobjectId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'contentobject_id' => (int)$contentobjectId ), $limit, $offset );
    }
}
