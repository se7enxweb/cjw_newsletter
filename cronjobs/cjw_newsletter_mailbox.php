<?php

/**
 * Cronjob cjw_newsletter_mailbox.php
 *
 * Collect the mails of the active mail accounts and parse them: bounces set the status of the newsletter user.
 * One run at a time; the time and the result of the last run are shown on the start page of the newsletter module.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

// The code is in extension/cjw_newsletter/classes/runnable/cronjobs/cjw_newsletter_mailbox.php (#207); this file is the entry point.
return \Exponential\Cronjob\Extension\CjwNewsletter\CjwNewsletterMailbox::main( __FILE__, get_defined_vars() );
