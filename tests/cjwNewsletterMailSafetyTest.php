<?php
/**
 * Mails of the newsletter reach zetacomponents/mail only without line breaks in addresses, names, the subject and
 * headers, and SMTP gets CRLF line breaks, so a line starting with "." is escaped. Needs the kernel (eZINI) and
 * zetacomponents/mail, no database:
 *
 *   php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterMailSafetyTest.php
 *
 *  MS-01 - isValidAddress() refuses an address with a trailing newline, which ezcMailTools::validateEmailAddress() accepts
 *  MS-02 - secureMail() takes CR/LF out of names, subject and header values
 *  MS-03 - secureMail() refuses a mail with a line break in an address (MAIL FROM / RCPT TO)
 *  MS-04 - sent over SMTP with HeaderLineEnding LF, a body line starting with "." is escaped and the line break is
 *          put back afterwards
 *
 * @copyright Copyright (C) 1998 - 2026 7x & Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package tests
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/classes/cjwnewslettertransport.php';

class cjwNewsletterMailSafetyTest extends TestCase
{
    private static function mail(): ezcMail
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'news@example.com', 'News' );
        $mail->addTo( new ezcMailAddress( 'reader@example.com', 'Reader' ) );
        $mail->subject = 'Hello';
        $mail->body = new ezcMailText( "Line one\n.\nLine three\n" );
        return $mail;
    }

    /** MS-01 */
    public function testAnAddressWithALineBreakIsNotValid()
    {
        $this->assertTrue( (bool)ezcMailTools::validateEmailAddress( "reader@example.com\n" ), 'the gap this closes' );
        $this->assertFalse( CjwNewsletterTransport::isValidAddress( "reader@example.com\n" ) );
        $this->assertFalse( CjwNewsletterTransport::isValidAddress( "reader@example.com\r\nRCPT TO:<x@example.org>" ) );
        $this->assertFalse( CjwNewsletterTransport::isValidAddress( "\"a\0b\"@example.com" ) );
        $this->assertFalse( CjwNewsletterTransport::isValidAddress( null ) );
        $this->assertTrue( CjwNewsletterTransport::isValidAddress( 'reader@example.com' ) );
    }

    /** MS-02 */
    public function testLineBreaksAreTakenOutOfNamesSubjectAndHeaders()
    {
        $mail = self::mail();
        $mail->from->name = "News\r\nBcc: victim@example.org";
        $mail->subject = "Hello\r\nBcc: victim@example.org";
        $mail->setHeader( 'Reply-To', new ezcMailAddress( 'news@example.com', "Eve\nBcc: victim@example.org" ) );
        $mail->setHeader( 'X-Custom', "a\r\nBcc: victim@example.org" );

        $this->assertNull( CjwNewsletterTransport::secureMail( $mail ) );
        $generated = $mail->generate();
        $this->assertDoesNotMatchRegularExpression( '/^Bcc:/mi', $generated );
        $this->assertStringContainsString( 'Bcc: victim@example.org', $mail->subject, 'kept as text on one line' );
    }

    /** MS-03 */
    public function testALineBreakInAnAddressRefusesTheMail()
    {
        $mail = self::mail();
        $mail->addTo( new ezcMailAddress( "x@example.org>\r\nRCPT TO:<victim@example.org", '' ) );
        $this->assertSame( 'invalid address in to', CjwNewsletterTransport::secureMail( $mail ) );

        $mail = self::mail();
        $mail->returnPath = new ezcMailAddress( "bounce@example.com\n", '' );
        $this->assertSame( 'invalid address in returnPath', CjwNewsletterTransport::secureMail( $mail ) );
    }

    /** MS-04 */
    public function testSmtpEscapesALineStartingWithADot()
    {
        $dir = sys_get_temp_dir() . '/cjwnl-smtp-' . getmypid();
        @mkdir( $dir, 0700 );
        $dataFile = $dir . '/data.txt';
        $server = stream_socket_server( 'tcp://127.0.0.1:0', $errno, $errstr );
        $this->assertNotFalse( $server, $errstr );
        $port = (int)substr( strrchr( stream_socket_get_name( $server, false ), ':' ), 1 );
        fclose( $server );
        $process = proc_open( array( PHP_BINARY, '-r', self::SERVER, (string)$port, $dataFile ), array(), $pipes );
        for ( $i = 0; $i < 50 && !file_exists( $dataFile . '.ready' ); $i++ )
            usleep( 100000 );
        @unlink( $dataFile . '.ready' );

        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        foreach ( array( 'SmtpTransportServer' => '127.0.0.1', 'SmtpTransportPort' => $port, 'SmtpTransportUser' => '',
                         'SmtpTransportPassword' => '', 'SmtpTransportConnectionType' => '' ) as $key => $value )
            $ini->setVariable( 'NewsletterMailSettings', $key, $value );

        $before = ezcMailTools::lineBreak();
        ezcMailTools::setLineBreak( "\n" );
        try
        {
            $transport = new CjwNewsletterTransport( 'smtp' );
            $result = $transport->send( self::mail() );
            $this->assertSame( "\n", ezcMailTools::lineBreak(), 'put back' );
        }
        finally
        {
            ezcMailTools::setLineBreak( $before );
        }
        proc_close( $process );
        $data = (string)@file_get_contents( $dataFile );
        @unlink( $dataFile );
        @rmdir( $dir );

        $this->assertTrue( $result, $result instanceof Exception ? $result->getMessage() : '' );
        $this->assertStringContainsString( "Line one\r\n..\r\nLine three", $data );
        $this->assertStringNotContainsString( "\r\n.\r\n", $data );
    }

    /** A one-message SMTP server: argv[1] port, argv[2] file for what came after DATA */
    const SERVER = <<<'PHP'
$server = stream_socket_server( 'tcp://127.0.0.1:' . $argv[1] );
touch( $argv[2] . '.ready' );
$client = stream_socket_accept( $server, 10 );
$say = function ( $line ) use ( $client ) { fwrite( $client, $line . "\r\n" ); };
$say( '220 test' );
$data = '';
$inData = false;
while ( ( $line = fgets( $client ) ) !== false )
{
    if ( $inData )
    {
        if ( $line === ".\r\n" ) { $inData = false; file_put_contents( $argv[2], $data ); $say( '250 ok' ); continue; }
        $data .= $line;
        continue;
    }
    $command = strtoupper( substr( $line, 0, 4 ) );
    if ( $command === 'EHLO' || $command === 'HELO' ) $say( '250 test' );
    else if ( $command === 'DATA' ) { $inData = true; $say( '354 go' ); }
    else if ( $command === 'QUIT' ) { $say( '221 bye' ); break; }
    else $say( '250 ok' );
}
PHP;
}

?>
