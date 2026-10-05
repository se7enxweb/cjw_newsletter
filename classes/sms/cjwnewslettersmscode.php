<?php
/**
 * File containing the CjwNewsletterSmsCode class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage sms
 */

/**
 * A confirmation code sent by SMS (stored as a hash), with attempts and expiry.
 *
 * Table cjwnl_sms_code (cjw_newsletter 4.2.0, area N5 SMS; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage sms
 */
class CjwNewsletterSmsCode extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'newsletter_user_id' => array( 'name' => 'NewsletterUserId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'phone_number' => array( 'name' => 'PhoneNumber', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'code_hash' => array( 'name' => 'CodeHash', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'purpose' => array( 'name' => 'Purpose', 'datatype' => 'string', 'default' => 'confirm', 'required' => false ),
                'attempts' => array( 'name' => 'Attempts', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'expires' => array( 'name' => 'Expires', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'used' => array( 'name' => 'Used', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterSmsCode',
            'name' => 'cjwnl_sms_code' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSmsCode
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSmsCode( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSmsCode|null
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
     * @return CjwNewsletterSmsCode[]
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
     * @return CjwNewsletterSmsCode[] the rows with these values (index cjwnl_sms_code_user), newest id first
     */
    static function fetchListByNewsletterUserIdAndPurpose( $newsletterUserId, $purpose, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'newsletter_user_id' => (int)$newsletterUserId, 'purpose' => (string)$purpose ), $limit, $offset );
    }
}
