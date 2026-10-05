<?php
/**
 * File containing the CjwNewsletterLink class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * A link of a send that the click redirect knows (only stored URLs are redirected to).
 *
 * Table cjwnl_link (cjw_newsletter 4.2.0, area N4 Statistics; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterLink extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'edition_send_id' => array( 'name' => 'EditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'url_hash' => array( 'name' => 'UrlHash', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'url' => array( 'name' => 'Url', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'contentobject_id' => array( 'name' => 'ContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'position' => array( 'name' => 'Position', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'click_count' => array( 'name' => 'ClickCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterLink',
            'name' => 'cjwnl_link' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterLink
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterLink( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterLink|null
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
     * @return CjwNewsletterLink[]
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
     * @return CjwNewsletterLink|null the row with these values (unique index cjwnl_link_send_url)
     */
    static function fetchByEditionSendIdAndUrlHash( $editionSendId, $urlHash )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'edition_send_id' => (int)$editionSendId, 'url_hash' => (string)$urlHash ), true );
        return $object ? $object : null;
    }

    /**
     * @return CjwNewsletterLink[] the rows with these values (index cjwnl_link_object), newest id first
     */
    static function fetchListByContentobjectId( $contentobjectId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'contentobject_id' => (int)$contentobjectId ), $limit, $offset );
    }
}
