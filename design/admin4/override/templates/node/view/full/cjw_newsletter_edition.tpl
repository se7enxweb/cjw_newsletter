{* The full view of a newsletter edition in admin4: the node page head (class icon with its class menu, title, badges,
   actions), a meta line, the send actions of a draft (send, test mail), the status and the tabs. Same data and actions as
   design/admin2/override/templates/node/view/full/cjw_newsletter_edition.tpl. Every id, class and form field the
   scripts use is kept (.content-view-full, .context-block, h1.context-title, #window-controls, .controlbar). *}
{set-block scope=root variable=cache_ttl}0{/set-block}
<div class="content-view-full a4-node newsletter">
 <div class="class-{$node.class_identifier}">

{include uri='design:infocollection_validation.tpl'}

<div class="content-navigation">

<div class="context-block a4-node-card">

{def $js_class_languages = $node.object.content_class.prioritized_languages_js_array
     $disable_another_language = cond( eq( 0, count( $node.object.content_class.can_create_languages ) ), 'edit-class-another-language', '' )
     $disabled_sub_menu = '["class-createnodefeed","class-removenodefeed"]'}

{if is_array( $js_class_languages )}{set $js_class_languages = $js_class_languages|implode( ',' )}{/if}

{def $newsletter_edition_attribute = $node.data_map.newsletter_edition
     $newsletter_edition_attribute_content = $newsletter_edition_attribute.content
     $newsletter_list_attribute_content = $newsletter_edition_attribute.content.list_attribute_content
     $newsletter_edition_status = $newsletter_edition_attribute_content.status}

{def $list_attribute_content = $newsletter_edition_attribute_content.list_attribute_content
     $output_format_array = $list_attribute_content.output_format_array
     $skin_name = $list_attribute_content.skin_name
     $list_main_siteaccess =  $list_attribute_content.main_siteaccess
     $contentobject_id = $newsletter_edition_attribute_content.contentobject_id
     $contentobject_version = $newsletter_edition_attribute_content.contentobject_attribute_version
     }

{def $nl_status_is_draft = $newsletter_edition_attribute_content.is_draft}

{* Check if user has rights and if there are any RSS/ATOM Feed exports for current node *}
{if is_set( ezini( 'RSSSettings', 'DefaultFeedItemClasses', 'site.ini' )[ $node.class_identifier ] )}
    {def $create_rss_access = fetch( 'user', 'has_access_to', hash( 'module', 'rss', 'function', 'edit' ) )}
    {if $create_rss_access}
        {if fetch( 'rss', 'has_export_by_node', hash( 'node_id', $node.node_id ) )}
            {set $disabled_sub_menu = '"class-createnodefeed"'}
        {else}
            {set $disabled_sub_menu = '"class-removenodefeed"'}
        {/if}
    {/if}
{/if}
{ezscript( array( 'cjw_newsletter.js' ) )}

{* ---- Header: class icon (class menu), title with badges, actions ---- *}
<div class="a4-node-head a4-pagehead">
    <a class="a4-node-icon" aria-haspopup="menu" aria-expanded="false" href={concat( '/class/view/', $node.object.contentclass_id )|ezurl} title="{'Class menu'|i18n( 'design/admin/node/view/full' )|wash}" data-menu-open="ClassMenu" data-menu-values="{json_encode( hash( '%classID%', $node.object.contentclass_id, '%objectID%', $node.contentobject_id, '%nodeID%', $node.node_id, '%currentURL%', $node.url ) )|wash}" data-menu-languages="{$js_class_languages|wash}" data-menu-title="{$node.class_name|wash}" data-menu-disable="{$disabled_sub_menu|wash}"{if $disable_another_language} data-menu-disable-item="{$disable_another_language|wash}"{/if}>{$node.class_identifier|class_icon( small, $node.class_name )}</a>
    <div class="a4-node-heading a4-pagehead-main">
        <h1 class="context-title a4-pagehead-title"><bdi>{$node.name|wash}</bdi></h1>
        <span class="a4-node-badges">
            <span class="a4-badge a4-badge-class" dir="auto">{$node.class_name|wash}</span>
            {* Newsletter status *}
            {include uri='design:cjw_newsletter_edition_status.tpl'}
            {if $node.is_invisible}<span class="a4-badge is-warn">{$node.hidden_status_string|wash}</span>{/if}
        </span>
    </div>

    <div class="controlbar a4-node-actions a4-pagehead-actions">
    <form method="post" action={'content/action'|ezurl}>
    <input type="hidden" name="TopLevelNode" value="{$node.object.main_node_id}" />
    <input type="hidden" name="ContentNodeID" value="{$node.node_id}" />
    <input type="hidden" name="ContentObjectID" value="{$node.contentobject_id}" />
    <div class="a4-toolbar">
    {* Edit button. *}
    {if and( $node.can_edit, $nl_status_is_draft )}
        {* only edit nl in current language *}
        <input name="ContentObjectLanguageCode" value="{$node.object.current_language}" type="hidden" />
        <input class="a4-btn a4-btn-primary" type="submit" name="EditButton" value="{'Edit'|i18n( 'design/admin/node/view/full' )}" title="{'Edit the contents of this item.'|i18n( 'design/admin/node/view/full' )}" />
    {else}
        <input class="a4-btn" type="submit" name="EditButton" value="{'Edit'|i18n( 'design/admin/node/view/full' )}" title="{'You do not have permission to edit this item.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
    {/if}

    {* Move button. *}
    {if and( $node.can_move, $nl_status_is_draft )}
        <input class="a4-btn" type="submit" name="MoveNodeButton" value="{'Move'|i18n( 'design/admin/node/view/full' )}" title="{'Move this item to another location.'|i18n( 'design/admin/node/view/full' )}" />
    {else}
        <input class="a4-btn" type="submit" name="MoveNodeButton" value="{'Move'|i18n( 'design/admin/node/view/full' )}" title="{'You do not have permission to move this item to another location.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
    {/if}

    {* Remove button. *}
    {if $node.can_remove}
        <input class="a4-btn a4-btn-danger" type="submit" name="ActionRemove" value="{'Remove'|i18n( 'design/admin/node/view/full' )}" title="{'Remove this item.'|i18n( 'design/admin/node/view/full' )}" />
    {else}
        <input class="a4-btn" type="submit" name="ActionRemove" value="{'Remove'|i18n( 'design/admin/node/view/full' )}" title="{'You do not have permission to remove this item.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
    {/if}

    {* copy newsletter subtree *}
    <a class="a4-btn" href={concat( 'content/copysubtree/', $node.node_id )|ezurl()} title="{'Create a copy of <%child_name>.'|i18n( 'design/admin/node/view/full',, hash( '%child_name', $node.name|wash ) )|wash}">{'Copy'|i18n( 'design/admin/node/view/full' )}</a>

    {* Custom content action buttons. *}
    {section var=ContentActions loop=$node.object.content_action_list}
        <input class="a4-btn" type="submit" name="{$ContentActions.item.action}" value="{$ContentActions.item.name}" />
    {/section}

    {* Link to manage versions *}
    <span class="versions"><a class="a4-icon-btn" href={concat("content/history/", $node.contentobject_id )|ezurl} title="{'View and manage (copy, delete, etc.) the versions of this object.'|i18n( 'design/admin/content/edit' )|wash}" aria-label="{'Manage versions'|i18n( 'design/admin/content/edit' )|wash}"><span class="a4-icon a4-bi-clock-history is-medium" aria-hidden="true"></span></a></span>
    </div>
    </form>
    </div>
</div>

{* ---- Newsletter send button and test mail (separate forms: nested forms are dropped by the browser) ---- *}
{if $nl_status_is_draft}
<div class="a4-toolbar cjwnl-a4-sendbar">
    <form action={'newsletter/send'|ezurl()} method="post" class="cjwnl-inline-form cjwnl-a4-inline-form">
        <input type="hidden" name="TopLevelNode" value="{$node.object.main_node_id}" /><input type="hidden" name="ContentNodeID" value="{$node.node_id}" /><input type="hidden" name="ContentObjectID" value="{$node.object.id}" /><input type="hidden" name="mail_newsletter" value="true" />
        <input class="a4-btn a4-btn-primary" type="submit" name="SendNewsletterButton" value="{"Send Newsletter"|i18n("cjw_newsletter/send")}" />
    </form>

    {* Newsletter testmail *}
    {include uri='design:cjw_newsletter_edition_send_testmail.tpl'}
</div>
{/if}

{* ---- One quiet meta line ---- *}
<p class="context-information a4-node-meta">
    <span title="{$node.object.modified|l10n( 'datetime' )}">{'Last modified'|i18n( 'design/admin/node/view/full' )}: {$node.object.modified|l10n(shortdatetime)}, <a href={$node.object.current.creator.main_node.url_alias|ezurl}>{$node.object.current.creator.name|wash}</a></span>
    <span>{'Node ID'|i18n( 'design/admin/node/view/full' )}: {$node.node_id}</span>
    <span>{'Object ID'|i18n( 'design/admin/node/view/full' )}: {$node.object.id}</span>
    <span>{$node.object.current_language_object.locale_object.intl_language_name|wash}</span>
    {include uri='design:parts/a4_tabs_switch.tpl'}
</p>

{* ---- The tabs ---- *}
<div id="window-controls" class="tab-block a4-node-tabs">
{include uri='design:cjw_newsletter_edition_window_controls.tpl' tabs_switch_elsewhere=true()}
{include uri='design:parts/a4_tabs_height.tpl'}
</div>

{undef $js_class_languages $disable_another_language $disabled_sub_menu $nl_status_is_draft}
</div>

{if $newsletter_edition_status|ne('draft')}
    {* Newsletter send statistic *}
    {include uri='design:cjw_newsletter_edition_send_statistic.tpl'}
{/if}

{* ---- Children ---- *}
<div id="content-view-children" class="a4-node-children">
{if $node.is_container}
    {include uri='design:children.tpl'}
{else}
    {include uri='design:no_children.tpl'}
{/if}
</div>

</div>

 </div>
</div>
