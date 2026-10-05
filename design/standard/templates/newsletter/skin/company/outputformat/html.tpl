{* Skin "company" (cjw_newsletter 4.2.0), HTML part: a calm corporate letter. Table layout 600px, every style inline,
   no web fonts, no background images, readable with images blocked. Variables: contentobject (the edition version),
   newsletter_list, newsletter_language, newsletter_skin (skin settings: accent ...), newsletter_site_url. *}
{def $map = $contentobject.data_map
     $accent = first_set( $newsletter_skin.accent, '#1f5f8b' )
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
<body style="margin:0;padding:0;background-color:#eef1f4;-webkit-text-size-adjust:100%;">
{if $map.short_title.has_content}<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#eef1f4;">{$map.short_title.content|wash}</div>{/if}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#eef1f4" style="background-color:#eef1f4;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background-color:#ffffff;border-collapse:collapse;">
<tr>
<td bgcolor="{$accent|wash}" style="background-color:{$accent|wash};padding:20px 32px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td width="56" valign="middle" style="width:56px;"><img src={'images/newsletter/skin/company/logo.png'|ezdesign} width="48" height="48" alt="" style="display:block;border:0;" /></td>
<td valign="middle" style="font-family:Georgia,'Times New Roman',serif;font-size:20px;line-height:26px;color:#ffffff;">{'[[list_name]]'}</td>
<td valign="middle" align="right" style="font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px;color:#dbe7f0;">{currentdate()|l10n( 'shortdate' )}</td>
</tr></table>
</td>
</tr>
<tr>
<td style="padding:32px 32px 8px 32px;font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:34px;color:#12384f;">{$title|wash}</td>
</tr>
{if $map.description.has_content}
<tr>
<td class="cjwnl-rich" style="padding:8px 32px 8px 32px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:#333333;">{attribute_view_gui attribute=$map.description}</td>
</tr>
{/if}
{foreach $articles as $article}
<tr>
<td style="padding:16px 32px 0 32px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;"><tr>
<td width="4" bgcolor="{$accent|wash}" style="width:4px;background-color:{$accent|wash};font-size:1px;line-height:1px;">&nbsp;</td>
<td style="padding:4px 0 8px 16px;">
<div style="font-family:Arial,Helvetica,sans-serif;font-size:19px;line-height:25px;font-weight:bold;color:#12384f;margin:0 0 8px 0;">{$article.data_map.title.content|wash}</div>
{if $article.data_map.short_description.has_content}<div class="cjwnl-rich" style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:#333333;">{attribute_view_gui attribute=$article.data_map.short_description}</div>{/if}
</td>
</tr></table>
</td>
</tr>
{/foreach}
<tr>
<td style="padding:16px 32px 8px 32px;">{cjwnl_interests_block( 4, 'Articles for your interests'|i18n( $i18n ), $accent )}</td>
</tr>
<tr>
<td bgcolor="#e8f0f6" style="background-color:#e8f0f6;padding:20px 32px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#4a5a66;">
{'You receive this newsletter because you subscribed to %list.'|i18n( $i18n,, hash( '%list', '[[list_name]]' ) )}<br />
<a style="color:{$accent|wash};text-decoration:underline;" href="[[manage_url]]">{'Change your newsletter settings'|i18n( $i18n )}</a>
&nbsp;|&nbsp;
<a style="color:{$accent|wash};text-decoration:underline;" href="[[unsubscribe_url]]">{'Unsubscribe'|i18n( $i18n )}</a>
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
{/set-block}{$html_mail|cjw_newsletter_str_replace(
    array( '<p>', '<p class="">', '<h2>', '<h3>', '<h4>', '<ul>', '<ol>', '<li>', '<a href=', '<table class="renderedtable"', '<blockquote>' ),
    array( '<p style="margin:0 0 14px 0;">',
           '<p style="margin:0 0 14px 0;">',
           '<h2 style="margin:18px 0 8px 0;font-family:Georgia,\'Times New Roman\',serif;font-size:21px;line-height:27px;color:#12384f;font-weight:normal;">',
           '<h3 style="margin:16px 0 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:23px;color:#12384f;">',
           '<h4 style="margin:14px 0 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:21px;color:#12384f;">',
           '<ul style="margin:0 0 14px 0;padding:0 0 0 22px;">',
           '<ol style="margin:0 0 14px 0;padding:0 0 0 22px;">',
           '<li style="margin:0 0 6px 0;">',
           concat( '<a style="color:', $accent, ';text-decoration:underline;" href=' ),
           '<table class="renderedtable" cellpadding="6" style="border-collapse:collapse;"',
           '<blockquote style="margin:0 0 14px 0;padding:0 0 0 14px;border-left:3px solid #c9d6e0;color:#4a5a66;">' ) )}
{undef $map $accent $i18n $title $articles}
