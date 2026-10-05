<?php
/**
 * File containing the CjwNewsletterSmsMessage class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * One SMS of an SMS edition to one newsletter user.
 *
 * Table cjwnl_sms_message (cjw_newsletter 4.2.0, area N5 SMS; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSmsMessage extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'edition_send_id' => array( 'name' => 'EditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'newsletter_user_id' => array( 'name' => 'NewsletterUserId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'phone_number' => array( 'name' => 'PhoneNumber', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'body' => array( 'name' => 'Body', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'status' => array( 'name' => 'Status', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'transport' => array( 'name' => 'Transport', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'provider_message_id' => array( 'name' => 'ProviderMessageId', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'error' => array( 'name' => 'Error', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'batch_id' => array( 'name' => 'BatchId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'processed' => array( 'name' => 'Processed', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterSmsMessage',
            'name' => 'cjwnl_sms_message' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSmsMessage
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSmsMessage( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSmsMessage|null
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
     * @return CjwNewsletterSmsMessage[]
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
     * @return CjwNewsletterSmsMessage[] the rows with these values (index cjwnl_sms_message_send), newest id first
     */
    static function fetchListByEditionSendIdAndStatus( $editionSendId, $status, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'edition_send_id' => (int)$editionSendId, 'status' => (int)$status ), $limit, $offset );
    }

    /**
     * @return CjwNewsletterSmsMessage[] the rows with these values (index cjwnl_sms_message_user), newest id first
     */
    static function fetchListByNewsletterUserId( $newsletterUserId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'newsletter_user_id' => (int)$newsletterUserId ), $limit, $offset );
    }
}
