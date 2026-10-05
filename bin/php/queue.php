#!/usr/bin/env php
<?php
/**
 * Create the mail queue of the sends that wait and send the mails of the queue (the two cronjob parts, by hand)
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/queue.php [options]   (or ./console ext:cjw_newsletter:queue)
 *   --help shows the options.
 *
 * @alias nl-queue
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_queue.php (#207); this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Queue::main( __FILE__ );
