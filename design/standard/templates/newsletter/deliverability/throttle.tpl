{*  newsletter/deliverability/throttle.tpl (cjw_newsletter 4.2.0, area deliverability): rate limits, pauses, batches and retries *}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-throttle">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Rate limits and batches'|i18n( 'cjw_newsletter/deliverability' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
        <dl class="nl-kv">
            <dt>{'Batches and limits'|i18n( 'cjw_newsletter/deliverability' )}</dt>
            <dd>{if $enabled}<span class="nl-pill is-ok">{'on'|i18n( 'cjw_newsletter/deliverability' )}</span> {'batches of %size, %pause seconds between two batches of a send'|i18n( 'cjw_newsletter/deliverability',, hash( '%size', $batch_size, '%pause', $pause_between ) )|wash}
                {else}<span class="nl-pill is-muted">{'off'|i18n( 'cjw_newsletter/deliverability' )}</span> {'the queue sends everything in one run ([ThrottleSettings] Throttle)'|i18n( 'cjw_newsletter/deliverability' )}{/if}</dd>
            <dt>{'Soft bounces'|i18n( 'cjw_newsletter/deliverability' )}</dt>
            <dd>{if $max_retries|gt( 0 )}{'sent again up to %count times, the first time after %minutes minutes'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $max_retries, '%minutes', div( $retry_delay, 60 )|floor ) )|wash}{else}{'not sent again'|i18n( 'cjw_newsletter/deliverability' )}{/if};
                {'%count mails wait for their retry'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $retries_waiting ) )|wash}</dd>
        </dl>

        <section>
            <h2>{'Transports'|i18n( 'cjw_newsletter/deliverability' )}</h2>
            <p class="nl-muted">{'A pause holds every send of the transport, also when the limits are off. The limits are set per transport in [ThrottleSettings] MaxPerMinute[] and MaxPerHour[].'|i18n( 'cjw_newsletter/deliverability' )}</p>
            <table class="list nl-table">
                <tr><th>{'Transport'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'Sent'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'State'|i18n( 'cjw_newsletter/deliverability' )}</th></tr>
                {foreach $transports as $t sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td class="nl-wrap"><code>{$t.transport|wash}</code>{if $t.is_cronjob}<br /><span class="nl-muted">{'(newsletter)'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                    <td>{'This minute'|i18n( 'cjw_newsletter/deliverability' )}: {$t.sent_minute} / {if $t.limit_minute}{$t.limit_minute}{else}&infin;{/if}<br />
                        {'This hour'|i18n( 'cjw_newsletter/deliverability' )}: {$t.sent_hour} / {if $t.limit_hour}{$t.limit_hour}{else}&infin;{/if}</td>
                    <td>
                        {if $t.paused}<span class="nl-pill is-warn">{'paused until %time'|i18n( 'cjw_newsletter/deliverability',, hash( '%time', $t.paused_until|l10n( 'shortdatetime' ) ) )|wash}</span>{elseif eq( $t.available, 0 )}<span class="nl-pill is-info">{'limit reached'|i18n( 'cjw_newsletter/deliverability' )}</span>{else}<span class="nl-pill is-ok">{'sends'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
                        <form action={'newsletter/throttle'|ezurl} method="post" style="margin: 0.4em 0 0 0">
                            <input type="hidden" name="Transport" value="{$t.transport|wash}" />
                            {if $t.paused}<input class="button" type="submit" name="ResumeButton" value="{'Resume'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
                            {else}<label>{'for'|i18n( 'cjw_newsletter/deliverability' )} <input type="number" name="Minutes" value="60" min="1" max="10080" style="width: 5em" aria-label="{'Minutes'|i18n( 'cjw_newsletter/deliverability' )|wash}" /> {'min'|i18n( 'cjw_newsletter/deliverability' )}</label>
                            <input class="button" type="submit" name="PauseButton" value="{'Pause'|i18n( 'cjw_newsletter/deliverability' )|wash}" />{/if}
                        </form>
                    </td>
                </tr>
                {/foreach}
            </table>
        </section>

        <section>
            <h2>{'Last batches'|i18n( 'cjw_newsletter/deliverability' )}</h2>
            {if $batches|count}
            <table class="list nl-table">
                <tr><th class="nl-num">{'Send'|i18n( 'cjw_newsletter/deliverability' )}</th><th class="nl-num">{'Batch'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'Channel'|i18n( 'cjw_newsletter/deliverability' )}</th><th class="nl-num">{'Taken'|i18n( 'cjw_newsletter/deliverability' )}</th><th class="nl-num">{'Sent'|i18n( 'cjw_newsletter/deliverability' )}</th><th class="nl-num">{'Failed'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'State'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'Started'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'Finished'|i18n( 'cjw_newsletter/deliverability' )}</th></tr>
                {foreach $batches as $b sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td class="nl-num">{$b.send_id}</td><td class="nl-num">{$b.number}</td><td>{$b.channel|wash}</td>
                    <td class="nl-num">{$b.items}</td><td class="nl-num">{$b.sent}</td><td class="nl-num">{$b.failed}</td>
                    <td><span class="nl-pill {cond( eq( $b.status, 'done' ), 'is-ok', eq( $b.status, 'paused' ), 'is-info', eq( $b.status, 'failed' ), 'is-bad', 'is-muted' )}">{$b.status|wash}</span></td>
                    <td>{if $b.started}{$b.started|l10n( 'shortdatetime' )}{/if}</td><td>{if $b.finished}{$b.finished|l10n( 'shortdatetime' )}{/if}</td>
                </tr>
                {/foreach}
            </table>
            {else}
            <p class="nl-muted">{'No batch has run yet.'|i18n( 'cjw_newsletter/deliverability' )}</p>
            {/if}
        </section>
    </div>
</div>
</div>
