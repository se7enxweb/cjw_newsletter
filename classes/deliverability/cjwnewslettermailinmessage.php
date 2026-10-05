<?php
/**
 * File containing the CjwNewsletterMailinMessage class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * One mail that came in at a mail-in address, and what was done with it.
 *
 * Table cjwnl_mailin_message (cjw_newsletter 4.2.0, area N1 Deliverability; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterMailinMessage extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'mailin_address_id' => array( 'name' => 'MailinAddressId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'message_identifier' => array( 'name' => 'MessageIdentifier', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'email_from' => array( 'name' => 'EmailFrom', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'action' => array( 'name' => 'Action', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'status' => array( 'name' => 'Status', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'pending_token' => array( 'name' => 'PendingToken', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'newsletter_user_id' => array( 'name' => 'NewsletterUserId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'note' => array( 'name' => 'Note', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'processed' => array( 'name' => 'Processed', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterMailinMessage',
            'name' => 'cjwnl_mailin_message' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterMailinMessage
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterMailinMessage( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterMailinMessage|null
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
     * @return CjwNewsletterMailinMessage[]
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
     * @return CjwNewsletterMailinMessage[] the rows with these values (index cjwnl_mailin_message_ident), newest id first
     */
    static function fetchListByMessageIdentifier( $messageIdentifier, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'message_identifier' => (string)$messageIdentifier ), $limit, $offset );
    }

    /**
     * @return CjwNewsletterMailinMessage[] the rows with these values (index cjwnl_mailin_message_status), newest id first
     */
    static function fetchListByStatusAndCreated( $status, $created, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'status' => (int)$status, 'created' => (int)$created ), $limit, $offset );
    }
}
