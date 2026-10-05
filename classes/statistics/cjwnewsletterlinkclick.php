<?php
/**
 * File containing the CjwNewsletterLinkClick class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * One click on a link; per person (send item) only with consent, else the item is 0.
 *
 * Table cjwnl_link_click (cjw_newsletter 4.2.0, area N4 Statistics; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterLinkClick extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'link_id' => array( 'name' => 'LinkId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'edition_send_item_id' => array( 'name' => 'EditionSendItemId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterLinkClick',
            'name' => 'cjwnl_link_click' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterLinkClick
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterLinkClick( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterLinkClick|null
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
     * @return CjwNewsletterLinkClick[]
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
     * @return CjwNewsletterLinkClick[] the rows with these values (index cjwnl_link_click_link), newest id first
     */
    static function fetchListByLinkId( $linkId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'link_id' => (int)$linkId ), $limit, $offset );
    }

    /**
     * @return CjwNewsletterLinkClick[] the rows with these values (index cjwnl_link_click_item), newest id first
     */
    static function fetchListByEditionSendItemId( $editionSendItemId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'edition_send_item_id' => (int)$editionSendItemId ), $limit, $offset );
    }

    /**
     * @return CjwNewsletterLinkClick[] the rows with these values (index cjwnl_link_click_created), newest id first
     */
    static function fetchListByCreated( $created, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'created' => (int)$created ), $limit, $offset );
    }
}
