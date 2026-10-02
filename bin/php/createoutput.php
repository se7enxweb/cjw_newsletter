#!/usr/bin/env php
<?php

/**
 * File createoutput.php
 *
 * -script to create an newsletter edtion output for a siteaccess<br>
 * -to use the correct locale and SiteUrl<br>
 * -php extension/cjw_newsletter/bin/php/createoutput.php --object_id=102 --object_version=5 --output_format_id=0 --current_hostname=admin.jac-example.de.jac400.fw.lokal --www_dir=tmp/ --skin_name=default -s jac-example_user<br>
 * - --current_hostname :  only important for preview ( in testsystem )
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require 'autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_createoutput.php (#207); this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\Createoutput::main( __FILE__ );
