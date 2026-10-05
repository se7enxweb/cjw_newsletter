<?php
/**
 * File containing the CjwNewsletterStatisticsReport class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * The numbers of the statistics pages (cjw_newsletter 4.2.0, area N4 Statistics): the report of a send, the dashboard
 * block, the article box and the CSV export. Everything here is a total; no page and no export shows a person.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterStatisticsReport
{
    /** @return int */
    protected static function count( $sql )
    {
        $rows = eZDB::instance()->arrayQuery( $sql );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }

    /** @return float a percentage with one decimal */
    static function percent( $part, $all )
    {
        return (int)$all > 0 ? round( 100 * (int)$part / (int)$all, 1 ) : 0.0;
    }

    /** @return array name, node_id of a content object (empty when it is gone) */
    static function objectInfo( $objectId )
    {
        $object = (int)$objectId > 0 ? eZContentObject::fetch( (int)$objectId ) : null;
        if ( !is_object( $object ) )
            return array( 'name' => '', 'node_id' => 0, 'object_id' => (int)$objectId );
        return array( 'name' => (string)$object->attribute( 'name' ), 'node_id' => (int)$object->attribute( 'main_node_id' ), 'object_id' => (int)$objectId );
    }

    /**
     * The totals of a send from cjwnl_stat_total.
     *
     * @return array type => total (send level), plus clicks (all links)
     */
    static function totals( $sendId )
    {
        $sendId = (int)$sendId;
        $out = array( 'open' => 0, 'unique_open' => 0, 'click' => 0, 'unique_click' => 0 );
        $rows = eZDB::instance()->arrayQuery( "SELECT stat_type, link_id, SUM( total ) AS t FROM cjwnl_stat_total WHERE edition_send_id = $sendId GROUP BY stat_type, link_id" );
        foreach ( (array)$rows as $row )
        {
            $type = (string)$row['stat_type'];
            if ( $type === 'click' )
                $out['click'] += (int)$row['t'];
            else if ( (int)$row['link_id'] === 0 && isset( $out[$type] ) )
                $out[$type] += (int)$row['t'];
        }
        return $out;
    }

    /** @return int unsubscribes from the list after the send went out, until the next send of the list */
    static function unsubscribes( $send )
    {
        $start = (int)$send->attribute( 'mailqueue_process_started' );
        if ( $start <= 0 )
            $start = (int)$send->attribute( 'created' );
        $listId = (int)$send->attribute( 'list_contentobject_id' );
        $rows = eZDB::instance()->arrayQuery( "SELECT MIN( created ) AS n FROM cjwnl_edition_send WHERE list_contentobject_id = $listId AND created > " . (int)$send->attribute( 'created' ) . ' AND id <> ' . (int)$send->attribute( 'id' ) );
        $end = isset( $rows[0]['n'] ) && (int)$rows[0]['n'] > 0 ? (int)$rows[0]['n'] : time() + 1;
        return self::count( "SELECT COUNT(*) AS c FROM cjwnl_subscription WHERE list_contentobject_id = $listId AND status = " . CjwNewsletterSubscription::STATUS_REMOVED_SELF
            . " AND removed >= $start AND removed < $end" );
    }

    /**
     * The report of a send.
     *
     * @param CjwNewsletterEditionSend $send
     * @return array
     */
    static function send( $send )
    {
        $sendId = (int)$send->attribute( 'id' );
        $items = $send->attribute( 'send_items_statistic' );
        $totals = self::totals( $sendId );
        $sent = (int)$items['items_send'];
        $bounced = (int)$items['items_bounced'];
        $delivered = max( 0, $sent - $bounced );
        $edition = self::objectInfo( $send->attribute( 'edition_contentobject_id' ) );
        $list = self::objectInfo( $send->attribute( 'list_contentobject_id' ) );
        $mode = CjwNewsletterTracking::cleanMode( $send->attribute( 'tracking_mode' ) );
        $uniqueBase = self::count( 'SELECT COUNT(*) AS c FROM cjwnl_edition_send_item WHERE edition_send_id = ' . $sendId . ' AND ( open_count > 0 OR click_count > 0 )' );

        $links = array();
        foreach ( CjwNewsletterLink::fetchList( array( 'edition_send_id' => $sendId ), 0, 0, array( 'position' => 'asc' ) ) as $link )
        {
            $unique = 0;
            $rows = eZDB::instance()->arrayQuery( 'SELECT SUM( total ) AS t FROM cjwnl_stat_total WHERE edition_send_id = ' . $sendId . ' AND link_id = ' . (int)$link->attribute( 'id' ) . " AND stat_type = 'unique_click'" );
            if ( isset( $rows[0]['t'] ) )
                $unique = (int)$rows[0]['t'];
            $object = (int)$link->attribute( 'contentobject_id' ) > 0 ? self::objectInfo( $link->attribute( 'contentobject_id' ) ) : null;
            $links[] = array( 'id' => (int)$link->attribute( 'id' ), 'url' => (string)$link->attribute( 'url' ), 'position' => (int)$link->attribute( 'position' ),
                'clicks' => (int)$link->attribute( 'click_count' ), 'unique_clicks' => $unique,
                'share' => self::percent( $link->attribute( 'click_count' ), max( 1, $totals['click'] ) ),
                'object' => $object );
        }
        usort( $links, function ( $a, $b ) { return $b['clicks'] - $a['clicks'] ?: $a['position'] - $b['position']; } );

        $test = CjwNewsletterAbTester::forSend( $send );
        return array(
            'id' => $sendId, 'edition' => $edition, 'list' => $list,
            'status' => (int)$send->attribute( 'status' ), 'created' => (int)$send->attribute( 'created' ),
            'started' => (int)$send->attribute( 'mailqueue_process_started' ), 'finished' => (int)$send->attribute( 'mailqueue_process_finished' ),
            'channel' => (string)$send->attribute( 'channel' ),
            'tracking_mode' => $mode, 'tracking_enabled' => CjwNewsletterTracking::enabled(),
            'items' => (int)$items['items_count'], 'waiting' => (int)$items['items_not_send'], 'sent' => $sent,
            'not_sent' => (int)$items['items_abort'], 'bounced' => $bounced, 'delivered' => $delivered,
            'opens' => $totals['open'], 'unique_opens' => $totals['unique_open'], 'clicks' => $totals['click'], 'unique_clicks' => $totals['unique_click'],
            'open_rate' => self::percent( $totals['unique_open'], $delivered ), 'click_rate' => self::percent( $totals['unique_click'], $delivered ),
            'counted_people' => $uniqueBase,
            'unsubscribes' => self::unsubscribes( $send ), 'unsubscribe_rate' => self::percent( self::unsubscribes( $send ), $delivered ),
            'bounce_rate' => self::percent( $bounced, $sent ),
            'links' => $links, 'days' => self::days( $sendId ),
            'ab_test' => $test ? CjwNewsletterAbTester::summary( $test, $send ) : null );
    }

    /**
     * Opens and clicks per day of a send (or of all sends), with bar heights for the charts.
     *
     * @param int $sendId 0 = every send
     * @param int $sinceDay yyyymmdd, 0 = no limit
     * @return array[] day (yyyymmdd), date (timestamp), opens, clicks, open_height, click_height (percent of the highest)
     */
    static function days( $sendId, $sinceDay = 0 )
    {
        $where = array( "stat_type IN ( 'open', 'click' )" );
        if ( (int)$sendId > 0 )
            $where[] = 'edition_send_id = ' . (int)$sendId;
        if ( (int)$sinceDay > 0 )
            $where[] = 'stat_day >= ' . (int)$sinceDay;
        $rows = eZDB::instance()->arrayQuery( 'SELECT stat_day, stat_type, SUM( total ) AS t FROM cjwnl_stat_total WHERE ' . implode( ' AND ', $where ) . ' GROUP BY stat_day, stat_type ORDER BY stat_day' );
        $days = array();
        foreach ( (array)$rows as $row )
        {
            $day = (int)$row['stat_day'];
            if ( !isset( $days[$day] ) )
                $days[$day] = array( 'day' => $day, 'date' => self::dayTime( $day ), 'opens' => 0, 'clicks' => 0 );
            $days[$day][$row['stat_type'] === 'open' ? 'opens' : 'clicks'] += (int)$row['t'];
        }
        return self::withHeights( array_values( $days ) );
    }

    /** @return int the timestamp of noon of a yyyymmdd day */
    static function dayTime( $day )
    {
        $day = (int)$day;
        return (int)mktime( 12, 0, 0, (int)( $day / 100 ) % 100, $day % 100, (int)( $day / 10000 ) );
    }

    /** Adds open_height and click_height (percent of the highest value of both series). */
    protected static function withHeights( $rows )
    {
        $max = 1;
        foreach ( $rows as $r )
            $max = max( $max, $r['opens'], $r['clicks'] );
        foreach ( $rows as $i => $r )
        {
            $rows[$i]['open_height'] = (int)round( 100 * $r['opens'] / $max );
            $rows[$i]['click_height'] = (int)round( 100 * $r['clicks'] / $max );
        }
        return $rows;
    }

    /**
     * Opens and clicks per week of the last $days days, oldest first (the dashboard's trend).
     *
     * @return array[] week start (timestamp), opens, clicks, heights
     */
    static function weeks( $days, $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        $weeks = array();
        $count = max( 1, (int)ceil( (int)$days / 7 ) );
        $start = strtotime( 'monday this week', $now ) - ( $count - 1 ) * 7 * 86400;
        for ( $i = 0; $i < $count; $i++ )
        {
            $t = $start + $i * 7 * 86400;
            $weeks[date( 'oW', $t )] = array( 'start' => (int)$t, 'opens' => 0, 'clicks' => 0 );
        }
        foreach ( self::days( 0, (int)date( 'Ymd', $start ) ) as $d )
        {
            $key = date( 'oW', $d['date'] );
            if ( isset( $weeks[$key] ) )
            {
                $weeks[$key]['opens'] += $d['opens'];
                $weeks[$key]['clicks'] += $d['clicks'];
            }
        }
        return self::withHeights( array_values( $weeks ) );
    }

    /**
     * The dashboard block's numbers.
     *
     * @return array
     */
    static function dashboard( $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $trendDays = $ini->hasVariable( 'StatisticsSettings', 'TrendDays' ) ? max( 7, (int)$ini->variable( 'StatisticsSettings', 'TrendDays' ) ) : 90;
        $since = (int)date( 'Ymd', $now - $trendDays * 86400 );
        $rows = eZDB::instance()->arrayQuery( "SELECT stat_type, SUM( total ) AS t FROM cjwnl_stat_total WHERE stat_day >= $since AND stat_type IN ( 'open', 'unique_open', 'click', 'unique_click' ) GROUP BY stat_type" );
        $totals = array( 'open' => 0, 'unique_open' => 0, 'click' => 0, 'unique_click' => 0 );
        foreach ( (array)$rows as $row )
            $totals[$row['stat_type']] = (int)$row['t'];

        $recent = array();
        $sends = eZPersistentObject::fetchObjectList( CjwNewsletterEditionSend::definition(), null, array( 'tracking_mode' => array( '>', 0 ) ),
            array( 'created' => 'desc' ), array( 'offset' => 0, 'limit' => 5 ), true );
        foreach ( (array)$sends as $send )
        {
            $r = self::send( $send );
            unset( $r['links'], $r['days'] );
            $recent[] = $r;
        }

        $tests = array();
        foreach ( CjwNewsletterAbTest::fetchList( array( 'status' => array( array( CjwNewsletterAbTester::STATUS_SAMPLING, CjwNewsletterAbTester::STATUS_WAITING, CjwNewsletterAbTester::STATUS_DECIDED ) ) ), 5 ) as $test )
        {
            $s = CjwNewsletterAbTester::summary( $test );
            $send = CjwNewsletterEditionSend::fetch( $test->attribute( 'edition_send_id' ) );
            $s['edition'] = is_object( $send ) ? self::objectInfo( $send->attribute( 'edition_contentobject_id' ) ) : self::objectInfo( 0 );
            $tests[] = $s;
        }

        $category = CjwNewsletterTracking::consentCategory();
        $registered = class_exists( 'expMailCategoryRegistry' ) && expMailCategoryRegistry::instance()->get( $category );
        $consents = 0;
        if ( $registered && class_exists( 'expMailPreferencesService' ) && expMailPreferencesService::tableExists( 'expmail_preference' ) )
            $consents = self::count( "SELECT COUNT(*) AS c FROM expmail_preference WHERE category = '" . eZDB::instance()->escapeString( $category ) . "' AND state = 'on'" );
        $lists = array( 0 => 0, 1 => 0, 2 => 0 );
        foreach ( (array)eZDB::instance()->arrayQuery( 'SELECT l.tracking_mode AS m, COUNT( DISTINCT l.contentobject_id ) AS c FROM cjwnl_list l, ezcontentobject o WHERE o.id = l.contentobject_id AND o.current_version = l.contentobject_attribute_version GROUP BY l.tracking_mode' ) as $row )
            $lists[CjwNewsletterTracking::cleanMode( $row['m'] )] += (int)$row['c'];

        $problems = array();
        if ( CjwNewsletterTracking::enabled() && !$registered )
            $problems[] = array( 'level' => 'warning', 'code' => 'statistics_category_missing',
                'text' => ezpI18n::tr( 'cjw_newsletter/statistics', 'Tracking is on, but the e-mail preference category "%category" is not set up: nobody can agree to per-person statistics, so only anonymous totals are counted.', null, array( '%category' => $category ) ), 'url' => '' );
        if ( !CjwNewsletterTracking::enabled() && ( $lists[1] + $lists[2] ) > 0 )
            $problems[] = array( 'level' => 'info', 'code' => 'statistics_tracking_disabled',
                'text' => ezpI18n::tr( 'cjw_newsletter/statistics', 'Some lists ask for tracking, but [TrackingSettings] Tracking is disabled for the site, so nothing is counted.' ), 'url' => '' );
        foreach ( $tests as $t )
            if ( $t['status_code'] === CjwNewsletterAbTester::STATUS_WAITING && $t['decide_at'] > 0 && $t['decide_at'] < $now - 3600 )
                $problems[] = array( 'level' => 'warning', 'code' => 'statistics_ab_overdue',
                    'text' => ezpI18n::tr( 'cjw_newsletter/statistics', 'The A/B test of "%edition" should have chosen its winner: does the mail queue run?', null, array( '%edition' => $t['edition']['name'] ) ),
                    'url' => 'newsletter/ab_test/' . $t['edition_send_id'] );

        return array( 'enabled' => CjwNewsletterTracking::enabled(), 'category' => $category, 'category_registered' => (bool)$registered,
            'consents' => $consents, 'lists' => $lists, 'trend_days' => $trendDays,
            'opens' => $totals['open'], 'unique_opens' => $totals['unique_open'], 'clicks' => $totals['click'], 'unique_clicks' => $totals['unique_click'],
            'weeks' => self::weeks( $trendDays, $now ), 'recent' => $recent, 'ab_tests' => $tests,
            'retention_months' => CjwNewsletterTracking::retentionMonths(), 'last_cleanup' => CjwNewsletterStatisticsRetention::lastRun(),
            'problems' => $problems );
    }

    /**
     * The sends with their numbers, newest first (the report index).
     *
     * @return array[]
     */
    static function sends( $limit = 25, $offset = 0 )
    {
        $out = array();
        $sends = eZPersistentObject::fetchObjectList( CjwNewsletterEditionSend::definition(), null, null,
            array( 'created' => 'desc' ), array( 'offset' => (int)$offset, 'limit' => (int)$limit ), true );
        foreach ( (array)$sends as $send )
        {
            $r = self::send( $send );
            unset( $r['links'], $r['days'] );
            $out[] = $r;
        }
        return $out;
    }

    /** @return int */
    static function sendCount()
    {
        return (int)eZPersistentObject::count( CjwNewsletterEditionSend::definition() );
    }

    /**
     * The article box: in which editions an article went out and how its links were clicked.
     *
     * @param int $objectId
     * @return array editions (with their sends), links, clicks, sends
     */
    static function article( $objectId )
    {
        $objectId = (int)$objectId;
        $editionIds = array();
        // N2: the articles taken into editions from a pool
        if ( class_exists( 'CjwNewsletterEditionArticle' ) )
            foreach ( (array)eZPersistentObject::fetchObjectList( CjwNewsletterEditionArticle::definition(), array( 'edition_contentobject_id' ), array( 'contentobject_id' => $objectId ), null, null, false ) as $row )
                $editionIds[(int)$row['edition_contentobject_id']] = true;
        // an article written in the edition (a child of the edition)
        $object = eZContentObject::fetch( $objectId );
        if ( is_object( $object ) )
            foreach ( (array)$object->assignedNodes() as $node )
            {
                $parent = $node->attribute( 'parent' );
                if ( is_object( $parent ) && $parent->attribute( 'class_identifier' ) === 'cjw_newsletter_edition' )
                    $editionIds[(int)$parent->attribute( 'contentobject_id' )] = true;
            }
        // links to the article in any send
        $links = array();
        $clicks = 0;
        $linkSends = array();
        foreach ( CjwNewsletterLink::fetchListByContentobjectId( $objectId ) as $link )
        {
            $send = CjwNewsletterEditionSend::fetch( $link->attribute( 'edition_send_id' ) );
            if ( !is_object( $send ) )
                continue;
            $editionIds[(int)$send->attribute( 'edition_contentobject_id' )] = true;
            $linkSends[(int)$send->attribute( 'id' )] = ( isset( $linkSends[(int)$send->attribute( 'id' )] ) ? $linkSends[(int)$send->attribute( 'id' )] : 0 ) + (int)$link->attribute( 'click_count' );
            $clicks += (int)$link->attribute( 'click_count' );
            $links[] = array( 'send_id' => (int)$send->attribute( 'id' ), 'url' => (string)$link->attribute( 'url' ), 'clicks' => (int)$link->attribute( 'click_count' ) );
        }
        $editions = array();
        $sendCount = 0;
        $sentMails = 0;
        foreach ( array_keys( $editionIds ) as $editionId )
        {
            $info = self::objectInfo( $editionId );
            $sends = array();
            foreach ( (array)CjwNewsletterEditionSend::fetchByEditionContentObjectId( $editionId ) as $send )
            {
                $stats = $send->attribute( 'send_items_statistic' );
                $sendId = (int)$send->attribute( 'id' );
                $sends[] = array( 'id' => $sendId, 'created' => (int)$send->attribute( 'created' ), 'status' => (int)$send->attribute( 'status' ),
                    'list' => self::objectInfo( $send->attribute( 'list_contentobject_id' ) ), 'sent' => (int)$stats['items_send'],
                    'tracking_mode' => CjwNewsletterTracking::cleanMode( $send->attribute( 'tracking_mode' ) ),
                    'article_clicks' => isset( $linkSends[$sendId] ) ? $linkSends[$sendId] : 0 );
                $sendCount++;
                $sentMails += (int)$stats['items_send'];
            }
            $info['sends'] = $sends;
            $editions[] = $info;
        }
        return array( 'object_id' => $objectId, 'editions' => $editions, 'links' => $links, 'clicks' => $clicks,
                      'sends' => $sendCount, 'sent' => $sentMails, 'tracking_enabled' => CjwNewsletterTracking::enabled() );
    }

    // ------------------------------------------------------------------ CSV

    /** @return string a cell that a spreadsheet does not run as a formula */
    static function cell( $value )
    {
        $value = (string)$value;
        return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
    }

    /** @return string CSV of the rows (header first) */
    static function csv( $rows )
    {
        $out = fopen( 'php://temp', 'w+' );
        foreach ( $rows as $row )
            fputcsv( $out, array_map( array( __CLASS__, 'cell' ), $row ), ',', '"', '' );
        rewind( $out );
        $csv = stream_get_contents( $out );
        fclose( $out );
        return $csv;
    }

    /**
     * The statistics as CSV rows.
     *
     * @param string $kind sends (one row per send), links (of $sendId) or days (of $sendId, 0 = all)
     * @param int $sendId
     * @return array[] the header row first
     */
    static function exportRows( $kind, $sendId = 0 )
    {
        $sendId = (int)$sendId;
        if ( $kind === 'links' )
        {
            $send = CjwNewsletterEditionSend::fetch( $sendId );
            $rows = array( array( 'edition_send_id', 'link_id', 'position', 'url', 'content_object_id', 'clicks', 'unique_clicks' ) );
            if ( is_object( $send ) )
                foreach ( self::send( $send )['links'] as $l )
                    $rows[] = array( $sendId, $l['id'], $l['position'], $l['url'], $l['object'] ? $l['object']['object_id'] : 0, $l['clicks'], $l['unique_clicks'] );
            return $rows;
        }
        if ( $kind === 'days' )
        {
            $rows = array( array( 'edition_send_id', 'day', 'opens', 'clicks' ) );
            foreach ( self::days( $sendId ) as $d )
                $rows[] = array( $sendId > 0 ? $sendId : '', date( 'Y-m-d', $d['date'] ), $d['opens'], $d['clicks'] );
            return $rows;
        }
        $rows = array( array( 'edition_send_id', 'edition', 'list', 'created', 'started', 'tracking_mode', 'mails', 'sent', 'delivered', 'bounced',
                              'not_sent', 'opens', 'unique_opens', 'open_rate', 'clicks', 'unique_clicks', 'click_rate', 'unsubscribes' ) );
        $sends = $sendId > 0 ? array( CjwNewsletterEditionSend::fetch( $sendId ) )
            : eZPersistentObject::fetchObjectList( CjwNewsletterEditionSend::definition(), null, null, array( 'created' => 'desc' ), null, true );
        foreach ( (array)$sends as $send )
        {
            if ( !is_object( $send ) )
                continue;
            $r = self::send( $send );
            $rows[] = array( $r['id'], $r['edition']['name'], $r['list']['name'], $r['created'] ? date( 'c', $r['created'] ) : '', $r['started'] ? date( 'c', $r['started'] ) : '',
                $r['tracking_mode'], $r['items'], $r['sent'], $r['delivered'], $r['bounced'], $r['not_sent'], $r['opens'], $r['unique_opens'], $r['open_rate'],
                $r['clicks'], $r['unique_clicks'], $r['click_rate'], $r['unsubscribes'] );
        }
        return $rows;
    }
}

?>
