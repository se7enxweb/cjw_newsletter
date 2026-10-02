#!/usr/bin/env php
<?php

/**
 * File iniloader.php
 *
 * -script to get a serialized ini object of the siteaccess<br>
 * -iniloader.php -s siteaccess<br>
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require 'autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_iniloader.php (#207); this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Iniloader::main( __FILE__ );
