<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:schedule - the recurring sends.
 *
 *   list                    the schedules with their next run (nothing changes)
 *   run                     run the schedules that are due (as the cronjob does when [ScheduleSettings] Schedules=enabled)
 *   run --id=<n> --force    run one schedule now, due or not
 *   --dry-run               say what would happen, write nothing (no edition, no send, no log)
 *   --at=<time>             the time of the run instead of now ("2026-10-12 08:00" or a Unix time), for tests
 */
class Schedule extends \Exponential\Runnable\Command
{
    protected function commandName() { return 'schedule'; }

    public function run()
    {
        $this->script( array( 'description' => 'List or run the recurring newsletter sends',
                              'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $options = $this->startup( '[dry-run][at:][id:][force][job:]', '[action]',
            array( 'dry-run' => 'Say what would be done, change nothing',
                   'at' => 'The time of the run instead of now: "YYYY-MM-DD HH:MM" (in the time zone of PHP) or a Unix time',
                   'id' => 'Only this schedule',
                   'force' => 'With --id: run the schedule even when it is not due or paused',
                   'job' => 'The ID of a background job (set by the admin)' ) );
        $jobID = ( !empty( $options['job'] ) && \CjwNewsletterJob::isID( $options['job'] ) ) ? $options['job'] : false;
        $out = new \CjwNewsletterJobOutput( $jobID ? false : $this->cli(), $jobID );
        if ( $jobID )
            \CjwNewsletterJob::markRunning( $jobID, $this->commandName() );
        $admin = \eZUser::fetchByName( 'admin' );

        $arguments = isset( $options['arguments'] ) ? array_values( (array)$options['arguments'] ) : array();
        $action = $arguments ? (string)$arguments[0] : 'list';
        $now = time();
        if ( !empty( $options['at'] ) )
        {
            $at = (string)$options['at'];
            $now = ctype_digit( $at ) ? (int)$at : strtotime( $at );
            if ( !$now )
            {
                $out->error( 'Cannot read the time of --at: ' . $at );
                return $this->done( $jobID, 2, array( 'error' => 'bad --at' ) );
            }
        }
        $id = isset( $options['id'] ) ? (int)$options['id'] : 0;

        if ( $action === 'list' )
        {
            $schedules = $id > 0 ? array_filter( array( \CjwNewsletterSchedule::fetch( $id ) ) ) : \CjwNewsletterSchedule::fetchVisible();
            if ( !$schedules )
                $out->output( 'There are no recurring sends.' );
            foreach ( $schedules as $schedule )
            {
                $out->output( sprintf( '#%d  %-8s %-6s %s  list "%s"%s  next %s  last %s %s',
                    $schedule->attribute( 'id' ), $schedule->isActive() ? 'active' : 'paused', $schedule->attribute( 'mode' ),
                    $schedule->recurrenceText() . ' (' . $schedule->timezoneName() . ')', $schedule->listName(),
                    $schedule->attribute( 'mode' ) === 'copy' ? ' template "' . $schedule->templateEditionName() . '"' . ( (int)$schedule->attribute( 'auto_fill' ) ? ' auto-fill' : '' ) : '',
                    (int)$schedule->attribute( 'next_run' ) ? date( 'Y-m-d H:i', (int)$schedule->attribute( 'next_run' ) ) : '-',
                    (int)$schedule->attribute( 'last_run' ) ? date( 'Y-m-d H:i', (int)$schedule->attribute( 'last_run' ) ) : '-',
                    (string)$schedule->attribute( 'last_result' ) ) . ( $schedule->isDue( $now ) ? '  DUE' : '' ) );
            }
            if ( !\CjwNewsletterScheduleRunner::enabled() )
                $out->output( 'Note: [ScheduleSettings] Schedules is disabled, the cronjob does not run them (this command does).' );
            return $this->done( $jobID, 0, array( 'schedules' => count( $schedules ) ) );
        }
        if ( $action !== 'run' )
        {
            $out->error( 'Unknown action "' . $action . '": use list or run.' );
            return $this->done( $jobID, 2, array( 'error' => 'unknown action' ) );
        }

        // the editions and sends a run makes belong to the administrator when the schedule has no creator
        if ( $admin && !\eZUser::currentUser()->isRegistered() )
            \eZUser::setCurrentlyLoggedInUser( $admin, $admin->attribute( 'contentobject_id' ) );
        $dry = (bool)$options['dry-run'];
        $totals = \CjwNewsletterScheduleRunner::runDue( $out, $jobID ? 'job' : 'console', $dry, $now, $id, (bool)$options['force'] );
        if ( $totals['locked'] )
        {
            $out->error( 'Another run of the recurring sends is active.' );
            return $this->done( $jobID, 1, array( 'error' => 'locked' ) );
        }
        $out->output( ( $dry ? 'Dry run: ' : '' ) . $totals['due'] . ' due, ' . $totals['sent'] . ( $dry ? ' would send, ' : ' sent, ' )
                      . $totals['skipped'] . ' skipped, ' . $totals['failed'] . ' failed.' );
        $flat = $totals;
        unset( $flat['runs'], $flat['errors'] );
        return $this->done( $jobID, $totals['failed'] ? 1 : 0, $flat );
    }

    private function done( $jobID, $code, $result )
    {
        if ( $jobID )
            \CjwNewsletterJob::markFinished( $jobID, 'schedule', $code ? 'failed' : 'done', $result );
        $this->shutdown( $code );
        return $code;
    }
}

}
