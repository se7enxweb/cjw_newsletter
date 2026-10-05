<?php /* #?ini charset="utf-8"? */
/**
 * File containing the override ini
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage ini
 * @filesource
 */

/*

[node/view/full#cjw_newsletter_edition]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_newsletter_edition.tpl
Subdir=templates
Match[class_identifier]=cjw_newsletter_edition

[node/view/full#cjw_newsletter_list]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_newsletter_list.tpl
Subdir=templates
Match[class_identifier]=cjw_newsletter_list

[node/view/full#cjw_newsletter_list_virtual]
Source=node/view/full.tpl
MatchFile=node/view/full/cjw_newsletter_list_virtual.tpl
Subdir=templates
Match[class_identifier]=cjw_newsletter_list_virtual

[node/view/line#cjw_newsletter_edition]
Source=node/view/line.tpl
MatchFile=node/view/line/cjw_newsletter_edition.tpl
Subdir=templates
Match[class_identifier]=cjw_newsletter_edition

# N4 Statistics: the box "Newsletter statistics" on the admin preview of an article (in which editions it went out
# and how its links were clicked), above the attributes ([StatisticsSettings] ArticleStatsBox)
[admin_preview_cjw_newsletter_article]
Source=node/view/admin_preview.tpl
MatchFile=node/view/admin_preview/cjw_newsletter_article.tpl
Subdir=templates
Match[class_identifier]=cjw_newsletter_article
# end N4

*/ ?>
