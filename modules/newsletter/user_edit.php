<?php
/**
 * File user_edit.php
 *
 * edit a newsletter user
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */


/**
 * newsletter/user_edit/ $nlUserId
 *
 * create a nl user if id = 0
 * PostParameter for email and firstname, lastname, Salutation + subscription so we can subscribe directly
 */

// linked from
// - newsletter/user_view
// - newsletter/user_list               ?RedirectUrl=
// - newsletter/subscription_list       ?RedirectUrl=

// The code is in extension/cjw_newsletter/classes/runnable/views/newsletter/user_edit.php (#207); this file is the entry point.
return \Exponential\View\Extension\CjwNewsletter\Newsletter\UserEdit::main( __FILE__, get_defined_vars() );
