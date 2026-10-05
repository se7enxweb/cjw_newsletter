<?php /* #?ini charset="utf-8"? */
/**
 * File containing the site ini
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage ini
 * @filesource
 */

/*

# The newsletter translations are loaded by default, so the newsletter
# admin screens follow the interface language. To control which ts files
# are loaded per siteaccess, move this setting to the siteaccess site.ini.
[RegionalSettings]
TranslationExtensions[]=cjw_newsletter


[TemplateSettings]
ExtensionAutoloadPath[]=cjw_newsletter

# N4 Statistics: the open pixel and the click redirect answer everybody (the mail client of a recipient has no
# login and no session); they check their own signature and only redirect to the links stored for the sent edition.
[RoleSettings]
PolicyOmitList[]=newsletter/r
PolicyOmitList[]=newsletter/o
# end N4

# N5 SMS: the endpoint an SMS provider calls (newsletter/sms_inbound) checks the provider's signature or secret
# itself and refuses everything else; the code page (newsletter/sms_confirm) is reached only with the subscriber's
# secret hash, like newsletter/configure. Neither needs a login or a session.
[RoleSettings]
PolicyOmitList[]=newsletter/sms_inbound
PolicyOmitList[]=newsletter/sms_confirm
# end N5

*/ ?>