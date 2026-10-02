<?php
/**
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
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

// to fetch instance in Cli mode for separate logdata, cause access rights phpcli + webserver

// The code is in extension/cjw_newsletter/classes/runnable/cronjobs/cjw_newsletter_mailqueue_process.php (#207); this file is the entry point.
return \Exponential\Cronjob\Extension\CjwNewsletter\CjwNewsletterMailqueueProcess::main( __FILE__, get_defined_vars() );
