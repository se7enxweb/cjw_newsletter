{* The mail-in addresses and their last messages, admin4: same data and actions as design/standard/templates/newsletter/deliverability/mailin_address_list.tpl *}
<div class="a4-page newsletter newsletter-mailin_address_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'Mail-in addresses'|i18n( 'cjw_newsletter/deliverability' )}</h1>
        </div>
        <div class="a4-pagehead-actions">
            <form action={'newsletter/mailin_address_edit/0'|ezurl} method="post">
                <input class="a4-btn a4-btn-primary" type="submit" name="NewButton" value="{'New mail-in address'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            </form>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    <p class="a4-intro">{'People subscribe and unsubscribe by writing to these addresses. A subscribe mail only starts the subscription: the confirmation mail goes to the sender, who must open its link. An unsubscribe mail is honoured: at once when it proves the address, otherwise the address gets a mail with its unsubscribe link. The From header alone never changes anything.'|i18n( 'cjw_newsletter/deliverability' )}</p>
    <p class="a4-badges">
        {if $enabled}<span class="a4-badge is-ok">{'Mail-in is on'|i18n( 'cjw_newsletter/deliverability' )}</span>{else}<span class="a4-badge is-warn">{'Mail-in is off ([MailInSettings] MailIn)'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
        {if $plus_addressing}<span class="a4-badge is-info">{'Plus-addressing'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
        {if $kernel_reader}<span class="a4-badge is-ok">{'The bounce reader of the e-mail preferences reads the mailbox'|i18n( 'cjw_newsletter/deliverability' )}</span>{else}<span class="a4-badge">{'The bounce reader of the e-mail preferences is off'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
    </p>

    {if $addresses|count}
    <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr>
                <th scope="col">{'Address'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col">{'List'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col">{'Takes'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col">{'Mailbox'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col">{'Active'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col">{'Actions'|i18n( 'cjw_newsletter/deliverability' )}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $addresses as $a}
            <tr>
                <td class="a4-wrap"><code>{$a.address|wash}</code></td>
                <td class="a4-wrap">{if $a.list_id}{if $a.list_name}{$a.list_name|wash}{else}#{$a.list_id}{/if}{else}<span class="a4-muted">{'every list'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                <td>{cond( eq( $a.action, 'subscribe' ), 'subscribe'|i18n( 'cjw_newsletter/deliverability' ), eq( $a.action, 'unsubscribe' ), 'unsubscribe'|i18n( 'cjw_newsletter/deliverability' ), 'both, by keyword'|i18n( 'cjw_newsletter/deliverability' ) )}</td>
                <td>{if $a.mailbox_id}<a href={concat( 'newsletter/mailbox_edit/', $a.mailbox_id )|ezurl}>#{$a.mailbox_id}</a>{else}{'bounce mailbox'|i18n( 'cjw_newsletter/deliverability' )}{/if}</td>
                <td>{if $a.is_active}<span class="a4-badge is-ok">{'yes'|i18n( 'cjw_newsletter/deliverability' )}</span>{else}<span class="a4-badge">{'no'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                <td><a class="a4-btn a4-btn-small" href={concat( 'newsletter/mailin_address_edit/', $a.id )|ezurl}>{'Edit'|i18n( 'cjw_newsletter/deliverability' )}</a></td>
            </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
    {else}
    <p class="a4-empty"><strong>{'There is no mail-in address yet.'|i18n( 'cjw_newsletter/deliverability' )}</strong></p>
    {/if}

    <section class="a4-section" aria-labelledby="nl-mi-messages">
        <h2 class="a4-h2" id="nl-mi-messages">{'Last messages'|i18n( 'cjw_newsletter/deliverability' )}</h2>
        {if $messages|count}
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                <tr><th scope="col">{'Received'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'To'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'From'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'Request'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'Result'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'Note'|i18n( 'cjw_newsletter/deliverability' )}</th></tr>
                </thead>
                <tbody>
                {foreach $messages as $m}
                <tr>
                    <td>{$m.created|l10n( 'shortdatetime' )}</td>
                    <td class="a4-wrap"><code>{$m.address|wash}</code></td>
                    <td class="a4-wrap">{if $m.newsletter_user_id}<a href={concat( 'newsletter/user_view/', $m.newsletter_user_id )|ezurl}>{$m.from|wash}</a>{else}{$m.from|wash}{/if}</td>
                    <td>{$m.action|wash}</td>
                    <td><span class="a4-badge{cond( eq( $m.status, 'done' ), ' is-ok', eq( $m.status, 'pending' ), ' is-info', eq( $m.status, 'rejected' ), ' is-warn', '' )}">{$m.status|wash}</span></td>
                    <td class="a4-wrap">{$m.note|wash}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        {else}
        <p class="a4-empty">{'No message has come in yet.'|i18n( 'cjw_newsletter/deliverability' )}</p>
        {/if}
    </section>
</div>
