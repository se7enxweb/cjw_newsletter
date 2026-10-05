<?php
/**
 * The code of extension/cjw_newsletter/cronjobs/cjw_newsletter_mailbox.php, as a class (#207). The file extension/cjw_newsletter/cronjobs/cjw_newsletter_mailbox.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 * @description Collect the mails of the active mail accounts and parse them (bounces)
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Cronjob\Extension\CjwNewsletter
{

class CjwNewsletterMailbox extends \Exponential\Runnable\CronjobPart
{
    public function run( array $scope )
    {
        @ini_set( 'memory_limit', '512M' );
        $cli = isset( $scope['cli'] ) ? $scope['cli'] : \eZCLI::instance();
        $cli->output( "START: cjw_newsletter_mailbox" );
        $totals = \CjwNewsletterRunner::mailbox( $cli, 'cron' );
        if ( $totals['locked'] )
        {
            $cli->output( 'Another run of this part is active; this run does nothing.' );
            return false;
        }
        $cli->output( 'Done: ' . $totals['mailboxes'] . ' mail accounts, ' . $totals['collected'] . ' mails collected, ' . $totals['parsed'] . ' parsed.' );
        $cli->output( "END: cjw_newsletter_mailbox" );
        return $totals['ok'];
    }
}

}
