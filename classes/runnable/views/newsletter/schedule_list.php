<?php
/**
 * The recurring sends: the list with the next and the last run, pause, resume, run now (also as a dry run), remove,
 * and the last runs of all schedules.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class ScheduleList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $tr = 'cjw_newsletter/editorial';
        $report = null;
        $confirm = null;

        $actionId = 0;
        foreach ( array( 'PauseButton', 'ResumeButton', 'RunButton', 'DryRunButton', 'RemoveButton', 'ConfirmRemoveButton' ) as $button )
        {
            if ( $http->hasPostVariable( $button ) && is_array( $http->postVariable( $button ) ) )
            {
                $keys = array_keys( $http->postVariable( $button ) );
                $actionId = (int)$keys[0];
                $schedule = $actionId > 0 ? \CjwNewsletterSchedule::fetch( $actionId ) : null;
                if ( !$schedule )
                    break;
                switch ( $button )
                {
                    case 'PauseButton':
                        $schedule->setAttribute( 'status', \CjwNewsletterSchedule::STATUS_PAUSED );
                        $schedule->updateNextRun();
                        $schedule->setAttribute( 'modified', time() );
                        $schedule->store();
                        \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The recurring send %id is paused.', null, array( '%id' => $actionId ) ) );
                        return $this->viewResult( null, $module->redirectTo( '/newsletter/schedule_list' ) );
                    case 'ResumeButton':
                        $schedule->setAttribute( 'status', \CjwNewsletterSchedule::STATUS_ACTIVE );
                        $schedule->updateNextRun();
                        $schedule->setAttribute( 'modified', time() );
                        $schedule->store();
                        \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The recurring send %id runs again; next run %time.', null,
                            array( '%id' => $actionId, '%time' => \CjwNewsletterEditorialUI::formatTime( $schedule->attribute( 'next_run' ), $schedule->timezoneName() ) ) ) );
                        return $this->viewResult( null, $module->redirectTo( '/newsletter/schedule_list' ) );
                    case 'RunButton':
                    case 'DryRunButton':
                        $dry = $button === 'DryRunButton';
                        $totals = \CjwNewsletterScheduleRunner::runDue( new \CjwNewsletterJobOutput( false ), 'admin', $dry, null, $actionId, true );
                        if ( $totals['locked'] )
                        {
                            \CjwNewsletterUI::notice( 'warning', \ezpI18n::tr( $tr, 'Another run of the recurring sends is active. Try again in a moment.' ) );
                        }
                        else if ( $totals['runs'] )
                        {
                            $run = $totals['runs'][0];
                            $text = \ezpI18n::tr( $tr, 'Recurring send %id: %result. %message', null,
                                array( '%id' => $actionId, '%result' => self::resultName( $run['result'] ), '%message' => $run['message'] ) );
                            \CjwNewsletterUI::notice( $run['result'] === 'failed' ? 'error' : 'feedback', trim( $text ) );
                        }
                        return $this->viewResult( null, $module->redirectTo( '/newsletter/schedule_list' ) );
                    case 'RemoveButton':
                        $confirm = $schedule;
                        break 2;
                    case 'ConfirmRemoveButton':
                        $schedule->removeSchedule();
                        \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The recurring send %id was removed. The editions and sends it made stay.', null, array( '%id' => $actionId ) ) );
                        return $this->viewResult( null, $module->redirectTo( '/newsletter/schedule_list' ) );
                }
            }
        }

        $schedules = \CjwNewsletterSchedule::fetchVisible();
        $log = \CjwNewsletterScheduleLog::fetchList( null, 20, 0, array( 'run_at' => 'desc', 'id' => 'desc' ) );
        $names = array();
        foreach ( $schedules as $schedule )
            $names[(int)$schedule->attribute( 'id' )] = $schedule->listName();

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'schedules', $schedules );
        $tpl->setVariable( 'schedule_names', $names );
        $tpl->setVariable( 'log', $log );
        $tpl->setVariable( 'result_names', self::resultNames() );
        $tpl->setVariable( 'enabled', \CjwNewsletterScheduleRunner::enabled() );
        $tpl->setVariable( 'last_run', \CjwNewsletterRunner::lastRun( \CjwNewsletterScheduleRunner::LAST_RUN ) );
        $tpl->setVariable( 'confirm_remove', $confirm );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/editorial/schedule_list.tpl' );
        $Result['path'] = \CjwNewsletterEditorialUI::path( array( \ezpI18n::tr( $tr, 'Recurring sends' ) ) );
        return $this->viewResult( $Result, null );
    }

    /** @return string[] result => shown name */
    static function resultNames()
    {
        $tr = 'cjw_newsletter/editorial';
        return array( 'sent' => \ezpI18n::tr( $tr, 'sent' ),
                      'skipped_empty' => \ezpI18n::tr( $tr, 'skipped, nothing new' ),
                      'skipped_condition' => \ezpI18n::tr( $tr, 'skipped, condition not met' ),
                      'skipped_dry_run' => \ezpI18n::tr( $tr, 'dry run' ),
                      'failed' => \ezpI18n::tr( $tr, 'failed' ) );
    }

    static function resultName( $result )
    {
        $names = self::resultNames();
        return isset( $names[$result] ) ? $names[$result] : (string)$result;
    }
}

}
