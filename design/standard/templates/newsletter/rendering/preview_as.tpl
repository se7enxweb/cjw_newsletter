{*  newsletter/rendering/preview_as.tpl (cjw_newsletter 4.2.0, area rendering)

    An edition as one subscriber gets it. Variables: node, edition, list, newsletter_user (or null), interests,
    subscribers (some subscribers of the list to choose from), skins, skin, format_id, mail (hash( subject, html,
    text, language, skin ) or null), from_send, search, message.
*}
{ezcss_require( array( 'newsletter_ui.css' ) )}
{def $i18n = 'cjw_newsletter/rendering'
     $base = concat( 'newsletter/preview_as/', $node.node_id )}
<div class="newsletter newsletter-preview_as">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Preview as a subscriber'|i18n( $i18n )}: {$node.name|wash}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {if $message}<div class="message-warning"><p>{$message|wash}</p></div>{/if}

        <form class="nl-status" action={$base|ezurl} method="get">
            <div class="nl-status-facts">
                <label for="SubscriberSearch">{'E-mail address of the subscriber'|i18n( $i18n )}</label>
                <input class="halfbox" id="SubscriberSearch" type="search" name="SubscriberSearch" value="{$search|wash}" placeholder="name@example.org" />
                {if $skins|count|gt( 1 )}
                <label for="SkinName">{'Skin'|i18n( $i18n )}</label>
                <select id="SkinName" name="SkinName">{foreach $skins as $s}<option value="{$s|wash}"{if $s|eq( $skin )} selected="selected"{/if}>{$s|wash}</option>{/foreach}</select>
                {/if}
                <input class="defaultbutton" type="submit" value="{'Show'|i18n( $i18n )|wash}" />
            </div>
        </form>

        {if $subscribers|count}
        <p class="nl-hint">{'Or one of the subscribers of the list:'|i18n( $i18n )}
        {foreach $subscribers as $s}<a href={concat( $base, '/', $s.id, '/', $format_id, '?SkinName=', $skin|urlencode )|ezurl}>{$s.email|wash}</a>{delimiter}, {/delimiter}{/foreach}</p>
        {/if}

        {if $newsletter_user}
        <dl class="nl-kv">
            <dt>{'Subscriber'|i18n( $i18n )}</dt><dd><a href={concat( 'newsletter/user_view/', $newsletter_user.id )|ezurl}>{$newsletter_user.email|wash}</a> ({$newsletter_user.name|wash})</dd>
            <dt>{'Language'|i18n( $i18n )}</dt><dd>{$mail.language|wash}{if $newsletter_user.language|eq( '' )} <span class="nl-muted">({'the subscriber has not chosen one'|i18n( $i18n )})</span>{elseif $newsletter_user.language|ne( $mail.language )} <span class="nl-muted">({'chosen: %language, the edition has no translation in it'|i18n( $i18n,, hash( '%language', $newsletter_user.language ) )|wash})</span>{/if}</dd>
            <dt>{'Interests'|i18n( $i18n )}</dt><dd>{if $interests|count}{foreach $interests as $interest}{$interest.name|wash}{delimiter}, {/delimiter}{/foreach}{else}<span class="nl-muted">{'none'|i18n( $i18n )}</span>{/if}</dd>
            <dt>{'Skin'|i18n( $i18n )}</dt><dd>{$mail.skin|wash}{if $from_send} <span class="nl-muted">({'as it was sent'|i18n( $i18n )})</span>{/if}</dd>
            <dt>{'Subject'|i18n( $i18n )}</dt><dd><b>{$mail.subject|wash}</b></dd>
        </dl>

        <div class="nl-links">
            <a class="button{if $format_id|eq( 0 )} is-active{/if}" href={concat( $base, '/', $newsletter_user.id, '/0?SkinName=', $skin|urlencode )|ezurl}>{'HTML part'|i18n( $i18n )}</a>
            <a class="button{if $format_id|eq( 1 )} is-active{/if}" href={concat( $base, '/', $newsletter_user.id, '/1?SkinName=', $skin|urlencode )|ezurl}>{'Text part'|i18n( $i18n )}</a>
        </div>

        {if and( $format_id|eq( 0 ), $mail.html|ne( '' ) )}
        <iframe class="nl-preview-frame" title="{'HTML part'|i18n( $i18n )|wash}" sandbox="" srcdoc="{$mail.html|wash}" style="width:100%;min-height:900px;border:1px solid #d0d7de;border-radius:6px;background:#fff;"></iframe>
        {/if}
        <h2>{'Text part'|i18n( $i18n )}</h2>
        <pre class="nl-preview-text" style="white-space:pre-wrap;max-width:46em;padding:1em;border:1px solid #d0d7de;border-radius:6px;background:#fafafa;overflow:auto;">{$mail.text|wash}</pre>
        {else}
        <p class="nl-hint">{'Choose a subscriber: the preview shows his language, the conditional parts, the articles for his interests and his placeholders. Nothing is sent.'|i18n( $i18n )}</p>
        {/if}
    </div>
</div>
</div>
{undef $i18n $base}
