<?php
/**
 * File containing the CjwNewsletterScheduleRunner class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * Runs the recurring sends whose time has come (cjwnl_schedule): from the queue-create part of the cronjob (the
 * extension point queueCreateBefore, when [ScheduleSettings] Schedules=enabled) and from ext:cjw_newsletter:schedule.
 *
 * One run of a schedule:
 *  1. the condition handler, if the schedule has one, may skip the run (skipped_condition);
 *  2. mode latest: the newest edition of the list that was never sent; none = skipped_empty;
 *     mode copy: with auto-fill, the articles of the pool published since the last send are looked up first (the
 *     anonymous user's view; articles earlier editions of the schedule carried are left out); none and
 *     skip_if_empty = skipped_empty, and no copy is made; then the template edition is copied under the list and the
 *     articles are taken into the copy;
 *  3. if the list needs an approval and the version is not approved, the approval is asked for (the send waits);
 *  4. the send is made for now; the queue-create run that called this makes its queue in the same run.
 * Each run is a row of cjwnl_schedule_log; the next run is computed from the time of the run, so runs missed while
 * the cron was down are not made up. A dry run writes nothing (no log, no edition, no send).
 *
 * One run at a time: the lock "schedule" (CjwNewsletterRunner::lock).
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterScheduleRunner
{
    const LAST_RUN = 'cjw_newsletter_last_schedule';

    /**
     * Runs the due schedules (or one schedule, with $scheduleId, also when it is not due when $force is set).
     *
     * @param object|false $cli eZCLI or CjwNewsletterJobOutput
     * @param string $by cron, console, job, admin
     * @param bool $dryRun
     * @param int|null $now the time of the run (null = now; --at of the command, for tests)
     * @param int $scheduleId 0 = every due schedule
     * @param bool $force run $scheduleId even when it is not due or paused
     * @return array ok, locked, due, sent, skipped, failed, runs (one report per schedule)
     */
    static function runDue( $cli = false, $by = 'cron', $dryRun = false, $now = null, $scheduleId = 0, $force = false )
    {
        $now = $now === null ? time() : (int)$now;
        $out = $cli ? $cli : new CjwNewsletterJobOutput( false );
        $totals = array( 'ok' => true, 'locked' => false, 'due' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 0, 'runs' => array() );
        $lock = $dryRun ? true : CjwNewsletterRunner::lock( 'schedule' );
        if ( !$lock )
        {
            $totals['ok'] = false;
            $totals['locked'] = true;
            return $totals;
        }
        if ( (int)$scheduleId > 0 )
        {
            $one = CjwNewsletterSchedule::fetch( (int)$scheduleId );
            $schedules = ( $one && ( $force || $one->isDue( $now ) ) && (int)$one->attribute( 'status' ) !== CjwNewsletterSchedule::STATUS_REMOVED ) ? array( $one ) : array();
        }
        else
        {
            $schedules = CjwNewsletterSchedule::fetchDue( $now, self::maxRunsPerCall() );
        }
        $totals['due'] = count( $schedules );
        foreach ( $schedules as $schedule )
        {
            $report = self::runOne( $schedule, $now, $dryRun, $out );
            $totals['runs'][] = $report;
            if ( $report['result'] === 'sent' )
                ++$totals['sent'];
            else if ( $report['result'] === 'failed' )
                ++$totals['failed'];
            else
                ++$totals['skipped'];
        }
        if ( !$dryRun )
        {
            if ( $totals['failed'] )
                $totals['errors'] = array( $totals['failed'] . ' schedule runs failed' );
            $record = $totals;
            unset( $record['runs'] );
            CjwNewsletterRunner::recordRun( self::LAST_RUN, $record, $by );
            CjwNewsletterRunner::audit( 'schedule', $by, $record, false );
            CjwNewsletterRunner::unlock( $lock );
        }
        return $totals;
    }

    /** @return int [ScheduleSettings] MaxRunsPerCall (at least 1) */
    static function maxRunsPerCall()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return max( 1, $ini->hasVariable( 'ScheduleSettings', 'MaxRunsPerCall' ) ? (int)$ini->variable( 'ScheduleSettings', 'MaxRunsPerCall' ) : 10 );
    }

    /** @return bool [ScheduleSettings] Schedules=enabled: the cronjob runs the schedules */
    static function enabled()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'ScheduleSettings', 'Schedules' ) && $ini->variable( 'ScheduleSettings', 'Schedules' ) === 'enabled';
    }

    /**
     * @return string[] class name => shown name, the condition handlers of [ScheduleSettings] ConditionHandlers[] that exist
     */
    static function conditionHandlers()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $result = array();
        foreach ( $ini->hasVariable( 'ScheduleSettings', 'ConditionHandlers' ) ? (array)$ini->variable( 'ScheduleSettings', 'ConditionHandlers' ) : array() as $class )
        {
            $class = trim( (string)$class );
            if ( $class !== '' && class_exists( $class ) && in_array( 'CjwNewsletterScheduleConditionInterface', class_implements( $class ), true ) )
                $result[$class] = (string)call_user_func( array( $class, 'name' ) );
        }
        return $result;
    }

    /**
     * One run of one schedule.
     *
     * @return array schedule_id, result (sent, skipped_empty, skipped_condition, skipped_dry_run, failed), edition_id,
     *               send_id, article_count, message, next_run
     */
    static function runOne( $schedule, $now, $dryRun, $out )
    {
        $report = array( 'schedule_id' => (int)$schedule->attribute( 'id' ), 'result' => 'failed', 'edition_id' => 0, 'send_id' => 0,
                         'article_count' => 0, 'message' => '', 'next_run' => $schedule->nextRunAfter( $now ) );
        try
        {
            self::execute( $schedule, $now, $dryRun, $report );
        }
        catch ( Exception $e )
        {
            $report['result'] = 'failed';
            $report['message'] = $e->getMessage();
        }
        catch ( Error $e )
        {
            $report['result'] = 'failed';
            $report['message'] = get_class( $e ) . ': ' . $e->getMessage();
        }
        if ( $dryRun && $report['result'] !== 'failed' && strpos( $report['result'], 'skipped_' ) !== 0 )
            $report['result'] = 'skipped_dry_run';

        $out->output( sprintf( '[schedule %d] %s%s', $report['schedule_id'], $report['result'],
                               $report['message'] !== '' ? ': ' . $report['message'] : '' ) );
        if ( $dryRun )
            return $report;

        $log = CjwNewsletterScheduleLog::create( array(
            'schedule_id' => $report['schedule_id'],
            'run_at' => (int)$now,
            'result' => $report['result'],
            'edition_contentobject_id' => (int)$report['edition_id'],
            'edition_send_id' => (int)$report['send_id'],
            'article_count' => (int)$report['article_count'],
            'message' => mb_substr( (string)$report['message'], 0, 4000 ) ) );
        $log->store();
        $fresh = CjwNewsletterSchedule::fetch( $report['schedule_id'] );
        if ( $fresh )
        {
            $fresh->setAttribute( 'last_run', (int)$now );
            $fresh->setAttribute( 'last_result', $report['result'] );
            if ( $report['send_id'] )
                $fresh->setAttribute( 'last_edition_send_id', (int)$report['send_id'] );
            if ( $fresh->isActive() )
                $fresh->setAttribute( 'next_run', (int)$report['next_run'] );
            $fresh->setAttribute( 'modified', time() );
            $fresh->store();
        }
        return $report;
    }

    /** The steps of a run; fills $report. */
    protected static function execute( $schedule, $now, $dryRun, &$report )
    {
        $listId = (int)$schedule->attribute( 'list_contentobject_id' );
        $list = CjwNewsletterList::fetchByListObjectVersion( $listId, 0 );
        if ( !is_object( $list ) )
        {
            $report['message'] = 'The newsletter list ' . $listId . ' does not exist.';
            return;
        }

        $handler = trim( (string)$schedule->attribute( 'condition_handler' ) );
        if ( $handler !== '' )
        {
            $handlers = self::conditionHandlers();
            if ( !isset( $handlers[$handler] ) )
            {
                $report['message'] = 'The condition handler ' . $handler . ' is not listed in [ScheduleSettings] ConditionHandlers[] or does not exist.';
                return;
            }
            if ( !call_user_func( array( $handler, 'isMet' ), $schedule, (int)$now ) )
            {
                $report['result'] = 'skipped_condition';
                $report['message'] = $handlers[$handler];
                return;
            }
        }

        if ( $schedule->attribute( 'mode' ) === CjwNewsletterSchedule::MODE_COPY )
        {
            $template = eZContentObject::fetch( (int)$schedule->attribute( 'template_edition_contentobject_id' ) );
            if ( !$template || $template->attribute( 'class_identifier' ) !== 'cjw_newsletter_edition' )
            {
                $report['message'] = 'The template edition ' . (int)$schedule->attribute( 'template_edition_contentobject_id' ) . ' does not exist.';
                return;
            }
            $nodes = array();
            $pool = null;
            if ( (int)$schedule->attribute( 'auto_fill' ) )
            {
                $pool = $schedule->articlePool();
                // not the template's own articles, nor what earlier copies carried
                $exclude = CjwNewsletterEditionBuilder::childObjectIds( $template );
                foreach ( $schedule->editionObjectIds() as $editionId )
                    $exclude = array_merge( $exclude, CjwNewsletterEditionBuilder::pickedObjectIds( $editionId ) );
                $nodes = CjwNewsletterArticlePoolFinder::find( $pool, array( 'since' => $schedule->lastSentTime(), 'until' => (int)$now + 1,
                                                                              'anonymous' => true, 'exclude_object_ids' => $exclude ) );
                $report['article_count'] = count( $nodes );
                if ( !$nodes && (int)$schedule->attribute( 'skip_if_empty' ) )
                {
                    $report['result'] = 'skipped_empty';
                    $report['message'] = 'No new articles in the pool "' . $pool->label() . '".';
                    return;
                }
            }
            if ( $dryRun )
            {
                $report['result'] = 'sent';
                $report['message'] = 'Would copy "' . $template->attribute( 'name' ) . '"' . ( $pool ? ' with ' . count( $nodes ) . ' articles' : '' ) . ' and send it.';
                return;
            }
            $title = self::copyTitle( $template, $schedule, $now );
            $edition = CjwNewsletterEditionBuilder::copyEdition( $template, $listId, $title );
            if ( !$edition )
            {
                $report['message'] = 'The template edition could not be copied.';
                return;
            }
            $report['edition_id'] = (int)$edition->attribute( 'id' );
            $added = 0;
            foreach ( $nodes as $node )
                if ( CjwNewsletterEditionBuilder::addArticle( $edition, $node, CjwNewsletterEditionArticle::ADDED_BY_AUTO_FILL, $pool ? (int)$pool->attribute( 'id' ) : 0 ) )
                    ++$added;
            $report['article_count'] = $added;
            $edition = eZContentObject::fetch( $edition->attribute( 'id' ) );
        }
        else
        {
            $edition = CjwNewsletterEditionBuilder::latestUnsentEdition( $listId );
            if ( !$edition )
            {
                $report['result'] = 'skipped_empty';
                $report['message'] = 'The list has no edition that was not sent yet.';
                return;
            }
            $report['edition_id'] = (int)$edition->attribute( 'id' );
            if ( $dryRun )
            {
                $report['result'] = 'sent';
                $report['message'] = 'Would send "' . $edition->attribute( 'name' ) . '".';
                return;
            }
        }

        $content = CjwNewsletterEditionBuilder::editionContent( $edition );
        if ( !$content )
        {
            $report['message'] = 'The edition ' . (int)$edition->attribute( 'id' ) . ' has no newsletter data.';
            return;
        }
        if ( $content->isProcess() )
        {
            $report['result'] = 'skipped_empty';
            $report['message'] = 'The edition is being sent already.';
            return;
        }

        $version = (int)$edition->attribute( 'current_version' );
        $notes = array();
        if ( CjwNewsletterApprovalFlow::isRequired( $listId ) && !CjwNewsletterApprovalFlow::isApproved( $edition->attribute( 'id' ), $version ) )
        {
            $creator = (int)$schedule->attribute( 'creator_contentobject_id' );
            CjwNewsletterApprovalFlow::request( $edition, $version, $creator > 0 ? $creator : (int)eZUser::currentUserID(),
                ezpI18n::tr( 'cjw_newsletter/editorial', 'Made by the recurring send %id.', null, array( '%id' => (int)$schedule->attribute( 'id' ) ) ) );
            $notes[] = 'The send waits for the approval of the edition.';
        }

        $send = $content->createNewsletterSendObject( (int)$now );
        if ( !is_object( $send ) )
        {
            $report['message'] = 'The send could not be made.';
            return;
        }
        $send->setAttribute( 'schedule_id', (int)$schedule->attribute( 'id' ) );
        if ( (int)$schedule->attribute( 'creator_contentobject_id' ) > 0 )
            $send->setAttribute( 'creator_id', (int)$schedule->attribute( 'creator_contentobject_id' ) );
        $send->store();
        // the other areas set their per-send values as for a send of the form without choices (the list's defaults)
        CjwNewsletterExtensionPoints::call( 'sendFormStored', array( $send, eZHTTPTool::instance(), $edition->attribute( 'current' ) ) );

        $report['send_id'] = (int)$send->attribute( 'id' );
        $report['result'] = 'sent';
        $report['message'] = trim( '"' . $edition->attribute( 'name' ) . '"' . ( $report['article_count'] ? ', ' . $report['article_count'] . ' articles' : '' ) . '. ' . implode( ' ', $notes ) );
    }

    /**
     * @return string the title of a copy: [ScheduleSettings] CopyTitle with %title (the template's title) and %date
     */
    static function copyTitle( $template, $schedule, $now )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $pattern = $ini->hasVariable( 'ScheduleSettings', 'CopyTitle' ) ? (string)$ini->variable( 'ScheduleSettings', 'CopyTitle' ) : '%title %date';
        $format = $ini->hasVariable( 'ScheduleSettings', 'CopyTitleDateFormat' ) ? (string)$ini->variable( 'ScheduleSettings', 'CopyTitleDateFormat' ) : 'Y-m-d';
        $date = new DateTime( '@' . (int)$now );
        $date->setTimezone( new DateTimeZone( $schedule->timezoneName() ) );
        $map = $template->attribute( 'data_map' );
        $title = isset( $map['title'] ) ? trim( (string)$map['title']->attribute( 'data_text' ) ) : '';
        if ( $title === '' )
            $title = (string)$template->attribute( 'name' );
        return trim( strtr( $pattern, array( '%title' => $title, '%date' => $date->format( $format !== '' ? $format : 'Y-m-d' ) ) ) );
    }
}
