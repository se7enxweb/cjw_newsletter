{* Rate limits, pauses, batches and retries, admin4: same data and actions as design/standard/templates/newsletter/deliverability/throttle.tpl *}
<div class="a4-page newsletter newsletter-throttle">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'Rate limits and batches'|i18n( 'cjw_newsletter/deliverability' )}</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    <div class="a4-card">
        <dl class="a4-facts is-rows">
            <dt>{'Batches and limits'|i18n( 'cjw_newsletter/deliverability' )}</dt>
            <dd>{if $enabled}<span class="a4-badge is-ok">{'on'|i18n( 'cjw_newsletter/deliverability' )}</span> {'batches of %size, %pause seconds between two batches of a send'|i18n( 'cjw_newsletter/deliverability',, hash( '%size', $batch_size, '%pause', $pause_between ) )|wash}
                {else}<span class="a4-badge">{'off'|i18n( 'cjw_newsletter/deliverability' )}</span> {'the queue sends everything in one run ([ThrottleSettings] Throttle)'|i18n( 'cjw_newsletter/deliverability' )}{/if}</dd>
            <dt>{'Soft bounces'|i18n( 'cjw_newsletter/deliverability' )}</dt>
            <dd>{if $max_retries|gt( 0 )}{'sent again up to %count times, the first time after %minutes minutes'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $max_retries, '%minutes', div( $retry_delay, 60 )|floor ) )|wash}{else}{'not sent again'|i18n( 'cjw_newsletter/deliverability' )}{/if};
                {'%count mails wait for their retry'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $retries_waiting ) )|wash}</dd>
        </dl>
    </div>

    <section class="a4-section" aria-labelledby="nl-th-transports">
        <h2 class="a4-h2" id="nl-th-transports">{'Transports'|i18n( 'cjw_newsletter/deliverability' )}</h2>
        <p class="a4-intro">{'A pause holds every send of the transport, also when the limits are off. The limits are set per transport in [ThrottleSettings] MaxPerMinute[] and MaxPerHour[].'|i18n( 'cjw_newsletter/deliverability' )}</p>
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead><tr><th scope="col">{'Transport'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'Sent'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'State'|i18n( 'cjw_newsletter/deliverability' )}</th></tr></thead>
                <tbody>
                {foreach $transports as $t}
                <tr>
                    <td class="a4-wrap"><code>{$t.transport|wash}</code>{if $t.is_cronjob}<br /><span class="a4-muted">{'(newsletter)'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                    <td>{'This minute'|i18n( 'cjw_newsletter/deliverability' )}: {$t.sent_minute} / {if $t.limit_minute}{$t.limit_minute}{else}&infin;{/if}<br />
                        {'This hour'|i18n( 'cjw_newsletter/deliverability' )}: {$t.sent_hour} / {if $t.limit_hour}{$t.limit_hour}{else}&infin;{/if}</td>
                    <td>
                        {if $t.paused}<span class="a4-badge is-warn">{'paused until %time'|i18n( 'cjw_newsletter/deliverability',, hash( '%time', $t.paused_until|l10n( 'shortdatetime' ) ) )|wash}</span>{elseif eq( $t.available, 0 )}<span class="a4-badge is-info">{'limit reached'|i18n( 'cjw_newsletter/deliverability' )}</span>{else}<span class="a4-badge is-ok">{'sends'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}
                        <form class="a4-row cjwnl-a4-throttle-form" action={'newsletter/throttle'|ezurl} method="post">
                            <input type="hidden" name="Transport" value="{$t.transport|wash}" />
                            {if $t.paused}<input class="a4-btn a4-btn-small" type="submit" name="ResumeButton" value="{'Resume'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
                            {else}<label class="a4-inline-label">{'for'|i18n( 'cjw_newsletter/deliverability' )} <input class="cjwnl-a4-minutes" type="number" name="Minutes" value="60" min="1" max="10080" aria-label="{'Minutes'|i18n( 'cjw_newsletter/deliverability' )|wash}" /> {'min'|i18n( 'cjw_newsletter/deliverability' )}</label>
                            <input class="a4-btn a4-btn-small" type="submit" name="PauseButton" value="{'Pause'|i18n( 'cjw_newsletter/deliverability' )|wash}" />{/if}
                        </form>
                    </td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </section>

    <section class="a4-section" aria-labelledby="nl-th-batches">
        <h2 class="a4-h2" id="nl-th-batches">{'Last batches'|i18n( 'cjw_newsletter/deliverability' )}</h2>
        {if $batches|count}
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead><tr><th scope="col" class="a4-num">{'Send'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col" class="a4-num">{'Batch'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'Channel'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col" class="a4-num">{'Taken'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col" class="a4-num">{'Sent'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col" class="a4-num">{'Failed'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'State'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'Started'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'Finished'|i18n( 'cjw_newsletter/deliverability' )}</th></tr></thead>
                <tbody>
                {foreach $batches as $b}
                <tr>
                    <td class="a4-num">{$b.send_id}</td><td class="a4-num">{$b.number}</td><td>{$b.channel|wash}</td>
                    <td class="a4-num">{$b.items}</td><td class="a4-num">{$b.sent}</td><td class="a4-num">{$b.failed}</td>
                    <td><span class="a4-badge{cond( eq( $b.status, 'done' ), ' is-ok', eq( $b.status, 'paused' ), ' is-info', eq( $b.status, 'failed' ), ' is-bad', '' )}">{$b.status|wash}</span></td>
                    <td>{if $b.started}{$b.started|l10n( 'shortdatetime' )}{/if}</td><td>{if $b.finished}{$b.finished|l10n( 'shortdatetime' )}{/if}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        {else}
        <p class="a4-empty">{'No batch has run yet.'|i18n( 'cjw_newsletter/deliverability' )}</p>
        {/if}
    </section>
</div>
