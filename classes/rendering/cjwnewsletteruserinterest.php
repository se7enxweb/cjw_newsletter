<?php
/**
 * File containing the CjwNewsletterUserInterest class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage rendering
 */

/**
 * An interest a newsletter user picked.
 *
 * Table cjwnl_user_interest (cjw_newsletter 4.2.0, area N3 Rendering; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage rendering
 */
class CjwNewsletterUserInterest extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'newsletter_user_id' => array( 'name' => 'NewsletterUserId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'interest_id' => array( 'name' => 'InterestId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterUserInterest',
            'name' => 'cjwnl_user_interest' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterUserInterest
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterUserInterest( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterUserInterest|null
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
     * @return CjwNewsletterUserInterest[]
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
     * @return CjwNewsletterUserInterest|null the row with these values (unique index cjwnl_user_interest_pair)
     */
    static function fetchByNewsletterUserIdAndInterestId( $newsletterUserId, $interestId )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'newsletter_user_id' => (int)$newsletterUserId, 'interest_id' => (int)$interestId ), true );
        return $object ? $object : null;
    }

    /**
     * @return CjwNewsletterUserInterest[] the rows with these values (index cjwnl_user_interest_interest), newest id first
     */
    static function fetchListByInterestId( $interestId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'interest_id' => (int)$interestId ), $limit, $offset );
    }
}
