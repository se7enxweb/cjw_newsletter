{* The settings links of the left menu, admin4: only for a user with the policy newsletter/admin.
   Same data and actions as design/standard/templates/parts/newsletter/newsletter_menu_admin.tpl *}
{def $has_admin_access = fetch( 'user', 'has_access_to',
                                hash( 'module', 'newsletter',
                                      'function', 'admin' ) )}
{if $has_admin_access}
    <h4 id="leftmenu-title-newsletter-settings"><span class="a4-icon a4-bi-gear cjwnl-a4-lead" aria-hidden="true"></span>{'Settings'|i18n( 'cjw_newsletter/menu' )}</h4>
    {include uri='design:parts/ini_menu_items.tpl' labelledby='leftmenu-title-newsletter-settings' current_uri_string=$uri_string items=array(
        hash( 'key', 'mailbox_list', 'state', 'link', 'url', 'newsletter/mailbox_list',  'name', concat( '<span class="a4-icon a4-bi-inbox cjwnl-a4-lead" aria-hidden="true"></span>', 'Mail accounts'|i18n( 'cjw_newsletter/menu' )|wash ) ),
        hash( 'key', 'import_list',  'state', 'link', 'url', 'newsletter/import_list',   'name', concat( '<span class="a4-icon a4-bi-upload cjwnl-a4-lead" aria-hidden="true"></span>', 'Imports'|i18n( 'cjw_newsletter/menu' )|wash ) ),
        hash( 'key', 'subscribe',    'state', 'link', 'url', 'newsletter/subscribe',     'name', concat( '<span class="a4-icon a4-bi-input-cursor-text cjwnl-a4-lead" aria-hidden="true"></span>', 'Subscription form'|i18n( 'cjw_newsletter/menu' )|wash ) ),
        hash( 'key', 'settings',     'state', 'link', 'url', 'newsletter/settings',      'name', concat( '<span class="a4-icon a4-bi-sliders cjwnl-a4-lead" aria-hidden="true"></span>', 'INI Settings'|i18n( 'cjw_newsletter/menu' )|wash ) )
    )}
{/if}
{undef $has_admin_access}
