<?php
/**
 * File containing the CjwNewsletterAbTester class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * The A/B subject test of a send (cjw_newsletter 4.2.0, area N4 Statistics).
 *
 * 1. The send form stores a test (cjwnl_ab_test, status 0 sampling) with its variants: A is the edition's own subject,
 *    B (and more) the subjects the editor typed.
 * 2. When the mail queue is made, a random sample of [ABTestSettings] SamplePercent of the recipients per variant gets
 *    a variant (cjwnl_edition_send_item.ab_variant_id). The other items are stored again behind the samples, so the
 *    queue sends the samples first; the first item without a variant stops the send (status 1 waiting).
 * 3. After the wait, the variant with the best open rate wins (unique opens of the people who agreed to the
 *    statistics, per sent mail of the variant); with anonymous totals only, or when nobody's opens are known, the click
 *    rate decides. Ties go to the earlier variant. The rest of the list then gets the winner's subject (status 2, then
 *    3 done when the send has finished).
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterAbTester
{
    const STATUS_SAMPLING = 0;
    const STATUS_WAITING = 1;
    const STATUS_DECIDED = 2;
    const STATUS_DONE = 3;
    const STATUS_CANCELLED = 9;

    /** @var array send id => CjwNewsletterAbTest|false, for the queue run */
    protected static $cache = array();

    /** @return array sample_percent, variants, criterion, wait_hours from [ABTestSettings] */
    static function defaults()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $get = function ( $name, $default ) use ( $ini ) {
            return $ini->hasVariable( 'ABTestSettings', $name ) ? $ini->variable( 'ABTestSettings', $name ) : $default;
        };
        $criterion = (string)$get( 'Criterion', 'open' );
        return array( 'sample_percent' => max( 1, min( 49, (int)$get( 'SamplePercent', 10 ) ) ),
                      'variants' => max( 2, min( 5, (int)$get( 'Variants', 2 ) ) ),
                      'criterion' => in_array( $criterion, array( 'open', 'click' ), true ) ? $criterion : 'open',
                      'wait_hours' => max( 0, (float)$get( 'WaitHours', 4 ) ) );
    }

    /** @return string[] status => name */
    static function statusNames()
    {
        return array( self::STATUS_SAMPLING => 'sampling', self::STATUS_WAITING => 'waiting', self::STATUS_DECIDED => 'winner chosen',
                      self::STATUS_DONE => 'done', self::STATUS_CANCELLED => 'cancelled' );
    }

    /**
     * Checks the values of a test as the send form posts them.
     *
     * @param string[] $subjects the subjects of the variants B, C, ... (empty ones are left out)
     * @param int $samplePercent per variant
     * @param float $waitHours
     * @return string[] error strings
     */
    static function validate( $subjects, $samplePercent, $waitHours, $trackingMode )
    {
        $errors = array();
        $subjects = array_values( array_filter( array_map( 'trim', (array)$subjects ), 'strlen' ) );
        if ( !$subjects )
            $errors[] = ezpI18n::tr( 'cjw_newsletter/statistics', 'The A/B test needs at least one other subject.' );
        foreach ( $subjects as $s )
            if ( mb_strlen( $s ) > 255 )
                $errors[] = ezpI18n::tr( 'cjw_newsletter/statistics', 'A subject is longer than 255 characters.' );
        $variants = count( $subjects ) + 1;
        if ( !is_numeric( $samplePercent ) || (int)$samplePercent < 1 || (int)$samplePercent * $variants >= 100 )
            $errors[] = ezpI18n::tr( 'cjw_newsletter/statistics', 'The sample must be between 1 % and %max % per variant.', null, array( '%max' => max( 1, (int)floor( 99 / $variants ) ) ) );
        if ( !is_numeric( $waitHours ) || (float)$waitHours < 0 || (float)$waitHours > 336 )
            $errors[] = ezpI18n::tr( 'cjw_newsletter/statistics', 'The wait must be between 0 and 336 hours.' );
        if ( (int)$trackingMode === CjwNewsletterTracking::MODE_OFF || !CjwNewsletterTracking::enabled() )
            $errors[] = ezpI18n::tr( 'cjw_newsletter/statistics', 'An A/B test needs tracking (anonymous totals or per person) for this send.' );
        return $errors;
    }

    /**
     * Makes the test of a send.
     *
     * @param CjwNewsletterEditionSend $send
     * @param string[] $subjects variants B, C, ...
     * @param int $samplePercent
     * @param string $criterion open or click
     * @param float $waitHours
     * @return CjwNewsletterAbTest
     */
    static function create( $send, $subjects, $samplePercent, $criterion, $waitHours )
    {
        $subjects = array_values( array_filter( array_map( 'trim', (array)$subjects ), 'strlen' ) );
        $now = time();
        $test = CjwNewsletterAbTest::create( array( 'edition_send_id' => (int)$send->attribute( 'id' ), 'status' => self::STATUS_SAMPLING,
            'sample_percent' => (int)$samplePercent, 'variant_count' => count( $subjects ) + 1,
            'criterion' => $criterion === 'click' ? 'click' : 'open', 'wait_seconds' => (int)round( (float)$waitHours * 3600 ),
            'created' => $now, 'modified' => $now ) );
        $test->store();
        // A keeps the edition's subject (empty = unchanged)
        $keys = range( 'A', 'Z' );
        $variant = CjwNewsletterAbVariant::create( array( 'ab_test_id' => (int)$test->attribute( 'id' ), 'variant_key' => 'A', 'subject' => '', 'created' => $now ) );
        $variant->store();
        foreach ( $subjects as $i => $subject )
        {
            $variant = CjwNewsletterAbVariant::create( array( 'ab_test_id' => (int)$test->attribute( 'id' ), 'variant_key' => $keys[$i + 1],
                'subject' => mb_substr( $subject, 0, 255 ), 'created' => $now ) );
            $variant->store();
        }
        $send->setAttribute( 'ab_test_id', (int)$test->attribute( 'id' ) );
        $send->store();
        unset( self::$cache[(int)$send->attribute( 'id' )] );
        return $test;
    }

    /** @return CjwNewsletterAbTest|null the test of a send */
    static function forSend( $send, $cached = false )
    {
        $sendId = (int)$send->attribute( 'id' );
        if ( (int)$send->attribute( 'ab_test_id' ) <= 0 )
            return null;
        if ( $cached && array_key_exists( $sendId, self::$cache ) )
            return self::$cache[$sendId] ? self::$cache[$sendId] : null;
        $test = CjwNewsletterAbTest::fetch( (int)$send->attribute( 'ab_test_id' ) );
        if ( $test && (int)$test->attribute( 'edition_send_id' ) !== $sendId )
            $test = null;
        self::$cache[$sendId] = $test ? $test : false;
        return $test;
    }

    static function resetCache()
    {
        self::$cache = array();
    }

    /** @return CjwNewsletterAbVariant[] in key order */
    static function variants( $test )
    {
        return CjwNewsletterAbVariant::fetchList( array( 'ab_test_id' => (int)$test->attribute( 'id' ) ), 0, 0, array( 'variant_key' => 'asc' ) );
    }

    /**
     * Gives the samples their variants and puts the other items behind them (queue creation).
     *
     * @return array samples, rest (counts)
     */
    static function assignSamples( $send, $test )
    {
        $db = eZDB::instance();
        $sendId = (int)$send->attribute( 'id' );
        $variants = self::variants( $test );
        if ( count( $variants ) < 2 )
            return array( 'samples' => 0, 'rest' => 0 );
        $rows = $db->arrayQuery( 'SELECT id FROM cjwnl_edition_send_item WHERE edition_send_id = ' . $sendId . ' AND status = ' . CjwNewsletterEditionSendItem::STATUS_NEW . ' ORDER BY id' );
        $ids = array_map( 'intval', array_column( (array)$rows, 'id' ) );
        $n = count( $ids );
        if ( $n === 0 )
            return array( 'samples' => 0, 'rest' => 0 );
        $per = max( 1, (int)floor( $n * (int)$test->attribute( 'sample_percent' ) / 100 ) );
        $shuffled = $ids;
        shuffle( $shuffled );
        $sampleCount = min( $n, $per * count( $variants ) );
        $samples = array_slice( $shuffled, 0, $sampleCount );
        $db->begin();
        foreach ( $samples as $i => $itemId )
        {
            $variantId = (int)$variants[$i % count( $variants )]->attribute( 'id' );
            $db->query( 'UPDATE cjwnl_edition_send_item SET ab_variant_id = ' . $variantId . ' WHERE id = ' . (int)$itemId );
        }
        // the queue sends in the order of the ids: the rest is stored again (same values, new ids) behind the samples
        $maxSample = $samples ? max( $samples ) : 0;
        $sampleSet = array_flip( $samples );
        $moved = 0;
        foreach ( $ids as $itemId )
        {
            if ( isset( $sampleSet[$itemId] ) || $itemId > $maxSample )
                continue;
            $row = eZPersistentObject::fetchObject( CjwNewsletterEditionSendItem::definition(), null, array( 'id' => $itemId ), false );
            if ( !is_array( $row ) )
                continue;
            $db->query( 'DELETE FROM cjwnl_edition_send_item WHERE id = ' . (int)$itemId );
            unset( $row['id'] );
            $row['ab_variant_id'] = 0;
            $copy = new CjwNewsletterEditionSendItem( $row );
            $copy->store();
            $moved++;
        }
        $db->commit();
        return array( 'samples' => $sampleCount, 'rest' => $n - $sampleCount, 'moved' => $moved );
    }

    /** @return int the NEW sample items left */
    static function samplesLeft( $test )
    {
        $rows = eZDB::instance()->arrayQuery( 'SELECT COUNT(*) AS c FROM cjwnl_edition_send_item WHERE edition_send_id = ' . (int)$test->attribute( 'edition_send_id' )
            . ' AND ab_variant_id > 0 AND status = ' . CjwNewsletterEditionSendItem::STATUS_NEW );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }

    /** The samples are out: the test waits. */
    static function markWaiting( $test, $now = null )
    {
        if ( (int)$test->attribute( 'status' ) !== self::STATUS_SAMPLING )
            return;
        $now = $now === null ? time() : (int)$now;
        $test->setAttribute( 'status', self::STATUS_WAITING );
        $test->setAttribute( 'samples_sent', $now );
        $test->setAttribute( 'modified', $now );
        $test->store();
    }

    /** @return bool the wait is over */
    static function due( $test, $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        return (int)$test->attribute( 'status' ) === self::STATUS_WAITING
            && (int)$test->attribute( 'samples_sent' ) + (int)$test->attribute( 'wait_seconds' ) <= $now;
    }

    /**
     * open or click: what decides the test of this send.
     */
    static function effectiveCriterion( $test, $send )
    {
        if ( CjwNewsletterTracking::cleanMode( $send->attribute( 'tracking_mode' ) ) !== CjwNewsletterTracking::MODE_PERSON )
            return 'click';
        if ( (string)$test->attribute( 'criterion' ) === 'click' )
            return 'click';
        $opens = 0;
        foreach ( self::variants( $test ) as $v )
            $opens += (int)$v->attribute( 'open_count' );
        return $opens > 0 ? 'open' : 'click';
    }

    /**
     * @return array variant id => rate (0..1) by the criterion
     */
    static function rates( $test, $criterion )
    {
        $rates = array();
        foreach ( self::variants( $test ) as $v )
        {
            $sent = (int)$v->attribute( 'item_count' );
            $hits = (int)$v->attribute( $criterion === 'open' ? 'open_count' : 'click_count' );
            $rates[(int)$v->attribute( 'id' )] = $sent > 0 ? $hits / $sent : 0.0;
        }
        return $rates;
    }

    /**
     * Chooses the winner.
     *
     * @return CjwNewsletterAbVariant|null the winner
     */
    static function decide( $test, $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        $status = (int)$test->attribute( 'status' );
        if ( !in_array( $status, array( self::STATUS_SAMPLING, self::STATUS_WAITING ), true ) )
            return $test->attribute( 'winner_variant_id' ) ? CjwNewsletterAbVariant::fetch( $test->attribute( 'winner_variant_id' ) ) : null;
        $send = CjwNewsletterEditionSend::fetch( $test->attribute( 'edition_send_id' ) );
        if ( !is_object( $send ) )
            return null;
        $criterion = self::effectiveCriterion( $test, $send );
        $winner = null;
        $best = -1.0;
        $rates = self::rates( $test, $criterion );
        foreach ( self::variants( $test ) as $v )
        {
            $rate = $rates[(int)$v->attribute( 'id' )];
            if ( $rate > $best + 1e-12 )
            {
                $best = $rate;
                $winner = $v;
            }
        }
        if ( !$winner )
            return null;
        if ( !(int)$test->attribute( 'samples_sent' ) )
            $test->setAttribute( 'samples_sent', $now );
        $test->setAttribute( 'winner_variant_id', (int)$winner->attribute( 'id' ) );
        $test->setAttribute( 'decided', $now );
        $test->setAttribute( 'status', self::STATUS_DECIDED );
        $test->setAttribute( 'criterion', $criterion );
        $test->setAttribute( 'modified', $now );
        $test->store();
        unset( self::$cache[(int)$send->attribute( 'id' )] );
        return $winner;
    }

    /** Cancels a test: the rest of the list gets the edition's own subject. */
    static function cancel( $test )
    {
        $status = (int)$test->attribute( 'status' );
        if ( $status === self::STATUS_DONE || $status === self::STATUS_CANCELLED )
            return false;
        $test->setAttribute( 'status', self::STATUS_CANCELLED );
        $test->setAttribute( 'modified', time() );
        $test->store();
        unset( self::$cache[(int)$test->attribute( 'edition_send_id' )] );
        return true;
    }

    /**
     * The subject of an item, or null for the edition's own.
     */
    static function subjectFor( $test, $sendItem )
    {
        $variantId = (int)$sendItem->attribute( 'ab_variant_id' );
        if ( $variantId <= 0 )
        {
            if ( (int)$test->attribute( 'status' ) === self::STATUS_CANCELLED )
                return null;
            $variantId = (int)$test->attribute( 'winner_variant_id' );
        }
        if ( $variantId <= 0 )
            return null;
        $variant = self::variantById( $variantId );
        if ( !$variant || (int)$variant->attribute( 'ab_test_id' ) !== (int)$test->attribute( 'id' ) )
            return null;
        $subject = (string)$variant->attribute( 'subject' );
        return $subject !== '' ? $subject : null;
    }

    /** @return CjwNewsletterAbVariant|null, cached for the queue run */
    static function variantById( $id )
    {
        static $variants = array();
        if ( !array_key_exists( $id, $variants ) || count( $variants ) > 100 )
        {
            if ( count( $variants ) > 100 )
                $variants = array();
            $variants[$id] = CjwNewsletterAbVariant::fetch( $id );
        }
        return $variants[$id];
    }

    /**
     * The test for the templates (report, A/B page, dashboard).
     *
     * @return array
     */
    static function summary( $test, $send = null )
    {
        if ( $send === null )
            $send = CjwNewsletterEditionSend::fetch( $test->attribute( 'edition_send_id' ) );
        $names = self::statusNames();
        $status = (int)$test->attribute( 'status' );
        $criterion = is_object( $send ) && in_array( $status, array( self::STATUS_SAMPLING, self::STATUS_WAITING ), true )
            ? self::effectiveCriterion( $test, $send ) : (string)$test->attribute( 'criterion' );
        $rates = self::rates( $test, $criterion );
        $variants = array();
        $editionSubject = '';
        if ( is_object( $send ) )
        {
            $parsed = $send->getParsedOutputXml();
            $first = reset( $parsed );
            $editionSubject = is_array( $first ) && isset( $first['subject'] ) ? (string)$first['subject'] : '';
        }
        foreach ( self::variants( $test ) as $v )
        {
            $sent = (int)$v->attribute( 'item_count' );
            $variants[] = array( 'id' => (int)$v->attribute( 'id' ), 'key' => (string)$v->attribute( 'variant_key' ),
                'subject' => (string)$v->attribute( 'subject' ) !== '' ? (string)$v->attribute( 'subject' ) : $editionSubject,
                'own_subject' => (string)$v->attribute( 'subject' ) === '',
                'sent' => $sent, 'opens' => (int)$v->attribute( 'open_count' ), 'clicks' => (int)$v->attribute( 'click_count' ),
                'open_rate' => $sent > 0 ? round( 100 * (int)$v->attribute( 'open_count' ) / $sent, 1 ) : 0,
                'click_rate' => $sent > 0 ? round( 100 * (int)$v->attribute( 'click_count' ) / $sent, 1 ) : 0,
                'rate' => round( 100 * $rates[(int)$v->attribute( 'id' )], 1 ),
                'winner' => (int)$v->attribute( 'id' ) === (int)$test->attribute( 'winner_variant_id' ) );
        }
        $due = (int)$test->attribute( 'samples_sent' ) > 0 ? (int)$test->attribute( 'samples_sent' ) + (int)$test->attribute( 'wait_seconds' ) : 0;
        return array( 'id' => (int)$test->attribute( 'id' ), 'edition_send_id' => (int)$test->attribute( 'edition_send_id' ),
            'status_code' => $status, 'status' => isset( $names[$status] ) ? $names[$status] : (string)$status,
            'sample_percent' => (int)$test->attribute( 'sample_percent' ), 'criterion' => $criterion,
            'configured_criterion' => (string)$test->attribute( 'criterion' ),
            'wait_hours' => round( (int)$test->attribute( 'wait_seconds' ) / 3600, 2 ),
            'samples_sent' => (int)$test->attribute( 'samples_sent' ), 'decide_at' => $due, 'decided' => (int)$test->attribute( 'decided' ),
            'open' => in_array( $status, array( self::STATUS_SAMPLING, self::STATUS_WAITING ), true ),
            'variants' => $variants );
    }
}

?>
