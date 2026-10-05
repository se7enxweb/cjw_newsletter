{* Skin "shop" (cjw_newsletter 4.2.0), HTML part: a shop mailing. A banner with the edition's title and text, the
   articles as cards two in a row (one in a row on narrow screens that ignore the width), each with a button to its
   page. Table layout 600px, every style inline, buttons are table cells (they work without images and in Outlook).
   Variables: contentobject, newsletter_list, newsletter_language, newsletter_skin, newsletter_site_url. *}
{def $map = $contentobject.data_map
     $accent = first_set( $newsletter_skin.accent, '#2e7d32' )
     $i18n = 'cjw_newsletter/rendering'
     $title = cond( $map.title.has_content, $map.title.content, $contentobject.name )
     $articles = fetch( 'content', 'list', hash( 'parent_node_id', $contentobject.contentobject.main_node_id,
                                                 'sort_by', array( 'priority', true() ),
                                                 'class_filter_type', 'include',
                                                 'class_filter_array', array( 'cjw_newsletter_article' ) ) )
     $rows = array()
     $row = array()}
{foreach $articles as $article}{set $row = $row|append( $article )}{if $row|count|eq( 2 )}{set $rows = $rows|append( $row ) $row = array()}{/if}{/foreach}
{if $row|count|gt( 0 )}{set $rows = $rows|append( $row )}{/if}
{set-block variable=$subject scope=root}{cond( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' )|ne( '' ), concat( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' ), ' ' ), '' )}{$title}{/set-block}
{set-block variable=$html_mail}<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="x-apple-disable-message-reformatting" />
<title>{$title|wash}</title>
</head>
<body style="margin:0;padding:0;background-color:#f6f7f5;-webkit-text-size-adjust:100%;">
{if $map.short_title.has_content}<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f6f7f5;">{$map.short_title.content|wash}</div>{/if}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f6f7f5" style="background-color:#f6f7f5;">
<tr><td align="center" style="padding:20px 8px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background-color:#ffffff;border-collapse:collapse;">
<tr>
<td style="padding:16px 24px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td width="44" valign="middle" style="width:44px;"><img src={'images/newsletter/skin/shop/logo.png'|ezdesign} width="36" height="36" alt="" style="display:block;border:0;" /></td>
<td valign="middle" style="font-family:Arial,Helvetica,sans-serif;font-size:20px;line-height:24px;font-weight:bold;color:#1b3d1d;">{'[[list_name]]'}</td>
</tr></table>
</td>
</tr>
<tr>
<td bgcolor="#e9f3ea" style="background-color:#e9f3ea;padding:28px 24px;">
<div style="font-family:Arial,Helvetica,sans-serif;font-size:28px;line-height:34px;font-weight:bold;color:#1b3d1d;margin:0 0 10px 0;">{$title|wash}</div>
{if $map.description.has_content}<div class="cjwnl-rich" style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:#2f4a31;">{attribute_view_gui attribute=$map.description}</div>{/if}
</td>
</tr>
{foreach $rows as $cards}
<tr>
<td style="padding:16px 12px 0 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
{foreach $cards as $article}
<td width="50%" valign="top" style="width:50%;padding:0 12px 16px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #dfe6df;border-collapse:separate;">
<tr><td height="6" bgcolor="{$accent|wash}" style="height:6px;background-color:{$accent|wash};font-size:1px;line-height:1px;">&nbsp;</td></tr>
<tr><td style="padding:14px 14px 6px 14px;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:22px;font-weight:bold;color:#1b3d1d;">{$article.data_map.title.content|wash}</td></tr>
{if $article.data_map.short_description.has_content}<tr><td class="cjwnl-rich" style="padding:0 14px 6px 14px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;color:#444444;">{attribute_view_gui attribute=$article.data_map.short_description}</td></tr>{/if}
<tr><td style="padding:4px 14px 16px 14px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="{$accent|wash}" style="background-color:{$accent|wash};border-radius:4px;">
<a style="display:inline-block;padding:9px 18px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:18px;font-weight:bold;color:#ffffff;text-decoration:none;" href={$article.url_alias|ezurl}>{'View'|i18n( $i18n )}</a>
</td></tr></table>
</td></tr>
</table>
</td>
{/foreach}
{if $cards|count|eq( 1 )}<td width="50%" style="width:50%;">&nbsp;</td>{/if}
</tr></table>
</td>
</tr>
{/foreach}
<tr>
<td style="padding:8px 24px 8px 24px;">{cjwnl_interests_block( 4, 'Picked for you'|i18n( $i18n ), $accent )}</td>
</tr>
<tr>
<td bgcolor="#e9f3ea" style="background-color:#e9f3ea;padding:18px 24px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#2f4a31;">
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
    array( '<p>', '<p class="">', '<h2>', '<h3>', '<h4>', '<ul>', '<ol>', '<li>', '<a href=', '<table class="renderedtable"' ),
    array( '<p style="margin:0 0 10px 0;">',
           '<p style="margin:0 0 10px 0;">',
           '<h2 style="margin:14px 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:19px;line-height:25px;color:#1b3d1d;">',
           '<h3 style="margin:12px 0 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:22px;color:#1b3d1d;">',
           '<h4 style="margin:10px 0 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;color:#1b3d1d;">',
           '<ul style="margin:0 0 10px 0;padding:0 0 0 20px;">',
           '<ol style="margin:0 0 10px 0;padding:0 0 0 20px;">',
           '<li style="margin:0 0 4px 0;">',
           concat( '<a style="color:', $accent, ';text-decoration:underline;" href=' ),
           '<table class="renderedtable" cellpadding="5" style="border-collapse:collapse;"' ) )}
{undef $map $accent $i18n $title $articles $rows $row}
