<?php
/**
 * File containing the CjwNewsletterEditionSendOutput class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage rendering
 */

/**
 * The rendered output of a send in one language (one edition, many translations).
 *
 * Table cjwnl_edition_send_output (cjw_newsletter 4.2.0, area N3 Rendering; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage rendering
 */
class CjwNewsletterEditionSendOutput extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'edition_send_id' => array( 'name' => 'EditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'language' => array( 'name' => 'Language', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'output_xml' => array( 'name' => 'OutputXml', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterEditionSendOutput',
            'name' => 'cjwnl_edition_send_output' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterEditionSendOutput
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterEditionSendOutput( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterEditionSendOutput|null
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
     * @return CjwNewsletterEditionSendOutput[]
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
     * @return CjwNewsletterEditionSendOutput|null the row with these values (unique index cjwnl_edition_send_output_lang)
     */
    static function fetchByEditionSendIdAndLanguage( $editionSendId, $language )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'edition_send_id' => (int)$editionSendId, 'language' => (string)$language ), true );
        return $object ? $object : null;
    }
}
