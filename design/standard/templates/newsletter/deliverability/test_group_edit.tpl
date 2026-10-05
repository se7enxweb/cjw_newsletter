{*  newsletter/deliverability/test_group_edit.tpl (cjw_newsletter 4.2.0, area deliverability): create, edit or remove a test group *}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-test_group_edit">
<form method="post" action={concat( 'newsletter/test_group_edit/', $group.id )|ezurl}>
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{if $group.id}{'Test group "%name"'|i18n( 'cjw_newsletter/deliverability',, hash( '%name', $group.name ) )|wash}{else}{'New test group'|i18n( 'cjw_newsletter/deliverability' )}{/if}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {if $confirm_remove}
        <div class="nl-confirm">
            <h2>{'Remove this test group?'|i18n( 'cjw_newsletter/deliverability' )}</h2>
            <p>{$group.name|wash}</p>
            <input class="button nl-danger" type="submit" name="ConfirmRemoveButton" value="{'Yes, remove'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            <input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        </div>
        {else}
        {if $errors|count}
        <div class="message-error"><h2>{'Please correct the form'|i18n( 'cjw_newsletter/deliverability' )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}
        <div class="block{if is_set( $errors.name )} nl-field-error{/if}">
            <label for="nl-tg-name">{'Name'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <input id="nl-tg-name" class="halfbox" type="text" name="name" value="{$group.name|wash}" maxlength="255" />
        </div>
        <div class="block{if is_set( $errors.list_contentobject_id )} nl-field-error{/if}">
            <label for="nl-tg-list">{'List'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <select id="nl-tg-list" name="list_contentobject_id">
                <option value="0"{if $group.list_contentobject_id|eq( 0 )} selected="selected"{/if}>{'Every list'|i18n( 'cjw_newsletter/deliverability' )}</option>
                {foreach $lists as $list}<option value="{$list.id}"{if $group.list_contentobject_id|eq( $list.id )} selected="selected"{/if}>{$list.name|wash}</option>{/foreach}
            </select>
        </div>
        <div class="block{if is_set( $errors.email_list )} nl-field-error{/if}">
            <label for="nl-tg-emails">{'Addresses'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <textarea id="nl-tg-emails" class="box" name="email_list" rows="8" cols="60">{$group.email_list|wash}</textarea>
            <span class="nl-hint">{'One address per line (or separated by ";" or ","), at most %max.'|i18n( 'cjw_newsletter/deliverability',, hash( '%max', $max_size ) )|wash}</span>
        </div>
        {/if}
    </div>
    {if $confirm_remove|not}
    <div class="controlbar">
        <input class="defaultbutton" type="submit" name="StoreButton" value="{'Store'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        <input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        {if $group.id}<input class="button" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'cjw_newsletter/deliverability' )|wash}" />{/if}
    </div>
    {/if}
</div>
</form>
</div>
