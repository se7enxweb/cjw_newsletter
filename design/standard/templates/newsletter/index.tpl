{ezcss_require( 'newsletter_ui.css' )}
{def $newsletter_root_node_id = ezini( 'NewsletterSettings', 'RootFolderNodeId', 'cjw_newsletter.ini' )
     $page_uri = 'newsletter/index'
     $limit = 10
     $users = $summary.users
     $subs = $summary.subscriptions
     $sends = $summary.sends
     $runs = $summary.runs
     $transport = $summary.transport}

<div class="newsletter newsletter-index">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Newsletter dashboard'|i18n( 'cjw_newsletter/index' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        {include uri='design:parts/newsletter/job.tpl' job_id=$job_id}

        {if $summary.tables_missing|count}
        <div class="nl-confirm">
            <h2>{'The extension is not installed in this database yet'|i18n( 'extension/cjw_newsletter' )}</h2>
            <p>{'These tables are missing: %tables.'|i18n( 'extension/cjw_newsletter',, hash( '%tables', $summary.tables_missing|implode( ', ' ) ) )|wash}</p>
            <p class="nl-hint">{'They are created from the schema file share/db_schema.dba of the extension, by the setup wizard, by the installer or with the schema tools of Exponential.'|i18n( 'extension/cjw_newsletter' )}</p>
        </div>
        {else}

        {* Status strip. *}
        <div class="nl-status">
            <div class="nl-status-facts">
                <span><strong>{$summary.lists|count}</strong> {'lists'|i18n( 'extension/cjw_newsletter' )}</span>
                <span><strong>{$subs.approved}</strong> {'subscribers'|i18n( 'extension/cjw_newsletter' )}</span>
                <span><strong>{$summary.editions}</strong> {'editions'|i18n( 'extension/cjw_newsletter' )}</span>
                <span><strong>{$summary.blacklist}</strong> {'blacklisted'|i18n( 'extension/cjw_newsletter' )}</span>
            </div>
            <div>
                {if $transport.real}<span class="nl-pill is-ok">{'Sends by %method'|i18n( 'extension/cjw_newsletter',, hash( '%method', $transport.method ) )|wash}</span>
                {else}<span class="nl-pill is-info">{'Writes mails to files'|i18n( 'extension/cjw_newsletter' )}</span>{/if}
                {if $summary.problems|count}<span class="nl-pill is-warn">{'%count to look at'|i18n( 'extension/cjw_newsletter',, hash( '%count', $summary.problems|count ) )}</span>
                {else}<span class="nl-pill is-ok">{'No problems'|i18n( 'extension/cjw_newsletter' )}</span>{/if}
            </div>
        </div>

        <div class="nl-cards">
            <section class="nl-card">
                <h2>{'Subscribers'|i18n( 'extension/cjw_newsletter' )}</h2>
                <ul class="nl-stats">
                    <li><strong>{$users.total}</strong><span class="nl-muted">{'users'|i18n( 'extension/cjw_newsletter' )}</span></li>
                    <li><strong>{$subs.approved}</strong><span class="nl-muted">{'approved'|i18n( 'extension/cjw_newsletter' )}</span></li>
                    <li><strong>{sum( $users.pending, $subs.waiting )}</strong><span class="nl-muted">{'waiting'|i18n( 'extension/cjw_newsletter' )}</span></li>
                    <li><strong{if $users.bounced} class="nl-danger"{/if}>{$users.bounced}</strong><span class="nl-muted">{'bounced'|i18n( 'extension/cjw_newsletter' )}</span></li>
                </ul>
                <div class="nl-links">
                    <a class="button" href={'newsletter/user_list'|ezurl}>{'Users'|i18n( 'extension/cjw_newsletter' )}</a>
                    <a class="button" href={'newsletter/blacklist_item_list'|ezurl}>{'Blacklists'|i18n( 'extension/cjw_newsletter' )}</a>
                    <a class="button" href={'newsletter/import_list'|ezurl}>{'Imports'|i18n( 'extension/cjw_newsletter' )}</a>
                </div>
            </section>

            <section class="nl-card">
                <h2>{'Sending'|i18n( 'extension/cjw_newsletter' )}</h2>
                <ul class="nl-stats">
                    <li><strong>{sum( $sends.scheduled, $sends.waiting, $sends.queued )}</strong><span class="nl-muted">{'waiting'|i18n( 'extension/cjw_newsletter' )}</span></li>
                    <li><strong>{$sends.sending}</strong><span class="nl-muted">{'sending'|i18n( 'extension/cjw_newsletter' )}</span></li>
                    <li><strong>{$sends.finished}</strong><span class="nl-muted">{'finished'|i18n( 'extension/cjw_newsletter' )}</span></li>
                </ul>
                <p class="nl-muted">
                    {if $transport.real}{'Transport'|i18n( 'extension/cjw_newsletter' )}: <code>{$transport.method|wash}</code>
                    {else}{'Outbox'|i18n( 'extension/cjw_newsletter' )}: <code>{$transport.dir|wash}</code>, {'%count mails'|i18n( 'extension/cjw_newsletter',, hash( '%count', $transport.files ) )}{if $transport.last}, {'last'|i18n( 'extension/cjw_newsletter' )} <time>{$transport.last|l10n( 'shortdatetime' )}</time>{/if}{/if}
                </p>
                <p class="nl-muted">
                    {if $runs.queue_process}{'Last send run'|i18n( 'extension/cjw_newsletter' )}: <time>{$runs.queue_process.time|l10n( 'shortdatetime' )}</time> ({$runs.queue_process.by|wash}){else}{'The mail queue has not run yet.'|i18n( 'extension/cjw_newsletter' )}{/if}
                </p>
            </section>

            <section class="nl-card">
                <h2>{'Bounces'|i18n( 'extension/cjw_newsletter' )}</h2>
                <ul class="nl-stats">
                    <li><strong>{$summary.mailboxes.active}</strong><span class="nl-muted">{'active accounts'|i18n( 'extension/cjw_newsletter' )}</span></li>
                    <li><strong>{$summary.mailboxes.items}</strong><span class="nl-muted">{'collected mails'|i18n( 'extension/cjw_newsletter' )}</span></li>
                    <li><strong>{$summary.mailboxes.unparsed}</strong><span class="nl-muted">{'not parsed'|i18n( 'extension/cjw_newsletter' )}</span></li>
                </ul>
                <p class="nl-muted">
                    {if $runs.mailbox}{'Last run'|i18n( 'extension/cjw_newsletter' )}: <time>{$runs.mailbox.time|l10n( 'shortdatetime' )}</time> ({$runs.mailbox.by|wash}){else}{'The mail accounts have not been read yet.'|i18n( 'extension/cjw_newsletter' )}{/if}
                </p>
                <div class="nl-links">
                    <a class="button" href={'newsletter/mailbox_list'|ezurl}>{'Mail accounts'|i18n( 'extension/cjw_newsletter' )}</a>
                    <a class="button" href={'newsletter/mailbox_item_list'|ezurl}>{'Bounces'|i18n( 'extension/cjw_newsletter' )}</a>
                </div>
            </section>

            <section class="nl-card">
                <h2>{'Run now'|i18n( 'extension/cjw_newsletter' )}</h2>
                <p class="nl-muted">{'The cronjob parts do this on a schedule. Here you can run them by hand; the run goes on in the background.'|i18n( 'extension/cjw_newsletter' )}</p>
                <form action={'newsletter/index'|ezurl} method="post" data-nl-confirm="{'Create the mail queue and send the mails now?'|i18n( 'extension/cjw_newsletter' )|wash}">
                    <input class="button" type="submit" name="RunQueueButton" value="{'Send now'|i18n( 'extension/cjw_newsletter' )|wash}"{if $can_send|not} disabled="disabled"{/if} />
                    <input class="button" type="submit" name="RunQueueDryButton" value="{'Count only'|i18n( 'extension/cjw_newsletter' )|wash}" data-nl-confirm-button="" title="{'Say what a run would do, change nothing'|i18n( 'extension/cjw_newsletter' )|wash}"{if $can_send|not} disabled="disabled"{/if} />
                </form>
                <form action={'newsletter/index'|ezurl} method="post" data-nl-confirm="{'Read the mail accounts and parse the mails now?'|i18n( 'extension/cjw_newsletter' )|wash}">
                    <input class="button" type="submit" name="RunMailboxButton" value="{'Process the mail accounts'|i18n( 'extension/cjw_newsletter' )|wash}"{if or( $can_mailbox|not, $summary.mailboxes.active|eq( 0 ) )} disabled="disabled"{/if} />
                </form>
                <p class="nl-hint">{'In cron:'|i18n( 'extension/cjw_newsletter' )} <code>php runcronjobs.php cjw_newsletter</code> &middot; <code>php runcronjobs.php cjw_newsletter_mailbox</code></p>
            </section>
        </div>

        {* the blocks the feature areas add to the dashboard ([ExtensionPointSettings] DashboardBlocks[]); each gets
           summary, its own data is in summary.areas.<handler class> *}
        {foreach ezini( 'ExtensionPointSettings', 'DashboardBlocks', 'cjw_newsletter.ini' ) as $cjwnl_block}
        {include uri=$cjwnl_block summary=$summary can_send=$can_send can_admin=$can_admin}
        {/foreach}

        <section>
            <h2>{'Problems and hints'|i18n( 'extension/cjw_newsletter' )}</h2>
            {if $summary.problems|count}
            <ul class="nl-problems">
                {foreach $summary.problems as $problem}
                <li>
                    <span class="nl-pill {cond( eq( $problem.level, 'error' ), 'is-bad', eq( $problem.level, 'warning' ), 'is-warn', 'is-info' )}">{cond( eq( $problem.level, 'error' ), 'Problem'|i18n( 'extension/cjw_newsletter' ), eq( $problem.level, 'warning' ), 'Warning'|i18n( 'extension/cjw_newsletter' ), 'Hint'|i18n( 'extension/cjw_newsletter' ) )}</span>
                    <span>{$problem.text|wash}{if $problem.url} <a href={$problem.url|ezurl}>{'Open'|i18n( 'extension/cjw_newsletter' )}</a>{/if}</span>
                    {if and( is_set( $problem.action ), $problem.action|eq( 'repair' ), $can_admin )}
                    <form action={'newsletter/index'|ezurl} method="post" data-nl-confirm="{'Remove these subscriptions and mails? This cannot be undone.'|i18n( 'extension/cjw_newsletter' )|wash}">
                        <input class="button" type="submit" name="RepairButton" value="{'Remove them'|i18n( 'extension/cjw_newsletter' )|wash}" />
                    </form>
                    {/if}
                </li>
                {/foreach}
            </ul>
            {else}
            <p class="nl-ok">{'Nothing to report: the lists have subscribers, the mail queue runs and nothing is stuck.'|i18n( 'extension/cjw_newsletter' )}</p>
            {/if}
        </section>

        <section>
            <h2>{'Lists'|i18n( 'extension/cjw_newsletter' )}</h2>
            {if $summary.lists|count}
            <table class="list nl-table">
                <tr><th>{'Name'|i18n( 'extension/cjw_newsletter' )}</th><th class="nl-num">{'Approved'|i18n( 'extension/cjw_newsletter' )}</th><th class="nl-num">{'Waiting'|i18n( 'extension/cjw_newsletter' )}</th><th class="nl-num">{'All'|i18n( 'extension/cjw_newsletter' )}</th><th class="tight">{'Actions'|i18n( 'extension/cjw_newsletter' )}</th></tr>
                {foreach $summary.lists as $list sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td class="nl-wrap"><a href={concat( 'newsletter/subscription_list/', $list.node_id )|ezurl}>{$list.name|wash}</a></td>
                    <td class="nl-num">{$list.approved}</td><td class="nl-num">{$list.pending}</td><td class="nl-num">{$list.total}</td>
                    <td class="nl-actions"><a class="button" href={concat( 'newsletter/subscription_list/', $list.node_id )|ezurl}>{'Subscriptions'|i18n( 'extension/cjw_newsletter' )}</a>
                        <a class="button" href={concat( 'newsletter/subscription_list_csvimport/', $list.node_id )|ezurl}>{'Import CSV'|i18n( 'extension/cjw_newsletter' )}</a></td>
                </tr>
                {/foreach}
            </table>
            {else}
            <div class="nl-empty"><p><strong>{'There is no newsletter list yet.'|i18n( 'extension/cjw_newsletter' )}</strong></p></div>
            {/if}
        </section>

        <section>
            <h2>{'Last sends'|i18n( 'extension/cjw_newsletter' )}</h2>
            {if $summary.last_sends|count}
            <table class="list nl-table">
                <tr><th>{'Edition'|i18n( 'extension/cjw_newsletter' )}</th><th>{'Status'|i18n( 'extension/cjw_newsletter' )}</th><th class="nl-num">{'Mails'|i18n( 'extension/cjw_newsletter' )}</th><th class="nl-num">{'Sent'|i18n( 'extension/cjw_newsletter' )}</th><th class="nl-num">{'Failed'|i18n( 'extension/cjw_newsletter' )}</th><th>{'Created'|i18n( 'extension/cjw_newsletter' )}</th></tr>
                {foreach $summary.last_sends as $send sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td class="nl-wrap">{if $send.node_id}<a href={concat( 'content/view/full/', $send.node_id )|ezurl}>{$send.name|wash}</a>{else}{$send.name|wash}{/if}</td>
                    <td><span class="nl-pill {cond( eq( $send.status_code, 3 ), 'is-ok', eq( $send.status_code, 9 ), 'is-bad', 'is-warn' )}">{$send.status|i18n( 'extension/cjw_newsletter' )}</span></td>
                    <td class="nl-num">{$send.items.all}</td><td class="nl-num">{$send.items.sent}</td>
                    <td class="nl-num">{if $send.items.failed}<strong class="nl-danger">{$send.items.failed}</strong>{else}0{/if}</td>
                    <td>{$send.created|l10n( 'shortdatetime' )}</td>
                </tr>
                {/foreach}
            </table>
            {else}
            <p class="nl-muted">{'Nothing was sent yet.'|i18n( 'extension/cjw_newsletter' )}</p>
            {/if}
        </section>

        {if $newsletter_root_node_id|gt( 1 )}
        <section>
            <h2>{'Newsletter systems'|i18n( 'extension/cjw_newsletter' )}</h2>
            {def $newsletter_system_node_list = fetch( 'content', 'tree',
                                                       hash( 'parent_node_id', $newsletter_root_node_id,
                                                             'class_filter_type', 'include',
                                                             'class_filter_array', array( 'cjw_newsletter_system' ),
                                                             'sort_by', array( 'name', true() ) ) )}
            {foreach $newsletter_system_node_list as $newsletter_system_node}
                {include uri='design:newsletter/index_newsletter_system_info_box.tpl'
                         name='NlSystemBox'
                         newsletter_system_node=$newsletter_system_node}
            {/foreach}
            {undef $newsletter_system_node_list}
        </section>

        {def $last_edition_node_list = fetch( 'content', 'tree',
                                              hash( 'parent_node_id', $newsletter_root_node_id,
                                                    'class_filter_type', 'include',
                                                    'class_filter_array', array( 'cjw_newsletter_edition' ),
                                                    'limit', $limit,
                                                    'offset', $view_parameters.offset,
                                                    'sort_by', array( 'modified', false() ) ) )
             $last_edition_node_list_count = fetch( 'content', 'tree_count',
                                                    hash( 'parent_node_id', $newsletter_root_node_id,
                                                          'class_filter_type', 'include',
                                                          'class_filter_array', array( 'cjw_newsletter_edition' ) ) )}
        <section>
            <h2>{'Last actions'|i18n( 'cjw_newsletter/index' )}</h2>
            {include uri='design:includes/cjwnewsletteredition_statistic_list.tpl'
                     name='EditionList'
                     edition_node_list=$last_edition_node_list
                     edition_node_list_count=$last_edition_node_list_count
                     show_actions_colum=false()}
            <div class="nl-pager">
                {include name='Navigator'
                         uri='design:navigator/google.tpl'
                         page_uri=$page_uri
                         item_count=$last_edition_node_list_count
                         view_parameters=$view_parameters
                         item_limit=$limit}
            </div>
        </section>
        {/if}

        {/if}
    </div>
</div>
</div>
{include uri='design:parts/newsletter/script.tpl'}
