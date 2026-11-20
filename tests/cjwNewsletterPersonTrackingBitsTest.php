<?php
/**
 * personTrackingBits() tells which per-person tracking a message carries: bit 1 links, bit 2 the open pixel.
 * Kernel-free:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterPersonTrackingBitsTest.php
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/statistics/cjwnewslettertracking.php';

class cjwNewsletterPersonTrackingBitsTest extends TestCase
{
    private const P = CjwNewsletterTracking::PLACEHOLDER;

    public function testHtmlWithPixelAndLinksIsThree()
    {
        $bodies = array( 'html' => '<a href="https://x.test/newsletter/r/5/' . self::P . '">a</a><img src="https://x.test/newsletter/o/' . self::P . '" />' );
        $this->assertSame( 3, CjwNewsletterTracking::personTrackingBits( $bodies, true ) );
    }

    public function testTextWithLinksIsOne()
    {
        $bodies = array( 'text' => 'https://x.test/newsletter/r/5/' . self::P );
        $this->assertSame( 1, CjwNewsletterTracking::personTrackingBits( $bodies, true ) );
    }

    public function testPixelWithoutLinksIsTwo()
    {
        $bodies = array( 'html' => '<p>hi</p><img src="/newsletter/o/' . self::P . '" />' );
        $this->assertSame( 2, CjwNewsletterTracking::personTrackingBits( $bodies, true ) );
    }

    public function testAnonymousKeyIsZero()
    {
        $bodies = array( 'html' => '<img src="/newsletter/o/' . self::P . '" />' );
        $this->assertSame( 0, CjwNewsletterTracking::personTrackingBits( $bodies, false ) );
    }

    public function testNoPlaceholderIsZero()
    {
        $this->assertSame( 0, CjwNewsletterTracking::personTrackingBits( array( 'html' => '<p>hi</p>', 'text' => 'hi' ), true ) );
    }
}
