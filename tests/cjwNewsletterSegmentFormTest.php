<?php
/**
 * The segment form is parsed without the kernel:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSegmentFormTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentcondition.php';
require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentform.php';

class cjwNewsletterSegmentFormTest extends TestCase
{
    public function testRowsAreReindexedInPostOrder()
    {
        $rows = CjwNewsletterSegmentForm::rows( array( 'Condition' => array( 5 => array( 'type' => 'output_format', 'format' => 'html' ),
                                                                             2 => array( 'type' => 'not_opened', 'n' => '3' ) ) ) );
        $this->assertSame( array( 'output_format', 'not_opened' ), array( $rows[0]['type'], $rows[1]['type'] ) );
        $this->assertCount( 2, $rows );
    }

    public function testNoConditionsPostedGivesNoRows()
    {
        $this->assertSame( array(), CjwNewsletterSegmentForm::rows( array() ) );
        $this->assertSame( array(), CjwNewsletterSegmentForm::rows( array( 'Condition' => 'x' ) ) );
    }

    public function testAddRowAppendsAnEmptyRow()
    {
        $rows = CjwNewsletterSegmentForm::addRow( array( array( 'type' => 'language' ) ) );
        $this->assertCount( 2, $rows );
        $this->assertSame( array( 'type' => '' ), $rows[1] );
    }

    public function testRemoveRowDropsOnlyThatRow()
    {
        $rows = array( array( 'type' => 'a' ), array( 'type' => 'b' ), array( 'type' => 'c' ) );
        $this->assertSame( array( array( 'type' => 'a' ), array( 'type' => 'c' ) ), CjwNewsletterSegmentForm::removeRow( $rows, 1 ) );
        $this->assertCount( 3, CjwNewsletterSegmentForm::removeRow( $rows, 9 ) );
    }

    public function testValidRowsAreNormalised()
    {
        $result = CjwNewsletterSegmentForm::validate( array(
            array( 'type' => 'language', 'values' => ' ger-DE, eng-GB ,,ger-DE' ),
            array( 'type' => 'salutation', 'values' => array( '1', '2' ) ),
            array( 'type' => 'interest', 'interest_id' => '4', 'picked' => '0' ),
            array( 'type' => 'profile', 'field' => 'organisation', 'op' => 'set', 'value' => 'ignored' ),
            array( 'type' => 'clicked', 'window' => 'days', 'n' => '90' ),
        ) );
        $this->assertSame( array(), $result['errors'] );
        $this->assertSame( array(
            array( 'type' => 'language', 'values' => array( 'ger-DE', 'eng-GB' ) ),
            array( 'type' => 'salutation', 'values' => array( 1, 2 ) ),
            array( 'type' => 'interest', 'interest_id' => 4, 'picked' => false ),
            array( 'type' => 'profile', 'field' => 'organisation', 'op' => 'set' ),
            array( 'type' => 'clicked', 'window' => 'days', 'n' => 90 ),
        ), $result['conditions'] );
    }

    public function testErrorsAreKeyedPerRowAndNothingIsReturnedAsValid()
    {
        $result = CjwNewsletterSegmentForm::validate( array(
            array( 'type' => 'not_opened', 'n' => '3' ),
            array( 'type' => '' ),
            array( 'type' => 'not_clicked', 'n' => 'x' ),
        ) );
        $this->assertSame( array( 1 => CjwNewsletterSegmentCondition::ERROR_UNKNOWN_TYPE, 2 => CjwNewsletterSegmentCondition::ERROR_INVALID_PARAM ),
                           $result['errors'] );
        $this->assertSame( array(), $result['conditions'] );
    }

    public function testEmptyParameterIsReportedAsMissing()
    {
        $result = CjwNewsletterSegmentForm::validate( array( array( 'type' => 'language', 'values' => '  ' ) ) );
        $this->assertSame( array( 0 => CjwNewsletterSegmentCondition::ERROR_MISSING_PARAM ), $result['errors'] );
    }

    public function testTagsUnavailableIsPassedOn()
    {
        $result = CjwNewsletterSegmentForm::validate( array( array( 'type' => 'clicked_tag', 'tag_id' => '7' ) ), false );
        $this->assertSame( array( 0 => CjwNewsletterSegmentCondition::ERROR_TAGS_UNAVAILABLE ), $result['errors'] );
    }

    public function testRowsFromStoredConditionsShowListsAsText()
    {
        $rows = CjwNewsletterSegmentForm::fromConditions( array(
            array( 'type' => 'language', 'values' => array( 'ger-DE', 'eng-GB' ) ),
            array( 'type' => 'interest', 'interest_id' => 4, 'picked' => false ),
            array( 'type' => 'salutation', 'values' => array( 1, 2 ) ),
        ) );
        $this->assertSame( 'ger-DE, eng-GB', $rows[0]['values'] );
        $this->assertSame( '0', $rows[1]['picked'] );
        $this->assertSame( array( '1', '2' ), $rows[2]['values'] );
    }

    public function testMatchModeFallsBackToAll()
    {
        $this->assertSame( 'any', CjwNewsletterSegmentForm::matchMode( array( 'MatchMode' => 'any' ) ) );
        $this->assertSame( 'all', CjwNewsletterSegmentForm::matchMode( array( 'MatchMode' => 'x' ) ) );
        $this->assertSame( 'all', CjwNewsletterSegmentForm::matchMode( array() ) );
    }
}
