#!/usr/bin/env php
<?php
/**
 * Take the lists, subscribers and subscriptions of an eznewsletter installation over into cjw_newsletter
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/import-eznewsletter.php [options]   (or ./console ext:cjw_newsletter:import-eznewsletter)
 *   --help shows the options.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_import_eznewsletter.php; this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\ImportEznewsletter::main( __FILE__ );
