<?php
/**
 * File containing the CjwNewsletterSeriesHooks class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage series
 */

/**
 * The extension point handler of the welcome series ([ExtensionPointSettings] Handlers[]=CjwNewsletterSeriesHooks).
 *
 * - subscriptionStatusChanged: a subscription that becomes approved enters the series of its list.
 * - userRemoved: the removed subscriber's member rows go.
 * - sendRecipients: a step send goes only to the members that are pending for it (and still in the audience).
 * - sendQueueCreated: the members that got an item move one step on; every pending marker of the send is cleared.
 *
 * @package cjw_newsletter
 * @subpackage series
 */
class CjwNewsletterSeriesHooks
{
    /**
     * Pure decision: does a status change put the subscription into the series?
     *
     * @param int|null $oldStatus the status before (null for a new subscription)
     * @param int $newStatus the status now
     * @param bool $bulk true while an import runs
     * @param bool $hasSeries true when the list has a series (active or paused)
     * @return bool
     */
    static function shouldEnter( $oldStatus, $newStatus, $bulk, $hasSeries )
    {
        if ( $bulk || !$hasSeries )
            return false;
        return (int)$newStatus === 2 && ( $oldStatus === null || (int)$oldStatus !== 2 );
    }

    /**
     * @param CjwNewsletterSubscription $subscription
     * @param int|null $oldStatus
     * @param bool $bulk
     */
    static function subscriptionStatusChanged( $subscription, $oldStatus, $bulk )
    {
        if ( !is_object( $subscription ) )
            return;
        $series = CjwNewsletterSeries::fetchByList( (int)$subscription->attribute( 'list_contentobject_id' ) );
        if ( !self::shouldEnter( $oldStatus, (int)$subscription->attribute( 'status' ), (bool)$bulk, $series !== null ) )
            return;
        CjwNewsletterSeries::enterApproved( $subscription );
    }

    /**
     * @param int $newsletterUserId
     */
    static function userRemoved( $newsletterUserId )
    {
        eZPersistentObject::removeObject( CjwNewsletterSeriesMember::definition(),
            array( 'newsletter_user_id' => (int)$newsletterUserId ) );
    }

    /**
     * The due steps of the series make their sends, which the same queue-create run then queues.
     *
     * @param object|false $cli
     */
    static function queueCreateBefore( $cli )
    {
        $totals = CjwNewsletterSeriesRunner::run( time(), $cli );
        if ( !$cli )
            return;
        if ( $totals['locked'] )
            $cli->output( 'Welcome series: another run is active.' );
        else if ( $totals['sent'] || $totals['skipped'] || $totals['failed'] )
            $cli->output( 'Welcome series: ' . $totals['sent'] . ' sent, ' . $totals['skipped'] . ' skipped, ' . $totals['failed'] . ' failed.' );
    }

    /**
     * Fail-closed: a step send (series_step_id > 0) goes only to the members pending for it, so it never reaches
     * the whole list; without such members the result is empty. Other sends are unchanged.
     *
     * @param array|false $subscriptions what getSubscriptionObjectArray() returned
     * @param CjwNewsletterEditionSend $send
     * @return array|false
     */
    static function sendRecipients( $subscriptions, $send )
    {
        if ( !is_object( $send ) || (int)$send->attribute( 'series_step_id' ) < 1 )
            return $subscriptions;

        $ids = array();
        foreach ( (array)CjwNewsletterSeriesMember::fetchList( array( 'pending_send_id' => (int)$send->attribute( 'id' ) ) ) as $member )
            $ids[] = (int)$member->attribute( 'subscription_id' );

        $step = CjwNewsletterSeriesStep::fetch( (int)$send->attribute( 'series_step_id' ) );
        $series = $step ? CjwNewsletterSeries::fetch( (int)$step->attribute( 'series_id' ) ) : null;
        if ( !$series )
            return array();
        if ( $ids && (int)$series->attribute( 'segment_id' ) > 0 )
            $ids = array_values( array_intersect( $ids, CjwNewsletterSeriesRunner::audienceIds( $series ) ) );

        return CjwNewsletterSegmentHooks::keepIds( $subscriptions, $ids );
    }

    /**
     * Pure part of sendQueueCreated(): which member rows change.
     *
     * @param array $pendingMembers rows with id, pending_send_id, pending_position
     * @param int[] $subscriptionIdsWithItem subscriptions that have an item in the send
     * @param int $now unix time
     * @return array member id => field => value; rows without a pending marker are left out, so a second
     *               run over rows already updated returns nothing
     */
    static function advance( array $pendingMembers, array $subscriptionIdsWithItem, $now )
    {
        $withItem = array_flip( array_map( 'intval', $subscriptionIdsWithItem ) );
        $updates = array();
        foreach ( $pendingMembers as $member )
        {
            if ( (int)$member['pending_send_id'] < 1 )
                continue;
            $update = array( 'pending_send_id' => 0, 'pending_position' => 0 );
            if ( isset( $withItem[(int)$member['subscription_id']] ) )
            {
                $update['last_position'] = (int)$member['pending_position'];
                $update['last_sent'] = (int)$now;
            }
            $updates[(int)$member['id']] = $update;
        }
        return $updates;
    }

    /**
     * @param CjwNewsletterEditionSend $send
     * @param object|false $cli
     */
    static function sendQueueCreated( $send, $cli )
    {
        if ( !is_object( $send ) || (int)$send->attribute( 'series_step_id' ) < 1 )
            return;

        $sendId = (int)$send->attribute( 'id' );
        $members = CjwNewsletterSeriesMember::fetchList( array( 'pending_send_id' => $sendId ) );
        if ( !$members )
            return;
        $rows = array();
        foreach ( $members as $member )
            $rows[] = array(
                'id' => (int)$member->attribute( 'id' ),
                'subscription_id' => (int)$member->attribute( 'subscription_id' ),
                'pending_send_id' => (int)$member->attribute( 'pending_send_id' ),
                'pending_position' => (int)$member->attribute( 'pending_position' ) );

        $withItem = array();
        foreach ( (array)eZDB::instance()->arrayQuery( 'SELECT DISTINCT subscription_id FROM cjwnl_edition_send_item WHERE edition_send_id = ' . $sendId ) as $row )
            $withItem[] = (int)$row['subscription_id'];

        $byId = array();
        foreach ( $members as $member )
            $byId[(int)$member->attribute( 'id' )] = $member;
        foreach ( self::advance( $rows, $withItem, time() ) as $id => $fields )
        {
            foreach ( $fields as $name => $value )
                $byId[$id]->setAttribute( $name, $value );
            $byId[$id]->store();
        }
    }
}
