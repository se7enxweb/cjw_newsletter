<?php
/**
 * File containing the CjwNewsletterSeriesRunner class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage series
 */

/**
 * The run of the welcome series: who is due for which step.
 *
 * @package cjw_newsletter
 * @subpackage series
 */
class CjwNewsletterSeriesRunner
{
    /** Subscription status approved (CjwNewsletterSubscription::STATUS_APPROVED), literal to stay kernel-free. */
    const STATUS_APPROVED = 2;

    /**
     * The members that get a step in this run, grouped by the position that is due.
     *
     * Kernel-free. A member is due when its subscription is approved, no send is pending for it,
     * the audience check passes (only when the series has a segment) and the schedule says the next step is due.
     * A paused series has nobody due.
     *
     * @param CjwNewsletterSegmentDb $db
     * @param array $series id, status (0 active, 1 paused), segment_id (0 = whole list)
     * @param array $steps list of position and delay_days
     * @param int $now unix time
     * @param callable $isAudienceMember called with a subscription id, true when it is in the audience
     * @return array position => member rows (associative), only positions with members
     */
    public static function dueMembers( CjwNewsletterSegmentDb $db, array $series, array $steps, $now, $isAudienceMember )
    {
        if ( (int)$series['status'] !== 0 )
        {
            return array();
        }

        $rows = $db->rows(
            'SELECT m.* FROM cjwnl_series_member m'
            . ' INNER JOIN cjwnl_subscription s ON s.id = m.subscription_id'
            . ' WHERE m.series_id = ' . (int)$series['id']
            . ' AND m.pending_send_id = 0'
            . ' AND s.status = ' . self::STATUS_APPROVED
            . ' ORDER BY m.id' );

        $useAudience = (int)$series['segment_id'] > 0;
        $due = array();
        foreach ( $rows as $member )
        {
            $next = CjwNewsletterSeriesSchedule::nextDue( $member, $steps, $now );
            if ( $next === null || !$next['due'] )
            {
                continue;
            }
            if ( $useAudience && !call_user_func( $isAudienceMember, (int)$member['subscription_id'] ) )
            {
                continue;
            }
            $due[$next['position']][] = $member;
        }
        ksort( $due );
        return $due;
    }

    /** Reads [SeriesSettings] PendingTimeoutHours (default 24, at least 1). */
    static function pendingTimeoutHours()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return max( 1, $ini->hasVariable( 'SeriesSettings', 'PendingTimeoutHours' ) ? (int)$ini->variable( 'SeriesSettings', 'PendingTimeoutHours' ) : 24 );
    }

    /**
     * Makes the sends of the steps that are due: one send per due step, with the due members as pending.
     *
     * Needs the kernel. One run at a time (the lock "series"). A broken step (edition missing or not an edition of the
     * series' list) is logged and skipped; so is a step whose edition is being sent already.
     *
     * @param int $now unix time
     * @param object|false $cli eZCLI or CjwNewsletterJobOutput
     * @return array ok, locked, sent, skipped, failed
     */
    static function run( $now, $cli = false )
    {
        $now = (int)$now;
        $out = $cli ? $cli : new CjwNewsletterJobOutput( false );
        $totals = array( 'ok' => true, 'locked' => false, 'sent' => 0, 'skipped' => 0, 'failed' => 0 );
        $lock = CjwNewsletterRunner::lock( 'series' );
        if ( !$lock )
        {
            $totals['ok'] = false;
            $totals['locked'] = true;
            return $totals;
        }
        try
        {
            self::clearStalePending( $now );
            $db = new CjwNewsletterSegmentEzDb();
            foreach ( (array)CjwNewsletterSeries::fetchList() as $series )
            {
                if ( $series->isPaused() )
                    continue;
                self::runSeries( $series, $db, $now, $out, $totals );
            }
        }
        catch ( Exception $e )
        {
            $totals['ok'] = false;
            $totals['failed']++;
            $out->output( '[series] ' . $e->getMessage() );
        }
        catch ( Error $e )
        {
            $totals['ok'] = false;
            $totals['failed']++;
            $out->output( '[series] ' . get_class( $e ) . ': ' . $e->getMessage() );
        }
        CjwNewsletterRunner::unlock( $lock );
        return $totals;
    }

    protected static function runSeries( $series, $db, $now, $out, &$totals )
    {
        $steps = array();
        $stepObjects = array();
        foreach ( $series->steps() as $step )
        {
            $steps[] = array( 'position' => (int)$step->attribute( 'position' ), 'delay_days' => (int)$step->attribute( 'delay_days' ) );
            $stepObjects[(int)$step->attribute( 'position' )] = $step;
        }
        $row = array( 'id' => (int)$series->attribute( 'id' ), 'status' => (int)$series->attribute( 'status' ),
                      'segment_id' => (int)$series->attribute( 'segment_id' ) );
        $audience = null;
        $isAudienceMember = function ( $subscriptionId ) use ( $series, &$audience )
        {
            if ( $audience === null )
                $audience = array_flip( self::audienceIds( $series ) );
            return isset( $audience[(int)$subscriptionId] );
        };
        $due = self::dueMembers( $db, $row, $steps, $now, $isAudienceMember );
        foreach ( $due as $position => $members )
        {
            $step = isset( $stepObjects[$position] ) ? $stepObjects[$position] : null;
            $label = '[series ' . $row['id'] . ' step ' . $position . ']';
            try
            {
                $sendId = $step ? self::makeStepSend( $series, $step, $now, $label, $out ) : 0;
                if ( $sendId === 0 )
                {
                    ++$totals['skipped'];
                    continue;
                }
                foreach ( $members as $member )
                {
                    $object = CjwNewsletterSeriesMember::fetch( (int)$member['id'] );
                    if ( !$object )
                        continue;
                    $object->setAttribute( 'pending_send_id', $sendId );
                    $object->setAttribute( 'pending_position', (int)$position );
                    $object->store();
                }
                ++$totals['sent'];
                $out->output( $label . ' send ' . $sendId . ' for ' . count( $members ) . ' members' );
            }
            catch ( Exception $e )
            {
                ++$totals['failed'];
                $totals['ok'] = false;
                $out->output( $label . ' ' . $e->getMessage() );
            }
            catch ( Error $e )
            {
                ++$totals['failed'];
                $totals['ok'] = false;
                $out->output( $label . ' ' . get_class( $e ) . ': ' . $e->getMessage() );
            }
        }
    }

    /** @return int[] the subscription ids of the series' segment (empty and logged when it is unusable) */
    static function audienceIds( $series )
    {
        $segment = CjwNewsletterSegment::fetch( (int)$series->attribute( 'segment_id' ) );
        if ( !$segment || (int)$segment->attribute( 'list_contentobject_id' ) !== (int)$series->attribute( 'list_contentobject_id' ) )
        {
            CjwNewsletterLog::writeError( 'Series ' . (int)$series->attribute( 'id' ) . ' has a segment that is missing or of another list, nobody is due', __METHOD__ );
            return array();
        }
        $evaluator = new CjwNewsletterSegmentEvaluator( new CjwNewsletterSegmentEzDb(),
            (int)eZINI::instance( 'cjw_newsletter.ini' )->variable( 'SegmentSettings', 'BatchSize' ) );
        return $evaluator->members( (int)$segment->attribute( 'list_contentobject_id' ), (string)$segment->attribute( 'match_mode' ),
            $segment->conditionArray(), time(), CjwNewsletterStatisticsRetention::cutoff( time() ),
            function ( $userId )
            {
                return CjwNewsletterTracking::hasConsent( CjwNewsletterUser::fetch( (int)$userId ), true );
            } );
    }

    /** @return int the id of the made send, 0 when the step is broken or its edition is being sent */
    protected static function makeStepSend( $series, $step, $now, $label, $out )
    {
        $edition = eZContentObject::fetch( (int)$step->attribute( 'edition_contentobject_id' ) );
        $content = CjwNewsletterEditionBuilder::editionContent( $edition );
        $parent = $edition ? $edition->attribute( 'main_node' ) : null;
        $parent = $parent ? $parent->attribute( 'parent' ) : null;
        if ( !$content || !$parent || (int)$parent->attribute( 'contentobject_id' ) !== (int)$series->attribute( 'list_contentobject_id' ) )
        {
            $out->output( $label . ' broken: the edition is missing or not an edition of the list' );
            CjwNewsletterLog::writeError( $label . ' broken step, skipped', __METHOD__ );
            return 0;
        }
        if ( $content->isProcess() )
        {
            $out->output( $label . ' skipped: the edition is being sent already' );
            return 0;
        }
        $send = $content->createNewsletterSendObject( $now );
        if ( !is_object( $send ) )
        {
            $out->output( $label . ' the send could not be made' );
            return 0;
        }
        $send->setAttribute( 'series_step_id', (int)$step->attribute( 'id' ) );
        $send->store();
        CjwNewsletterExtensionPoints::call( 'sendFormStored', array( $send, eZHTTPTool::instance(), $edition->attribute( 'current' ) ) );
        return (int)$send->attribute( 'id' );
    }

    /**
     * Clears pending markers whose send is gone or aborted, or is older than the timeout and has no send item.
     */
    protected static function clearStalePending( $now )
    {
        $limit = $now - self::pendingTimeoutHours() * 3600;
        foreach ( (array)CjwNewsletterSeriesMember::fetchList( array( 'pending_send_id' => array( '>', 0 ) ) ) as $member )
        {
            $send = CjwNewsletterEditionSend::fetch( (int)$member->attribute( 'pending_send_id' ) );
            $stale = !$send || (int)$send->attribute( 'status' ) === CjwNewsletterEditionSend::STATUS_ABORT;
            if ( !$stale && (int)$send->attribute( 'created' ) < $limit )
                $stale = CjwNewsletterEditionSendItem::fetchListBySendIdAndStatusCount( (int)$send->attribute( 'id' ), CjwNewsletterEditionSendItem::STATUS_NEW ) === 0
                      && CjwNewsletterEditionSendItem::fetchListBySendIdAndStatusCount( (int)$send->attribute( 'id' ), CjwNewsletterEditionSendItem::STATUS_SEND ) === 0;
            if ( $stale )
            {
                $member->setAttribute( 'pending_send_id', 0 );
                $member->setAttribute( 'pending_position', 0 );
                $member->store();
            }
        }
    }
}
