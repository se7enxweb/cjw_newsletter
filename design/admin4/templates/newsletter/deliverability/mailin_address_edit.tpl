{* Create, edit or remove a mail-in address, admin4: same data and actions as design/standard/templates/newsletter/deliverability/mailin_address_edit.tpl *}
<div class="a4-page newsletter newsletter-mailin_address_edit">
<form method="post" action={concat( 'newsletter/mailin_address_edit/', $address.id )|ezurl}>
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{if $address.id}{'Mail-in address %address'|i18n( 'cjw_newsletter/deliverability',, hash( '%address', $display ) )|wash}{else}{'New mail-in address'|i18n( 'cjw_newsletter/deliverability' )}{/if}</h1>
        </div>
    </div>

    {if $confirm_remove}
    <div class="a4-notice is-warn" role="alert">
        <p class="a4-notice-title">{'Remove this mail-in address?'|i18n( 'cjw_newsletter/deliverability' )}</p>
        <p><code>{$display|wash}</code></p>
        <p>{'Mails to it are no longer acted on. The messages it got stay in the list.'|i18n( 'cjw_newsletter/deliverability' )}</p>
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
            <div class="a4-field{if is_set( $errors.email )} nl-field-error{/if}">
                <label for="nl-mi-email">{'Address'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <input id="nl-mi-email" type="email" name="email" value="{$address.email|wash}" maxlength="255"{if is_set( $errors.email )} aria-invalid="true"{/if} />
            </div>
            <div class="a4-field{if is_set( $errors.plus_tag )} nl-field-error{/if}">
                <label for="nl-mi-tag">{'Plus tag'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <input id="nl-mi-tag" type="text" name="plus_tag" value="{$address.plus_tag|wash}" maxlength="100" size="20" aria-describedby="nl-mi-tag-help"{if is_set( $errors.plus_tag )} aria-invalid="true"{/if} />
                <span class="a4-help" id="nl-mi-tag-help">{'Optional: with tag "weekly" the address is news+weekly@example.org.'|i18n( 'cjw_newsletter/deliverability' )}{if $plus_addressing|not} {'Plus-addressing is off ([MailInSettings] PlusAddressing), so only an address without a tag is reached.'|i18n( 'cjw_newsletter/deliverability' )}{/if}</span>
            </div>
            <div class="a4-field{if is_set( $errors.list_contentobject_id )} nl-field-error{/if}">
                <label for="nl-mi-list">{'List'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <select id="nl-mi-list" name="list_contentobject_id">
                    <option value="0"{if $address.list_contentobject_id|eq( 0 )} selected="selected"{/if}>{'Every list (unsubscribe only)'|i18n( 'cjw_newsletter/deliverability' )}</option>
                    {foreach $lists as $list}<option value="{$list.id}"{if $address.list_contentobject_id|eq( $list.id )} selected="selected"{/if}>{$list.name|wash}</option>{/foreach}
                </select>
            </div>
            <fieldset class="a4-field">
                <legend>{'Takes'|i18n( 'cjw_newsletter/deliverability' )}</legend>
                <ul class="a4-radios">
                    <li><label><input type="radio" name="action" value="both"{if or( $address.action|eq( 'both' ), $address.action|eq( '' ) )} checked="checked"{/if} /> <span>{'both, by keyword (subject or first line)'|i18n( 'cjw_newsletter/deliverability' )}</span></label></li>
                    <li><label><input type="radio" name="action" value="subscribe"{if $address.action|eq( 'subscribe' )} checked="checked"{/if} /> <span>{'subscribe'|i18n( 'cjw_newsletter/deliverability' )}</span></label></li>
                    <li><label><input type="radio" name="action" value="unsubscribe"{if $address.action|eq( 'unsubscribe' )} checked="checked"{/if} /> <span>{'unsubscribe'|i18n( 'cjw_newsletter/deliverability' )}</span></label></li>
                </ul>
            </fieldset>
            <div class="a4-field{if is_set( $errors.mailbox_id )} nl-field-error{/if}">
                <label for="nl-mi-mailbox">{'Mailbox'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <select id="nl-mi-mailbox" name="mailbox_id" aria-describedby="nl-mi-mailbox-help">
                    <option value="0"{if $address.mailbox_id|eq( 0 )} selected="selected"{/if}>{'The bounce mailbox of the e-mail preferences'|i18n( 'cjw_newsletter/deliverability' )}</option>
                    {foreach $mailboxes as $mb}<option value="{$mb.id}"{if $address.mailbox_id|eq( $mb.id )} selected="selected"{/if}>{$mb.name|wash}</option>{/foreach}
                </select>
                <span class="a4-help" id="nl-mi-mailbox-help">{'The mailbox the mails to this address arrive in. Only messages read from it are acted on.'|i18n( 'cjw_newsletter/deliverability' )}</span>
            </div>
            <div class="a4-field">
                <label class="a4-check"><input type="checkbox" name="is_active" value="1"{if $address.is_active} checked="checked"{/if} /> {'Active'|i18n( 'cjw_newsletter/deliverability' )}</label>
            </div>
        </div>
        <div class="a4-card-foot">
            <input class="a4-btn a4-btn-primary" type="submit" name="StoreButton" value="{'Store'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            <input class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            {if $address.id}<input class="a4-btn" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'cjw_newsletter/deliverability' )|wash}" />{/if}
        </div>
    </div>
    {/if}
</form>
</div>
