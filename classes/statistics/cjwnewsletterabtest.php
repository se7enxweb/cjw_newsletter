<?php
/**
 * File containing the CjwNewsletterAbTest class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * The A/B subject test of a send: samples, criterion, wait time, winner.
 *
 * Table cjwnl_ab_test (cjw_newsletter 4.2.0, area N4 Statistics; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterAbTest extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'edition_send_id' => array( 'name' => 'EditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'status' => array( 'name' => 'Status', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'sample_percent' => array( 'name' => 'SamplePercent', 'datatype' => 'integer', 'default' => 10, 'required' => false ),
                'variant_count' => array( 'name' => 'VariantCount', 'datatype' => 'integer', 'default' => 2, 'required' => false ),
                'criterion' => array( 'name' => 'Criterion', 'datatype' => 'string', 'default' => 'open', 'required' => false ),
                'wait_seconds' => array( 'name' => 'WaitSeconds', 'datatype' => 'integer', 'default' => 14400, 'required' => false ),
                'winner_variant_id' => array( 'name' => 'WinnerVariantId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'samples_sent' => array( 'name' => 'SamplesSent', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'decided' => array( 'name' => 'Decided', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterAbTest',
            'name' => 'cjwnl_ab_test' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterAbTest
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterAbTest( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterAbTest|null
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
     * @return CjwNewsletterAbTest[]
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
     * @return CjwNewsletterAbTest|null the row with these values (unique index cjwnl_ab_test_send)
     */
    static function fetchByEditionSendId( $editionSendId )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'edition_send_id' => (int)$editionSendId ), true );
        return $object ? $object : null;
    }

    /**
     * @return CjwNewsletterAbTest[] the rows with these values (index cjwnl_ab_test_status), newest id first
     */
    static function fetchListByStatus( $status, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'status' => (int)$status ), $limit, $offset );
    }
}
