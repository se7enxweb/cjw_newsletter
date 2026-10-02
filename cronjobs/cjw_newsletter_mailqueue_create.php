<?php

/**
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
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

// to fetch instance in Cli mode for separate logdata, cause access rights phpcli + webserver

// The code is in extension/cjw_newsletter/classes/runnable/cronjobs/cjw_newsletter_mailqueue_create.php (#207); this file is the entry point.
return \Exponential\Cronjob\Extension\CjwNewsletter\CjwNewsletterMailqueueCreate::main( __FILE__, get_defined_vars() );
