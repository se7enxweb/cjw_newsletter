{* The SMS block of the newsletter dashboard in admin4 (area N5, [ExtensionPointSettings] DashboardBlocks[]).
   Same data and actions as design/standard/templates/newsletter/dashboard/sms.tpl
   summary.areas.CjwNewsletterSmsHooks: CjwNewsletterSms::summary() *}
{if is_set( $summary.areas.CjwNewsletterSmsHooks )}
{def $sms = $summary.areas.CjwNewsletterSmsHooks
     $i18n = 'cjw_newsletter/sms'}
<section class="a4-card a4-stack cjwnl-a4-card cjwnl-a4-block" id="nl-dashboard-sms">
    <h2 class="a4-h2">{'SMS'|i18n( $i18n )}
    {if $sms.enabled|not}<span class="a4-badge">{'switched off'|i18n( $i18n )}</span>
    {elseif $sms.transport_ok|not}<span class="a4-badge is-bad">{'no transport'|i18n( $i18n )}</span>
    {elseif $sms.simulated}<span class="a4-badge is-info">{'writes SMS to files'|i18n( $i18n )}</span>
    {else}<span class="a4-badge is-ok">{'sends by %name'|i18n( $i18n,, hash( '%name', $sms.transport ) )|wash}</span>{/if}</h2>
    <ul class="a4-figures">
        <li class="a4-figure"><strong>{$sms.messages.new}</strong><span>{'in the queue'|i18n( $i18n )}</span></li>
        <li class="a4-figure"><strong>{$sms.messages.sent}</strong><span>{'sent'|i18n( $i18n )}</span></li>
        <li class="a4-figure{if $sms.messages.failed} is-bad{/if}"><strong{if $sms.messages.failed} class="nl-danger"{/if}>{$sms.messages.failed}</strong><span>{'failed'|i18n( $i18n )}</span></li>
        <li class="a4-figure"><strong>{$sms.phones.confirmed}</strong><span>{'confirmed numbers'|i18n( $i18n )}</span></li>
        <li class="a4-figure"><strong>{$sms.phones.pending}</strong><span>{'waiting for the code'|i18n( $i18n )}</span></li>
        <li class="a4-figure"><strong>{$sms.consents}</strong><span>{'SMS consents'|i18n( $i18n )}</span></li>
        <li class="a4-figure"><strong>{$sms.stops}</strong><span>{'STOP replies'|i18n( $i18n )} ({'%count in 30 days'|i18n( $i18n,, hash( '%count', $sms.stops_30 ) )})</span></li>
    </ul>
    {if $sms.simulated}<p class="a4-meta">{'Outbox'|i18n( $i18n )}: <code>{$sms.outbox|wash}</code></p>{/if}
    {if $sms.category_ok|not}<p class="a4-meta">{'The mail-preference category %category is not switched on; see doc/sms.md of the extension.'|i18n( $i18n,, hash( '%category', $sms.category ) )|wash}</p>{/if}
    {if $sms.sends|count}
    <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
                <tr><th scope="col">{'Last SMS sends'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Waiting'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Sent'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Failed'|i18n( $i18n )}</th></tr>
            </thead>
            <tbody>
            {foreach $sms.sends as $send}
                <tr>
                    <td>{if $send.node_id}<a href={concat( 'newsletter/sms_send/', $send.node_id )|ezurl}>{$send.name|wash}</a>{else}{$send.name|wash}{/if} <time class="a4-meta">{$send.created|l10n( 'shortdatetime' )}</time></td>
                    <td class="a4-num">{$send.waiting}</td>
                    <td class="a4-num">{$send.sent}</td>
                    <td class="a4-num">{if $send.failed}<span class="nl-danger a4-bad-text">{$send.failed}</span>{else}0{/if}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
    {else}
    <p class="a4-empty">{'No edition was sent by SMS yet. An edition is sent by SMS from its send page.'|i18n( $i18n )}</p>
    {/if}
</section>
{undef $sms $i18n}
{/if}
