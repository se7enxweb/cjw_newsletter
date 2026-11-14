{* Create, edit or remove a test group, admin4: same data and actions as design/standard/templates/newsletter/deliverability/test_group_edit.tpl *}
<div class="a4-page newsletter newsletter-test_group_edit">
<form method="post" action={concat( 'newsletter/test_group_edit/', $group.id )|ezurl}>
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{if $group.id}{'Test group "%name"'|i18n( 'cjw_newsletter/deliverability',, hash( '%name', $group.name ) )|wash}{else}{'New test group'|i18n( 'cjw_newsletter/deliverability' )}{/if}</h1>
        </div>
    </div>

    {if $confirm_remove}
    <div class="a4-notice is-warn" role="alert">
        <p class="a4-notice-title">{'Remove this test group?'|i18n( 'cjw_newsletter/deliverability' )}</p>
        <p>{$group.name|wash}</p>
        <div class="a4-actions">
            <input class="a4-btn a4-btn-danger nl-danger" type="submit" name="ConfirmRemoveButton" value="{'Yes, remove'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            <input class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        </div>
    </div>
    {else}
    {if $errors|count}
    <div class="a4-notice is-bad" role="alert">
        <p class="a4-notice-title">{'Please correct the form'|i18n( 'cjw_newsletter/deliverability' )}</p>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}
    <div class="a4-card">
        <div class="a4-card-body a4-form">
            <div class="a4-field{if is_set( $errors.name )} nl-field-error{/if}">
                <label for="nl-tg-name">{'Name'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <input id="nl-tg-name" type="text" name="name" value="{$group.name|wash}" maxlength="255"{if is_set( $errors.name )} aria-invalid="true"{/if} />
            </div>
            <div class="a4-field{if is_set( $errors.list_contentobject_id )} nl-field-error{/if}">
                <label for="nl-tg-list">{'List'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <select id="nl-tg-list" name="list_contentobject_id">
                    <option value="0"{if $group.list_contentobject_id|eq( 0 )} selected="selected"{/if}>{'Every list'|i18n( 'cjw_newsletter/deliverability' )}</option>
                    {foreach $lists as $list}<option value="{$list.id}"{if $group.list_contentobject_id|eq( $list.id )} selected="selected"{/if}>{$list.name|wash}</option>{/foreach}
                </select>
            </div>
            <div class="a4-field a4-field-wide{if is_set( $errors.email_list )} nl-field-error{/if}">
                <label for="nl-tg-emails">{'Addresses'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <textarea id="nl-tg-emails" name="email_list" rows="8" cols="60" aria-describedby="nl-tg-emails-help"{if is_set( $errors.email_list )} aria-invalid="true"{/if}>{$group.email_list|wash}</textarea>
                <span class="a4-help" id="nl-tg-emails-help">{'One address per line (or separated by ";" or ","), at most %max.'|i18n( 'cjw_newsletter/deliverability',, hash( '%max', $max_size ) )|wash}</span>
            </div>
        </div>
        <div class="a4-card-foot">
            <input class="a4-btn a4-btn-primary" type="submit" name="StoreButton" value="{'Store'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            <input class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            {if $group.id}<input class="a4-btn" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'cjw_newsletter/deliverability' )|wash}" />{/if}
        </div>
    </div>
    {/if}
</form>
</div>
