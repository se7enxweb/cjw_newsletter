{* The SMS block of the newsletter dashboard (area N5, [ExtensionPointSettings] DashboardBlocks[]).
   summary.areas.CjwNewsletterSmsHooks: CjwNewsletterSms::summary() *}
{if is_set( $summary.areas.CjwNewsletterSmsHooks )}
{def $sms = $summary.areas.CjwNewsletterSmsHooks
     $i18n = 'cjw_newsletter/sms'}
<section class="nl-card nl-area-sms" id="nl-dashboard-sms">
    <h2>{'SMS'|i18n( $i18n )}
    {if $sms.enabled|not}<span class="nl-pill is-muted">{'switched off'|i18n( $i18n )}</span>
    {elseif $sms.transport_ok|not}<span class="nl-pill is-bad">{'no transport'|i18n( $i18n )}</span>
    {elseif $sms.simulated}<span class="nl-pill is-info">{'writes SMS to files'|i18n( $i18n )}</span>
    {else}<span class="nl-pill is-ok">{'sends by %name'|i18n( $i18n,, hash( '%name', $sms.transport ) )|wash}</span>{/if}</h2>
    <ul class="nl-stats">
        <li><strong>{$sms.messages.new}</strong><span class="nl-muted">{'in the queue'|i18n( $i18n )}</span></li>
        <li><strong>{$sms.messages.sent}</strong><span class="nl-muted">{'sent'|i18n( $i18n )}</span></li>
        <li><strong{if $sms.messages.failed} class="nl-danger"{/if}>{$sms.messages.failed}</strong><span class="nl-muted">{'failed'|i18n( $i18n )}</span></li>
        <li><strong>{$sms.phones.confirmed}</strong><span class="nl-muted">{'confirmed numbers'|i18n( $i18n )}</span></li>
        <li><strong>{$sms.phones.pending}</strong><span class="nl-muted">{'waiting for the code'|i18n( $i18n )}</span></li>
        <li><strong>{$sms.consents}</strong><span class="nl-muted">{'SMS consents'|i18n( $i18n )}</span></li>
        <li><strong>{$sms.stops}</strong><span class="nl-muted">{'STOP replies'|i18n( $i18n )} ({'%count in 30 days'|i18n( $i18n,, hash( '%count', $sms.stops_30 ) )})</span></li>
    </ul>
    {if $sms.simulated}<p class="nl-muted">{'Outbox'|i18n( $i18n )}: <code>{$sms.outbox|wash}</code></p>{/if}
    {if $sms.category_ok|not}<p class="nl-hint">{'The mail-preference category %category is not switched on; see doc/sms.md of the extension.'|i18n( $i18n,, hash( '%category', $sms.category ) )|wash}</p>{/if}
    {if $sms.sends|count}
    <table class="list nl-table">
        <tr><th>{'Last SMS sends'|i18n( $i18n )}</th><th class="nl-num">{'Waiting'|i18n( $i18n )}</th><th class="nl-num">{'Sent'|i18n( $i18n )}</th><th class="nl-num">{'Failed'|i18n( $i18n )}</th></tr>
        {foreach $sms.sends as $send}
        <tr>
            <td>{if $send.node_id}<a href={concat( 'newsletter/sms_send/', $send.node_id )|ezurl}>{$send.name|wash}</a>{else}{$send.name|wash}{/if} <time class="nl-muted">{$send.created|l10n( 'shortdatetime' )}</time></td>
            <td class="nl-num">{$send.waiting}</td>
            <td class="nl-num">{$send.sent}</td>
            <td class="nl-num">{if $send.failed}<span class="nl-danger">{$send.failed}</span>{else}0{/if}</td>
        </tr>
        {/foreach}
    </table>
    {else}
    <p class="nl-muted">{'No edition was sent by SMS yet. An edition is sent by SMS from its send page.'|i18n( $i18n )}</p>
    {/if}
</section>
{undef $sms $i18n}
{/if}
