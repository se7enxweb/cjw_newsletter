{* Send an edition by SMS, admin4. Same data and actions as design/standard/templates/newsletter/sms/send.tpl.
   Variables: node, node_id, list_object_id, list_sms_enabled, list_sms_sender, sms_enabled, transport
   (CjwNewsletterSms::summary()), text, test_phone, segments hash( encoding, units, segments, per_segment, remaining ),
   max_segments, stop_hint, placeholders, audience hash( subscribers, confirmed, pending ), sends, errors, notices *}
{def $i18n = 'cjw_newsletter/sms'}
<div class="a4-page newsletter newsletter-sms_send">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Send by SMS: %name'|i18n( $i18n,, hash( '%name', $node.name ) )|wash}</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    {if $errors|count}
    <div class="a4-notice is-bad a4-error-summary" role="alert"><h2 class="a4-notice-title">{'Please check the SMS'|i18n( $i18n )}</h2>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}

    <form action={concat( 'newsletter/sms_send/', $node_id )|ezurl} method="post">

    <div class="a4-card">
        <dl class="a4-facts">
            <div><dt>{'subscribers'|i18n( $i18n )}</dt><dd><strong>{$audience.subscribers}</strong></dd></div>
            <div><dt>{'with a confirmed number'|i18n( $i18n )}</dt><dd><strong>{$audience.confirmed}</strong></dd></div>
            <div><dt>{'waiting for their code'|i18n( $i18n )}</dt><dd><strong>{$audience.pending}</strong></dd></div>
        </dl>
        <div class="a4-badges">
            {if $sms_enabled|not}<span class="a4-badge is-warn">{'SMS are switched off'|i18n( $i18n )}</span>
            {elseif $transport.transport_ok|not}<span class="a4-badge is-bad">{'No SMS transport'|i18n( $i18n )}</span>
            {elseif $transport.simulated}<span class="a4-badge is-info">{'Writes SMS to files'|i18n( $i18n )}</span>
            {else}<span class="a4-badge is-ok">{'Sends by %name'|i18n( $i18n,, hash( '%name', $transport.transport ) )|wash}</span>{/if}
            {if $list_sms_enabled|not}<span class="a4-badge is-warn">{'Not switched on for this list'|i18n( $i18n )}</span>{/if}
        </div>
    </div>

    <p class="a4-intro">{'Only subscribers who confirmed their mobile number with the code and agreed to newsletters by SMS on the preference page get the SMS. Everyone else is left out when the queue is made.'|i18n( $i18n )}</p>

    <div class="a4-card">
        <div class="a4-field">
            <label for="nl-sms-text">{'Text of the SMS'|i18n( $i18n )}</label>
            <textarea id="nl-sms-text" name="SmsText" cols="60" rows="5" maxlength="1000"
                      data-nl-sms-counter="nl-sms-counter" data-nl-sms-hint="{$stop_hint|wash}" data-nl-sms-max="{$max_segments}"
                      aria-describedby="nl-sms-counter nl-sms-placeholders">{$text|wash}</textarea>
            <p class="a4-help" id="nl-sms-counter" aria-live="polite">
                <span data-nl-sms-units>{$segments.units}</span> {'characters'|i18n( $i18n )},
                <span data-nl-sms-segments>{$segments.segments}</span> {'SMS parts'|i18n( $i18n )} ({'at most %max'|i18n( $i18n,, hash( '%max', $max_segments ) )}),
                <span data-nl-sms-encoding>{if $segments.encoding|eq( 'gsm' )}GSM 7-bit{else}Unicode{/if}</span>,
                <span data-nl-sms-remaining>{$segments.remaining}</span> {'left in this part'|i18n( $i18n )}
            </p>
            {if $stop_hint}<p class="a4-help">{'This line is added to every SMS and counted above:'|i18n( $i18n )} <code>{$stop_hint|wash}</code></p>{/if}
            <p class="a4-help" id="nl-sms-placeholders">{'Placeholders:'|i18n( $i18n )} {foreach $placeholders as $placeholder}<code>{$placeholder|wash}</code>{delimiter} {/delimiter}{/foreach}.
            {'The counter counts them as typed; a long name makes the SMS longer.'|i18n( $i18n )}</p>
        </div>
    </div>

    <div class="a4-grid is-wide">
        <section class="a4-card">
            <h2 class="a4-h3">{'Test SMS'|i18n( $i18n )}</h2>
            <div class="a4-field">
                <label for="nl-sms-test-phone">{'Mobile number'|i18n( $i18n )}</label>
                <input id="nl-sms-test-phone" type="text" inputmode="tel" autocomplete="off" name="SmsTestPhone" value="{$test_phone|wash}" maxlength="40" placeholder="+49 151 23456789" />
            </div>
            <p class="a4-help">{'The test goes only to this number; the placeholders stay as they are.'|i18n( $i18n )}</p>
            <div class="a4-actions">
                <input class="a4-btn" type="submit" name="SmsTestButton" value="{'Send a test SMS'|i18n( $i18n )|wash}"{if $sms_enabled|not} disabled="disabled"{/if} />
            </div>
        </section>
        <section class="a4-card">
            <h2 class="a4-h3">{'Sender'|i18n( $i18n )}</h2>
            <p>{if $list_sms_sender}<code>{$list_sms_sender|wash}</code>{else}<span class="a4-muted">{'The sender of the transport'|i18n( $i18n )}</span>{/if}</p>
            <p class="a4-help">{'The sender and the SMS switch of a list are set on the list object.'|i18n( $i18n )}</p>
        </section>
    </div>

    <section class="a4-section">
        <h2 class="a4-h2">{'SMS sends of this edition'|i18n( $i18n )}</h2>
        {if $sends|count}
        <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr><th scope="col">{'Created'|i18n( $i18n )}</th><th scope="col">{'Text'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Waiting'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Sent'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Failed'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Stopped'|i18n( $i18n )}</th></tr>
            </thead>
            <tbody>
            {foreach $sends as $send}
            <tr>
                <td><time>{$send.created|l10n( 'shortdatetime' )}</time></td>
                <td class="a4-wrap">{$send.text|wash|shorten( 80 )}</td>
                <td class="a4-num">{$send.waiting}</td>
                <td class="a4-num">{$send.sent}</td>
                <td class="a4-num">{if $send.failed}<span class="a4-bad-text nl-danger">{$send.failed}</span>{else}0{/if}</td>
                <td class="a4-num">{$send.aborted}</td>
            </tr>
            {/foreach}
            </tbody>
        </table>
        </div>
        {else}
        <p class="a4-empty">{'This edition was not sent by SMS yet.'|i18n( $i18n )}</p>
        {/if}
    </section>

    <div class="a4-toolbar is-bottom">
        <div class="a4-actions">
            <input class="a4-btn a4-btn-primary" type="submit" name="SmsSendButton" value="{'Send by SMS'|i18n( $i18n )|wash}"{if or( $sms_enabled|not, $list_sms_enabled|not )} disabled="disabled"{/if} />
            <input class="a4-btn" type="submit" name="SmsBackButton" value="{'Back to the edition'|i18n( $i18n )|wash}" />
        </div>
    </div>
    </form>
</div>
{include uri='design:newsletter/sms/counter_script.tpl'}
{undef $i18n}
