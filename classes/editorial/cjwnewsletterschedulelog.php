<?php
/**
 * File containing the CjwNewsletterScheduleLog class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * One run of a schedule: sent, skipped (empty, condition) or failed.
 *
 * Table cjwnl_schedule_log (cjw_newsletter 4.2.0, area N2 Editorial; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterScheduleLog extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'schedule_id' => array( 'name' => 'ScheduleId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'run_at' => array( 'name' => 'RunAt', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'result' => array( 'name' => 'Result', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'edition_contentobject_id' => array( 'name' => 'EditionContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'edition_send_id' => array( 'name' => 'EditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'article_count' => array( 'name' => 'ArticleCount', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'message' => array( 'name' => 'Message', 'datatype' => 'string', 'default' => '', 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterScheduleLog',
            'name' => 'cjwnl_schedule_log' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterScheduleLog
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterScheduleLog( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterScheduleLog|null
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
     * @return CjwNewsletterScheduleLog[]
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
     * @return CjwNewsletterScheduleLog[] the rows with these values (index cjwnl_schedule_log_schedule), newest id first
     */
    static function fetchListByScheduleIdAndRunAt( $scheduleId, $runAt, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'schedule_id' => (int)$scheduleId, 'run_at' => (int)$runAt ), $limit, $offset );
    }
}
