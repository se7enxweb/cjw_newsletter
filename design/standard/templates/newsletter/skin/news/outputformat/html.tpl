{* Skin "news" (cjw_newsletter 4.2.0), HTML part: a news site digest. The first article is the top story, the others
   follow as a list with rules. Table layout 600px, every style inline, readable with images blocked.
   Variables: contentobject, newsletter_list, newsletter_language, newsletter_skin, newsletter_site_url. *}
{def $map = $contentobject.data_map
     $accent = first_set( $newsletter_skin.accent, '#b3261e' )
     $i18n = 'cjw_newsletter/rendering'
     $title = cond( $map.title.has_content, $map.title.content, $contentobject.name )
     $articles = fetch( 'content', 'list', hash( 'parent_node_id', $contentobject.contentobject.main_node_id,
                                                 'sort_by', array( 'priority', true() ),
                                                 'class_filter_type', 'include',
                                                 'class_filter_array', array( 'cjw_newsletter_article' ) ) )}
{set-block variable=$subject scope=root}{cond( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' )|ne( '' ), concat( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' ), ' ' ), '' )}{$title}{/set-block}
{set-block variable=$html_mail}<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="x-apple-disable-message-reformatting" />
<title>{$title|wash}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;-webkit-text-size-adjust:100%;">
{if $map.short_title.has_content}<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f4f4f4;">{$map.short_title.content|wash}</div>{/if}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4" style="background-color:#f4f4f4;">
<tr><td align="center" style="padding:16px 8px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background-color:#ffffff;border-collapse:collapse;">
<tr><td height="6" bgcolor="{$accent|wash}" style="height:6px;background-color:{$accent|wash};font-size:1px;line-height:1px;">&nbsp;</td></tr>
<tr>
<td style="padding:18px 24px 14px 24px;border-bottom:3px solid #222222;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td width="44" valign="middle" style="width:44px;"><img src={'images/newsletter/skin/news/logo.png'|ezdesign} width="36" height="36" alt="" style="display:block;border:0;" /></td>
<td valign="middle" style="font-family:Georgia,'Times New Roman',serif;font-size:26px;line-height:30px;font-weight:bold;color:#222222;letter-spacing:-0.5px;">{'[[list_name]]'}</td>
<td valign="middle" align="right" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:16px;color:#666666;text-transform:uppercase;letter-spacing:1px;">{currentdate()|l10n( 'date' )}</td>
</tr></table>
</td>
</tr>
<tr>
<td style="padding:22px 24px 4px 24px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:16px;font-weight:bold;color:{$accent|wash};text-transform:uppercase;letter-spacing:1px;">{$title|wash}</td>
</tr>
{if $map.description.has_content}
<tr>
<td class="cjwnl-rich" style="padding:4px 24px 6px 24px;font-family:Georgia,'Times New Roman',serif;font-size:16px;line-height:24px;color:#333333;">{attribute_view_gui attribute=$map.description}</td>
</tr>
{/if}
{foreach $articles as $index => $article}
{if $index|eq( 0 )}
<tr>
<td style="padding:14px 24px 18px 24px;border-bottom:1px solid #dddddd;">
<div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:14px;font-weight:bold;color:#ffffff;background-color:{$accent|wash};display:inline-block;padding:3px 8px;margin:0 0 10px 0;text-transform:uppercase;letter-spacing:1px;">{'Top story'|i18n( $i18n )}</div>
<div style="font-family:Georgia,'Times New Roman',serif;font-size:26px;line-height:32px;font-weight:bold;color:#111111;margin:0 0 10px 0;">{$article.data_map.title.content|wash}</div>
{if $article.data_map.short_description.has_content}<div class="cjwnl-rich" style="font-family:Georgia,'Times New Roman',serif;font-size:16px;line-height:25px;color:#333333;">{attribute_view_gui attribute=$article.data_map.short_description}</div>{/if}
</td>
</tr>
{else}
<tr>
<td style="padding:14px 24px;border-bottom:1px solid #dddddd;">
<div style="font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:24px;font-weight:bold;color:#111111;margin:0 0 6px 0;">{$article.data_map.title.content|wash}</div>
{if $article.data_map.short_description.has_content}<div class="cjwnl-rich" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:21px;color:#444444;">{attribute_view_gui attribute=$article.data_map.short_description}</div>{/if}
</td>
</tr>
{/if}
{/foreach}
<tr>
<td style="padding:18px 24px 4px 24px;">{cjwnl_interests_block( 5, 'More for your interests'|i18n( $i18n ), $accent )}</td>
</tr>
<tr>
<td bgcolor="#222222" style="background-color:#222222;padding:18px 24px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#bbbbbb;">
{'You receive this newsletter because you subscribed to %list.'|i18n( $i18n,, hash( '%list', '[[list_name]]' ) )}<br />
<a style="color:#ffffff;text-decoration:underline;" href="[[manage_url]]">{'Change your newsletter settings'|i18n( $i18n )}</a>
&nbsp;&middot;&nbsp;
<a style="color:#ffffff;text-decoration:underline;" href="[[unsubscribe_url]]">{'Unsubscribe'|i18n( $i18n )}</a>
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
{/set-block}{$html_mail|cjw_newsletter_str_replace(
    array( '<p>', '<p class="">', '<h2>', '<h3>', '<h4>', '<ul>', '<ol>', '<li>', '<a href=', '<table class="renderedtable"', '<blockquote>' ),
    array( '<p style="margin:0 0 12px 0;">',
           '<p style="margin:0 0 12px 0;">',
           '<h2 style="margin:16px 0 8px 0;font-family:Georgia,\'Times New Roman\',serif;font-size:21px;line-height:27px;color:#111111;">',
           '<h3 style="margin:14px 0 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:22px;color:#111111;">',
           '<h4 style="margin:12px 0 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;color:#111111;">',
           '<ul style="margin:0 0 12px 0;padding:0 0 0 20px;">',
           '<ol style="margin:0 0 12px 0;padding:0 0 0 20px;">',
           '<li style="margin:0 0 5px 0;">',
           concat( '<a style="color:', $accent, ';text-decoration:underline;" href=' ),
           '<table class="renderedtable" cellpadding="6" style="border-collapse:collapse;"',
           concat( '<blockquote style="margin:0 0 12px 0;padding:0 0 0 14px;border-left:3px solid ', $accent, ';font-style:italic;color:#444444;">' ) ) )}
{undef $map $accent $i18n $title $articles}
