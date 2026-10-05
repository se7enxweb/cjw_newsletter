#!/usr/bin/env php
<?php
/**
 * List or run the recurring newsletter sends (cjwnl_schedule)
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/schedule.php [list|run] [options]   (or ./console ext:cjw_newsletter:schedule)
 *   --help shows the options.
 *
 * @alias nl-schedule
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_schedule.php; this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Schedule::main( __FILE__ );
