#!/usr/bin/env php
<?php
/**
 * Show the state of the newsletter: lists, subscribers, sends, the outbox, the last runs and the problems
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/status.php [options]   (or ./console ext:cjw_newsletter:status)
 *   --help shows the options.
 *
 * @alias nl-status
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_status.php (#207); this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Status::main( __FILE__ );
