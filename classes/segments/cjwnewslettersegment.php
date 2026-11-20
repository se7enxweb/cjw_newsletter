<?php
/**
 * File containing the CjwNewsletterSegment class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage segments
 */

/**
 * A saved selection of the approved subscriptions of one list.
 *
 * Table cjwnl_segment (cjw_newsletter 4.3.0). The conditions are stored as one JSON array of
 * {type, ...params}; they are always read and written together. Data only: validation and
 * evaluation belong to CjwNewsletterSegmentCondition and CjwNewsletterSegmentEvaluator.
 *
 * @package cjw_newsletter
 * @subpackage segments
 */
class CjwNewsletterSegment extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'list_contentobject_id' => array( 'name' => 'ListContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'name' => array( 'name' => 'Name', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'match_mode' => array( 'name' => 'MatchMode', 'datatype' => 'string', 'default' => 'all', 'required' => false ),
                'conditions' => array( 'name' => 'Conditions', 'datatype' => 'text', 'default' => '', 'required' => false ),
                'creator_contentobject_id' => array( 'name' => 'CreatorContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'name' => 'asc' ),
            'class_name' => 'CjwNewsletterSegment',
            'name' => 'cjwnl_segment' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSegment
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSegment( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSegment|null
     */
    static function fetch( $id )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'id' => (int)$id ), true );
        return $object ? $object : null;
    }

    /**
     * @param int $listId content object id of the list
     * @return CjwNewsletterSegment[] by name
     */
    static function fetchByList( $listId )
    {
        return self::fetchListByListContentobjectId( $listId );
    }

    /**
     * @param array|null $conditions field => value (eZPersistentObject conditions)
     * @param int $limit 0 = all
     * @param int $offset
     * @param array|null $sorts field => asc|desc, null = by name
     * @return CjwNewsletterSegment[]
     */
    static function fetchList( $conditions = null, $limit = 0, $offset = 0, $sorts = null )
    {
        $limits = (int)$limit > 0 ? array( 'limit' => (int)$limit, 'offset' => (int)$offset ) : null;
        $list = eZPersistentObject::fetchObjectList( self::definition(), null, $conditions,
            $sorts === null ? array( 'name' => 'asc', 'id' => 'asc' ) : $sorts, $limits, true );
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
     * @return CjwNewsletterSegment[] the segments of the list (index cjwnl_segment_list), by name
     */
    static function fetchListByListContentobjectId( $listContentobjectId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'list_contentobject_id' => (int)$listContentobjectId ), $limit, $offset );
    }

    /**
     * @return array the decoded conditions; empty when the column is empty or not a JSON array
     */
    function conditionArray()
    {
        $decoded = json_decode( (string)$this->attribute( 'conditions' ), true );
        return is_array( $decoded ) ? array_values( $decoded ) : array();
    }

    /**
     * @param array $conditions list of {type, ...params}
     */
    function setConditionArray( array $conditions )
    {
        $this->setAttribute( 'conditions', json_encode( array_values( $conditions ) ) );
    }

    /**
     * @return bool true when a send that uses this segment has not started yet (waiting or scheduled)
     */
    function isReferencedByWaitingSend()
    {
        return eZPersistentObject::count( CjwNewsletterEditionSend::definition(), array(
            'segment_id' => (int)$this->attribute( 'id' ),
            'status' => array( array( CjwNewsletterEditionSend::STATUS_WAIT_FOR_PROCESS,
                                      CjwNewsletterEditionSend::STATUS_WAIT_FOR_SCHEDULE ) ) ) ) > 0;
    }
}
