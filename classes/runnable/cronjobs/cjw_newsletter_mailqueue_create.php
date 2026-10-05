<?php
/**
 * The code of extension/cjw_newsletter/cronjobs/cjw_newsletter_mailqueue_create.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/cronjobs/cjw_newsletter_mailqueue_create.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 * @description Check pending newsletter users and create the mail queue of the editions to send
 */
/*
 * The original header of extension/cjw_newsletter/cronjobs/cjw_newsletter_mailqueue_create.php:
 *
 *
 * Cronjob cjw_newsletter_mailqueue_create.php
 *
 * Create all cjwnl_edition_send_items (all subscribers of the list linked to the edtion)
 * for a newsletter edition which is waiting to send out
 *
 * - search all cjwnl_users with status STATUS_PENDING_EZ_USER_REGISTER ( if subscribing in ez user register process )
 *   and if it is enabled => confirm the relationg nl_user + subscriptions
 *
 * -search all cjwnl_edition_send object with status == STATUS_WAIT_FOR_PROCESS
 * -seach all cjwnl_user which are in the list with status == STATUS_APPROVED
 * -create for every user a cjwnl_edition_send_item with status == STATUS_NOT_SEND
 * -mutex support (no double execute of cronjobs)->integrate in runcronjobs.php
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

class CjwNewsletterMailqueueCreate extends \Exponential\Runnable\CronjobPart
{
    public function run( array $scope )
    {
        @ini_set( 'memory_limit', '512M' );
        $cli = isset( $scope['cli'] ) ? $scope['cli'] : \eZCLI::instance();
        $cli->output( "START: cjw_newsletter_mailqueue_create" );
        $totals = \CjwNewsletterRunner::queueCreate( $cli, 'cron' );
        if ( $totals['locked'] )
        {
            $cli->output( 'Another run of this part is active; this run does nothing.' );
            return false;
        }
        $cli->output( 'Done: ' . $totals['users_confirmed'] . ' users confirmed, ' . $totals['scheduled'] . ' scheduled sends woken, ' . $totals['sends'] . ' sends queued, ' . $totals['items'] . ' items created.' );
        $cli->output( "END: cjw_newsletter_mailqueue_create" );
        return $totals['ok'];
    }
}

}
