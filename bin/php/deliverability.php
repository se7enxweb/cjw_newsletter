#!/usr/bin/env php
<?php
/**
 * Deliverability of the newsletter: the state, the suppression import, pauses of a transport, and reading saved
 * messages (bounces, subscribe and unsubscribe mails)
 *
 * Usage:
 *   php extension/cjw_newsletter/bin/php/deliverability.php <action> [options]   (or ./console ext:cjw_newsletter:deliverability)
 *   --help shows the actions and the options.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_deliverability.php; this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Deliverability::main( __FILE__ );
