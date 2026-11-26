<?php
/**
 * The mailbox connection never writes the mailbox password to the newsletter log. Needs the kernel autoload (eZPersistentObject),
 * no database:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterMailboxLogTest.php
 *
 *  ML-01 - loggableSettings() masks a non-empty password and keeps every other setting
 *  ML-02 - loggableSettings() keeps an empty or missing password as it is, so a missing password stays visible
 *  ML-03 - connect() passes its settings to the log only through loggableSettings()
 *
 * @copyright Copyright (C) 1998 - 2026 Exponential Foundation and 7x; All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/cjwnewslettermailbox.php';

class cjwNewsletterMailboxLogTest extends TestCase
{
    public function testPasswordIsMasked()
    {
        $settings = array( 'mailbox_id' => 3, 'type' => 'imap', 'server' => 'mail.example.com', 'user_name' => 'bounce',
                           'password' => 'S3cret!', 'port' => 993, 'is_ssl' => true, 'delete_mails_from_server' => false );
        $logged = CjwNewsletterMailbox::loggableSettings( $settings );

        $this->assertSame( '***', $logged['password'] );
        unset( $settings['password'], $logged['password'] );
        $this->assertSame( $settings, $logged );
    }

    public function testEmptyOrMissingPasswordStaysVisible()
    {
        $this->assertSame( '', CjwNewsletterMailbox::loggableSettings( array( 'password' => '' ) )['password'] );
        $this->assertNull( CjwNewsletterMailbox::loggableSettings( array( 'password' => null ) )['password'] );
        $this->assertSame( array( 'server' => 'x' ), CjwNewsletterMailbox::loggableSettings( array( 'server' => 'x' ) ) );
    }

    public function testConnectLogsOnlyMaskedSettings()
    {
        $method = new ReflectionMethod( 'CjwNewsletterMailbox', 'connect' );
        $lines = file( $method->getFileName() );
        $source = implode( '', array_slice( $lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1 ) );

        $this->assertSame( 2, preg_match_all( '/self::loggableSettings\(\s*\$settingArray\s*\)/', $source ) );
        // every other use of $settingArray is its definition
        $this->assertSame( 3, preg_match_all( '/\$settingArray\b/', $source ) );
    }
}
