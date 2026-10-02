<?php
/**
 * File subscription_list_csvimport.php
 *
 * import csv data to a subscription list
 * if an nl user with email of csv already exists => override existing data with csv data if it is not empty
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

// The code is in extension/cjw_newsletter/classes/runnable/views/newsletter/subscription_list_csvimport.php (#207); this file is the entry point.
return \Exponential\View\Extension\CjwNewsletter\Newsletter\SubscriptionListCsvimport::main( __FILE__, get_defined_vars() );
