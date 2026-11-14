{* An edition as one subscriber gets it, admin4. Same data and actions as design/standard/templates/newsletter/rendering/preview_as.tpl.
   Variables: node, edition, list, newsletter_user (or null), interests, subscribers (some subscribers of the list to
   choose from), skins, skin, format_id, mail (hash( subject, html, text, language, skin ) or null), from_send, search, message. *}
{def $i18n = 'cjw_newsletter/rendering'
     $base = concat( 'newsletter/preview_as/', $node.node_id )}
<div class="a4-page newsletter newsletter-preview_as">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Preview as a subscriber'|i18n( $i18n )}: {$node.name|wash}</h1>
        </div>
    </div>

    {if $message}<div class="a4-notice is-warn" role="status">{$message|wash}</div>{/if}

    <form class="a4-filterbar" action={$base|ezurl} method="get">
        <div class="a4-field">
            <label for="SubscriberSearch">{'E-mail address of the subscriber'|i18n( $i18n )}</label>
            <input id="SubscriberSearch" type="search" name="SubscriberSearch" value="{$search|wash}" placeholder="name@example.org" />
        </div>
        {if $skins|count|gt( 1 )}
        <div class="a4-field">
            <label for="SkinName">{'Skin'|i18n( $i18n )}</label>
            <select id="SkinName" name="SkinName">{foreach $skins as $s}<option value="{$s|wash}"{if $s|eq( $skin )} selected="selected"{/if}>{$s|wash}</option>{/foreach}</select>
        </div>
        {/if}
        <div class="a4-actions">
            <input class="a4-btn a4-btn-primary" type="submit" value="{'Show'|i18n( $i18n )|wash}" />
        </div>
    </form>

    {if $subscribers|count}
    <p class="a4-help">{'Or one of the subscribers of the list:'|i18n( $i18n )}
    {foreach $subscribers as $s}<a href={concat( $base, '/', $s.id, '/', $format_id, '?SkinName=', $skin|urlencode )|ezurl}>{$s.email|wash}</a>{delimiter}, {/delimiter}{/foreach}</p>
    {/if}

    {if $newsletter_user}
    <div class="a4-card">
        <dl class="a4-facts is-rows">
            <dt>{'Subscriber'|i18n( $i18n )}</dt><dd><a href={concat( 'newsletter/user_view/', $newsletter_user.id )|ezurl}>{$newsletter_user.email|wash}</a> ({$newsletter_user.name|wash})</dd>
            <dt>{'Language'|i18n( $i18n )}</dt><dd>{$mail.language|wash}{if $newsletter_user.language|eq( '' )} <span class="a4-muted">({'the subscriber has not chosen one'|i18n( $i18n )})</span>{elseif $newsletter_user.language|ne( $mail.language )} <span class="a4-muted">({'chosen: %language, the edition has no translation in it'|i18n( $i18n,, hash( '%language', $newsletter_user.language ) )|wash})</span>{/if}</dd>
            <dt>{'Interests'|i18n( $i18n )}</dt><dd>{if $interests|count}{foreach $interests as $interest}{$interest.name|wash}{delimiter}, {/delimiter}{/foreach}{else}<span class="a4-muted">{'none'|i18n( $i18n )}</span>{/if}</dd>
            <dt>{'Skin'|i18n( $i18n )}</dt><dd>{$mail.skin|wash}{if $from_send} <span class="a4-muted">({'as it was sent'|i18n( $i18n )})</span>{/if}</dd>
            <dt>{'Subject'|i18n( $i18n )}</dt><dd><b>{$mail.subject|wash}</b></dd>
        </dl>
    </div>

    <div class="a4-actions nl-links">
        <a class="a4-btn{if $format_id|eq( 0 )} a4-btn-primary is-active{/if}"{if $format_id|eq( 0 )} aria-current="true"{/if} href={concat( $base, '/', $newsletter_user.id, '/0?SkinName=', $skin|urlencode )|ezurl}>{'HTML part'|i18n( $i18n )}</a>
        <a class="a4-btn{if $format_id|eq( 1 )} a4-btn-primary is-active{/if}"{if $format_id|eq( 1 )} aria-current="true"{/if} href={concat( $base, '/', $newsletter_user.id, '/1?SkinName=', $skin|urlencode )|ezurl}>{'Text part'|i18n( $i18n )}</a>
    </div>

    {if and( $format_id|eq( 0 ), $mail.html|ne( '' ) )}
    <iframe class="cjwnl-a4-frame nl-preview-frame" title="{'HTML part'|i18n( $i18n )|wash}" sandbox="" srcdoc="{$mail.html|wash}"></iframe>
    {/if}
    <section class="a4-section">
        <h2 class="a4-h2">{'Text part'|i18n( $i18n )}</h2>
        <pre class="a4-pre is-tall nl-preview-text">{$mail.text|wash}</pre>
    </section>
    {else}
    <p class="a4-empty">{'Choose a subscriber: the preview shows his language, the conditional parts, the articles for his interests and his placeholders. Nothing is sent.'|i18n( $i18n )}</p>
    {/if}
</div>
{undef $i18n $base}
