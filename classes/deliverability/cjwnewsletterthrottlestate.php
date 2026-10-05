<?php
/**
 * File containing the CjwNewsletterThrottleState class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * The sending rate of one transport in the current minute or hour window, and a pause.
 *
 * Table cjwnl_throttle_state (cjw_newsletter 4.2.0, area N1 Deliverability; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterThrottleState extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'transport' => array( 'name' => 'Transport', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'window_type' => array( 'name' => 'WindowType', 'datatype' => 'string', 'default' => 'minute', 'required' => false ),
                'window_start' => array( 'name' => 'WindowStart', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'sent_count' => array( 'name' => 'SentCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'paused_until' => array( 'name' => 'PausedUntil', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterThrottleState',
            'name' => 'cjwnl_throttle_state' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterThrottleState
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterThrottleState( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterThrottleState|null
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
     * @return CjwNewsletterThrottleState[]
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
     * @return CjwNewsletterThrottleState|null the row with these values (unique index cjwnl_throttle_state_window)
     */
    static function fetchByTransportAndWindowType( $transport, $windowType )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'transport' => (string)$transport, 'window_type' => (string)$windowType ), true );
        return $object ? $object : null;
    }
}
