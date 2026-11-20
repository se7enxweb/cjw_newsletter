<?php
/**
 * The bulk counter of the extension points nests and never goes below zero, without the kernel:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterExtensionPointsBulkTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/cjwnewsletterextensionpoints.php';

class cjwNewsletterExtensionPointsBulkTest extends TestCase
{
    protected function setUp(): void
    {
        while ( CjwNewsletterExtensionPoints::inBulk() )
            CjwNewsletterExtensionPoints::endBulk();
    }

    public function testNotInBulkByDefault()
    {
        $this->assertFalse( CjwNewsletterExtensionPoints::inBulk() );
    }

    public function testCounterNests()
    {
        CjwNewsletterExtensionPoints::beginBulk();
        CjwNewsletterExtensionPoints::beginBulk();
        $this->assertTrue( CjwNewsletterExtensionPoints::inBulk() );
        CjwNewsletterExtensionPoints::endBulk();
        $this->assertTrue( CjwNewsletterExtensionPoints::inBulk() );
        CjwNewsletterExtensionPoints::endBulk();
        $this->assertFalse( CjwNewsletterExtensionPoints::inBulk() );
    }

    public function testUnbalancedEndDoesNotGoNegative()
    {
        CjwNewsletterExtensionPoints::endBulk();
        CjwNewsletterExtensionPoints::endBulk();
        $this->assertFalse( CjwNewsletterExtensionPoints::inBulk() );
        CjwNewsletterExtensionPoints::beginBulk();
        $this->assertTrue( CjwNewsletterExtensionPoints::inBulk() );
        CjwNewsletterExtensionPoints::endBulk();
        $this->assertFalse( CjwNewsletterExtensionPoints::inBulk() );
    }
}
