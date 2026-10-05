<?php
/**
 * File containing the CjwNewsletterMailinAddress class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * An address (or a plus-address tag) of a list that takes subscribe and unsubscribe mails.
 *
 * Table cjwnl_mailin_address (cjw_newsletter 4.2.0, area N1 Deliverability; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterMailinAddress extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'list_contentobject_id' => array( 'name' => 'ListContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'email' => array( 'name' => 'Email', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'plus_tag' => array( 'name' => 'PlusTag', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'action' => array( 'name' => 'Action', 'datatype' => 'string', 'default' => 'both', 'required' => false ),
                'mailbox_id' => array( 'name' => 'MailboxId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'is_active' => array( 'name' => 'IsActive', 'datatype' => 'integer', 'default' => 1, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterMailinAddress',
            'name' => 'cjwnl_mailin_address' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterMailinAddress
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterMailinAddress( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterMailinAddress|null
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
     * @return CjwNewsletterMailinAddress[]
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
     * @return CjwNewsletterMailinAddress|null the row with these values (unique index cjwnl_mailin_address_email)
     */
    static function fetchByEmailAndPlusTag( $email, $plusTag )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'email' => (string)$email, 'plus_tag' => (string)$plusTag ), true );
        return $object ? $object : null;
    }

    /**
     * @return CjwNewsletterMailinAddress[] the rows with these values (index cjwnl_mailin_address_list), newest id first
     */
    static function fetchListByListContentobjectId( $listContentobjectId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'list_contentobject_id' => (int)$listContentobjectId ), $limit, $offset );
    }
}
