#!/usr/bin/env php
<?php
/**
 * File class-languages.php: ext:cjw_newsletter:class-languages, the 4.2.1 upgrade step that moves the newsletter
 * content classes from eng-GB to eng-US.
 *
 *   ./console ext:cjw_newsletter:class-languages --dry-run
 *   php extension/cjw_newsletter/bin/php/class-languages.php
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_class_languages.php; this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\ClassLanguages::main( __FILE__ );
