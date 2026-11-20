<?php
/**
 * Tests for the non-behavior conditions of CjwNewsletterSegmentEvaluator on SQLite, no kernel.
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

class cjwNewsletterSegmentEvaluatorTest extends TestCase
{
    const NOW = 10000000;

    /** @var CjwNewsletterSegmentSqliteDb */
    private $db;

    protected function setUp(): void
    {
        $this->db = new CjwNewsletterSegmentSqliteDb();
    }

    /** Creates a user and an approved subscription; returns the subscription id. */
    private function sub( array $user = array(), array $sub = array() )
    {
        $uid = $this->db->addUser( $user + array( 'email' => uniqid() . '@example.org' ) );
        return $this->db->addSubscription( $sub + array( 'newsletter_user_id' => $uid, 'status' => 2 ) );
    }

    private function members( $mode, array $conditions, $list = 1 )
    {
        $e = new CjwNewsletterSegmentEvaluator( $this->db );
        return $e->members( $list, $mode, $conditions, self::NOW, 0, function () { return true; } );
    }

    public function testSubscribedWithin()
    {
        $old = $this->sub( array(), array( 'created' => self::NOW - 40 * 86400, 'approved' => self::NOW - 5 * 86400 ) );
        $new = $this->sub( array(), array( 'created' => self::NOW - 2 * 86400, 'approved' => self::NOW - 1 * 86400 ) );
        $this->assertSame( array( $new ), $this->members( 'all', array( array( 'type' => 'subscribed_within', 'field' => 'created', 'days' => 30 ) ) ) );
        $this->assertSame( array( $old, $new ), $this->members( 'all', array( array( 'type' => 'subscribed_within', 'field' => 'approved', 'days' => 30 ) ) ) );
    }

    public function testLanguage()
    {
        $de = $this->sub( array( 'language' => 'ger-DE' ) );
        $en = $this->sub( array( 'language' => 'eng-GB' ) );
        $this->sub( array( 'language' => 'fre-FR' ) );
        $this->assertSame( array( $de, $en ), $this->members( 'all', array( array( 'type' => 'language', 'values' => array( 'ger-DE', 'eng-GB' ) ) ) ) );
    }

    public function testOutputFormat()
    {
        $html = $this->sub( array(), array( 'output_format_array_string' => ';0;' ) );
        $text = $this->sub( array(), array( 'output_format_array_string' => ';1;' ) );
        $both = $this->sub( array(), array( 'output_format_array_string' => ';0;1;' ) );
        $this->assertSame( array( $html, $both ), $this->members( 'all', array( array( 'type' => 'output_format', 'format' => 'html' ) ) ) );
        $this->assertSame( array( $text, $both ), $this->members( 'all', array( array( 'type' => 'output_format', 'format' => 'text' ) ) ) );
    }

    public function testSalutation()
    {
        $a = $this->sub( array( 'salutation' => 1 ) );
        $this->sub( array( 'salutation' => 2 ) );
        $this->sub( array( 'salutation' => null ) );
        $this->assertSame( array( $a ), $this->members( 'all', array( array( 'type' => 'salutation', 'values' => array( 1 ) ) ) ) );
    }

    public function testProfileOperators()
    {
        $set = $this->sub( array( 'organisation' => 'Acme 100%' ) );
        $empty = $this->sub( array( 'organisation' => '' ) );
        $null = $this->sub( array( 'organisation' => null ) );
        $other = $this->sub( array( 'organisation' => 'Acme 1000' ) );
        $p = function ( $op, $value = null ) {
            $c = array( 'type' => 'profile', 'field' => 'organisation', 'op' => $op );
            if ( $value !== null )
            {
                $c['value'] = $value;
            }
            return $c;
        };
        $this->assertSame( array( $set, $other ), $this->members( 'all', array( $p( 'set' ) ) ) );
        $this->assertSame( array( $empty, $null ), $this->members( 'all', array( $p( 'not_set' ) ) ) );
        $this->assertSame( array( $set ), $this->members( 'all', array( $p( 'equals', 'Acme 100%' ) ) ) );
        $this->assertSame( array( $set ), $this->members( 'all', array( $p( 'contains', '100%' ) ) ) );
        $this->assertSame( array(), $this->members( 'all', array( $p( 'contains', "x'y_" ) ) ) );
    }

    public function testInterestPickedAndNotPicked()
    {
        $i1 = $this->db->addInterest( array( 'list_contentobject_id' => 1 ) );
        $foreign = $this->db->addInterest( array( 'list_contentobject_id' => 2 ) );
        $with = $this->sub();
        $without = $this->sub();
        $uid = $this->db->rows( 'SELECT newsletter_user_id AS n FROM cjwnl_subscription WHERE id = ' . $with )[0]['n'];
        $this->db->addUserInterest( array( 'newsletter_user_id' => $uid, 'interest_id' => $i1 ) );
        $this->assertSame( array( $with ), $this->members( 'all', array( array( 'type' => 'interest', 'interest_id' => $i1, 'picked' => true ) ) ) );
        $this->assertSame( array( $without ), $this->members( 'all', array( array( 'type' => 'interest', 'interest_id' => $i1, 'picked' => false ) ) ) );
        // An interest of another list never matches for this list.
        $this->db->addUserInterest( array( 'newsletter_user_id' => $uid, 'interest_id' => $foreign ) );
        $this->assertSame( array(), $this->members( 'all', array( array( 'type' => 'interest', 'interest_id' => $foreign, 'picked' => true ) ) ) );
    }

    public function testAllVersusAny()
    {
        $a = $this->sub( array( 'language' => 'ger-DE', 'salutation' => 1 ) );
        $b = $this->sub( array( 'language' => 'ger-DE', 'salutation' => 2 ) );
        $c = $this->sub( array( 'language' => 'eng-GB', 'salutation' => 1 ) );
        $conds = array(
            array( 'type' => 'language', 'values' => array( 'ger-DE' ) ),
            array( 'type' => 'salutation', 'values' => array( 1 ) ),
        );
        $this->assertSame( array( $a ), $this->members( 'all', $conds ) );
        $this->assertSame( array( $a, $b, $c ), $this->members( 'any', $conds ) );
    }

    public function testOnlyApprovedOfThisListAreMembers()
    {
        $ok = $this->sub( array( 'language' => 'ger-DE' ) );
        $this->sub( array( 'language' => 'ger-DE' ), array( 'status' => 0 ) );
        $this->sub( array( 'language' => 'ger-DE' ), array( 'status' => 1 ) );
        $this->sub( array( 'language' => 'ger-DE' ), array( 'status' => 3 ) );
        $this->sub( array( 'language' => 'ger-DE' ), array( 'list_contentobject_id' => 2 ) );
        $this->assertSame( array( $ok ), $this->members( 'all', array( array( 'type' => 'language', 'values' => array( 'ger-DE' ) ) ) ) );
    }

    public function testInvalidConditionGivesEmpty()
    {
        $this->sub( array( 'language' => 'ger-DE' ) );
        $good = array( 'type' => 'language', 'values' => array( 'ger-DE' ) );
        $this->assertSame( array(), $this->members( 'any', array( $good, array( 'type' => 'nope' ) ) ) );
        $this->assertSame( array(), $this->members( 'all', array( array( 'type' => 'profile', 'field' => 'email; DROP', 'op' => 'set' ) ) ) );
        $this->assertSame( array(), $this->members( 'all', array() ) );
    }

    public function testInterestedOrganisations()
    {
        $i = $this->db->addInterest();
        $match = $this->sub( array( 'organisation' => 'Acme' ) );
        $noOrg = $this->sub( array( 'organisation' => '' ) );
        $noInterest = $this->sub( array( 'organisation' => 'Beta' ) );
        foreach ( array( $match, $noOrg ) as $sid )
        {
            $uid = $this->db->rows( 'SELECT newsletter_user_id AS n FROM cjwnl_subscription WHERE id = ' . $sid )[0]['n'];
            $this->db->addUserInterest( array( 'newsletter_user_id' => $uid, 'interest_id' => $i ) );
        }
        $this->assertSame( array( $match ), $this->members( 'all', array(
            array( 'type' => 'interest', 'interest_id' => $i, 'picked' => true ),
            array( 'type' => 'profile', 'field' => 'organisation', 'op' => 'set' ),
        ) ) );
        $this->assertNotContains( $noInterest, $this->members( 'any', array( array( 'type' => 'interest', 'interest_id' => $i ) ) ) );
    }
}
