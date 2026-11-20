<?php
/**
 * The eZ Tags check of the segment evaluator uses the database layer's table list when it has one, and runs no
 * probe query then (on eZDB a probe in the wrong SQL flavour logs an error, and on PostgreSQL it aborts an open
 * transaction):
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSegmentTagsTableTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentcondition.php';
require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentdb.php';
require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentevaluator.php';

/** A database layer with a table list that fails the test on any query. */
class cjwNewsletterSegmentTagsTableTestDb implements CjwNewsletterSegmentDb
{
    public $tables;
    public $asked = 0;

    public function __construct( array $tables )
    {
        $this->tables = $tables;
    }

    public function rows( $sql, $offset = 0, $limit = 0 )
    {
        throw new RuntimeException( 'no query expected: ' . $sql );
    }

    public function quote( $string )
    {
        return $string;
    }

    public function tableExists( $name )
    {
        $this->asked++;
        return in_array( $name, $this->tables, true );
    }
}

class cjwNewsletterSegmentTagsTableTest extends TestCase
{
    public function testTableListSaysYes()
    {
        $db = new cjwNewsletterSegmentTagsTableTestDb( array( 'eztags_attribute_link' ) );
        $this->assertTrue( ( new CjwNewsletterSegmentEvaluator( $db ) )->tagsAvailable() );
    }

    public function testTableListSaysNoWithoutAnyQuery()
    {
        $db = new cjwNewsletterSegmentTagsTableTestDb( array() );
        $this->assertFalse( ( new CjwNewsletterSegmentEvaluator( $db ) )->tagsAvailable() );
    }

    public function testAnswerIsKeptForTheEvaluator()
    {
        $db = new cjwNewsletterSegmentTagsTableTestDb( array( 'eztags_attribute_link' ) );
        $e = new CjwNewsletterSegmentEvaluator( $db );
        $e->tagsAvailable();
        $e->tagsAvailable();
        $e->tagsAvailable();
        $this->assertSame( 1, $db->asked );
    }
}
