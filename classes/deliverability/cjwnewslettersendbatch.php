<?php
/**
 * File containing the CjwNewsletterSendBatch class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * One batch of a send: which items it took, how far it got (resumable), its counts.
 *
 * Table cjwnl_send_batch (cjw_newsletter 4.2.0, area N1 Deliverability; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterSendBatch extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'edition_send_id' => array( 'name' => 'EditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'channel' => array( 'name' => 'Channel', 'datatype' => 'string', 'default' => 'email', 'required' => false ),
                'batch_number' => array( 'name' => 'BatchNumber', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'item_count' => array( 'name' => 'ItemCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'sent_count' => array( 'name' => 'SentCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'failed_count' => array( 'name' => 'FailedCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_item_id' => array( 'name' => 'LastItemId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'status' => array( 'name' => 'Status', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'started' => array( 'name' => 'Started', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'finished' => array( 'name' => 'Finished', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterSendBatch',
            'name' => 'cjwnl_send_batch' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSendBatch
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSendBatch( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSendBatch|null
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
     * @return CjwNewsletterSendBatch[]
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
     * @return CjwNewsletterSendBatch[] the rows with these values (index cjwnl_send_batch_send), newest id first
     */
    static function fetchListByEditionSendIdAndStatus( $editionSendId, $status, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'edition_send_id' => (int)$editionSendId, 'status' => (int)$status ), $limit, $offset );
    }
}
