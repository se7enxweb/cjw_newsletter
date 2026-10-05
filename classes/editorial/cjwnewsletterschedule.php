<?php
/**
 * File containing the CjwNewsletterSchedule class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * A recurring send of a list: copy a template edition or send the latest edition, when, auto-fill from a pool.
 *
 * Table cjwnl_schedule (cjw_newsletter 4.2.0, area N2 Editorial; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterSchedule extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'list_contentobject_id' => array( 'name' => 'ListContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'mode' => array( 'name' => 'Mode', 'datatype' => 'string', 'default' => 'latest', 'required' => false ),
                'template_edition_contentobject_id' => array( 'name' => 'TemplateEditionContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'recurrence_type' => array( 'name' => 'RecurrenceType', 'datatype' => 'string', 'default' => 'w', 'required' => false ),
                'recurrence_value' => array( 'name' => 'RecurrenceValue', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'send_time' => array( 'name' => 'SendTime', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'timezone' => array( 'name' => 'Timezone', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'auto_fill' => array( 'name' => 'AutoFill', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'article_pool_id' => array( 'name' => 'ArticlePoolId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'skip_if_empty' => array( 'name' => 'SkipIfEmpty', 'datatype' => 'integer', 'default' => 1, 'required' => false ),
                'condition_handler' => array( 'name' => 'ConditionHandler', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'next_run' => array( 'name' => 'NextRun', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_run' => array( 'name' => 'LastRun', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_edition_send_id' => array( 'name' => 'LastEditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_result' => array( 'name' => 'LastResult', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'status' => array( 'name' => 'Status', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'creator_contentobject_id' => array( 'name' => 'CreatorContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterSchedule',
            'name' => 'cjwnl_schedule' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSchedule
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSchedule( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSchedule|null
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
     * @return CjwNewsletterSchedule[]
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
     * @return CjwNewsletterSchedule[] the rows with these values (index cjwnl_schedule_due), newest id first
     */
    static function fetchListByStatusAndNextRun( $status, $nextRun, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'status' => (int)$status, 'next_run' => (int)$nextRun ), $limit, $offset );
    }

    /**
     * @return CjwNewsletterSchedule[] the rows with these values (index cjwnl_schedule_list), newest id first
     */
    static function fetchListByListContentobjectId( $listContentobjectId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'list_contentobject_id' => (int)$listContentobjectId ), $limit, $offset );
    }
}
