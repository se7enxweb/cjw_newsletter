<?php
/**
 * The next-step schedule of a welcome series is computed without the kernel:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSeriesScheduleTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/series/cjwnewsletterseriesschedule.php';

class cjwNewsletterSeriesScheduleTest extends TestCase
{
    const DAY = 86400;

    private static function steps()
    {
        return array(
            array( 'position' => 1, 'delay_days' => 0 ),
            array( 'position' => 2, 'delay_days' => 3 ),
            array( 'position' => 3, 'delay_days' => 14 ),
        );
    }

    public function testFirstStepWithDelayZeroIsDueAtEntered()
    {
        $member = array( 'entered' => 1000, 'last_position' => 0, 'last_sent' => 0 );
        $r = CjwNewsletterSeriesSchedule::nextDue( $member, self::steps(), 1000 );
        $this->assertSame( array( 'position' => 1, 'due_at' => 1000, 'due' => true ), $r );
    }

    public function testNormalCourse()
    {
        $march1 = 1000000;
        $member = array( 'entered' => $march1, 'last_position' => 1, 'last_sent' => $march1 );
        $r = CjwNewsletterSeriesSchedule::nextDue( $member, self::steps(), $march1 + 2 * self::DAY );
        $this->assertSame( 2, $r['position'] );
        $this->assertSame( $march1 + 3 * self::DAY, $r['due_at'] );
        $this->assertFalse( $r['due'] );

        $r = CjwNewsletterSeriesSchedule::nextDue( $member, self::steps(), $march1 + 3 * self::DAY );
        $this->assertTrue( $r['due'] );
    }

    public function testBackAWeekLater()
    {
        $d1 = 1000000;
        $member = array( 'entered' => $d1, 'last_position' => 1, 'last_sent' => $d1 );
        $r = CjwNewsletterSeriesSchedule::nextDue( $member, self::steps(), $d1 + 7 * self::DAY );
        $this->assertSame( 2, $r['position'] );
        $this->assertTrue( $r['due'] );

        // Sent on day 8: the last step counts from that day.
        $member = array( 'entered' => $d1, 'last_position' => 2, 'last_sent' => $d1 + 7 * self::DAY );
        $r = CjwNewsletterSeriesSchedule::nextDue( $member, self::steps(), $d1 + 8 * self::DAY );
        $this->assertSame( 3, $r['position'] );
        $this->assertSame( $d1 + 21 * self::DAY, $r['due_at'] );
        $this->assertFalse( $r['due'] );
    }

    public function testPausedThreeWeeksReturnsOnlyNextStep()
    {
        $d1 = 1000000;
        $member = array( 'entered' => $d1, 'last_position' => 1, 'last_sent' => $d1 );
        $r = CjwNewsletterSeriesSchedule::nextDue( $member, self::steps(), $d1 + 21 * self::DAY );
        $this->assertSame( 2, $r['position'] );
        $this->assertTrue( $r['due'] );
    }

    public function testLastStepReceivedReturnsNull()
    {
        $member = array( 'entered' => 1000, 'last_position' => 3, 'last_sent' => 5000 );
        $this->assertNull( CjwNewsletterSeriesSchedule::nextDue( $member, self::steps(), 99999999 ) );
    }

    public function testGapsAndUnsortedPositions()
    {
        $steps = array(
            array( 'position' => 10, 'delay_days' => 7 ),
            array( 'position' => 2, 'delay_days' => 1 ),
            array( 'position' => 5, 'delay_days' => 2 ),
        );
        $member = array( 'entered' => 0, 'last_position' => 0, 'last_sent' => 0 );
        $r = CjwNewsletterSeriesSchedule::nextDue( $member, $steps, 0 );
        $this->assertSame( 2, $r['position'] );

        $member = array( 'entered' => 0, 'last_position' => 2, 'last_sent' => 100 );
        $r = CjwNewsletterSeriesSchedule::nextDue( $member, $steps, 0 );
        $this->assertSame( 5, $r['position'] );
        $this->assertSame( 100 + 2 * self::DAY, $r['due_at'] );

        $member['last_position'] = 5;
        $r = CjwNewsletterSeriesSchedule::nextDue( $member, $steps, 0 );
        $this->assertSame( 10, $r['position'] );
    }

    public function testEmptyStepsReturnsNull()
    {
        $member = array( 'entered' => 0, 'last_position' => 0, 'last_sent' => 0 );
        $this->assertNull( CjwNewsletterSeriesSchedule::nextDue( $member, array(), 0 ) );
    }
}
