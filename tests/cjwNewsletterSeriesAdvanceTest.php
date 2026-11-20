<?php
/**
 * The progress of the members of a step send, without the kernel:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSeriesAdvanceTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/series/cjwnewsletterserieshooks.php';

class cjwNewsletterSeriesAdvanceTest extends TestCase
{
    protected function member( $id, $subscriptionId, $sendId = 7, $position = 2 )
    {
        return array( 'id' => $id, 'subscription_id' => $subscriptionId, 'pending_send_id' => $sendId, 'pending_position' => $position );
    }

    public function testMemberWithItemMovesOneStep()
    {
        $updates = CjwNewsletterSeriesHooks::advance( array( $this->member( 1, 10 ) ), array( 10 ), 1000 );
        $this->assertSame( array( 1 => array( 'pending_send_id' => 0, 'pending_position' => 0, 'last_position' => 2, 'last_sent' => 1000 ) ), $updates );
    }

    public function testMemberWithoutItemKeepsProgress()
    {
        $updates = CjwNewsletterSeriesHooks::advance( array( $this->member( 1, 10 ) ), array(), 1000 );
        $this->assertSame( array( 1 => array( 'pending_send_id' => 0, 'pending_position' => 0 ) ), $updates );
    }

    public function testMixedAndPendingClearedForBoth()
    {
        $updates = CjwNewsletterSeriesHooks::advance( array( $this->member( 1, 10 ), $this->member( 2, 11 ) ), array( 11 ), 5 );
        $this->assertArrayNotHasKey( 'last_position', $updates[1] );
        $this->assertSame( 2, $updates[2]['last_position'] );
        $this->assertSame( 0, $updates[1]['pending_send_id'] );
        $this->assertSame( 0, $updates[2]['pending_send_id'] );
    }

    public function testStepsInOrderAndOnce()
    {
        $row = $this->member( 1, 10 );
        $updates = CjwNewsletterSeriesHooks::advance( array( $row ), array( 10 ), 1000 );
        $row = array_merge( $row, $updates[1] );
        $this->assertSame( array(), CjwNewsletterSeriesHooks::advance( array( $row ), array( 10 ), 2000 ) );
        $this->assertSame( 2, $row['last_position'] );
        $this->assertSame( 1000, $row['last_sent'] );
    }
}
