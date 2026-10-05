#!/usr/bin/env php
<?php
/**
 * File translatable-fields.php: ext:cjw_newsletter:translatable-fields, the 4.2.0 upgrade step that makes the text
 * fields of the newsletter classes translatable.
 *
 *   ./console ext:cjw_newsletter:translatable-fields --dry-run
 *   php extension/cjw_newsletter/bin/php/translatable-fields.php
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

require_once dirname( __FILE__ ) . '/../../../../autoload.php';

// The code is in extension/cjw_newsletter/classes/runnable/commands/php_translatable_fields.php; this file is the entry point.
\Exponential\Command\Extension\CjwNewsletter\TranslatableFields::main( __FILE__ );
