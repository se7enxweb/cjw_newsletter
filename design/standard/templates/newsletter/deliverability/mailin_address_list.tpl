{*  newsletter/deliverability/mailin_address_list.tpl (cjw_newsletter 4.2.0, area deliverability): the mail-in addresses and their last messages *}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-mailin_address_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Mail-in addresses'|i18n( 'cjw_newsletter/deliverability' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
        <p class="nl-muted">{'People subscribe and unsubscribe by writing to these addresses. A subscribe mail only starts the subscription: the confirmation mail goes to the sender, who must open its link. An unsubscribe mail is honoured: at once when it proves the address, otherwise the address gets a mail with its unsubscribe link. The From header alone never changes anything.'|i18n( 'cjw_newsletter/deliverability' )}</p>
        <p>
            {if $enabled}<span class="nl-pill is-ok">{'Mail-in is on'|i18n( 'cjw_newsletter/deliverability' )}</span>{else}<span class="nl-pill is-warn">{'Mail-in is off ([MailInSettings] MailIn)'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
            {if $plus_addressing}<span class="nl-pill is-info">{'Plus-addressing'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
            {if $kernel_reader}<span class="nl-pill is-ok">{'The bounce reader of the e-mail preferences reads the mailbox'|i18n( 'cjw_newsletter/deliverability' )}</span>{else}<span class="nl-pill is-muted">{'The bounce reader of the e-mail preferences is off'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
        </p>
        <form action={'newsletter/mailin_address_edit/0'|ezurl} method="post" style="margin: 0 0 0.9em 0">
            <input class="defaultbutton" type="submit" name="NewButton" value="{'New mail-in address'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        </form>
        {if $addresses|count}
        <table class="list nl-table">
            <tr>
                <th>{'Address'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th>{'List'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th>{'Takes'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th>{'Mailbox'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th>{'Active'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th class="tight">{'Actions'|i18n( 'cjw_newsletter/deliverability' )}</th>
            </tr>
            {foreach $addresses as $a sequence array( 'bglight', 'bgdark' ) as $seq}
            <tr class="{$seq}">
                <td class="nl-wrap"><code>{$a.address|wash}</code></td>
                <td class="nl-wrap">{if $a.list_id}{if $a.list_name}{$a.list_name|wash}{else}#{$a.list_id}{/if}{else}<span class="nl-muted">{'every list'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                <td>{cond( eq( $a.action, 'subscribe' ), 'subscribe'|i18n( 'cjw_newsletter/deliverability' ), eq( $a.action, 'unsubscribe' ), 'unsubscribe'|i18n( 'cjw_newsletter/deliverability' ), 'both, by keyword'|i18n( 'cjw_newsletter/deliverability' ) )}</td>
                <td>{if $a.mailbox_id}<a href={concat( 'newsletter/mailbox_edit/', $a.mailbox_id )|ezurl}>#{$a.mailbox_id}</a>{else}{'bounce mailbox'|i18n( 'cjw_newsletter/deliverability' )}{/if}</td>
                <td>{if $a.is_active}<span class="nl-pill is-ok">{'yes'|i18n( 'cjw_newsletter/deliverability' )}</span>{else}<span class="nl-pill is-muted">{'no'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                <td class="nl-actions"><a class="button" href={concat( 'newsletter/mailin_address_edit/', $a.id )|ezurl}>{'Edit'|i18n( 'cjw_newsletter/deliverability' )}</a></td>
            </tr>
            {/foreach}
        </table>
        {else}
        <div class="nl-empty"><p><strong>{'There is no mail-in address yet.'|i18n( 'cjw_newsletter/deliverability' )}</strong></p></div>
        {/if}

        <section>
            <h2>{'Last messages'|i18n( 'cjw_newsletter/deliverability' )}</h2>
            {if $messages|count}
            <table class="list nl-table">
                <tr><th>{'Received'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'To'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'From'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'Request'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'Result'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'Note'|i18n( 'cjw_newsletter/deliverability' )}</th></tr>
                {foreach $messages as $m sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td>{$m.created|l10n( 'shortdatetime' )}</td>
                    <td class="nl-wrap"><code>{$m.address|wash}</code></td>
                    <td class="nl-wrap">{if $m.newsletter_user_id}<a href={concat( 'newsletter/user_view/', $m.newsletter_user_id )|ezurl}>{$m.from|wash}</a>{else}{$m.from|wash}{/if}</td>
                    <td>{$m.action|wash}</td>
                    <td><span class="nl-pill {cond( eq( $m.status, 'done' ), 'is-ok', eq( $m.status, 'pending' ), 'is-info', eq( $m.status, 'rejected' ), 'is-warn', 'is-muted' )}">{$m.status|wash}</span></td>
                    <td class="nl-wrap">{$m.note|wash}</td>
                </tr>
                {/foreach}
            </table>
            {else}
            <p class="nl-muted">{'No message has come in yet.'|i18n( 'cjw_newsletter/deliverability' )}</p>
            {/if}
        </section>
    </div>
</div>
</div>
