<?php
/**
 * The SQLite helper for the segment tests loads the schema and answers through the CjwNewsletterSegmentDb interface.
 * No kernel, no database server:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSegmentSqliteDbTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/segments/cjwnewslettersegmentdb.php';
require_once __DIR__ . '/fixtures/segments/cjwnewslettersegmentsqlitedb.php';

class cjwNewsletterSegmentSqliteDbTest extends TestCase
{
    public function testSchemaLoadsAndHelperRowComesBack()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $id = $db->addUser( array( 'email' => 'a@example.org' ) );
        $rows = $db->rows( 'SELECT id, email FROM cjwnl_user' );
        $this->assertCount( 1, $rows );
        $this->assertSame( 'a@example.org', $rows[0]['email'] );
        $this->assertEquals( $id, $rows[0]['id'] );
    }

    public function testAllHelpersInsert()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $db->addUser();
        $db->addSubscription();
        $db->addInterest();
        $db->addUserInterest();
        $db->addSend();
        $db->addItem();
        $db->addLink();
        $db->addClick();
        foreach ( array( 'cjwnl_user', 'cjwnl_subscription', 'cjwnl_interest', 'cjwnl_user_interest', 'cjwnl_edition_send', 'cjwnl_edition_send_item', 'cjwnl_link', 'cjwnl_link_click' ) as $table )
        {
            $this->assertCount( 1, $db->rows( 'SELECT * FROM ' . $table ), $table );
        }
    }

    public function testQuoteDoublesSingleQuote()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        $this->assertSame( "O''Brien", $db->quote( "O'Brien" ) );
        $db->addUser( array( 'email' => "o'b@example.org" ) );
        $rows = $db->rows( "SELECT id FROM cjwnl_user WHERE email = '" . $db->quote( "o'b@example.org" ) . "'" );
        $this->assertCount( 1, $rows );
    }

    public function testOffsetAndLimit()
    {
        $db = new CjwNewsletterSegmentSqliteDb();
        for ( $i = 1; $i <= 5; $i++ )
        {
            $db->addUser( array( 'email' => "u$i@example.org" ) );
        }
        $rows = $db->rows( 'SELECT email FROM cjwnl_user ORDER BY id', 1, 2 );
        $this->assertSame( array( 'u2@example.org', 'u3@example.org' ), array_column( $rows, 'email' ) );
        $this->assertCount( 5, $db->rows( 'SELECT id FROM cjwnl_user' ) );
    }
}
