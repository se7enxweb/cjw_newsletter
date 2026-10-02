<?php
/**
 * File user_create.php
 *
 * create a new newsletter user - if email exists redirect to user_edit of existing newsletter user
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */


/**
 * newsletter/user_create?Email=abc@examplcom
 * PostParameter for email
 */

// linked from
// - newsletter/user_list               ?RedirectUrl=
// - newsletter/subscription_list       ?RedirectUrl=

// The code is in extension/cjw_newsletter/classes/runnable/views/newsletter/user_create.php (#207); this file is the entry point.
return \Exponential\View\Extension\CjwNewsletter\Newsletter\UserCreate::main( __FILE__, get_defined_vars() );
