<?php
/**
 * File containing the CjwNewsletterScheduleConditionInterface interface
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * A condition a recurring send asks before each run (cjwnl_schedule.condition_handler). The class must be listed in
 * cjw_newsletter.ini [ScheduleSettings] ConditionHandlers[]; a run whose condition is not met is logged as
 * skipped_condition and the schedule moves on to its next run.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
interface CjwNewsletterScheduleConditionInterface
{
    /**
     * @param CjwNewsletterSchedule $schedule
     * @param int $now the time of the run
     * @return bool true = the run goes ahead
     */
    public static function isMet( $schedule, $now );

    /**
     * @return string the name shown in the schedule form
     */
    public static function name();
}
