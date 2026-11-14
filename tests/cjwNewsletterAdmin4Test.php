<?php
/**
 * The newsletter administration in admin4 (and admin4l): every administration template has an admin4 copy built from
 * admin4's shared components, with no inline CSS, Bootstrap icons the kernel defines and one stylesheet that takes its
 * colours from the --a4-* tokens. Reads files only, without a kernel or a database:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterAdmin4Test.php
 *
 *  A4-01 - No <style> block and no style="" attribute in any admin4 template (templates and overrides)
 *  A4-02 - Every administration template has an admin4 copy at the same path: the templates of design/admin and
 *          design/admin2, the pages the module views fetch, the parts of the extension points and the edit templates
 *          of the datatypes. Only the subscriber's pages and the mail templates are left to design/standard.
 *  A4-03 - No box frame, classic list table, classic button or Crystal image in the admin4 templates
 *  A4-04 - The stylesheet is registered for the administration, takes every colour and radius from a --a4-* token
 *          (the mailed preview's white page aside) and defines no token of its own
 *  A4-05 - Every cjwnl-a4-* rule of the stylesheet is used by a template
 *  A4-06 - Inside an installation: every a4-bi-* icon the templates use is defined by the kernel's components.css
 *
 * @copyright Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

class cjwNewsletterAdmin4Test extends TestCase
{
    /**
     * Templates the subscriber or a mail sees, never the administration: design/standard serves them in every design.
     */
    const NOT_ADMINISTRATION = '#^templates/(newsletter/(configure|subscribe[a-z_]*|unsubscribe[a-z_]*|mail/.*|deliverability/mail/.*|sms/(confirm|subscribe_part)|rendering/plaintext_attribute)|mail/.*|mailpreferences/.*|notification/handler/cjwnewsletter/parts/.*)\.tpl$#';

    const STYLESHEET = 'design/standard/stylesheets/cjw_newsletter_admin4.css';

    private static function root(): string
    {
        return dirname( __DIR__ );
    }

    /**
     * @return array relative path below the design (templates/..., override/templates/...) => source, comments left out
     */
    private static function design( string $design ): array
    {
        $out = array();
        $base = self::root() . '/design/' . $design;
        if ( !is_dir( $base ) )
            return $out;
        $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
        foreach ( $it as $file )
            if ( substr( (string)$file, -4 ) === '.tpl' )
                $out[substr( (string)$file, strlen( $base ) + 1 )] = (string)preg_replace( '/\{\*.*?\*\}/s', '', (string)file_get_contents( (string)$file ) );
        ksort( $out );
        return $out;
    }

    /** A4-01 */
    public function testNoInlineCss()
    {
        $templates = self::design( 'admin4' );
        $this->assertGreaterThan( 100, count( $templates ) );
        foreach ( $templates as $path => $source )
        {
            $this->assertDoesNotMatchRegularExpression( '/<style\b/i', $source, $path );
            $this->assertDoesNotMatchRegularExpression( '/(?<![\w.\-])style\s*=\s*["\'{]/i', $source, $path );
        }
    }

    /** A4-02 */
    public function testEveryAdministrationTemplateHasAnAdmin4Copy()
    {
        $wanted = array();
        foreach ( array( 'admin', 'admin2' ) as $design )
            foreach ( array_keys( self::design( $design ) ) as $path )
                $wanted[$path] = "design/$design";

        // the pages of the module views and the parts of the extension points ([ExtensionPointSettings], *Parts[])
        $sources = array( self::root() . '/settings/cjw_newsletter.ini' );
        $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( self::root() . '/classes/runnable/views', FilesystemIterator::SKIP_DOTS ) );
        foreach ( $it as $file )
            $sources[] = (string)$file;
        foreach ( $sources as $file )
        {
            preg_match_all( '#design:([a-z0-9_/]+\.tpl)#', (string)file_get_contents( $file ), $m );
            foreach ( $m[1] as $name )
                $wanted['templates/' . $name] = basename( $file );
        }

        // the edit templates of the datatypes, shown in the object edit form
        foreach ( array_keys( self::design( 'standard' ) ) as $path )
            if ( preg_match( '#^templates/content/datatype/edit/cjw[a-z]+\.tpl$#', $path ) )
                $wanted[$path] = 'design/standard';

        $admin4 = self::design( 'admin4' );
        $missing = array();
        foreach ( $wanted as $path => $from )
        {
            if ( preg_match( self::NOT_ADMINISTRATION, $path ) )
                continue;
            if ( !isset( $admin4[$path] ) )
                $missing[] = "$path ($from)";
        }
        $this->assertGreaterThan( 60, count( $wanted ) );
        $this->assertSame( array(), $missing, 'administration templates without an admin4 copy' );
    }

    /** A4-03 */
    public function testNoClassicMarkup()
    {
        foreach ( self::design( 'admin4' ) as $path => $source )
        {
            $this->assertDoesNotMatchRegularExpression( '/class="[^"]*\bbox-(header|content|ml|mr|tc|bc|bl|br|tl|tr)\b/', $source, $path );
            $this->assertDoesNotMatchRegularExpression( '/<table\b[^>]*class="list\b/', $source, $path );
            $this->assertDoesNotMatchRegularExpression( '/class="(button|defaultbutton|halfbox|box)"/', $source, $path );
            $this->assertDoesNotMatchRegularExpression( '/(crystal|\|ezimage\b|\.(png|gif)["\'])/i', $source, $path );
        }
    }

    /** A4-04 */
    public function testStylesheetUsesOnlyTokens()
    {
        $design = (string)file_get_contents( self::root() . '/settings/design.ini.append.php' );
        $this->assertStringContainsString( 'BackendCSSFileList[]=cjw_newsletter_admin4.css', $design );

        $css = (string)preg_replace( '#/\*.*?\*/#s', '', (string)file_get_contents( self::root() . '/' . self::STYLESHEET ) );
        $this->assertDoesNotMatchRegularExpression( '/(^|[;{\s])--[a-z][\w-]*\s*:/', $css, 'the stylesheet defines a custom property' );

        preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER );
        $this->assertGreaterThan( 50, count( $rules ) );
        foreach ( $rules as $rule )
        {
            $selector = trim( $rule[1] );
            foreach ( explode( ';', $rule[2] ) as $declaration )
            {
                if ( !preg_match( '/^\s*([a-z-]+)\s*:\s*(.+?)\s*$/s', $declaration, $d ) )
                    continue;
                list( , $property, $value ) = $d;
                $this->assertDoesNotMatchRegularExpression( '/#[0-9a-f]{3,8}\b|\b(rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch)\(/i', $value, "$selector { $property }" );
                if ( preg_match( '/^(color|background|background-color|border(-[a-z]+)*-color|outline-color|fill|stroke|box-shadow|text-decoration-color|caret-color|accent-color)$/', $property )
                     || ( preg_match( '/^(border|border-(top|right|bottom|left|block|inline)(-(start|end))?|outline)$/', $property ) && !preg_match( '/^(0|none)$/', $value ) ) )
                {
                    $plain = preg_replace( '/var\(--a4-[\w-]+(,[^)]*)?\)/', '', $value );
                    $plain = preg_replace( '/\b(inherit|currentColor|transparent|none|initial|unset|\d+(\.\d+)?(px|em|rem)?|solid|dashed|dotted)\b/i', '', $plain );
                    if ( preg_match( '/\bCanvas(Text)?\b/', $plain ) )
                    {
                        // the mailed preview keeps its white page: only together with color-scheme: light
                        $this->assertMatchesRegularExpression( '/color-scheme\s*:\s*light/', $rule[2], "$selector { $property }" );
                        $plain = preg_replace( '/\bCanvas(Text)?\b/', '', $plain );
                    }
                    $this->assertSame( '', trim( $plain, " \t\n,/" ), "$selector { $property: $value } takes a colour that is no --a4-* token" );
                }
                if ( preg_match( '/radius$/', $property ) )
                    $this->assertMatchesRegularExpression( '/^(0|50%|(var\(--a4-radius[\w-]*\)\s*|0\s*)+)$/', $value, "$selector { $property }" );
            }
        }
    }

    /** A4-05 */
    public function testEveryRuleIsUsed()
    {
        $css = (string)file_get_contents( self::root() . '/' . self::STYLESHEET );
        preg_match_all( '/\.(cjwnl-a4-[a-z0-9-]+)/', $css, $m );
        $templates = implode( "\n", self::design( 'admin4' ) );
        $unused = array();
        foreach ( array_unique( $m[1] ) as $class )
            if ( strpos( $templates, $class ) === false )
                $unused[] = $class;
        $this->assertSame( array(), $unused, 'rules no admin4 template uses' );
    }

    /** A4-06 */
    public function testIconsAreTheKernels()
    {
        $components = dirname( self::root(), 2 ) . '/design/admin4/stylesheets/components.css';
        if ( !is_file( $components ) )
            $this->markTestSkipped( 'not inside an installation with design/admin4' );
        preg_match_all( '/^\.(a4-bi-[a-z0-9-]+)/m', (string)file_get_contents( $components ), $k );
        $defined = array_flip( $k[1] );
        $used = array();
        foreach ( self::design( 'admin4' ) as $path => $source )
        {
            preg_match_all( '/\b(a4-bi-[a-z0-9-]+)/', $source, $m );
            foreach ( $m[1] as $icon )
                if ( !isset( $defined[$icon] ) )
                    $used[] = "$path: $icon";
        }
        $this->assertSame( array(), $used, 'icons the kernel does not define' );
    }
}
