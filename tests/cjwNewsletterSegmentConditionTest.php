<?php
/**
 * Segment conditions are validated without the kernel:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSegmentConditionTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentcondition.php';

class cjwNewsletterSegmentConditionTest extends TestCase
{
    public static function validProvider()
    {
        return array(
            'subscribed_within' => array( array( 'type' => 'subscribed_within', 'field' => 'created', 'days' => '30' ), array( 'type' => 'subscribed_within', 'field' => 'created', 'days' => 30 ) ),
            'language' => array( array( 'type' => 'language', 'values' => array( 'ger-DE', 'ger-DE', 'eng-GB' ) ), array( 'type' => 'language', 'values' => array( 'ger-DE', 'eng-GB' ) ) ),
            'output_format' => array( array( 'type' => 'output_format', 'format' => 'html' ), array( 'type' => 'output_format', 'format' => 'html' ) ),
            'salutation' => array( array( 'type' => 'salutation', 'values' => array( '1', 2 ) ), array( 'type' => 'salutation', 'values' => array( 1, 2 ) ) ),
            'profile equals' => array( array( 'type' => 'profile', 'field' => 'organisation', 'op' => 'equals', 'value' => 'ACME', 'junk' => 1 ), array( 'type' => 'profile', 'field' => 'organisation', 'op' => 'equals', 'value' => 'ACME' ) ),
            'profile set' => array( array( 'type' => 'profile', 'field' => 'last_name', 'op' => 'set' ), array( 'type' => 'profile', 'field' => 'last_name', 'op' => 'set' ) ),
            'interest' => array( array( 'type' => 'interest', 'interest_id' => 4 ), array( 'type' => 'interest', 'interest_id' => 4, 'picked' => true ) ),
            'opened' => array( array( 'type' => 'opened', 'window' => 'days', 'n' => 90 ), array( 'type' => 'opened', 'window' => 'days', 'n' => 90 ) ),
            'clicked' => array( array( 'type' => 'clicked', 'window' => 'sends', 'n' => 3 ), array( 'type' => 'clicked', 'window' => 'sends', 'n' => 3 ) ),
            'not_opened' => array( array( 'type' => 'not_opened', 'n' => 5 ), array( 'type' => 'not_opened', 'n' => 5 ) ),
            'not_clicked' => array( array( 'type' => 'not_clicked', 'n' => 5 ), array( 'type' => 'not_clicked', 'n' => 5 ) ),
            'clicked_object' => array( array( 'type' => 'clicked_object', 'contentobject_id' => 18150 ), array( 'type' => 'clicked_object', 'contentobject_id' => 18150 ) ),
            'clicked_tag' => array( array( 'type' => 'clicked_tag', 'tag_id' => 7 ), array( 'type' => 'clicked_tag', 'tag_id' => 7 ) ),
        );
    }

    #[DataProvider( 'validProvider' )]
    public function testValidConditionIsCleaned( array $in, array $expected )
    {
        $this->assertSame( $expected, CjwNewsletterSegmentCondition::normalise( $in ) );
    }

    public static function invalidProvider()
    {
        return array(
            'unknown type' => array( array( 'type' => 'bogus' ), 'unknown_type' ),
            'no type' => array( array(), 'unknown_type' ),
            'subscribed_within days 0' => array( array( 'type' => 'subscribed_within', 'field' => 'created', 'days' => 0 ), 'invalid_param' ),
            'subscribed_within no days' => array( array( 'type' => 'subscribed_within', 'field' => 'created' ), 'missing_param' ),
            'subscribed_within bad field' => array( array( 'type' => 'subscribed_within', 'field' => 'email', 'days' => 3 ), 'field_not_allowed' ),
            'language empty' => array( array( 'type' => 'language', 'values' => array() ), 'invalid_param' ),
            'language missing' => array( array( 'type' => 'language' ), 'missing_param' ),
            'output_format bad' => array( array( 'type' => 'output_format', 'format' => 'pdf' ), 'invalid_param' ),
            'salutation 7' => array( array( 'type' => 'salutation', 'values' => array( 7 ) ), 'invalid_param' ),
            'profile injection' => array( array( 'type' => 'profile', 'field' => 'email; DROP', 'op' => 'set' ), 'field_not_allowed' ),
            'profile bad op' => array( array( 'type' => 'profile', 'field' => 'last_name', 'op' => 'like' ), 'invalid_param' ),
            'profile equals no value' => array( array( 'type' => 'profile', 'field' => 'last_name', 'op' => 'equals' ), 'missing_param' ),
            'interest zero' => array( array( 'type' => 'interest', 'interest_id' => 0 ), 'invalid_param' ),
            'interest picked not bool' => array( array( 'type' => 'interest', 'interest_id' => 1, 'picked' => 'yes' ), 'invalid_param' ),
            'opened bad window' => array( array( 'type' => 'opened', 'window' => 'weeks', 'n' => 1 ), 'invalid_param' ),
            'clicked n 0' => array( array( 'type' => 'clicked', 'window' => 'days', 'n' => 0 ), 'invalid_param' ),
            'not_opened n 0' => array( array( 'type' => 'not_opened', 'n' => 0 ), 'invalid_param' ),
            'not_clicked missing' => array( array( 'type' => 'not_clicked' ), 'missing_param' ),
            'clicked_object text' => array( array( 'type' => 'clicked_object', 'contentobject_id' => 'x' ), 'invalid_param' ),
            'clicked_tag missing' => array( array( 'type' => 'clicked_tag' ), 'missing_param' ),
        );
    }

    #[DataProvider( 'invalidProvider' )]
    public function testInvalidConditionGivesErrorCode( array $in, $code )
    {
        $this->assertSame( $code, CjwNewsletterSegmentCondition::normalise( $in ) );
    }

    public function testClickedTagIsRefusedWithoutEzTags()
    {
        $this->assertSame( 'tags_unavailable', CjwNewsletterSegmentCondition::normalise( array( 'type' => 'clicked_tag', 'tag_id' => 7 ), false ) );
    }

    public function testIsBehavior()
    {
        foreach ( array( 'opened', 'clicked', 'not_opened', 'not_clicked', 'clicked_object', 'clicked_tag' ) as $type )
        {
            $this->assertTrue( CjwNewsletterSegmentCondition::isBehavior( array( 'type' => $type ) ), $type );
        }
        foreach ( array( 'subscribed_within', 'language', 'output_format', 'salutation', 'profile', 'interest' ) as $type )
        {
            $this->assertFalse( CjwNewsletterSegmentCondition::isBehavior( array( 'type' => $type ) ), $type );
        }
        $this->assertFalse( CjwNewsletterSegmentCondition::isBehavior( array() ) );
    }
}
