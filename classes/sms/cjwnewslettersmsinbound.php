<?php
/**
 * File containing the CjwNewsletterSmsInbound class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * One SMS that came in (a STOP keyword, a code), and what was done with it.
 *
 * Table cjwnl_sms_inbound (cjw_newsletter 4.2.0, area N5 SMS; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSmsInbound extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'phone_number' => array( 'name' => 'PhoneNumber', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'keyword' => array( 'name' => 'Keyword', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'body' => array( 'name' => 'Body', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'newsletter_user_id' => array( 'name' => 'NewsletterUserId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'action' => array( 'name' => 'Action', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'provider_message_id' => array( 'name' => 'ProviderMessageId', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'processed' => array( 'name' => 'Processed', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterSmsInbound',
            'name' => 'cjwnl_sms_inbound' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSmsInbound
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSmsInbound( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSmsInbound|null
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
     * @return CjwNewsletterSmsInbound[]
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
     * @return CjwNewsletterSmsInbound[] the rows with these values (index cjwnl_sms_inbound_phone), newest id first
     */
    static function fetchListByPhoneNumber( $phoneNumber, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'phone_number' => (string)$phoneNumber ), $limit, $offset );
    }
}
