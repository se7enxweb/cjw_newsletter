<?php
/**
 * File containing the CjwNewsletterStatTotal class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * Anonymous totals per send, link, type and day; kept after the per-person rows are removed.
 *
 * Table cjwnl_stat_total (cjw_newsletter 4.2.0, area N4 Statistics; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterStatTotal extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'edition_send_id' => array( 'name' => 'EditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'link_id' => array( 'name' => 'LinkId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'stat_type' => array( 'name' => 'StatType', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'stat_day' => array( 'name' => 'StatDay', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'total' => array( 'name' => 'Total', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterStatTotal',
            'name' => 'cjwnl_stat_total' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterStatTotal
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterStatTotal( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterStatTotal|null
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
     * @return CjwNewsletterStatTotal[]
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
     * @return CjwNewsletterStatTotal|null the row with these values (unique index cjwnl_stat_total_key)
     */
    static function fetchByEditionSendIdAndLinkIdAndStatTypeAndStatDay( $editionSendId, $linkId, $statType, $statDay )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'edition_send_id' => (int)$editionSendId, 'link_id' => (int)$linkId, 'stat_type' => (string)$statType, 'stat_day' => (int)$statDay ), true );
        return $object ? $object : null;
    }

    /**
     * @return CjwNewsletterStatTotal[] the rows with these values (index cjwnl_stat_total_day), newest id first
     */
    static function fetchListByStatTypeAndStatDay( $statType, $statDay, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'stat_type' => (string)$statType, 'stat_day' => (int)$statDay ), $limit, $offset );
    }
}
