<?php
/**
 * File containing the CjwNewsletterSeriesSchedule class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage series
 */

/**
 * Decides which step of a welcome series a member gets next, and when.
 *
 * Kernel-free: plain arrays in, plain array out.
 *
 * @package cjw_newsletter
 * @subpackage series
 */
class CjwNewsletterSeriesSchedule
{
    /**
     * The next step for a member, never more than one.
     *
     * The first step counts from `entered`, later ones from `last_sent`,
     * so a pause or absence shifts all following steps.
     *
     * @param array $member entered, last_position (0 = nothing received), last_sent
     * @param array $steps list of position and delay_days, any order
     * @param int $now unix time
     * @return array|null position, due_at, due; null when no step is left
     */
    public static function nextDue( array $member, array $steps, $now )
    {
        $next = null;
        $last = (int)$member['last_position'];
        foreach ( $steps as $step )
        {
            $position = (int)$step['position'];
            if ( $position > $last && ( $next === null || $position < (int)$next['position'] ) )
            {
                $next = $step;
            }
        }
        if ( $next === null )
        {
            return null;
        }

        $base = $last === 0 ? (int)$member['entered'] : (int)$member['last_sent'];
        $dueAt = $base + (int)$next['delay_days'] * 86400;

        return array(
            'position' => (int)$next['position'],
            'due_at' => $dueAt,
            'due' => $dueAt <= (int)$now,
        );
    }
}
