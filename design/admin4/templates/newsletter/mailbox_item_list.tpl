{* The mails collected from the mail accounts (bounces) and the two actions on them, admin4. Same data and actions as design/standard/templates/newsletter/mailbox_item_list.tpl *}
{def $page_uri = 'newsletter/mailbox_item_list'}
<div class="a4-page newsletter newsletter-mailbox_item_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-exclamation-triangle cjwnl-a4-lead" aria-hidden="true"></span>{'Bounces'|i18n( 'cjw_newsletter/mailbox_item_list' )}</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    {include uri='design:parts/newsletter/job.tpl' job_id=$job_id}

    <p class="a4-intro">{'Collect reads the active mail accounts and stores the new mails; Parse looks at the stored mails and sets the status of the users that bounced. Both run in the background.'|i18n( 'extension/cjw_newsletter' )}</p>

    <form class="a4-toolbar" action={'newsletter/mailbox_item_list'|ezurl} method="post">
        <input class="a4-btn a4-btn-primary" type="submit" name="ConnectMailboxButton" value="{'Collect all mails'|i18n( 'cjw_newsletter/mailbox_item_list' )|wash}"{if $active_mailboxes|eq( 0 )} disabled="disabled" title="{'No mail account is active.'|i18n( 'extension/cjw_newsletter' )|wash}"{/if} />
        <input class="a4-btn" type="submit" name="BounceMailItemButton" value="{'Parse mails'|i18n( 'cjw_newsletter/mailbox_item_list' )|wash}" />
        <a class="a4-btn" href={'newsletter/mailbox_list'|ezurl}><span class="a4-icon a4-bi-inbox" aria-hidden="true"></span>{'Mail accounts'|i18n( 'extension/cjw_newsletter' )}</a>
    </form>

    <h2 class="a4-h2">{'Mailbox items'|i18n( 'cjw_newsletter/mailbox_item_list' )} [{$mailbox_item_list_count}]</h2>
    {if $mailbox_item_list|count}
    <div class="a4-table-wrap">
    <table class="a4-table">
        <thead>
        <tr>
            <th scope="col" class="a4-num">{'ID'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
            <th scope="col">{'Subject'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
            <th scope="col">{'From'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
            <th scope="col">{'Bouncecode'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
            <th scope="col">{'IsBounce'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
            <th scope="col">{'Nl user'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
            <th scope="col">{'Created'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
            <th scope="col">{'Processed'|i18n( 'cjw_newsletter/mailbox_item_list' )}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $mailbox_item_list as $mailbox_item}
        <tr>
            <td class="a4-num"><a href={concat( 'newsletter/mailbox_item_view/', $mailbox_item.id )|ezurl}>{$mailbox_item.id}</a></td>
            <td class="a4-wrap">{$mailbox_item.email_subject|wash|shorten( 60 )}</td>
            <td class="a4-wrap">{$mailbox_item.email_from|wash|shorten( 30 )}</td>
            <td>{$mailbox_item.bounce_code|wash}</td>
            <td>{if $mailbox_item.is_bounce|eq( true() )}<span class="a4-badge is-warn">{'bounce'|i18n( 'extension/cjw_newsletter' )}</span>{else}-{/if}</td>
            <td>{if $mailbox_item.newsletter_user_id|ne( 0 )}<a href={concat( 'newsletter/user_view/', $mailbox_item.newsletter_user_id )|ezurl}>{$mailbox_item.newsletter_user_id|wash}</a>{/if}</td>
            <td>{$mailbox_item.created|l10n( 'shortdatetime' )}</td>
            <td>{if $mailbox_item.processed|gt( 0 )}{$mailbox_item.processed|l10n( 'shortdatetime' )}{else}<span class="a4-badge">{'waiting'|i18n( 'extension/cjw_newsletter' )}</span>{/if}</td>
        </tr>
        {/foreach}
        </tbody>
    </table>
    </div>
    <div class="a4-listfoot">
        <span class="a4-meta">{'%count mails'|i18n( 'extension/cjw_newsletter',, hash( '%count', $mailbox_item_list_count ) )}</span>
        <span class="a4-meta">{'Per page'|i18n( 'extension/cjw_newsletter' )}:
            {foreach array( 10, 25, 50, 100 ) as $n}{if eq( $limit, $n )}<strong aria-current="true">{$n}</strong>{else}<a href={concat( 'newsletter/mailbox_item_list/(limit)/', $n )|ezurl}>{$n}</a>{/if} {/foreach}
        </span>
        <div class="a4-pager">
        {include name='Navigator'
                 uri='design:navigator/google.tpl'
                 page_uri=$page_uri
                 item_count=$mailbox_item_list_count
                 view_parameters=$view_parameters
                 item_limit=$limit}
        </div>
    </div>
    {else}
    <p class="a4-empty">{'No mail was collected yet.'|i18n( 'extension/cjw_newsletter' )}</p>
    {/if}
</div>
{include uri='design:parts/newsletter/script.tpl'}
