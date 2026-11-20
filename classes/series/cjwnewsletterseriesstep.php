<?php
/**
 * File containing the CjwNewsletterSeriesStep class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage series
 */

/**
 * One step of a welcome series: an edition and the delay after the previous step.
 *
 * Table cjwnl_series_step. Data only: the definition and fetch helpers.
 *
 * @package cjw_newsletter
 * @subpackage series
 */
class CjwNewsletterSeriesStep extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'series_id' => array( 'name' => 'SeriesId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'position' => array( 'name' => 'Position', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'edition_contentobject_id' => array( 'name' => 'EditionContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'delay_days' => array( 'name' => 'DelayDays', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'position' => 'asc' ),
            'class_name' => 'CjwNewsletterSeriesStep',
            'name' => 'cjwnl_series_step' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSeriesStep
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSeriesStep( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSeriesStep|null
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
     * @param array|null $sorts field => asc|desc, null = the default sort of the definition
     * @return CjwNewsletterSeriesStep[]
     */
    static function fetchList( $conditions = null, $limit = 0, $offset = 0, $sorts = null )
    {
        $limits = (int)$limit > 0 ? array( 'limit' => (int)$limit, 'offset' => (int)$offset ) : null;
        $list = eZPersistentObject::fetchObjectList( self::definition(), null, $conditions,
            $sorts === null ? array( 'position' => 'asc' ) : $sorts, $limits, true );
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
     * @param int $seriesId
     * @return CjwNewsletterSeriesStep[] ordered by position ascending
     */
    static function fetchBySeries( $seriesId )
    {
        return self::fetchList( array( 'series_id' => (int)$seriesId ), 0, 0, array( 'position' => 'asc' ) );
    }

    /**
     * @param int $seriesId
     * @param int $position
     * @return CjwNewsletterSeriesStep|null the step at this position (index cjwnl_series_step_pos)
     */
    static function fetchBySeriesIdAndPosition( $seriesId, $position )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null,
            array( 'series_id' => (int)$seriesId, 'position' => (int)$position ), true );
        return $object ? $object : null;
    }
}
