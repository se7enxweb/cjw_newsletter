#!/usr/bin/env php
<?php
/**
 * Newsletter statistics: the retention cleanup of the per-person rows, the A/B winners, a summary
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/statistics.php [options]   (or ./console ext:cjw_newsletter:statistics)
 *   --help shows the options.
 *
 * @alias nl-statistics
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_statistics.php; this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Statistics::main( __FILE__ );
