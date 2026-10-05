<?php
/**
 * File containing the CjwNewsletterMigrationLog class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage importexport
 */

/**
 * One row the eznewsletter importer read, and what it did with it (or would do, in a dry run).
 *
 * Table cjwnl_migration_log (cjw_newsletter 4.2.0, area N6 Import/export and migration; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage importexport
 */
class CjwNewsletterMigrationLog extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'run_id' => array( 'name' => 'RunId', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'source_table' => array( 'name' => 'SourceTable', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'source_id' => array( 'name' => 'SourceId', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'target_table' => array( 'name' => 'TargetTable', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'target_id' => array( 'name' => 'TargetId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'action' => array( 'name' => 'Action', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'is_dry_run' => array( 'name' => 'IsDryRun', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'message' => array( 'name' => 'Message', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterMigrationLog',
            'name' => 'cjwnl_migration_log' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterMigrationLog
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterMigrationLog( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterMigrationLog|null
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
     * @return CjwNewsletterMigrationLog[]
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
     * @return CjwNewsletterMigrationLog[] the rows with these values (index cjwnl_migration_log_run), newest id first
     */
    static function fetchListByRunId( $runId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'run_id' => (string)$runId ), $limit, $offset );
    }

    /**
     * @return CjwNewsletterMigrationLog[] the rows with these values (index cjwnl_migration_log_source), newest id first
     */
    static function fetchListBySourceTableAndSourceId( $sourceTable, $sourceId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'source_table' => (string)$sourceTable, 'source_id' => (string)$sourceId ), $limit, $offset );
    }
}
