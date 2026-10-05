<?php /* #?ini charset="utf-8"? */

/**
 * File containing the cronjob ini
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage ini
 * @filesource
 */

/*
# php runcronjobs.php cjw_newsletter
# php runcronjobs.php -s siteaccess cjw_newsletter_mailqueue_create
# php runcronjobs.php -s siteaccess cjw_newsletter_mailqueue_process
# php runcronjobs.php -s siteaccess cjw_newsletter_mailbox
#
# the same work by hand: ./console ext:cjw_newsletter:queue, ext:cjw_newsletter:mailbox

[CronjobSettings]
ExtensionDirectories[]=cjw_newsletter
ScriptDirectories[]=cronjobs

#Scripts[]=cjw_newsletter_mailqueue_create.php
#Scripts[]=cjw_newsletter_mailqueue_process.php

# CronjobPart for Testing
[CronjobPart-cjw_newsletter]
Scripts[]=cjw_newsletter_mailqueue_create.php
Scripts[]=cjw_newsletter_mailqueue_process.php


[CronjobPart-cjw_newsletter_mailqueue_create]
Scripts[]=cjw_newsletter_mailqueue_create.php

[CronjobPart-cjw_newsletter_mailqueue_process]
Scripts[]=cjw_newsletter_mailqueue_process.php

# collect the mails of the active mail accounts and parse the bounces (not part of the cjw_newsletter part above)
[CronjobPart-cjw_newsletter_mailbox]
Scripts[]=cjw_newsletter_mailbox.php

*/ ?>
