{* The deliverability block of the newsletter dashboard in admin4: rate limits and batches of the transports, bounces and the
   kernel suppression list, soft-bounce retries, test groups and the mail-in addresses.
   Same data and actions as design/standard/templates/newsletter/dashboard/deliverability.tpl
   Data: summary.areas.CjwNewsletterDeliverabilityHooks (CjwNewsletterDeliverability::summary()). *}
{if is_set( $summary.areas.CjwNewsletterDeliverabilityHooks )}
{def $d = $summary.areas.CjwNewsletterDeliverabilityHooks}
<section class="a4-section">
    <h2 class="a4-h2">{'Deliverability'|i18n( 'cjw_newsletter/deliverability' )}</h2>
    <div class="a4-grid is-wide cjwnl-a4-cards">
        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Sending rate'|i18n( 'cjw_newsletter/deliverability' )}</h3>
            <p class="a4-badges">
                {if $d.throttle}<span class="a4-badge is-ok">{'Batches of %size'|i18n( 'cjw_newsletter/deliverability',, hash( '%size', $d.batch_size ) )}</span>
                {else}<span class="a4-badge">{'No rate limit'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
                {if $d.batches_running}<span class="a4-badge is-info">{'%count batches waiting'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $d.batches_running ) )}</span>{/if}
            </p>
            <dl class="a4-facts is-rows">
                {foreach $d.transports as $t}
                <div><dt><code>{$t.transport|wash}</code></dt>
                <dd>{if $t.paused}<span class="a4-badge is-warn">{'paused'|i18n( 'cjw_newsletter/deliverability' )}</span> {/if}{'%minute this minute, %hour this hour'|i18n( 'cjw_newsletter/deliverability',, hash( '%minute', cond( $t.limit_minute, concat( $t.sent_minute, '/', $t.limit_minute ), $t.sent_minute ), '%hour', cond( $t.limit_hour, concat( $t.sent_hour, '/', $t.limit_hour ), $t.sent_hour ) ) )|wash}</dd></div>
                {/foreach}
            </dl>
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/throttle'|ezurl}>{'Rate limits and batches'|i18n( 'cjw_newsletter/deliverability' )}</a>
            </div>
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Bounces and suppression'|i18n( 'cjw_newsletter/deliverability' )}</h3>
            <ul class="a4-figures">
                <li class="a4-figure"><strong>{$d.bounces_30}</strong><span>{'bounces in 30 days'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li class="a4-figure"><strong>{$d.retries_waiting}</strong><span>{'retries waiting'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                {if $d.suppressed.available}
                <li class="a4-figure"><strong>{$d.suppressed.bounce}</strong><span>{'suppressed: bounce'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li class="a4-figure"><strong>{$d.suppressed.complaint}</strong><span>{'suppressed: complaint'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                {/if}
            </ul>
            <p class="a4-meta">
                {if and( $d.suppressed.available, $d.suppress_hard_bounces )}{'Hard bounces and complaints go on the suppression list of the site: no optional mail goes to them any more.'|i18n( 'cjw_newsletter/deliverability' )}
                {else}{'Hard bounces only mark the newsletter user as bounced.'|i18n( 'cjw_newsletter/deliverability' )}{/if}
                {if $d.max_retries|gt( 0 )}{'A soft bounce is sent again up to %count times.'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $d.max_retries ) )}{/if}
            </p>
            <p class="a4-meta">
                {if $d.kernel_reader.available}{'Bounce reader of the e-mail preferences'|i18n( 'cjw_newsletter/deliverability' )}:
                    {if $d.kernel_reader.enabled}<span class="a4-badge is-ok">{'on'|i18n( 'cjw_newsletter/deliverability' )}</span>{if $d.kernel_reader.last_run} <time>{$d.kernel_reader.last_run|l10n( 'shortdatetime' )}</time>{/if}
                    {else}<span class="a4-badge">{'off'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}{/if}
            </p>
            <div class="a4-actions">
                {if $d.suppressed.available}<a class="a4-btn" href={'newsletter/suppression_import'|ezurl}>{'Import into the suppression list'|i18n( 'cjw_newsletter/deliverability' )}</a>{/if}
                <a class="a4-btn" href={'newsletter/mailbox_item_list'|ezurl}><span class="a4-icon a4-bi-exclamation-triangle" aria-hidden="true"></span>{'Bounces'|i18n( 'cjw_newsletter/deliverability' )}</a>
            </div>
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Test sends and mail-in'|i18n( 'cjw_newsletter/deliverability' )}</h3>
            <ul class="a4-figures">
                <li class="a4-figure"><strong>{$d.test_groups}</strong><span>{'test groups'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li class="a4-figure"><strong>{$d.mailin.addresses}</strong><span>{'mail-in addresses'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li class="a4-figure"><strong>{$d.mailin.pending}</strong><span>{'waiting for a confirmation'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
                <li class="a4-figure"><strong>{$d.mailin.done_30}</strong><span>{'handled in 30 days'|i18n( 'cjw_newsletter/deliverability' )}</span></li>
            </ul>
            {if $d.mailin.address_list|count}
            <ul class="cjwnl-a4-list is-plain">
                {foreach $d.mailin.address_list as $address}<li><code>{$address|wash}</code></li>{/foreach}
            </ul>
            {/if}
            {if $d.mailin.enabled|not}<p class="a4-meta">{'Subscribe and unsubscribe by e-mail is switched off ([MailInSettings] MailIn).'|i18n( 'cjw_newsletter/deliverability' )}</p>{/if}
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/test_group_list'|ezurl}>{'Test groups'|i18n( 'cjw_newsletter/deliverability' )}</a>
                <a class="a4-btn" href={'newsletter/mailin_address_list'|ezurl}>{'Mail-in addresses'|i18n( 'cjw_newsletter/deliverability' )}</a>
            </div>
        </section>
    </div>
</section>
{undef $d}
{/if}
