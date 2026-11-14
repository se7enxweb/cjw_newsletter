{* Edit or add a mail account (the account bounces are collected from), admin4. Same data and actions as design/standard/templates/newsletter/mailbox_edit.tpl *}
{def $mailbox_id = 0}
{if and( is_set( $mailbox.id ), $mailbox.id|gt( 0 ) )}
    {set $mailbox_id = $mailbox.id}
{/if}
<div class="a4-page newsletter newsletter-mailbox_edit">
<form name="editform" id="editform" method="post" action={concat( '/newsletter/mailbox_edit/', $mailbox_id )|ezurl}>
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-inbox cjwnl-a4-lead" aria-hidden="true"></span>{if $mailbox_id}{'Edit <%mailbox.email> '|i18n( 'cjw_newsletter/mailbox_edit',, hash( '%mailbox.email', $mailbox.email ) )|wash}{else}{'Add new mail account'|i18n( 'cjw_newsletter/mailbox_edit' )}{/if}</h1>
        </div>
    </div>

    {if $confirm_remove}
    <div class="a4-card is-bad"><div class="a4-card-body">
        <h2 class="a4-h2">{'Remove this mail account?'|i18n( 'cjw_newsletter/mailbox_edit' )}</h2>
        <p>{$mailbox.email|wash} ({$mailbox.server|wash})</p>
        <p>{'The mails collected from it stay; no more bounces are collected.'|i18n( 'cjw_newsletter/mailbox_edit' )}</p>
        <input type="hidden" name="redirect" value="{$redirect_uri|wash}" />
    </div></div>
    <div class="a4-toolbar">
        <input class="a4-btn a4-btn-danger nl-danger" type="submit" name="ConfirmRemoveButton" value="{'Yes, remove'|i18n( 'extension/cjw_newsletter' )|wash}" />
        <input class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'extension/cjw_newsletter' )|wash}" />
    </div>
    {else}

    {if $errors|count}
    <div class="a4-notice is-bad" role="alert"><strong class="a4-notice-title">{'Input did not validate'|i18n( 'cjw_newsletter/subscribe' )}</strong>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}

    <div class="a4-card"><div class="a4-card-body cjwnl-a4-lists-form">
        <div class="a4-field">
            <label for="nl-mb-email">{'Email'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-email" class="a4-input" type="text" name="email" value="{$mailbox.email|wash}" maxlength="150"{if is_set( $errors.email )} aria-invalid="true"{/if} />
        </div>
        <div class="a4-field">
            <label for="nl-mb-server">{'Server'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-server" class="a4-input" type="text" name="server" value="{$mailbox.server|wash}" maxlength="150"{if is_set( $errors.server )} aria-invalid="true"{/if} />
        </div>
        <div class="a4-field">
            <label for="nl-mb-port">{'Port'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-port" class="a4-input is-auto" type="text" name="port" size="6" value="{if $mailbox.port|gt( 0 )}{$mailbox.port|wash}{/if}" aria-describedby="nl-mb-port-help"{if is_set( $errors.port )} aria-invalid="true"{/if} />
            <span class="a4-help" id="nl-mb-port-help">{'Empty: the default of the type (IMAP 143, IMAP with SSL 993, POP3 110, POP3 with SSL 995).'|i18n( 'cjw_newsletter/mailbox_edit' )}</span>
        </div>
        <div class="a4-field">
            <label for="nl-mb-user">{'User'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-user" class="a4-input" type="text" name="user_name" value="{$mailbox.user_name|wash}" autocomplete="off"{if is_set( $errors.user_name )} aria-invalid="true"{/if} />
        </div>
        <div class="a4-field">
            <label for="nl-mb-password">{'Password'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-password" class="a4-input" type="password" name="password" value="" autocomplete="new-password"{if $mailbox_id} aria-describedby="nl-mb-password-help"{/if}{if is_set( $errors.password )} aria-invalid="true"{/if} />
            {if $mailbox_id}<span class="a4-help" id="nl-mb-password-help">{'Leave it empty to keep the stored password.'|i18n( 'cjw_newsletter/mailbox_edit' )}</span>{/if}
        </div>
        <fieldset class="a4-field">
            <legend>{'Type'|i18n( 'cjw_newsletter/mailbox_edit' )}</legend>
            <div class="a4-radios">
                <label><input type="radio" name="type" value="imap"{if $mailbox.type|ne( 'pop3' )} checked="checked"{/if} /> IMAP</label>
                <label><input type="radio" name="type" value="pop3"{if $mailbox.type|eq( 'pop3' )} checked="checked"{/if} /> POP3</label>
            </div>
        </fieldset>
        <fieldset class="a4-field">
            <legend>{'SSL'|i18n( 'cjw_newsletter/mailbox_edit' )}</legend>
            <div class="a4-radios">
                <label><input type="radio" name="is_ssl" value="1"{if $mailbox.is_ssl|gt( 0 )} checked="checked"{/if} /> {'True'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
                <label><input type="radio" name="is_ssl" value="0"{if $mailbox.is_ssl|lt( 1 )} checked="checked"{/if} /> {'False'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            </div>
        </fieldset>
        <fieldset class="a4-field">
            <legend>{'Delete mails from server'|i18n( 'cjw_newsletter/mailbox_edit' )}</legend>
            <div class="a4-radios">
                <label><input type="radio" name="delete_mails_from_server" value="1"{if $mailbox.delete_mails_from_server|gt( 0 )} checked="checked"{/if} /> {'True'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
                <label><input type="radio" name="delete_mails_from_server" value="0"{if $mailbox.delete_mails_from_server|lt( 1 )} checked="checked"{/if} /> {'False'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            </div>
        </fieldset>
        <fieldset class="a4-field">
            <legend>{'Active'|i18n( 'cjw_newsletter/mailbox_edit' )}</legend>
            <div class="a4-radios">
                <label><input type="radio" name="is_activated" value="1"{if or( $mailbox_id|eq( 0 ), $mailbox.is_activated|gt( 0 ) )} checked="checked"{/if} /> {'True'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
                <label><input type="radio" name="is_activated" value="0"{if and( $mailbox_id|gt( 0 ), $mailbox.is_activated|lt( 1 ) )} checked="checked"{/if} /> {'False'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            </div>
        </fieldset>
        <input type="hidden" name="edit" value="true" />
        <input type="hidden" name="redirect" value="{$redirect_uri|wash}" />
    </div></div>

    <div class="a4-toolbar">
        <input class="a4-btn a4-btn-primary" type="submit" name="PublishButton" value="{'Store Changes'|i18n( 'cjw_newsletter/mailbox_edit' )|wash}" />
        <input class="a4-btn" type="submit" name="DiscardButton" value="{'Discard'|i18n( 'cjw_newsletter/mailbox_edit' )|wash}" />
        {if $mailbox_id}<input class="a4-btn a4-btn-outline-danger" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'extension/cjw_newsletter' )|wash}" />{/if}
    </div>
    {/if}
</form>
</div>
