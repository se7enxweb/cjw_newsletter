{* Edit or add a mail account (the account bounces are collected from) *}
{ezcss_require( 'newsletter_ui.css' )}
{def $mailbox_id = 0}
{if and( is_set( $mailbox.id ), $mailbox.id|gt( 0 ) )}
    {set $mailbox_id = $mailbox.id}
{/if}
<div class="newsletter newsletter-mailbox_edit">
<form name="editform" id="editform" method="post" action={concat( '/newsletter/mailbox_edit/', $mailbox_id )|ezurl}>
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">
            {if $mailbox_id}{'Edit <%mailbox.email> '|i18n( 'cjw_newsletter/mailbox_edit',, hash( '%mailbox.email', $mailbox.email ) )|wash}{else}{'Add new mail account'|i18n( 'cjw_newsletter/mailbox_edit' )}{/if}
        </h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {if $confirm_remove}
        <div class="nl-confirm">
            <h2>{'Remove this mail account?'|i18n( 'cjw_newsletter/mailbox_edit' )}</h2>
            <p>{$mailbox.email|wash} ({$mailbox.server|wash})</p>
            <p>{'The mails collected from it stay; no more bounces are collected.'|i18n( 'cjw_newsletter/mailbox_edit' )}</p>
            <input type="hidden" name="redirect" value="{$redirect_uri|wash}" />
            <input class="button nl-danger" type="submit" name="ConfirmRemoveButton" value="{'Yes, remove'|i18n( 'extension/cjw_newsletter' )|wash}" />
            <input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'extension/cjw_newsletter' )|wash}" />
        </div>
        {else}

        {if $errors|count}
        <div class="message-error"><h2>{'Input did not validate'|i18n( 'cjw_newsletter/subscribe' )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}

        <div class="block {if is_set( $errors.email )}nl-field-error{/if}">
            <label for="nl-mb-email">{'Email'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-email" class="halfbox" type="text" name="email" value="{$mailbox.email|wash}" maxlength="150" />
        </div>
        <div class="block {if is_set( $errors.server )}nl-field-error{/if}">
            <label for="nl-mb-server">{'Server'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-server" class="halfbox" type="text" name="server" value="{$mailbox.server|wash}" maxlength="150" />
        </div>
        <div class="block {if is_set( $errors.port )}nl-field-error{/if}">
            <label for="nl-mb-port">{'Port'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-port" type="text" name="port" size="6" value="{if $mailbox.port|gt( 0 )}{$mailbox.port|wash}{/if}" />
            <span class="nl-hint">{'Empty: the default of the type (IMAP 143, IMAP with SSL 993, POP3 110, POP3 with SSL 995).'|i18n( 'cjw_newsletter/mailbox_edit' )}</span>
        </div>
        <div class="block {if is_set( $errors.user_name )}nl-field-error{/if}">
            <label for="nl-mb-user">{'User'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-user" class="halfbox" type="text" name="user_name" value="{$mailbox.user_name|wash}" autocomplete="off" />
        </div>
        <div class="block {if is_set( $errors.password )}nl-field-error{/if}">
            <label for="nl-mb-password">{'Password'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input id="nl-mb-password" class="halfbox" type="password" name="password" value="" autocomplete="new-password" />
            {if $mailbox_id}<span class="nl-hint">{'Leave it empty to keep the stored password.'|i18n( 'cjw_newsletter/mailbox_edit' )}</span>{/if}
        </div>
        <div class="block">
            <label>{'Type'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input type="radio" name="type" value="imap"{if $mailbox.type|ne( 'pop3' )} checked="checked"{/if} /> IMAP
            <input type="radio" name="type" value="pop3"{if $mailbox.type|eq( 'pop3' )} checked="checked"{/if} /> POP3
        </div>
        <div class="block">
            <label>{'SSL'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input type="radio" name="is_ssl" value="1"{if $mailbox.is_ssl|gt( 0 )} checked="checked"{/if} /> {'True'|i18n( 'cjw_newsletter/mailbox_edit' )}
            <input type="radio" name="is_ssl" value="0"{if $mailbox.is_ssl|lt( 1 )} checked="checked"{/if} /> {'False'|i18n( 'cjw_newsletter/mailbox_edit' )}
        </div>
        <div class="block">
            <label>{'Delete mails from server'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input type="radio" name="delete_mails_from_server" value="1"{if $mailbox.delete_mails_from_server|gt( 0 )} checked="checked"{/if} /> {'True'|i18n( 'cjw_newsletter/mailbox_edit' )}
            <input type="radio" name="delete_mails_from_server" value="0"{if $mailbox.delete_mails_from_server|lt( 1 )} checked="checked"{/if} /> {'False'|i18n( 'cjw_newsletter/mailbox_edit' )}
        </div>
        <div class="block">
            <label>{'Active'|i18n( 'cjw_newsletter/mailbox_edit' )}</label>
            <input type="radio" name="is_activated" value="1"{if or( $mailbox_id|eq( 0 ), $mailbox.is_activated|gt( 0 ) )} checked="checked"{/if} /> {'True'|i18n( 'cjw_newsletter/mailbox_edit' )}
            <input type="radio" name="is_activated" value="0"{if and( $mailbox_id|gt( 0 ), $mailbox.is_activated|lt( 1 ) )} checked="checked"{/if} /> {'False'|i18n( 'cjw_newsletter/mailbox_edit' )}
        </div>
        <input type="hidden" name="edit" value="true" />
        <input type="hidden" name="redirect" value="{$redirect_uri|wash}" />
        {/if}
    </div>
    {if $confirm_remove|not}
    <div class="controlbar">
        <input class="defaultbutton" type="submit" name="PublishButton" value="{'Store Changes'|i18n( 'cjw_newsletter/mailbox_edit' )|wash}" />
        <input class="button" type="submit" name="DiscardButton" value="{'Discard'|i18n( 'cjw_newsletter/mailbox_edit' )|wash}" />
        {if $mailbox_id}<input class="button" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'extension/cjw_newsletter' )|wash}" />{/if}
    </div>
    {/if}
</div>
</form>
</div>
