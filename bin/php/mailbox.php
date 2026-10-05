#!/usr/bin/env php
<?php
/**
 * Collect the mails of the active mail accounts and parse them (bounces)
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/mailbox.php [options]   (or ./console ext:cjw_newsletter:mailbox)
 *   --help shows the options.
 *
 * @alias nl-mailbox
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_mailbox.php (#207); this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Mailbox::main( __FILE__ );
