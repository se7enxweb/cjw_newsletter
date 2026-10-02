<?php
/**
 * File mailbox_item_view.php
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

// newsletter/mailbox_item_view/ $mailboxItemId => show details of message
// newsletter/mailbox_item_view/ $mailboxItemId ?GetRawMailContent => show raw message as text
// newsletter/mailbox_item_view/ $mailboxItemId ?DownloadRawMailContent => download raw message

// The code is in extension/cjw_newsletter/classes/runnable/views/newsletter/mailbox_item_view.php (#207); this file is the entry point.
return \Exponential\View\Extension\CjwNewsletter\Newsletter\MailboxItemView::main( __FILE__, get_defined_vars() );
