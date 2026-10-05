{*  newsletter/sms/send.tpl: send an edition by SMS (area N5)

    node, node_id, list_object_id, list_sms_enabled, list_sms_sender, sms_enabled, transport (CjwNewsletterSms::summary()),
    text, test_phone, segments hash( encoding, units, segments, per_segment, remaining ), max_segments, stop_hint,
    placeholders, audience hash( subscribers, confirmed, pending ), sends, errors, notices
*}
{ezcss_require( 'newsletter_ui.css' )}
{def $i18n = 'cjw_newsletter/sms'}
<div class="newsletter newsletter-sms_send">
<form action={concat( 'newsletter/sms_send/', $node_id )|ezurl} method="post">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Send by SMS: %name'|i18n( $i18n,, hash( '%name', $node.name ) )|wash}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        {if $errors|count}
        <div class="message-error" role="alert"><h2>{'Please check the SMS'|i18n( $i18n )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}

        <div class="nl-status">
            <div class="nl-status-facts">
                <span><strong>{$audience.subscribers}</strong> {'subscribers'|i18n( $i18n )}</span>
                <span><strong>{$audience.confirmed}</strong> {'with a confirmed number'|i18n( $i18n )}</span>
                <span><strong>{$audience.pending}</strong> {'waiting for their code'|i18n( $i18n )}</span>
            </div>
            <div>
                {if $sms_enabled|not}<span class="nl-pill is-warn">{'SMS are switched off'|i18n( $i18n )}</span>
                {elseif $transport.transport_ok|not}<span class="nl-pill is-bad">{'No SMS transport'|i18n( $i18n )}</span>
                {elseif $transport.simulated}<span class="nl-pill is-info">{'Writes SMS to files'|i18n( $i18n )}</span>
                {else}<span class="nl-pill is-ok">{'Sends by %name'|i18n( $i18n,, hash( '%name', $transport.transport ) )|wash}</span>{/if}
                {if $list_sms_enabled|not}<span class="nl-pill is-warn">{'Not switched on for this list'|i18n( $i18n )}</span>{/if}
            </div>
        </div>

        <p class="nl-hint">{'Only subscribers who confirmed their mobile number with the code and agreed to newsletters by SMS on the preference page get the SMS. Everyone else is left out when the queue is made.'|i18n( $i18n )}</p>

        <div class="block">
            <label for="nl-sms-text">{'Text of the SMS'|i18n( $i18n )}</label>
            <textarea id="nl-sms-text" class="box" name="SmsText" cols="60" rows="5" maxlength="1000"
                      data-nl-sms-counter="nl-sms-counter" data-nl-sms-hint="{$stop_hint|wash}" data-nl-sms-max="{$max_segments}"
                      aria-describedby="nl-sms-counter nl-sms-placeholders">{$text|wash}</textarea>
            <p class="nl-muted" id="nl-sms-counter" aria-live="polite">
                <span data-nl-sms-units>{$segments.units}</span> {'characters'|i18n( $i18n )},
                <span data-nl-sms-segments>{$segments.segments}</span> {'SMS parts'|i18n( $i18n )} ({'at most %max'|i18n( $i18n,, hash( '%max', $max_segments ) )}),
                <span data-nl-sms-encoding>{if $segments.encoding|eq( 'gsm' )}GSM 7-bit{else}Unicode{/if}</span>,
                <span data-nl-sms-remaining>{$segments.remaining}</span> {'left in this part'|i18n( $i18n )}
            </p>
            {if $stop_hint}<p class="nl-hint">{'This line is added to every SMS and counted above:'|i18n( $i18n )} <code>{$stop_hint|wash}</code></p>{/if}
            <p class="nl-hint" id="nl-sms-placeholders">{'Placeholders:'|i18n( $i18n )} {foreach $placeholders as $placeholder}<code>{$placeholder|wash}</code>{delimiter} {/delimiter}{/foreach}.
            {'The counter counts them as typed; a long name makes the SMS longer.'|i18n( $i18n )}</p>
        </div>

        <div class="nl-cards">
            <section class="nl-card">
                <h2>{'Test SMS'|i18n( $i18n )}</h2>
                <div class="block">
                    <label for="nl-sms-test-phone">{'Mobile number'|i18n( $i18n )}</label>
                    <input id="nl-sms-test-phone" class="halfbox" type="text" inputmode="tel" autocomplete="off" name="SmsTestPhone" value="{$test_phone|wash}" maxlength="40" placeholder="+49 151 23456789" />
                </div>
                <p class="nl-hint">{'The test goes only to this number; the placeholders stay as they are.'|i18n( $i18n )}</p>
                <input class="button" type="submit" name="SmsTestButton" value="{'Send a test SMS'|i18n( $i18n )|wash}"{if $sms_enabled|not} disabled="disabled"{/if} />
            </section>
            <section class="nl-card">
                <h2>{'Sender'|i18n( $i18n )}</h2>
                <p>{if $list_sms_sender}<code>{$list_sms_sender|wash}</code>{else}<span class="nl-muted">{'The sender of the transport'|i18n( $i18n )}</span>{/if}</p>
                <p class="nl-hint">{'The sender and the SMS switch of a list are set on the list object.'|i18n( $i18n )}</p>
            </section>
        </div>

        <section>
            <h2>{'SMS sends of this edition'|i18n( $i18n )}</h2>
            {if $sends|count}
            <table class="list nl-table">
                <tr><th>{'Created'|i18n( $i18n )}</th><th>{'Text'|i18n( $i18n )}</th><th class="nl-num">{'Waiting'|i18n( $i18n )}</th><th class="nl-num">{'Sent'|i18n( $i18n )}</th><th class="nl-num">{'Failed'|i18n( $i18n )}</th><th class="nl-num">{'Stopped'|i18n( $i18n )}</th></tr>
                {foreach $sends as $send sequence array( 'bglight', 'bgdark' ) as $style}
                <tr class="{$style}">
                    <td><time>{$send.created|l10n( 'shortdatetime' )}</time></td>
                    <td>{$send.text|wash|shorten( 80 )}</td>
                    <td class="nl-num">{$send.waiting}</td>
                    <td class="nl-num">{$send.sent}</td>
                    <td class="nl-num">{if $send.failed}<span class="nl-danger">{$send.failed}</span>{else}0{/if}</td>
                    <td class="nl-num">{$send.aborted}</td>
                </tr>
                {/foreach}
            </table>
            {else}
            <p class="nl-empty">{'This edition was not sent by SMS yet.'|i18n( $i18n )}</p>
            {/if}
        </section>
    </div>
    <div class="controlbar">
        <input class="defaultbutton" type="submit" name="SmsSendButton" value="{'Send by SMS'|i18n( $i18n )|wash}"{if or( $sms_enabled|not, $list_sms_enabled|not )} disabled="disabled"{/if} />
        <input class="button" type="submit" name="SmsBackButton" value="{'Back to the edition'|i18n( $i18n )|wash}" />
    </div>
</div>
</form>
</div>
{include uri='design:newsletter/sms/counter_script.tpl'}
{undef $i18n}
