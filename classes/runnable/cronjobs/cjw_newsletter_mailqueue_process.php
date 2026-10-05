<?php
/**
 * The code of extension/cjw_newsletter/cronjobs/cjw_newsletter_mailqueue_process.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/cronjobs/cjw_newsletter_mailqueue_process.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 * @description Send the newsletter mails that wait in the mail queue
 */
/*
 * The original header of extension/cjw_newsletter/cronjobs/cjw_newsletter_mailqueue_process.php:
 *
 *
 * Cronjob cjw_newsletter_create_mailqueue.php
 *
 * Send out all newsletter mails - which are in the mailqueue
 *
 * - search all cjwnl_edition_send_items, which are not send
 * - process every cjwnl_edition_send_item separatly
 * - generate the newsletter with personal user data (unsubsribe link, configure link, personalisation ... )
 * - send newsletter via mail + set status to SEND
 * - mutex support (no double execute of cronjobs)->integrate in runcronjobs.php
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage cronjobs
 * @filesource
 *
 */

namespace Exponential\Cronjob\Extension\CjwNewsletter
{

class CjwNewsletterMailqueueProcess extends \Exponential\Runnable\CronjobPart
{
    public function run( array $scope )
    {
        @ini_set( 'memory_limit', '512M' );
        $cli = isset( $scope['cli'] ) ? $scope['cli'] : \eZCLI::instance();
        $cli->output( "START: cjw_newsletter_mailqueue_process" );
        $totals = \CjwNewsletterRunner::queueProcess( $cli, 'cron' );
        if ( $totals['locked'] )
        {
            $cli->output( 'Another run of this part is active; this run does nothing.' );
            return false;
        }
        $cli->output( 'Done: ' . $totals['sent'] . ' mails sent, ' . $totals['failed'] . ' failed, ' . $totals['finished'] . ' sends finished.' );
        $cli->output( "END: cjw_newsletter_mailqueue_process" );
        return $totals['ok'];
    }
}

}
