<?php
/**
 * File containing the CjwNewsletterStatisticsCategoryHandler class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * The handler of the e-mail preference category "newsletter_statistics" (cjw_newsletter 4.2.0, area N4 Statistics):
 * the consent to per-person counting of newsletter opens and clicks. The category is optional and off by default;
 * the person switches it on on the central preference page, and the kernel logs every change in the consent log.
 *
 * - No older data maps to the category: stateFor() has no opinion (the default, off, applies).
 * - When the person switches it off, or is erased, the per-person rows go at once; the anonymous totals stay.
 * - Its part on the preference page says what is counted and for how long.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterStatisticsCategoryHandler implements expMailCategoryHandler
{
    public function stateFor( expMailRecipient $recipient, expMailCategory $category )
    {
        return null;
    }

    public function frequencyFor( expMailRecipient $recipient, expMailCategory $category )
    {
        return null;
    }

    public function changed( expMailRecipient $recipient, expMailCategory $category, $state, expConsentContext $context )
    {
        if ( $state !== 'on' )
            CjwNewsletterStatisticsRetention::forgetRecipient( $recipient );
        CjwNewsletterTracking::resetCache();
    }

    /** The person was erased (kernel expMailPreferences::erase()): the per-person statistics go. */
    public function erased( expMailRecipient $recipient, expConsentContext $context )
    {
        CjwNewsletterStatisticsRetention::forgetRecipient( $recipient );
        CjwNewsletterTracking::resetCache();
    }

    public function partTemplate( expMailRecipient $recipient, expMailCategory $category, $mode )
    {
        return 'design:mailpreferences/category/newsletter_statistics.tpl';
    }

    public function partVariables( expMailRecipient $recipient, expMailCategory $category, $mode )
    {
        return array( 'retention_months' => CjwNewsletterTracking::retentionMonths(),
                      'tracking_enabled' => CjwNewsletterTracking::enabled(),
                      'per_person_lists' => self::perPersonListCount() );
    }

    /** @return int lists that count per person (with consent) */
    protected static function perPersonListCount()
    {
        $rows = eZDB::instance()->arrayQuery( 'SELECT COUNT( DISTINCT l.contentobject_id ) AS c FROM cjwnl_list l, ezcontentobject o WHERE o.id = l.contentobject_id AND l.tracking_mode = ' . CjwNewsletterTracking::MODE_PERSON );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }
}

?>
