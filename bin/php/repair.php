#!/usr/bin/env php
<?php
/**
 * Remove the subscriptions of removed newsletter users and the mails that can never be sent
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/repair.php [options]   (or ./console ext:cjw_newsletter:repair)
 *   --help shows the options.
 *
 * @alias nl-repair
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_repair.php (#207); this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Repair::main( __FILE__ );
