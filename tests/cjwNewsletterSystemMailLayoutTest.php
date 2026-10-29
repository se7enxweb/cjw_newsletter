<?php
/**
 * The system mails of the newsletter (subscription confirmation and information, the unsubscribe links) are sent as
 * text and as HTML in the kernel's mail layout (CjwNewsletterMail::systemMailBody()). Needs the kernel (Exponential
 * 6.0.15 or later), no database, sends nothing:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterSystemMailLayoutTest.php
 *
 *  SL-01 - the text stays the text part; the HTML part is the layout with the subject (without its [Newsletter]
 *          prefix) as the heading and the first address on a line of its own as the button
 *  SL-02 - values in the text (a name) are escaped in the HTML part
 *
 * @copyright Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/cjwnewslettermail.php';

class cjwNewsletterSystemMailLayoutTest extends TestCase
{
    private $savedGlobals = array();

    protected function setUp(): void
    {
        if ( !class_exists( 'expMailTemplateBody' ) || !method_exists( 'expMailTemplateBody', 'textLayout' ) )
            $this->markTestSkipped( 'needs the kernel with the mail layout (Exponential 6.0.15 or later)' );
        foreach ( array( 'eZTemplateCompilerSettings', 'eZSiteBasics', 'eZCurrentAccess' ) as $name )
            $this->savedGlobals[$name] = array_key_exists( $name, $GLOBALS ) ? array( $GLOBALS[$name] ) : null;
        $GLOBALS['eZTemplateCompilerSettings']['compile'] = false;
        $GLOBALS['eZSiteBasics']['no-cache-adviced'] = true;
        if ( !isset( $GLOBALS['eZCurrentAccess']['name'] ) )
            $GLOBALS['eZCurrentAccess'] = array( 'name' => eZINI::instance()->variable( 'SiteSettings', 'DefaultAccess' ),
                                                 'type' => eZSiteAccess::TYPE_DEFAULT );
    }

    protected function tearDown(): void
    {
        foreach ( $this->savedGlobals as $name => $saved )
        {
            if ( $saved === null )
                unset( $GLOBALS[$name] );
            else
                $GLOBALS[$name] = $saved[0];
        }
    }

    private static function body( $text, $subject )
    {
        $handler = set_exception_handler( null );
        restore_exception_handler();
        try
        {
            return CjwNewsletterMail::systemMailBody( $text, $subject );
        }
        finally
        {
            $current = set_exception_handler( null );
            restore_exception_handler();
            if ( $current !== $handler )
                restore_exception_handler();
        }
    }

    /** SL-01 */
    public function testTextAndHtmlInTheLayout()
    {
        $text = "Hello Jane Doe\n\nThank you for subscribing to the following newsletter:\n- News\n\nTo activate or edit your subscription, please visit this link:\n\n"
              . "https://www.example.invalid/newsletter/configure/abc\n\n--\n";
        $body = self::body( $text, '[Newsletter www.example.invalid] Subscription verification' );
        $this->assertSame( $text, $body['text'] );
        $this->assertStringContainsString( 'role="presentation"', $body['html'] );
        $this->assertMatchesRegularExpression( '#<h1[^>]*>Subscription verification</h1>#', $body['html'] );
        $this->assertMatchesRegularExpression( '#<a href="https://www.example.invalid/newsletter/configure/abc" style="display:inline-block#', $body['html'] );
        $this->assertStringContainsString( 'Thank you for subscribing to the following newsletter:<br />- News', $body['html'] );
    }

    /** SL-02 */
    public function testValuesAreEscaped()
    {
        $body = self::body( "Hello <script>x</script>\n\nhttps://www.example.invalid/newsletter/configure/abc\n", 'Subscription information' );
        $this->assertStringNotContainsString( '<script>', $body['html'] );
        $this->assertStringContainsString( 'Hello &lt;script&gt;x&lt;/script&gt;', $body['html'] );
    }
}
