<?php
/**
 * The decision whether a status change enters a subscription into the welcome series, without the kernel:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSeriesEnterTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/series/cjwnewsletterserieshooks.php';

class cjwNewsletterSeriesEnterTest extends TestCase
{
    public function testNotYetConfirmed()
    {
        $this->assertFalse( CjwNewsletterSeriesHooks::shouldEnter( 0, 1, false, true ) );
    }

    public function testListApprovesAutomatically()
    {
        $this->assertTrue( CjwNewsletterSeriesHooks::shouldEnter( 0, 2, false, true ) );
    }

    public function testNewSubscriptionApproved()
    {
        $this->assertTrue( CjwNewsletterSeriesHooks::shouldEnter( null, 2, false, true ) );
    }

    public function testListNeedsApproval()
    {
        $this->assertTrue( CjwNewsletterSeriesHooks::shouldEnter( 1, 2, false, true ) );
    }

    public function testCsvImport()
    {
        $this->assertFalse( CjwNewsletterSeriesHooks::shouldEnter( 0, 2, true, true ) );
    }

    public function testRemovedThenApproved()
    {
        $this->assertTrue( CjwNewsletterSeriesHooks::shouldEnter( 4, 2, false, true ) );
    }

    public function testNoSeries()
    {
        $this->assertFalse( CjwNewsletterSeriesHooks::shouldEnter( 1, 2, false, false ) );
    }

    public function testStayingApprovedDoesNotEnter()
    {
        $this->assertFalse( CjwNewsletterSeriesHooks::shouldEnter( 2, 2, false, true ) );
    }
}
