{*  newsletter/dashboard/deliverability.tpl (cjw_newsletter 4.2.0, area deliverability)

    The dashboard block: rate limits and batches of the transports, bounces and the kernel suppression list, soft-bounce
    retries, test groups and the mail-in addresses. Data: summary.areas.CjwNewsletterDeliverabilityHooks
    (CjwNewsletterDeliverability::summary()).
*}
{if is_set( $summary.areas.CjwNewsletterDeliverabilityHooks )}
{def $d = $summary.areas.CjwNewsletterDeliverabilityHooks}
<section class="nl-area nl-area-deliverability">
    <h2>{'Deliverability'|i18n( 'cjw_newsletter/deliverability' )}</h2>
    <div class="nl-cards">
        <section class="nl-card">
            <h3>{'Sending rate'|i18n( 'cjw_newsletter/deliverability' )}</h3>
            <p>
                {if $d.throttle}<span class="nl-pill is-ok">{'Batches of %size'|i18n( 'cjw_newsletter/deliverability',, hash( '%size', $d.batch_size ) )}</span>
                {else}<span class="nl-pill is-muted">{'No rate limit'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
                {if $d.batches_running}<span class="nl-pill is-info">{'%count batches waiting'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $d.batches_running ) )}</span>{/if}
            </p>
            <table class="list nl-table">
                <tr><th>{'Transport'|i18n( 'cjw_newsletter/deliverability' )}</th><th class="nl-num">{'This minute'|i18n( 'cjw_newsletter/deliverability' )}</th><th class="nl-num">{'This hour'|i18n( 'cjw_newsletter/deliverability' )}</th></tr>
                {foreach $d.transports as $t sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td><code>{$t.transport|wash}</code>{if $t.paused} <span class="nl-pill is-warn">{'paused'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                    <td class="nl-num">{$t.sent_minute}{if $t.limit_minute} / {$t.limit_minute}{/if}</td>
                    <td class="nl-num">{$t.sent_hour}{if $t.limit_hour} / {$t.limit_hour}{/if}</td>
                </tr>
                {/foreach}
            </table>
            <div class="nl-links">
                <a class="button" href={'newsletter/throttle'|ezurl}>{'Rate limits and batches'|i18n( 'cjw_newsletter/deliverability' )}</a>
            </div>
        </section>

        <section class="nl-card">
            <h3>{'Bounces and suppression'|i18n( 'cjw_newsletter/deliverability' )}</h3>
            <ul class="nl-stats">
                <li><strong>{$d.bounces_30}</strong><span class="nl-muted">{'bounces in 30 days'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li><strong>{$d.retries_waiting}</strong><span class="nl-muted">{'retries waiting'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                {if $d.suppressed.available}
                <li><strong>{$d.suppressed.bounce}</strong><span class="nl-muted">{'suppressed: bounce'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li><strong>{$d.suppressed.complaint}</strong><span class="nl-muted">{'suppressed: complaint'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                {/if}
            </ul>
            <p class="nl-muted">
                {if and( $d.suppressed.available, $d.suppress_hard_bounces )}{'Hard bounces and complaints go on the suppression list of the site: no optional mail goes to them any more.'|i18n( 'cjw_newsletter/deliverability' )}
                {else}{'Hard bounces only mark the newsletter user as bounced.'|i18n( 'cjw_newsletter/deliverability' )}{/if}
                {if $d.max_retries|gt( 0 )}{'A soft bounce is sent again up to %count times.'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $d.max_retries ) )}{/if}
            </p>
            <p class="nl-muted">
                {if $d.kernel_reader.available}{'Bounce reader of the e-mail preferences'|i18n( 'cjw_newsletter/deliverability' )}:
                    {if $d.kernel_reader.enabled}<span class="nl-pill is-ok">{'on'|i18n( 'cjw_newsletter/deliverability' )}</span>{if $d.kernel_reader.last_run} <time>{$d.kernel_reader.last_run|l10n( 'shortdatetime' )}</time>{/if}
                    {else}<span class="nl-pill is-muted">{'off'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}{/if}
            </p>
            <div class="nl-links">
                {if $d.suppressed.available}<a class="button" href={'newsletter/suppression_import'|ezurl}>{'Import into the suppression list'|i18n( 'cjw_newsletter/deliverability' )}</a>{/if}
                <a class="button" href={'newsletter/mailbox_item_list'|ezurl}>{'Bounces'|i18n( 'cjw_newsletter/deliverability' )}</a>
            </div>
        </section>

        <section class="nl-card">
            <h3>{'Test sends and mail-in'|i18n( 'cjw_newsletter/deliverability' )}</h3>
            <ul class="nl-stats">
                <li><strong>{$d.test_groups}</strong><span class="nl-muted">{'test groups'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li><strong>{$d.mailin.addresses}</strong><span class="nl-muted">{'mail-in addresses'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li><strong>{$d.mailin.pending}</strong><span class="nl-muted">{'waiting for a confirmation'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li><strong>{$d.mailin.done_30}</strong><span class="nl-muted">{'handled in 30 days'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
            </ul>
            {if $d.mailin.address_list|count}
            <ul class="nl-muted">
                {foreach $d.mailin.address_list as $address}<li><code>{$address|wash}</code></li>{/foreach}
            </ul>
            {/if}
            {if $d.mailin.enabled|not}<p class="nl-muted">{'Subscribe and unsubscribe by e-mail is switched off ([MailInSettings] MailIn).'|i18n( 'cjw_newsletter/deliverability' )}</p>{/if}
            <div class="nl-links">
                <a class="button" href={'newsletter/test_group_list'|ezurl}>{'Test groups'|i18n( 'cjw_newsletter/deliverability' )}</a>
                <a class="button" href={'newsletter/mailin_address_list'|ezurl}>{'Mail-in addresses'|i18n( 'cjw_newsletter/deliverability' )}</a>
            </div>
        </section>
    </div>
</section>
{undef $d}
{/if}
