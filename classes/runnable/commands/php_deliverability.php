<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:deliverability - the area "deliverability" of cjw_newsletter 4.2.0 on the console.
 *
 *   status                 rate limits, pauses, batches, retries, suppressions, test groups, mail-in
 *   suppression-import     a CSV file (--file) into the kernel suppression list with --reason [--note] [--dry-run]
 *   pause                  pause --transport for --minutes [--dry-run]
 *   resume                 lift the pause of --transport [--dry-run]
 *   read                   saved messages (--file: an .eml file or a directory of them): bounces of newsletter mails
 *                          and mails to the mail-in addresses, as the mailbox readers would handle them [--mailbox] [--dry-run]
 */
class Deliverability extends \Exponential\Runnable\Command
{
    public function run()
    {
        $cli = $this->cli();
        $this->script( array(
            'description' => "Deliverability of the newsletter\n" .
                             "  status               rate limits, pauses, batches, retries, suppressions, test groups, mail-in\n" .
                             "  suppression-import   --file=<csv> --reason=<legal|admin|bounce|complaint|unsubscribe_all> [--note=] [--dry-run]\n" .
                             "  pause                --transport=<name> --minutes=<n> [--dry-run]\n" .
                             "  resume               --transport=<name> [--dry-run]\n" .
                             "  read                 --file=<eml or directory> [--mailbox=<id>] [--dry-run]\n" .
                             "\n" .
                             "./console ext:cjw_newsletter:deliverability status\n" .
                             "./console ext:cjw_newsletter:deliverability suppression-import --file=donotcontact.csv --reason=legal --dry-run\n" .
                             "./console ext:cjw_newsletter:deliverability pause --transport=smtp --minutes=30",
            'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $options = $this->startup( '[file:][reason:][note:][transport:][minutes:][mailbox:][dry-run][json]', '',
            array( 'file' => 'suppression-import: the CSV file; read: an .eml file or a directory',
                   'reason' => 'suppression-import: the reason of the entries',
                   'note' => 'suppression-import: a note for the administrators',
                   'transport' => 'pause, resume: the transport (smtp, sendmail, file, sms, ...); empty = the newsletter transport',
                   'minutes' => 'pause: how long (1 to 10080)',
                   'mailbox' => 'read: the newsletter mail account the messages came from (0 = the kernel bounce mailbox)',
                   'dry-run' => 'say what would be done, change nothing',
                   'json' => 'status: JSON' ) );
        $args = isset( $options['arguments'] ) ? array_values( $options['arguments'] ) : array();
        $action = $args ? strtolower( (string)$args[0] ) : 'status';
        $dry = !empty( $options['dry-run'] );
        try
        {
            switch ( $action )
            {
                case 'status':
                    $this->status( $cli, !empty( $options['json'] ) );
                    break;
                case 'suppression-import':
                    $file = isset( $options['file'] ) ? (string)$options['file'] : '';
                    if ( $file === '' || !is_file( $file ) || !is_readable( $file ) )
                        throw new \InvalidArgumentException( 'suppression-import needs --file with a readable CSV file' );
                    $r = \CjwNewsletterSuppressionImport::import( (string)file_get_contents( $file ), isset( $options['reason'] ) ? (string)$options['reason'] : '',
                                                                  isset( $options['note'] ) ? (string)$options['note'] : '', $dry, $file );
                    if ( !$r['ok'] )
                        throw new \RuntimeException( $r['error'] );
                    $cli->output( sprintf( '%s: %d rows, %d valid addresses (%d twice), %d not an address, %d already suppressed, %d %s (reason %s)',
                        basename( $file ), $r['rows'], $r['valid'], $r['duplicates'], $r['invalid_count'], $r['already'], $r['added'],
                        $dry ? 'would be suppressed' : 'suppressed', $r['reason'] ) );
                    foreach ( $r['invalid'] as $bad )
                        $cli->output( '  line ' . $bad['line'] . ': not an address' );
                    break;
                case 'pause':
                case 'resume':
                    $transport = \CjwNewsletterThrottle::name( isset( $options['transport'] ) ? (string)$options['transport'] : '' );
                    if ( $action === 'pause' )
                    {
                        $minutes = isset( $options['minutes'] ) ? (int)$options['minutes'] : 0;
                        if ( $minutes < 1 || $minutes > 10080 )
                            throw new \InvalidArgumentException( 'pause needs --minutes from 1 to 10080' );
                        if ( !$dry )
                            \CjwNewsletterThrottle::pause( $transport, time() + $minutes * 60 );
                        $cli->output( ( $dry ? 'Would pause' : 'Paused' ) . " \"$transport\" until " . date( 'Y-m-d H:i', time() + $minutes * 60 ) . '.' );
                    }
                    else
                    {
                        if ( !$dry )
                            \CjwNewsletterThrottle::resume( $transport );
                        $cli->output( ( $dry ? 'Would resume' : 'Resumed' ) . " \"$transport\"." );
                    }
                    break;
                case 'read':
                    $this->read( $cli, isset( $options['file'] ) ? (string)$options['file'] : '', isset( $options['mailbox'] ) ? (int)$options['mailbox'] : 0, $dry );
                    break;
                default:
                    throw new \InvalidArgumentException( "unknown action '$action' (status, suppression-import, pause, resume, read)" );
            }
        }
        catch ( \Throwable $e )
        {
            $cli->error( 'FAIL: ' . $e->getMessage() );
            $this->shutdown( 1 );
            return 1;
        }
        $cli->output( 'PASS' );
        $this->shutdown( 0 );
        return 0;
    }

    protected function status( $cli, $json )
    {
        $s = \CjwNewsletterDeliverability::summary();
        if ( $json )
        {
            $s['batches'] = count( $s['batches'] );
            $cli->output( json_encode( $s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
            return;
        }
        $cli->output( 'Batches and limits: ' . ( $s['throttle'] ? 'on, batches of ' . $s['batch_size'] : 'off' ) . ', ' . $s['batches_running'] . ' batches waiting' );
        foreach ( $s['transports'] as $t )
            $cli->output( sprintf( '  %-10s minute %d/%s  hour %d/%s%s', $t['transport'], $t['sent_minute'], $t['limit_minute'] ? $t['limit_minute'] : '-',
                                   $t['sent_hour'], $t['limit_hour'] ? $t['limit_hour'] : '-', $t['paused'] ? '  paused until ' . date( 'Y-m-d H:i', $t['paused_until'] ) : '' ) );
        $cli->output( 'Retries: ' . $s['retries_waiting'] . ' waiting (at most ' . $s['max_retries'] . ' per mail), ' . $s['retries_made'] . ' mails were retried' );
        $cli->output( 'Bounces in 30 days: ' . $s['bounces_30'] . '; hard bounces and complaints ' . ( $s['suppress_hard_bounces'] ? 'go on the suppression list' : 'only mark the user' ) );
        if ( $s['suppressed']['available'] )
            $cli->output( 'Suppressed: ' . $s['suppressed']['all'] . ' (bounce ' . $s['suppressed']['bounce'] . ', complaint ' . $s['suppressed']['complaint'] . ')' );
        $cli->output( 'Test groups: ' . $s['test_groups'] );
        $cli->output( 'Mail-in: ' . ( $s['mailin']['enabled'] ? 'on' : 'off' ) . ', ' . $s['mailin']['addresses'] . ' addresses, ' . $s['mailin']['pending'] . ' waiting for a confirmation' );
        foreach ( $s['problems'] as $p )
            $cli->output( '  ' . $p['level'] . ': ' . $p['text'] );
    }

    protected function read( $cli, $path, $mailboxId, $dry )
    {
        $files = array();
        if ( is_dir( $path ) )
        {
            $files = glob( rtrim( $path, '/' ) . '/*.eml' );
            sort( $files );
        }
        else if ( is_file( $path ) )
            $files = array( $path );
        else
            throw new \InvalidArgumentException( 'read needs --file with an .eml file or a directory' );
        foreach ( $files as $file )
        {
            $raw = (string)file_get_contents( $file );
            $c = \CjwNewsletterBounce::classify( $raw );
            if ( $c['kind'] !== \CjwNewsletterBounce::NONE )
            {
                $headers = \CjwNewsletterBounce::cjwHeaders( $raw );
                $item = isset( $headers['x-cjwnl-senditem'] ) ? \CjwNewsletterEditionSendItem::fetchByHash( $headers['x-cjwnl-senditem'], true ) : null;
                $user = ( !is_object( $item ) && isset( $headers['x-cjwnl-user'] ) ) ? \CjwNewsletterUser::fetchByHash( $headers['x-cjwnl-user'], true ) : null;
                if ( !is_object( $item ) && !is_object( $user ) )
                {
                    $cli->output( basename( $file ) . ': ' . $c['kind'] . ' (' . $c['detail'] . '), not a newsletter mail' );
                    continue;
                }
                $r = \CjwNewsletterBounce::handle( $c, is_object( $item ) ? $item : null, is_object( $user ) ? $user : null, $dry );
                $cli->output( basename( $file ) . ': ' . $c['kind'] . ' (' . $c['detail'] . '), ' . ( $dry ? 'would be ' : '' ) . $r['action'] );
                continue;
            }
            $r = \CjwNewsletterMailin::handleRawMessage( $raw, $mailboxId, $dry );
            $cli->output( basename( $file ) . ': ' . ( is_array( $r ) ? 'mail-in ' . ( $r['action'] !== '' ? $r['action'] : '?' ) . ', ' . ( $dry ? 'would be ' : '' ) . $r['status'] . ' (' . $r['note'] . ')'
                                                                     : 'no bounce, not for a mail-in address' . ( \CjwNewsletterMailin::enabled() ? '' : ' (mail-in is off)' ) ) );
        }
    }
}

}
