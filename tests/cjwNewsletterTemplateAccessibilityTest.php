<?php
/**
 * The newsletter templates name their controls for assistive technology, and the admin2 templates carry no inline
 * CSS. Reads the templates only, without a kernel or a database:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterTemplateAccessibilityTest.php
 *
 *  NA-01 - Every check-all box (data-nl-check-all) has an aria-label, not only a title
 *  NA-02 - Every <img> of the templates has an alt attribute or a title
 *  NA-03 - No style="" attribute and no <style> block in the admin2 templates
 *  NA-04 - No inline <script> with code, no on...= handler and no javascript: URL in any template
 *
 * @copyright Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

class cjwNewsletterTemplateAccessibilityTest extends TestCase
{
    /**
     * @return array path => source, template comments left out
     */
    private static function templates( string $dir ): array
    {
        $out = array();
        $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( dirname( __DIR__ ) . '/' . $dir, FilesystemIterator::SKIP_DOTS ) );
        foreach ( $it as $file )
            if ( substr( (string)$file, -4 ) === '.tpl' )
                $out[(string)$file] = (string)preg_replace( '/\{\*.*?\*\}/s', '', (string)file_get_contents( (string)$file ) );
        ksort( $out );
        return $out;
    }

    /** NA-01 */
    public function testCheckAllBoxesHaveAnAccessibleName()
    {
        $seen = 0;
        foreach ( self::templates( 'design' ) as $path => $source )
        {
            preg_match_all( '/<input\b[^>]*data-nl-check-all[^>]*>/', $source, $m );
            foreach ( $m[0] as $tag )
            {
                ++$seen;
                $this->assertStringContainsString( 'aria-label="', $tag, basename( $path ) );
            }
        }
        $this->assertGreaterThan( 0, $seen );
    }

    /** NA-02 */
    public function testImagesHaveAnAlternative()
    {
        foreach ( self::templates( 'design' ) as $path => $source )
        {
            // the tags, with template blocks ({...}) taken out so a '>' inside one does not end the tag
            $flat = (string)preg_replace( '/\{[^{}]*\}/', 'X', $source );
            preg_match_all( '/<img\b[^>]*>/i', $flat, $m );
            foreach ( $m[0] as $tag )
                $this->assertMatchesRegularExpression( '/\s(alt|title)=/', $tag, basename( $path ) . ': ' . $tag );
        }
    }

    /** NA-04 */
    public function testNoInlineScriptCode()
    {
        $seen = 0;
        foreach ( self::templates( 'design' ) as $path => $source )
        {
            preg_match_all( '/<script\b([^>]*)>(.*?)<\/script>/is', $source, $m, PREG_SET_ORDER );
            foreach ( $m as $script )
            {
                if ( preg_match( '/\bsrc\s*=|type\s*=\s*["\']application\/(ld\+)?json["\']/i', $script[1] ) )
                    continue;
                $this->assertSame( '', trim( $script[2] ), basename( $path ) . ': inline <script> with code' );
            }
            $this->assertDoesNotMatchRegularExpression( '/<[a-z][^>]*\son[a-z]+\s*=/i', $source, basename( $path ) . ': on...= handler' );
            $this->assertDoesNotMatchRegularExpression( '/(href|action|src)\s*=\s*["\']?\s*javascript:/i', $source, basename( $path ) . ': javascript: URL' );
            $seen += substr_count( $source, 'cjw_newsletter.js' );
        }
        // the behavior lives in the file the templates print
        $this->assertGreaterThan( 0, $seen );
        $this->assertFileExists( dirname( __DIR__ ) . '/design/standard/javascript/cjw_newsletter.js' );
    }

    /** NA-03 */
    public function testNoInlineCssInAdmin2()
    {
        foreach ( self::templates( 'design/admin2' ) as $path => $source )
        {
            $this->assertDoesNotMatchRegularExpression( '/<style\b/i', $source, basename( $path ) );
            $this->assertDoesNotMatchRegularExpression( '/(?<![\w.\-])style\s*=\s*["\'{]/i', $source, basename( $path ) );
        }
    }
}
