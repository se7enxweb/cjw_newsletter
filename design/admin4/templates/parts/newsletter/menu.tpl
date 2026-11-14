{*
  The left menu of the newsletter module in admin4 (and the designs built on it, such as admin4l): the newsletter tree
  and the administration links, then admin4's own width switch (parts/left_width_control.tpl), the same markup, script
  and preference as every kernel module's menu. The links are the list of every ini menu (parts/ini_menu_items.tpl),
  each group under an h4 of the left column. admin4 draws the column's drag handle in its page layout.
*}
{* Newsletter related stuff *}
<div id="newsletter-menu">
{if $module_result.ui_context|ne('edit')}

    {include uri='design:parts/newsletter/newsletter_menu.tpl'}

    <h4 id="leftmenu-title-newsletter-administer"><span class="a4-icon a4-bi-tools cjwnl-a4-lead" aria-hidden="true"></span>{'Administer'|i18n( 'cjw_newsletter/menu' )}</h4>
    {include uri='design:parts/ini_menu_items.tpl' labelledby='leftmenu-title-newsletter-administer' current_uri_string=$uri_string items=array(
        hash( 'key', 'index',     'state', 'link', 'url', 'newsletter/index',              'name', concat( '<span class="a4-icon a4-bi-speedometer2 cjwnl-a4-lead" aria-hidden="true"></span>', 'Dashboard'|i18n( 'cjw_newsletter/menu' )|wash ) ),
        hash( 'key', 'user_list', 'state', 'link', 'url', 'newsletter/user_list',          'name', concat( '<span class="a4-icon a4-bi-people cjwnl-a4-lead" aria-hidden="true"></span>', 'Users'|i18n( 'cjw_newsletter/menu' )|wash ) ),
        hash( 'key', 'blacklist', 'state', 'link', 'url', 'newsletter/blacklist_item_list', 'name', concat( '<span class="a4-icon a4-bi-lock cjwnl-a4-lead" aria-hidden="true"></span>', 'Blacklists'|i18n( 'cjw_newsletter/menu' )|wash ) ),
        hash( 'key', 'bounces',   'state', 'link', 'url', 'newsletter/mailbox_item_list',  'name', concat( '<span class="a4-icon a4-bi-exclamation-triangle cjwnl-a4-lead" aria-hidden="true"></span>', 'Bounces'|i18n( 'cjw_newsletter/menu' )|wash ) )
    )}

    {include uri='design:parts/newsletter/newsletter_menu_admin.tpl'}

{/if}
</div>

{include uri='design:parts/left_width_control.tpl'}
