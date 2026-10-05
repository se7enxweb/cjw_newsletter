<?php
/**
 * File containing the CjwNewsletterScheduleConditionNewArticles class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * The run goes ahead only when the article pool of the schedule has content published since its last send (the
 * anonymous user's view of it), in either mode.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterScheduleConditionNewArticles implements CjwNewsletterScheduleConditionInterface
{
    public static function isMet( $schedule, $now )
    {
        $since = $schedule->lastSentTime();
        $found = CjwNewsletterArticlePoolFinder::find( $schedule->articlePool(),
            array( 'since' => $since, 'until' => (int)$now + 1, 'limit' => 1, 'anonymous' => true ) );
        return count( $found ) > 0;
    }

    public static function name()
    {
        return ezpI18n::tr( 'cjw_newsletter/editorial', 'Only when the pool has new articles' );
    }
}
