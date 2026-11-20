<?php
/**
 * File containing the CjwNewsletterSeriesMember class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage series
 */

/**
 * The progress of one subscription through a welcome series.
 *
 * Table cjwnl_series_member. Data only: the definition and fetch helpers.
 *
 * @package cjw_newsletter
 * @subpackage series
 */
class CjwNewsletterSeriesMember extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'series_id' => array( 'name' => 'SeriesId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'subscription_id' => array( 'name' => 'SubscriptionId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'newsletter_user_id' => array( 'name' => 'NewsletterUserId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'entered' => array( 'name' => 'Entered', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_position' => array( 'name' => 'LastPosition', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_sent' => array( 'name' => 'LastSent', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'pending_send_id' => array( 'name' => 'PendingSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'pending_position' => array( 'name' => 'PendingPosition', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array(),
            'sort' => array( 'id' => 'asc' ),
            'class_name' => 'CjwNewsletterSeriesMember',
            'name' => 'cjwnl_series_member' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSeriesMember
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSeriesMember( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSeriesMember|null
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
     * @return CjwNewsletterSeriesMember[]
     */
    static function fetchList( $conditions = null, $limit = 0, $offset = 0, $sorts = null )
    {
        $limits = (int)$limit > 0 ? array( 'limit' => (int)$limit, 'offset' => (int)$offset ) : null;
        $list = eZPersistentObject::fetchObjectList( self::definition(), null, $conditions,
            $sorts === null ? array( 'id' => 'asc' ) : $sorts, $limits, true );
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
     * @return CjwNewsletterSeriesMember[]
     */
    static function fetchBySeries( $seriesId )
    {
        return self::fetchList( array( 'series_id' => (int)$seriesId ) );
    }

    /**
     * @param int $seriesId
     * @param int $subscriptionId
     * @return CjwNewsletterSeriesMember|null
     */
    static function fetchBySeriesAndSubscription( $seriesId, $subscriptionId )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null,
            array( 'series_id' => (int)$seriesId, 'subscription_id' => (int)$subscriptionId ), true );
        return $object ? $object : null;
    }

    /**
     * @param int $seriesId
     * @param int $subscriptionId
     * @return CjwNewsletterSeriesMember|null (index cjwnl_series_member_sub)
     */
    static function fetchBySeriesIdAndSubscriptionId( $seriesId, $subscriptionId )
    {
        return self::fetchBySeriesAndSubscription( $seriesId, $subscriptionId );
    }

    /**
     * @param int $pendingSendId
     * @return CjwNewsletterSeriesMember[] the members waiting for this step send (index cjwnl_series_member_pending)
     */
    static function fetchListByPendingSendId( $pendingSendId )
    {
        return self::fetchList( array( 'pending_send_id' => (int)$pendingSendId ) );
    }
}
