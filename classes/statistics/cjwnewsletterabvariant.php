<?php
/**
 * File containing the CjwNewsletterAbVariant class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * One subject variant of an A/B test and its counts.
 *
 * Table cjwnl_ab_variant (cjw_newsletter 4.2.0, area N4 Statistics; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterAbVariant extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'ab_test_id' => array( 'name' => 'AbTestId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'variant_key' => array( 'name' => 'VariantKey', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'subject' => array( 'name' => 'Subject', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'item_count' => array( 'name' => 'ItemCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'open_count' => array( 'name' => 'OpenCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'click_count' => array( 'name' => 'ClickCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterAbVariant',
            'name' => 'cjwnl_ab_variant' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterAbVariant
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterAbVariant( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterAbVariant|null
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
     * @return CjwNewsletterAbVariant[]
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
     * @return CjwNewsletterAbVariant|null the row with these values (unique index cjwnl_ab_variant_test)
     */
    static function fetchByAbTestIdAndVariantKey( $abTestId, $variantKey )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'ab_test_id' => (int)$abTestId, 'variant_key' => (string)$variantKey ), true );
        return $object ? $object : null;
    }
}
