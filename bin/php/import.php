#!/usr/bin/env php
<?php
/**
 * Import a CSV file that was uploaded in the admin into its newsletter list
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/import.php [options]   (or ./console ext:cjw_newsletter:import)
 *   --help shows the options.
 *
 * @alias nl-import
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_import.php (#207); this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Import::main( __FILE__ );
