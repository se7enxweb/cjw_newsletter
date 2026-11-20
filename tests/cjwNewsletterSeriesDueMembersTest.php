<?php
/**
 * Tests which members of a welcome series are due. No kernel, SQLite helper of the segment tests:
 *
 *   php vendor/bin/phpunit --no-coverage extension/cjw_newsletter/tests/cjwNewsletterSeriesDueMembersTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentdb.php';
require_once dirname( __DIR__ ) . '/classes/series/cjwnewsletterseriesschedule.php';
require_once dirname( __DIR__ ) . '/classes/series/cjwnewsletterseriesrunner.php';
require_once __DIR__ . '/fixtures/segments/cjwnewslettersegmentsqlitedb.php';

class cjwNewsletterSeriesDueMembersTest extends TestCase
{
    const DAY = 86400;
    const APPROVED = 2;

    private $steps = array(
        array( 'position' => 1, 'delay_days' => 0 ),
        array( 'position' => 2, 'delay_days' => 3 ),
    );

    private function series( array $o = array() )
    {
        return $o + array( 'id' => 1, 'status' => 0, 'segment_id' => 0 );
    }

    /** @return int member id */
    private function member( CjwNewsletterSegmentSqliteDb $db, $subscriptionStatus, array $o = array() )
    {
        $subscriptionId = $db->addSubscription( array( 'status' => $subscriptionStatus ) );
        return $db->insert( 'cjwnl_series_member', $o + array(
            'series_id' => 1, 'subscription_id' => $subscriptionId, 'newsletter_user_id' => 1, 'entered' => 1000 ) );
    }

    private function ids( array $due, $position )
    {
        return isset( $due[$position] ) ? array_map( 'intval', array_column( $due[$position], 'id' ) ) : array();
    }

    public function testNotYetConfirmedIsNotDue()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $this->member( $db, 0 );
        $this->member( $db, 1 );
        $due = CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 1000 + self::DAY, null );
        $this->assertSame( array(), $due );
    }

    public function testApprovedIsDueAtFirstStep()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $id = $this->member( $db, self::APPROVED );
        $due = CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 1000, null );
        $this->assertSame( array( $id ), $this->ids( $due, 1 ) );
        $this->assertSame( array( 1 ), array_keys( $due ) );
    }

    public function testUnsubscribedAfterStepOneIsNotDue()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $this->member( $db, 3, array( 'last_position' => 1, 'last_sent' => 1000 ) );
        $this->member( $db, 4, array( 'last_position' => 1, 'last_sent' => 1000 ) );
        $due = CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 1000 + 10 * self::DAY, null );
        $this->assertSame( array(), $due );
    }

    public function testStepTwoOnlyWhenDelayPassed()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $id = $this->member( $db, self::APPROVED, array( 'last_position' => 1, 'last_sent' => 1000 ) );
        $this->assertSame( array(), CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 1000 + 2 * self::DAY, null ) );
        $due = CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 1000 + 3 * self::DAY, null );
        $this->assertSame( array( $id ), $this->ids( $due, 2 ) );
    }

    public function testMemberWithPendingSendIsNotDue()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $this->member( $db, self::APPROVED, array( 'pending_send_id' => 7, 'pending_position' => 1 ) );
        $this->assertSame( array(), CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 5000, null ) );
    }

    public function testPausedSeriesHasNobodyDue()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $this->member( $db, self::APPROVED );
        $this->assertSame( array(), CjwNewsletterSeriesRunner::dueMembers( $db, $this->series( array( 'status' => 1 ) ), $this->steps, 5000, null ) );
    }

    public function testOtherSeriesMembersAreIgnored()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $this->member( $db, self::APPROVED, array( 'series_id' => 2 ) );
        $this->assertSame( array(), CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 5000, null ) );
    }

    public function testLeavesTheSegment()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $this->member( $db, self::APPROVED, array( 'last_position' => 1, 'last_sent' => 1000 ) );
        $calls = array();
        $audience = function ( $subscriptionId ) use ( &$calls ) { $calls[] = $subscriptionId; return false; };
        $due = CjwNewsletterSeriesRunner::dueMembers( $db, $this->series( array( 'segment_id' => 5 ) ), $this->steps, 1000 + 5 * self::DAY, $audience );
        $this->assertSame( array(), $due );
        $this->assertCount( 1, $calls );
    }

    public function testNotAMemberWhenApprovedBecomesDueOnceMember()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $id = $this->member( $db, self::APPROVED );
        $isMember = false;
        $audience = function () use ( &$isMember ) { return $isMember; };
        $series = $this->series( array( 'segment_id' => 5 ) );
        $this->assertSame( array(), CjwNewsletterSeriesRunner::dueMembers( $db, $series, $this->steps, 2000, $audience ) );
        $isMember = true;
        $due = CjwNewsletterSeriesRunner::dueMembers( $db, $series, $this->steps, 3000, $audience );
        $this->assertSame( array( $id ), $this->ids( $due, 1 ) );
    }

    public function testAudienceNotAskedForWholeList()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $id = $this->member( $db, self::APPROVED );
        $audience = function () { $this->fail( 'audience callable must not be called without a segment' ); };
        $due = CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 2000, $audience );
        $this->assertSame( array( $id ), $this->ids( $due, 1 ) );
    }

    public function testGroupedByPosition()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $a = $this->member( $db, self::APPROVED, array( 'last_position' => 1, 'last_sent' => 1000 ) );
        $b = $this->member( $db, self::APPROVED );
        $due = CjwNewsletterSeriesRunner::dueMembers( $db, $this->series(), $this->steps, 1000 + 4 * self::DAY, null );
        $this->assertSame( array( 1, 2 ), array_keys( $due ) );
        $this->assertSame( array( $b ), $this->ids( $due, 1 ) );
        $this->assertSame( array( $a ), $this->ids( $due, 2 ) );
    }
}
