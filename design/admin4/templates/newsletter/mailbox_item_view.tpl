{* The full view of a mailbox item (a collected mail), admin4. Same data and actions as design/standard/templates/newsletter/mailbox_item_view.tpl *}
<div class="a4-page newsletter newsletter-mailbox_item_view">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-exclamation-triangle cjwnl-a4-lead" aria-hidden="true"></span>{$mailbox_item.id|wash} [{'Mailbox Item Database Infos'|i18n( 'cjw_newsletter/mailbox_item_view',, hash() )|wash}]</h1>
        </div>
    </div>

    <div class="a4-card"><div class="a4-card-body">
    <dl class="a4-facts is-rows">
        <div><dt>id</dt><dd>{$mailbox_item.id|wash}</dd></div>
        <div><dt>mailbox_id</dt><dd>{$mailbox_item.mailbox_id|wash}</dd></div>
        <div><dt>message_id</dt><dd>{$mailbox_item.message_id|wash}</dd></div>
        <div><dt>message_identifier</dt><dd>{$mailbox_item.message_identifier|wash}</dd></div>
        <div><dt>message_size</dt><dd>{$mailbox_item.message_size|si( 'byte', 'kilo' )}</dd></div>
        <div><dt>{'created'|i18n( 'cjw_newsletter/mailbox_item_view' )}</dt><dd>{$mailbox_item.created|l10n( shortdatetime )}</dd></div>
        <div><dt>{'processed'|i18n( 'cjw_newsletter/mailbox_item_view' )}</dt><dd>{if $mailbox_item.processed|ne(0)}{$mailbox_item.processed|l10n( shortdatetime )}{/if}</dd></div>
        <div><dt>bounce_code</dt><dd>{$mailbox_item.bounce_code|wash}</dd></div>
        <div><dt>email_from</dt><dd>{$mailbox_item.email_from|wash}</dd></div>
        <div><dt>email_to</dt><dd>{$mailbox_item.email_to|wash}</dd></div>
        <div><dt>email_subject</dt><dd>{$mailbox_item.email_subject|wash}</dd></div>
        <div><dt>email_send_date</dt><dd>{if $mailbox_item.email_send_date|ne(0)}{$mailbox_item.email_send_date|l10n( shortdatetime )}{/if}</dd></div>
        <div><dt>edition_send_id</dt><dd>{if $mailbox_item.edition_send_id|ne(0)}<a href={concat( 'newsletter/preview_archive/', $mailbox_item.edition_send_id)|ezurl}>{$mailbox_item.edition_send_id|wash}</a>{else}{$mailbox_item.edition_send_id|wash}{/if}</dd></div>
        <div><dt>edition_send_item_id</dt><dd>{$mailbox_item.edition_send_item_id|wash}</dd></div>
        <div><dt>newsletter_user_id</dt><dd>{if eq( $mailbox_item.newsletter_user_id, 0 )}-{else}<a href={concat( 'newsletter/user_view/', $mailbox_item.newsletter_user_id)|ezurl}>{$mailbox_item.newsletter_user_id|wash}</a>{/if}</dd></div>
        <div><dt>is_bounce</dt><dd>{if $mailbox_item.is_bounce|eq(true())}x{else}-{/if}</dd></div>
        <div><dt>is_system_bounce</dt><dd>{if $mailbox_item.is_system_bounce|eq(true())}<span class="a4-badge is-warn">x</span>{else}-{/if}</dd></div>
    </dl>
    </div></div>

    <section class="a4-section">
        <h2 class="a4-h2">{'LIVE mailbox item parse infos'|i18n( 'cjw_newsletter/mailbox_item_view',, hash( ) )}</h2>
        <div class="a4-table-wrap">
        <table class="a4-table">
            <tbody>
            {foreach $mailbox_header_hash as $header_key => $header_value}
            <tr>
                <th scope="row">{$header_key|wash}</th>
                <td class="a4-wrap">{$header_value|wash}</td>
            </tr>
            {/foreach}
            </tbody>
        </table>
        </div>
    </section>

    <section class="a4-section">
        <h2 class="a4-h2">{'Raw mail content'|i18n( 'cjw_newsletter/mailbox_item_view',, hash( ) )}</h2>
        <p class="a4-wrap">{$mailbox_item.file_path|wash}</p>
        <p class="a4-actions">
            <a class="a4-btn" href="?GetRawMailContent" target="_blank">{'Full View'|i18n( 'cjw_newsletter/mailbox_item_view',, hash( ) )}</a>
            <a class="a4-btn" href="?DownloadRawMailContent">{'Download'|i18n( 'cjw_newsletter/mailbox_item_view',, hash( ) )}</a>
            {if ezpreference( 'admin_mailbox_item_view_raw_iframe' )|eq(0)}
            <a class="a4-btn" href={'/user/preferences/set/admin_mailbox_item_view_raw_iframe/1'|ezurl} title="{'Show raw mail content inline.'|i18n( 'cjw_newsletter/mailbox_item_view' )}">{'Show inline'|i18n( 'cjw_newsletter/mailbox_item_view' )}</a>
            {else}
            <a class="a4-btn" href={'/user/preferences/set/admin_mailbox_item_view_raw_iframe/0'|ezurl} title="{'Hide raw mail content inline.'|i18n( 'cjw_newsletter/mailbox_item_view' )}">{'Hide inline'|i18n( 'cjw_newsletter/mailbox_item_view' )}</a>
            {/if}
        </p>
        {if ezpreference( 'admin_mailbox_item_view_raw_iframe' )|ne(0)}
        <iframe class="cjwnl-a4-rawmail" name="RawMailConent" title="{'Raw mail content'|i18n( 'cjw_newsletter/mailbox_item_view',, hash( ) )|wash}" src="?GetRawMailContent"></iframe>
        {/if}
    </section>
</div>
