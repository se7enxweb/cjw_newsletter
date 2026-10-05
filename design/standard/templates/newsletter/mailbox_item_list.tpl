{*  newsletter/mailbox_item_list.tpl

    the mails collected from the mail accounts (bounces), and the two actions on them
*}
{ezcss_require( 'newsletter_ui.css' )}
{def $page_uri = 'newsletter/mailbox_item_list'}
<div class="newsletter newsletter-mailbox_item_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Bounces'|i18n( 'cjw_newsletter/mailbox_item_list' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
        {include uri='design:parts/newsletter/job.tpl' job_id=$job_id}

        <p class="nl-muted">{'Collect reads the active mail accounts and stores the new mails; Parse looks at the stored mails and sets the status of the users that bounced. Both run in the background.'|i18n( 'extension/cjw_newsletter' )}</p>

        <form action={'newsletter/mailbox_item_list'|ezurl} method="post" style="margin: 0 0 0.9em 0">
            <input class="button" type="submit" name="ConnectMailboxButton" value="{'Collect all mails'|i18n( 'cjw_newsletter/mailbox_item_list' )|wash}"{if $active_mailboxes|eq( 0 )} disabled="disabled" title="{'No mail account is active.'|i18n( 'extension/cjw_newsletter' )|wash}"{/if} />
            <input class="button" type="submit" name="BounceMailItemButton" value="{'Parse mails'|i18n( 'cjw_newsletter/mailbox_item_list' )|wash}" />
            <a class="button" href={'newsletter/mailbox_list'|ezurl}>{'Mail accounts'|i18n( 'extension/cjw_newsletter' )}</a>
        </form>

        <h2>{'Mailbox items'|i18n( 'cjw_newsletter/mailbox_item_list' )} [{$mailbox_item_list_count}]</h2>
        {if $mailbox_item_list|count}
        <table class="list nl-table">
            <tr>
                <th class="nl-num">{'ID'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
                <th>{'Subject'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
                <th>{'From'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
                <th>{'Bouncecode'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
                <th>{'IsBounce'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
                <th>{'Nl user'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
                <th>{'Created'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
                <th>{'Processed'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
            </tr>
            {foreach $mailbox_item_list as $mailbox_item sequence array( 'bglight', 'bgdark' ) as $style}
            <tr class="{$style}">
                <td class="nl-num"><a href={concat( 'newsletter/mailbox_item_view/', $mailbox_item.id )|ezurl}>{$mailbox_item.id}</a></td>
                <td class="nl-wrap">{$mailbox_item.email_subject|wash|shorten( 60 )}</td>
                <td class="nl-wrap">{$mailbox_item.email_from|wash|shorten( 30 )}</td>
                <td>{$mailbox_item.bounce_code|wash}</td>
                <td>{if $mailbox_item.is_bounce|eq( true() )}<span class="nl-pill is-warn">{'bounce'|i18n( 'extension/cjw_newsletter' )}</span>{else}-{/if}</td>
                <td>{if $mailbox_item.newsletter_user_id|ne( 0 )}<a href={concat( 'newsletter/user_view/', $mailbox_item.newsletter_user_id )|ezurl}>{$mailbox_item.newsletter_user_id|wash}</a>{/if}</td>
                <td>{$mailbox_item.created|l10n( 'shortdatetime' )}</td>
                <td>{if $mailbox_item.processed|gt( 0 )}{$mailbox_item.processed|l10n( 'shortdatetime' )}{else}<span class="nl-pill is-muted">{'waiting'|i18n( 'extension/cjw_newsletter' )}</span>{/if}</td>
            </tr>
            {/foreach}
        </table>
        <div class="nl-pager">
            <span>{'%count mails'|i18n( 'extension/cjw_newsletter',, hash( '%count', $mailbox_item_list_count ) )}</span>
            <span class="table-preferences">{'Per page'|i18n( 'extension/cjw_newsletter' )}:
                {foreach array( 10, 25, 50, 100 ) as $n}{if eq( $limit, $n )}<span class="nl-page is-current">{$n}</span>{else}<a href={concat( 'newsletter/mailbox_item_list/(limit)/', $n )|ezurl}>{$n}</a>{/if} {/foreach}
            </span>
            {include name='Navigator'
                     uri='design:navigator/google.tpl'
                     page_uri=$page_uri
                     item_count=$mailbox_item_list_count
                     view_parameters=$view_parameters
                     item_limit=$limit}
        </div>
        {else}
        <div class="nl-empty">
            <p><strong>{'No mail was collected yet.'|i18n( 'extension/cjw_newsletter' )}</strong></p>
        </div>
        {/if}
    </div>
</div>
</div>
{include uri='design:parts/newsletter/script.tpl'}
