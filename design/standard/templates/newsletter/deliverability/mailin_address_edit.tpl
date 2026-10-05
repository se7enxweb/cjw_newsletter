{*  newsletter/deliverability/mailin_address_edit.tpl (cjw_newsletter 4.2.0, area deliverability): create, edit or remove a mail-in address *}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-mailin_address_edit">
<form method="post" action={concat( 'newsletter/mailin_address_edit/', $address.id )|ezurl}>
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{if $address.id}{'Mail-in address %address'|i18n( 'cjw_newsletter/deliverability',, hash( '%address', $display ) )|wash}{else}{'New mail-in address'|i18n( 'cjw_newsletter/deliverability' )}{/if}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {if $confirm_remove}
        <div class="nl-confirm">
            <h2>{'Remove this mail-in address?'|i18n( 'cjw_newsletter/deliverability' )}</h2>
            <p><code>{$display|wash}</code></p>
            <p>{'Mails to it are no longer acted on. The messages it got stay in the list.'|i18n( 'cjw_newsletter/deliverability' )}</p>
            <input class="button nl-danger" type="submit" name="ConfirmRemoveButton" value="{'Yes, remove'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            <input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        </div>
        {else}
        {if $errors|count}
        <div class="message-error"><h2>{'Please correct the form'|i18n( 'cjw_newsletter/deliverability' )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}
        <div class="block{if is_set( $errors.email )} nl-field-error{/if}">
            <label for="nl-mi-email">{'Address'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <input id="nl-mi-email" class="halfbox" type="email" name="email" value="{$address.email|wash}" maxlength="255" />
        </div>
        <div class="block{if is_set( $errors.plus_tag )} nl-field-error{/if}">
            <label for="nl-mi-tag">{'Plus tag'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <input id="nl-mi-tag" type="text" name="plus_tag" value="{$address.plus_tag|wash}" maxlength="100" size="20" />
            <span class="nl-hint">{'Optional: with tag "weekly" the address is news+weekly@example.org.'|i18n( 'cjw_newsletter/deliverability' )}{if $plus_addressing|not} {'Plus-addressing is off ([MailInSettings] PlusAddressing), so only an address without a tag is reached.'|i18n( 'cjw_newsletter/deliverability' )}{/if}</span>
        </div>
        <div class="block{if is_set( $errors.list_contentobject_id )} nl-field-error{/if}">
            <label for="nl-mi-list">{'List'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <select id="nl-mi-list" name="list_contentobject_id">
                <option value="0"{if $address.list_contentobject_id|eq( 0 )} selected="selected"{/if}>{'Every list (unsubscribe only)'|i18n( 'cjw_newsletter/deliverability' )}</option>
                {foreach $lists as $list}<option value="{$list.id}"{if $address.list_contentobject_id|eq( $list.id )} selected="selected"{/if}>{$list.name|wash}</option>{/foreach}
            </select>
        </div>
        <div class="block">
            <label>{'Takes'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <label class="nl-inline"><input type="radio" name="action" value="both"{if or( $address.action|eq( 'both' ), $address.action|eq( '' ) )} checked="checked"{/if} /> {'both, by keyword (subject or first line)'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <label class="nl-inline"><input type="radio" name="action" value="subscribe"{if $address.action|eq( 'subscribe' )} checked="checked"{/if} /> {'subscribe'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <label class="nl-inline"><input type="radio" name="action" value="unsubscribe"{if $address.action|eq( 'unsubscribe' )} checked="checked"{/if} /> {'unsubscribe'|i18n( 'cjw_newsletter/deliverability' )}</label>
        </div>
        <div class="block{if is_set( $errors.mailbox_id )} nl-field-error{/if}">
            <label for="nl-mi-mailbox">{'Mailbox'|i18n( 'cjw_newsletter/deliverability' )}</label>
            <select id="nl-mi-mailbox" name="mailbox_id">
                <option value="0"{if $address.mailbox_id|eq( 0 )} selected="selected"{/if}>{'The bounce mailbox of the e-mail preferences'|i18n( 'cjw_newsletter/deliverability' )}</option>
                {foreach $mailboxes as $mb}<option value="{$mb.id}"{if $address.mailbox_id|eq( $mb.id )} selected="selected"{/if}>{$mb.name|wash}</option>{/foreach}
            </select>
            <span class="nl-hint">{'The mailbox the mails to this address arrive in. Only messages read from it are acted on.'|i18n( 'cjw_newsletter/deliverability' )}</span>
        </div>
        <div class="block">
            <label class="nl-inline"><input type="checkbox" name="is_active" value="1"{if $address.is_active} checked="checked"{/if} /> {'Active'|i18n( 'cjw_newsletter/deliverability' )}</label>
        </div>
        {/if}
    </div>
    {if $confirm_remove|not}
    <div class="controlbar">
        <input class="defaultbutton" type="submit" name="StoreButton" value="{'Store'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        <input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        {if $address.id}<input class="button" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'cjw_newsletter/deliverability' )|wash}" />{/if}
    </div>
    {/if}
</div>
</form>
</div>
