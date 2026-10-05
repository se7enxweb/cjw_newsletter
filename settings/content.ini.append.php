<?php /* #?ini charset="utf-8"? */

/**
 * File containing the content ini
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage ini
 * @filesource
 */
/*
[DataTypeSettings]
ExtensionDirectories[]=cjw_newsletter

AvailableDataTypes[]=cjwnewsletterlist
AvailableDataTypes[]=cjwnewsletteredition
AvailableDataTypes[]=cjwnewslettersubscription
AvailableDataTypes[]=cjwnewsletterlistvirtual

# The "newsletter condition" (4.2.0): a block of an edition that only some subscribers get, decided per subscriber
# when the mail is made, the same in the HTML and the text part. On the web site its content is shown as it is.
# All settings that are filled must match:
#   field     a subscriber field: salutation, first_name, last_name, organisation, email, language, custom_1 .. custom_4
#   operator  eq (the default with a value), ne, contains, starts, in (value: a list with commas), empty,
#             not_empty (the default without a value)
#   value     compared without regard to case
#   list      ids of newsletter list objects, with commas
#   language  locales with commas, e.g. ger-DE,eng-GB: the language the subscriber gets
#   interest  identifiers of interests (or eztags ids), with commas
#   negate    1: the opposite
# The same settings are the ezconfig values of the eztemplate "newsletter_condition" in ezrichtext.
[CustomTagSettings]
AvailableCustomTags[]=newsletter_condition

[newsletter_condition]
CustomAttributes[]=field
CustomAttributes[]=operator
CustomAttributes[]=value
CustomAttributes[]=list
CustomAttributes[]=language
CustomAttributes[]=interest
CustomAttributes[]=negate

*/?>
