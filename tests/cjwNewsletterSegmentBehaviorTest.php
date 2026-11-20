<?php
/**
 * Tests for the behavior conditions of CjwNewsletterSegmentEvaluator on SQLite, no kernel.
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentdb.php';
require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentcondition.php';
require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentevaluator.php';
require_once __DIR__ . '/fixtures/segments/cjwnewslettersegmentsqlitedb.php';

class cjwNewsletterSegmentBehaviorTest extends TestCase
{
    const NOW = 10000000;
    const CUTOFF = 5000000;
    const DAY = 86400;

    /** @var CjwNewsletterSegmentSqliteDb */
    private $db;

    protected function setUp(): void
    {
        $this->db = new CjwNewsletterSegmentSqliteDb();
    }

    /** Approved subscription of a new user; returns array( subscription id, user id ). */
    private function sub()
    {
        $uid = $this->db->addUser( array( 'email' => uniqid() . '@example.org' ) );
        return array( $this->db->addSubscription( array( 'newsletter_user_id' => $uid, 'status' => 2 ) ), $uid );
    }

    private function send( $daysAgo, array $o = array() )
    {
        return $this->db->addSend( $o + array( 'created' => self::NOW - $daysAgo * self::DAY ) );
    }

    /** One item of a user; $o overrides, default: sent, tracked for both opens and clicks, not opened. */
    private function item( $sendId, $userId, array $o = array() )
    {
        return $this->db->addItem( $o + array( 'edition_send_id' => $sendId, 'newsletter_user_id' => $userId, 'status' => 1, 'person_tracking' => 3 ) );
    }

    private function members( array $conditions, $consent = null, $batch = 5000, $mode = 'all' )
    {
        $e = new CjwNewsletterSegmentEvaluator( $this->db, $batch );
        $consent = $consent ?: function () { return true; };
        return $e->members( 1, $mode, $conditions, self::NOW, self::CUTOFF, $consent );
    }

    private function c( $type, array $o = array() )
    {
        return array( $o + array( 'type' => $type, 'window' => 'sends', 'n' => 1 ) );
    }

    public function testOpenedOverSends()
    {
        list( $a, $ua ) = $this->sub();
        list( $b, $ub ) = $this->sub();
        $s1 = $this->send( 30 );
        $s2 = $this->send( 20 );
        $s3 = $this->send( 10 );
        $this->item( $s1, $ua, array( 'open_count' => 1 ) );
        $this->item( $s2, $ua );
        $this->item( $s3, $ua );
        $this->item( $s1, $ub );
        $this->item( $s3, $ub, array( 'first_opened' => self::NOW - 9 * self::DAY ) );
        $this->assertSame( array( $b ), $this->members( $this->c( 'opened', array( 'n' => 1 ) ) ) );
        $this->assertSame( array( $a, $b ), $this->members( $this->c( 'opened', array( 'n' => 3 ) ) ) );
    }

    public function testClickedOverDaysWindow()
    {
        list( $a, $ua ) = $this->sub();
        list( $b, $ub ) = $this->sub();
        $this->item( $this->send( 40 ), $ua, array( 'click_count' => 2 ) );
        $this->item( $this->send( 5 ), $ub, array( 'click_count' => 1 ) );
        $this->assertSame( array( $b ), $this->members( $this->c( 'clicked', array( 'window' => 'days', 'n' => 30 ) ) ) );
        $this->assertSame( array( $a, $b ), $this->members( $this->c( 'clicked', array( 'window' => 'days', 'n' => 60 ) ) ) );
    }

    public function testNotOpenedNeedsAtLeastNKnownSends()
    {
        list( $a, $ua ) = $this->sub();
        list( $b, $ub ) = $this->sub();
        list( $c, $uc ) = $this->sub();
        $sends = array( $this->send( 30 ), $this->send( 20 ), $this->send( 10 ) );
        foreach ( $sends as $s )
        {
            $this->item( $s, $ua );
        }
        $this->item( $sends[0], $ub, array( 'open_count' => 1 ) );
        $this->item( $sends[1], $ub );
        $this->item( $sends[2], $ub );
        $this->item( $sends[2], $uc );
        $this->assertSame( array( $a, $b ), $this->members( $this->c( 'not_opened', array( 'n' => 2 ) ) ) );
        $this->assertSame( array( $a ), $this->members( $this->c( 'not_opened', array( 'n' => 3 ) ) ) );
    }

    /** Scenario: Untracked sends in between */
    public function testUntrackedSendsInBetweenAreIgnored()
    {
        list( $a, $ua ) = $this->sub();
        $s1 = $this->send( 30 );
        $s2 = $this->send( 20 );
        $s3 = $this->send( 10 );
        $this->item( $s1, $ua, array( 'person_tracking' => 0, 'open_count' => 1 ) );
        $this->item( $s2, $ua, array( 'person_tracking' => 0 ) );
        $this->item( $s3, $ua );
        // Only one known send: not_opened with n = 2 does not match, n = 1 does.
        $this->assertSame( array(), $this->members( $this->c( 'not_opened', array( 'n' => 2 ) ) ) );
        $this->assertSame( array( $a ), $this->members( $this->c( 'not_opened', array( 'n' => 1 ) ) ) );
        // The untracked, opened send does not count as an open.
        $this->assertSame( array(), $this->members( $this->c( 'opened', array( 'n' => 3 ) ) ) );
    }

    /** Scenario: New subscriber */
    public function testNewSubscriberWithFewerSendsThanNIsNotMatched()
    {
        $this->sub();
        list( $b, $ub ) = $this->sub();
        $this->item( $this->send( 10 ), $ub );
        $this->assertSame( array(), $this->members( $this->c( 'not_clicked', array( 'n' => 3 ) ) ) );
        $this->assertSame( array( $b ), $this->members( $this->c( 'not_clicked', array( 'n' => 1 ) ) ) );
    }

    /** Scenario: Older than the retention period */
    public function testSendsBeforeCutoffAreUnknown()
    {
        list( $a, $ua ) = $this->sub();
        $this->item( $this->send( 90 ), $ua, array( 'click_count' => 1, 'open_count' => 1 ) );
        $this->item( $this->send( 50 ), $ua );
        $this->assertSame( array(), $this->members( $this->c( 'clicked', array( 'n' => 5 ) ) ) );
        $this->assertSame( array(), $this->members( $this->c( 'not_opened', array( 'n' => 2 ) ) ) );
        $this->assertSame( array( $a ), $this->members( $this->c( 'not_opened', array( 'n' => 1 ) ) ) );
    }

    /** Scenario: Consent missing */
    public function testNonConsentingCount()
    {
        $consenting = array();
        for ( $i = 1; $i <= 1000; $i++ )
        {
            list( , $uid ) = $this->sub();
            if ( $i <= 400 )
            {
                $consenting[$uid] = true;
            }
        }
        // A pending subscription and another list never count.
        $uid = $this->db->addUser( array( 'email' => 'p@example.org' ) );
        $this->db->addSubscription( array( 'newsletter_user_id' => $uid, 'status' => 1 ) );
        $this->db->addSubscription( array( 'newsletter_user_id' => $uid, 'status' => 2, 'list_contentobject_id' => 2 ) );
        $e = new CjwNewsletterSegmentEvaluator( $this->db );
        $this->assertSame( 600, $e->nonConsentingCount( 1, function ( $id ) use ( $consenting ) { return isset( $consenting[$id] ); } ) );
    }

    /** Scenario: Consent withdrawn */
    public function testWithdrawnConsentRemovesMember()
    {
        list( $a, $ua ) = $this->sub();
        list( $b, $ub ) = $this->sub();
        $s = $this->send( 10 );
        $this->item( $s, $ua, array( 'click_count' => 1 ) );
        $this->item( $s, $ub, array( 'click_count' => 1 ) );
        $consent = function ( $id ) use ( $ua ) { return $id === $ua; };
        $this->assertSame( array( $a ), $this->members( $this->c( 'clicked' ), $consent ) );
    }

    public function testSmsAndTestGroupSendsNeverCount()
    {
        list( $a, $ua ) = $this->sub();
        $sms = $this->send( 10, array( 'channel' => 'sms' ) );
        $test = $this->send( 9, array( 'test_group_id' => 3 ) );
        $this->item( $sms, $ua, array( 'click_count' => 1, 'open_count' => 1 ) );
        $this->item( $test, $ua, array( 'click_count' => 1, 'open_count' => 1 ) );
        $this->assertSame( array(), $this->members( $this->c( 'clicked' ) ) );
        $this->assertSame( array(), $this->members( $this->c( 'opened' ) ) );
        $this->assertSame( array(), $this->members( $this->c( 'not_opened' ) ) );
    }

    public function testUnsentItemsAndOtherListsDoNotCount()
    {
        list( $a, $ua ) = $this->sub();
        $this->item( $this->send( 10 ), $ua, array( 'status' => 0, 'click_count' => 1 ) );
        $this->item( $this->send( 9, array( 'list_contentobject_id' => 2 ) ), $ua, array( 'click_count' => 1 ) );
        $this->assertSame( array(), $this->members( $this->c( 'clicked' ) ) );
    }

    public function testClicksOnlyTrackedItemCountsForClicksNotOpens()
    {
        list( $a, $ua ) = $this->sub();
        $this->item( $this->send( 10 ), $ua, array( 'person_tracking' => 1, 'click_count' => 1, 'open_count' => 1 ) );
        $this->assertSame( array( $a ), $this->members( $this->c( 'clicked' ) ) );
        $this->assertSame( array(), $this->members( $this->c( 'opened' ) ) );
        $this->assertSame( array(), $this->members( $this->c( 'not_opened' ) ) );
        $this->assertSame( array(), $this->members( $this->c( 'not_clicked' ) ) );
    }

    public function testBatchSizeDoesNotChangeTheResult()
    {
        $expected = array();
        for ( $i = 0; $i < 7; $i++ )
        {
            list( $s, $u ) = $this->sub();
            $sends = array( $this->send( 30 ), $this->send( 20 ), $this->send( 10 ) );
            foreach ( $sends as $k => $send )
            {
                $this->item( $send, $u, $i % 2 && $k === 0 ? array( 'open_count' => 1 ) : array() );
            }
            if ( $i % 2 )
            {
                $expected[] = $s;
            }
        }
        foreach ( array( 'opened', 'not_opened' ) as $type )
        {
            $cond = $this->c( $type, array( 'n' => 3 ) );
            $this->assertSame( $this->members( $cond, null, 5000 ), $this->members( $cond, null, 2 ), $type );
            $this->assertSame( $this->members( $cond, null, 5000 ), $this->members( $cond, null, 1 ), $type );
        }
        $this->assertSame( $expected, $this->members( $this->c( 'opened', array( 'n' => 3 ) ), null, 2 ) );
    }

    public function testClickedObject()
    {
        list( $a, $ua ) = $this->sub();
        list( $b, $ub ) = $this->sub();
        list( $c, $uc ) = $this->sub();
        $s = $this->send( 10 );
        $ia = $this->item( $s, $ua, array( 'click_count' => 1 ) );
        $ib = $this->item( $s, $ub, array( 'click_count' => 1 ) );
        $ic = $this->item( $s, $uc, array( 'click_count' => 1, 'person_tracking' => 2 ) );
        $l7 = $this->db->addLink( array( 'edition_send_id' => $s, 'contentobject_id' => 7 ) );
        $l8 = $this->db->addLink( array( 'edition_send_id' => $s, 'contentobject_id' => 8 ) );
        $this->db->addClick( array( 'link_id' => $l7, 'edition_send_item_id' => $ia ) );
        $this->db->addClick( array( 'link_id' => $l8, 'edition_send_item_id' => $ib ) );
        $this->db->addClick( array( 'link_id' => $l7, 'edition_send_item_id' => $ic ) );
        $this->assertSame( array( $a ), $this->members( $this->c( 'clicked_object', array( 'contentobject_id' => 7 ) ) ) );
        $this->assertSame( array(), $this->members( $this->c( 'clicked_object', array( 'contentobject_id' => 9 ) ) ) );
    }

    public function testClickedTagWithAndWithoutTable()
    {
        list( $a, $ua ) = $this->sub();
        $s = $this->send( 10 );
        $ia = $this->item( $s, $ua, array( 'click_count' => 1 ) );
        $l7 = $this->db->addLink( array( 'edition_send_id' => $s, 'contentobject_id' => 7 ) );
        $this->db->addClick( array( 'link_id' => $l7, 'edition_send_item_id' => $ia ) );
        $cond = $this->c( 'clicked_tag', array( 'tag_id' => 5 ) );

        $e = new CjwNewsletterSegmentEvaluator( $this->db );
        $this->assertFalse( $e->tagsAvailable() );
        $this->assertSame( array(), $this->members( $cond ) );

        $this->db->exec( 'CREATE TABLE eztags_attribute_link ( id INTEGER PRIMARY KEY, keyword_id INTEGER, object_id INTEGER )' );
        $this->db->exec( 'INSERT INTO eztags_attribute_link ( keyword_id, object_id ) VALUES ( 5, 7 ), ( 6, 8 )' );
        $this->assertTrue( $e->tagsAvailable() );
        $this->assertSame( array( $a ), $this->members( $cond ) );
        $this->assertSame( array(), $this->members( $this->c( 'clicked_tag', array( 'tag_id' => 6 ) ) ) );
    }
}
