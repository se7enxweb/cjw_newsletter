<?php
/**
 * File containing the CjwNewsletterStatisticsRetention class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * How long per-person statistics are kept (cjw_newsletter 4.2.0, area N4 Statistics).
 *
 * Per person are: the rows of cjwnl_open and cjwnl_link_click with an item (edition_send_item_id > 0) and the
 * columns first_opened, open_count and click_count of cjwnl_edition_send_item. They are removed
 * - after [TrackingSettings] PersonRetentionMonths (12) by cleanup(), which runs once a day from the mail queue and
 *   with ext:cjw_newsletter:statistics --cleanup;
 * - at once when the person withdraws the consent (the category handler's changed()) or is erased (erased());
 * - by cleanup() for every person whose consent is no longer on, as a safety net.
 * The anonymous totals (cjwnl_stat_total, cjwnl_link.click_count, the A/B counts) are never personal and stay.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterStatisticsRetention
{
    const LAST_RUN = 'cjw_newsletter_statistics_cleanup';

    /** @return int the time before which per-person rows are removed */
    static function cutoff( $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        return (int)strtotime( '-' . CjwNewsletterTracking::retentionMonths() . ' months', $now );
    }

    /**
     * @param int|null $now the time to act as if it were (tests, --at)
     * @param bool $dryRun count only
     * @return array expired_opens, expired_clicks, expired_items, withdrawn_users, withdrawn_rows, cutoff
     */
    static function cleanup( $now = null, $dryRun = false )
    {
        $now = $now === null ? time() : (int)$now;
        $cutoff = self::cutoff( $now );
        $db = eZDB::instance();
        $count = function ( $sql ) use ( $db ) {
            $rows = $db->arrayQuery( $sql );
            return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
        };
        $result = array( 'cutoff' => $cutoff, 'dry_run' => (bool)$dryRun,
            'expired_opens' => $count( "SELECT COUNT(*) AS c FROM cjwnl_open WHERE edition_send_item_id > 0 AND created < $cutoff" ),
            'expired_clicks' => $count( "SELECT COUNT(*) AS c FROM cjwnl_link_click WHERE edition_send_item_id > 0 AND created < $cutoff" ),
            'expired_items' => 0, 'withdrawn_users' => 0, 'withdrawn_rows' => 0 );
        // items: the per-person columns of sends older than the retention, once nothing newer of the item is left
        $itemWhere = "( first_opened > 0 OR open_count > 0 OR click_count > 0 ) AND edition_send_id IN ( SELECT id FROM cjwnl_edition_send WHERE created < $cutoff )";
        $result['expired_items'] = $count( "SELECT COUNT(*) AS c FROM cjwnl_edition_send_item WHERE $itemWhere" );
        if ( !$dryRun )
        {
            $db->query( "DELETE FROM cjwnl_open WHERE edition_send_item_id > 0 AND created < $cutoff" );
            $db->query( "DELETE FROM cjwnl_link_click WHERE edition_send_item_id > 0 AND created < $cutoff" );
            $db->query( "UPDATE cjwnl_edition_send_item SET first_opened = 0, open_count = 0, click_count = 0 WHERE $itemWhere" );
        }
        // the safety net: people with per-person rows whose consent is not on any more (withdrawn, erased, removed)
        $rows = $db->arrayQuery( 'SELECT DISTINCT newsletter_user_id FROM cjwnl_edition_send_item WHERE first_opened > 0 OR open_count > 0 OR click_count > 0' );
        foreach ( (array)$rows as $row )
        {
            $userId = (int)$row['newsletter_user_id'];
            $user = $userId > 0 ? CjwNewsletterUser::fetch( $userId ) : null;
            if ( is_object( $user ) && CjwNewsletterTracking::hasConsent( $user ) )
                continue;
            $result['withdrawn_users']++;
            $result['withdrawn_rows'] += self::forgetNewsletterUserId( $userId, $dryRun );
        }
        if ( !$dryRun )
            self::recordRun( $now, $result );
        return $result;
    }

    /**
     * Removes the per-person statistics of a newsletter user.
     *
     * @param int $newsletterUserId
     * @param bool $dryRun
     * @return int the rows removed or reset
     */
    static function forgetNewsletterUserId( $newsletterUserId, $dryRun = false )
    {
        $db = eZDB::instance();
        $id = (int)$newsletterUserId;
        $items = "SELECT id FROM cjwnl_edition_send_item WHERE newsletter_user_id = $id";
        $c = function ( $sql ) use ( $db ) {
            $rows = $db->arrayQuery( $sql );
            return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
        };
        $n = $c( "SELECT COUNT(*) AS c FROM cjwnl_open WHERE edition_send_item_id IN ( $items )" )
           + $c( "SELECT COUNT(*) AS c FROM cjwnl_link_click WHERE edition_send_item_id IN ( $items )" )
           + $c( "SELECT COUNT(*) AS c FROM cjwnl_edition_send_item WHERE newsletter_user_id = $id AND ( first_opened > 0 OR open_count > 0 OR click_count > 0 )" );
        if ( !$dryRun && $n > 0 )
        {
            $db->begin();
            $db->query( "DELETE FROM cjwnl_open WHERE edition_send_item_id IN ( $items )" );
            $db->query( "DELETE FROM cjwnl_link_click WHERE edition_send_item_id IN ( $items )" );
            $db->query( "UPDATE cjwnl_edition_send_item SET first_opened = 0, open_count = 0, click_count = 0 WHERE newsletter_user_id = $id" );
            $db->commit();
        }
        return $n;
    }

    /**
     * Removes the per-person statistics of a person of the e-mail preferences (an account and its address).
     *
     * @param expMailRecipient $recipient
     * @return int the rows removed or reset
     */
    static function forgetRecipient( $recipient )
    {
        $ids = array();
        if ( $recipient->userId() > 0 )
            foreach ( (array)eZPersistentObject::fetchObjectList( CjwNewsletterUser::definition(), array( 'id' ), array( 'ez_user_id' => (int)$recipient->userId() ), null, null, false ) as $row )
                $ids[(int)$row['id']] = true;
        $email = $recipient->email();
        if ( $email === '' && $recipient->userId() > 0 )
        {
            $user = eZUser::fetch( $recipient->userId() );
            $email = $user ? (string)$user->attribute( 'email' ) : '';
        }
        if ( $email !== '' )
        {
            $user = CjwNewsletterUser::fetchByEmail( $email );
            if ( is_object( $user ) )
                $ids[(int)$user->attribute( 'id' )] = true;
        }
        $n = 0;
        foreach ( array_keys( $ids ) as $id )
            $n += self::forgetNewsletterUserId( $id );
        return $n;
    }

    /** Runs cleanup() at most once a day (called by the mail queue). @return array|null */
    static function cleanupIfDue( $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        $last = self::lastRun();
        if ( $last && (int)$last['time'] > $now - 86400 && (int)$last['time'] <= $now )
            return null;
        return self::cleanup( $now );
    }

    /** @return array|null time, result of the last cleanup */
    static function lastRun()
    {
        $row = eZSiteData::fetchByName( self::LAST_RUN );
        if ( !$row )
            return null;
        $data = json_decode( (string)$row->attribute( 'value' ), true );
        return is_array( $data ) ? $data : null;
    }

    protected static function recordRun( $now, $result )
    {
        $value = json_encode( array( 'time' => (int)$now, 'result' => $result ) );
        $row = eZSiteData::fetchByName( self::LAST_RUN );
        if ( $row )
            $row->setAttribute( 'value', $value );
        else
            $row = eZSiteData::create( self::LAST_RUN, $value );
        $row->store();
    }
}

?>
