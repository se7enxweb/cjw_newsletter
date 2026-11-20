<?php
/**
 * keepIds() narrows a send's subscriptions to a segment's members. Kernel-free:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSegmentHooksTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmenthooks.php';

class cjwNewsletterSegmentHooksTest extends TestCase
{
    private function subscription( $id )
    {
        return new class( $id )
        {
            private $id;
            function __construct( $id ) { $this->id = $id; }
            function attribute( $name ) { return $this->id; }
        };
    }

    public function testKeepIdsKeepsOrderAndDropsTheRest()
    {
        $all = array( $this->subscription( 5 ), $this->subscription( 2 ), $this->subscription( 9 ), $this->subscription( 3 ) );
        $kept = CjwNewsletterSegmentHooks::keepIds( $all, array( 3, 5, 9 ) );
        $this->assertSame( array( $all[0], $all[2], $all[3] ), $kept );
    }

    public function testEmptyIdListGivesNoRecipients()
    {
        $all = array( $this->subscription( 1 ), $this->subscription( 2 ) );
        $this->assertSame( array(), CjwNewsletterSegmentHooks::keepIds( $all, array() ) );
    }

    public function testNoSubscriptionsGivesAnEmptyArray()
    {
        $this->assertSame( array(), CjwNewsletterSegmentHooks::keepIds( false, array( 1 ) ) );
    }

    public function testSmallSizesAreHiddenOnlyFromThoseWhoMayNotSeeSubscribers()
    {
        $this->assertTrue( CjwNewsletterSegmentHooks::isFew( 4, 5, false ) );
        $this->assertFalse( CjwNewsletterSegmentHooks::isFew( 5, 5, false ) );
        $this->assertFalse( CjwNewsletterSegmentHooks::isFew( 2, 5, true ) );
    }
}
