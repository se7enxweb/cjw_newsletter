<?php
/**
 * File containing the CjwNewsletterSeries class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage series
 */

/**
 * A welcome series: an ordered chain of editions sent to new subscribers of one list.
 *
 * Table cjwnl_series. Data only: the definition and fetch helpers.
 *
 * @package cjw_newsletter
 * @subpackage series
 */
class CjwNewsletterSeries extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'list_contentobject_id' => array( 'name' => 'ListContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'segment_id' => array( 'name' => 'SegmentId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'status' => array( 'name' => 'Status', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'creator_contentobject_id' => array( 'name' => 'CreatorContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterSeries',
            'name' => 'cjwnl_series' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSeries
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        // A new series starts paused (status 1) until it is switched on.
        if ( !array_key_exists( 'status', (array)$row ) )
            $data['status'] = 1;
        unset( $data['id'] );
        return new CjwNewsletterSeries( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSeries|null
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
     * @return CjwNewsletterSeries[]
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
     * @param int $listContentobjectId
     * @return CjwNewsletterSeries|null the series of this list (one per list) or null
     */
    static function fetchByList( $listContentobjectId )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null,
            array( 'list_contentobject_id' => (int)$listContentobjectId ), true );
        return $object ? $object : null;
    }

    /**
     * @param int $listContentobjectId
     * @return CjwNewsletterSeries|null the series of the list (index cjwnl_series_list)
     */
    static function fetchByListContentobjectId( $listContentobjectId )
    {
        return self::fetchByList( $listContentobjectId );
    }

    /**
     * @return CjwNewsletterSeriesStep[] ordered by position, first step first
     */
    function steps()
    {
        return CjwNewsletterSeriesStep::fetchBySeries( (int)$this->attribute( 'id' ) );
    }

    /**
     * @return bool true when status is not 0 (active)
     */
    function isPaused()
    {
        return (int)$this->attribute( 'status' ) !== 0;
    }

    /**
     * Enters an approved subscription into the series of its list. A row that exists is left as it is, so a
     * returning subscriber continues where he stopped.
     *
     * @param CjwNewsletterSubscription $subscription
     * @return CjwNewsletterSeriesMember|null the member, null when the list has no series or the subscription is not approved
     */
    static function enterApproved( $subscription )
    {
        if ( !is_object( $subscription ) || (int)$subscription->attribute( 'status' ) !== CjwNewsletterSubscription::STATUS_APPROVED )
            return null;
        $series = self::fetchByList( (int)$subscription->attribute( 'list_contentobject_id' ) );
        if ( $series === null )
            return null;
        $seriesId = (int)$series->attribute( 'id' );
        $subscriptionId = (int)$subscription->attribute( 'id' );
        $member = CjwNewsletterSeriesMember::fetchBySeriesAndSubscription( $seriesId, $subscriptionId );
        if ( $member !== null )
            return $member;
        $member = CjwNewsletterSeriesMember::create( array(
            'series_id' => $seriesId,
            'subscription_id' => $subscriptionId,
            'newsletter_user_id' => (int)$subscription->attribute( 'newsletter_user_id' ),
            'entered' => time() ) );
        $member->store();
        return $member;
    }
}
