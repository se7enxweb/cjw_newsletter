{* The newsletter dashboard in admin4: status strip, the cards (subscribers, sending, bounces, run now), the blocks of the
   feature areas, problems, lists, last sends, systems and last actions.
   Same data and actions as design/standard/templates/newsletter/index.tpl *}
{def $newsletter_root_node_id = ezini( 'NewsletterSettings', 'RootFolderNodeId', 'cjw_newsletter.ini' )
     $page_uri = 'newsletter/index'
     $limit = 10
     $users = $summary.users
     $subs = $summary.subscriptions
     $sends = $summary.sends
     $runs = $summary.runs
     $transport = $summary.transport}

<div class="a4-page newsletter newsletter-index">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-speedometer2 cjwnl-a4-lead" aria-hidden="true"></span>{'Newsletter dashboard'|i18n( 'cjw_newsletter/index' )}</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    {include uri='design:parts/newsletter/job.tpl' job_id=$job_id}

    {if $summary.tables_missing|count}
    <div class="a4-notice is-warn" role="alert">
        <h2 class="a4-h2">{'The extension is not installed in this database yet'|i18n( 'extension/cjw_newsletter' )}</h2>
        <p>{'These tables are missing: %tables.'|i18n( 'extension/cjw_newsletter',, hash( '%tables', $summary.tables_missing|implode( ', ' ) ) )|wash}</p>
        <p class="a4-meta">{'They are created from the schema file share/db_schema.dba of the extension, by the setup wizard, by the installer or with the schema tools of Exponential.'|i18n( 'extension/cjw_newsletter' )}</p>
    </div>
    {else}

    {* Status strip. *}
    <div class="a4-card cjwnl-a4-status">
        <dl class="a4-facts cjwnl-a4-status-facts">
            <div><dt>{'lists'|i18n( 'extension/cjw_newsletter' )}</dt><dd>{$summary.lists|count}</dd></div>
            <div><dt>{'subscribers'|i18n( 'extension/cjw_newsletter' )}</dt><dd>{$subs.approved}</dd></div>
            <div><dt>{'editions'|i18n( 'extension/cjw_newsletter' )}</dt><dd>{$summary.editions}</dd></div>
            <div><dt>{'blacklisted'|i18n( 'extension/cjw_newsletter' )}</dt><dd>{$summary.blacklist}</dd></div>
        </dl>
        <div class="a4-badges">
            {if $transport.real}<span class="a4-badge is-ok">{'Sends by %method'|i18n( 'extension/cjw_newsletter',, hash( '%method', $transport.method ) )|wash}</span>
            {else}<span class="a4-badge is-info">{'Writes mails to files'|i18n( 'extension/cjw_newsletter' )}</span>{/if}
            {if $summary.problems|count}<span class="a4-badge is-warn">{'%count to look at'|i18n( 'extension/cjw_newsletter',, hash( '%count', $summary.problems|count ) )}</span>
            {else}<span class="a4-badge is-ok">{'No problems'|i18n( 'extension/cjw_newsletter' )}</span>{/if}
        </div>
    </div>

    <div class="a4-grid is-wide cjwnl-a4-cards">
        <section class="a4-card a4-stack cjwnl-a4-card">
            <h2 class="a4-h2">{'Subscribers'|i18n( 'extension/cjw_newsletter' )}</h2>
            <ul class="a4-figures">
                <li class="a4-figure"><strong>{$users.total}</strong><span>{'users'|i18n( 'extension/cjw_newsletter' )}</span></li>
                <li class="a4-figure"><strong>{$subs.approved}</strong><span>{'approved'|i18n( 'extension/cjw_newsletter' )}</span></li>
                <li class="a4-figure"><strong>{sum( $users.pending, $subs.waiting )}</strong><span>{'waiting'|i18n( 'extension/cjw_newsletter' )}</span></li>
                <li class="a4-figure{if $users.bounced} is-bad{/if}"><strong{if $users.bounced} class="nl-danger"{/if}>{$users.bounced}</strong><span>{'bounced'|i18n( 'extension/cjw_newsletter' )}</span></li>
            </ul>
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/user_list'|ezurl}><span class="a4-icon a4-bi-people" aria-hidden="true"></span>{'Users'|i18n( 'extension/cjw_newsletter' )}</a>
                <a class="a4-btn" href={'newsletter/blacklist_item_list'|ezurl}><span class="a4-icon a4-bi-lock" aria-hidden="true"></span>{'Blacklists'|i18n( 'extension/cjw_newsletter' )}</a>
                <a class="a4-btn" href={'newsletter/import_list'|ezurl}><span class="a4-icon a4-bi-upload" aria-hidden="true"></span>{'Imports'|i18n( 'extension/cjw_newsletter' )}</a>
            </div>
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h2 class="a4-h2">{'Sending'|i18n( 'extension/cjw_newsletter' )}</h2>
            <ul class="a4-figures">
                <li class="a4-figure"><strong>{sum( $sends.scheduled, $sends.waiting, $sends.queued )}</strong><span>{'waiting'|i18n( 'extension/cjw_newsletter' )}</span></li>
                <li class="a4-figure"><strong>{$sends.sending}</strong><span>{'sending'|i18n( 'extension/cjw_newsletter' )}</span></li>
                <li class="a4-figure"><strong>{$sends.finished}</strong><span>{'finished'|i18n( 'extension/cjw_newsletter' )}</span></li>
            </ul>
            <p class="a4-meta">
                {if $transport.real}{'Transport'|i18n( 'extension/cjw_newsletter' )}: <code>{$transport.method|wash}</code>
                {else}{'Outbox'|i18n( 'extension/cjw_newsletter' )}: <code>{$transport.dir|wash}</code>, {'%count mails'|i18n( 'extension/cjw_newsletter',, hash( '%count', $transport.files ) )}{if $transport.last}, {'last'|i18n( 'extension/cjw_newsletter' )} <time>{$transport.last|l10n( 'shortdatetime' )}</time>{/if}{/if}
            </p>
            <p class="a4-meta">
                {if $runs.queue_process}{'Last send run'|i18n( 'extension/cjw_newsletter' )}: <time>{$runs.queue_process.time|l10n( 'shortdatetime' )}</time> ({$runs.queue_process.by|wash}){else}{'The mail queue has not run yet.'|i18n( 'extension/cjw_newsletter' )}{/if}
            </p>
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h2 class="a4-h2">{'Bounces'|i18n( 'extension/cjw_newsletter' )}</h2>
            <ul class="a4-figures">
                <li class="a4-figure"><strong>{$summary.mailboxes.active}</strong><span>{'active accounts'|i18n( 'extension/cjw_newsletter' )}</span></li>
                <li class="a4-figure"><strong>{$summary.mailboxes.items}</strong><span>{'collected mails'|i18n( 'extension/cjw_newsletter' )}</span></li>
                <li class="a4-figure"><strong>{$summary.mailboxes.unparsed}</strong><span>{'not parsed'|i18n( 'extension/cjw_newsletter' )}</span></li>
            </ul>
            <p class="a4-meta">
                {if $runs.mailbox}{'Last run'|i18n( 'extension/cjw_newsletter' )}: <time>{$runs.mailbox.time|l10n( 'shortdatetime' )}</time> ({$runs.mailbox.by|wash}){else}{'The mail accounts have not been read yet.'|i18n( 'extension/cjw_newsletter' )}{/if}
            </p>
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/mailbox_list'|ezurl}><span class="a4-icon a4-bi-inbox" aria-hidden="true"></span>{'Mail accounts'|i18n( 'extension/cjw_newsletter' )}</a>
                <a class="a4-btn" href={'newsletter/mailbox_item_list'|ezurl}><span class="a4-icon a4-bi-exclamation-triangle" aria-hidden="true"></span>{'Bounces'|i18n( 'extension/cjw_newsletter' )}</a>
            </div>
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h2 class="a4-h2">{'Run now'|i18n( 'extension/cjw_newsletter' )}</h2>
            <p class="a4-meta">{'The cronjob parts do this on a schedule. Here you can run them by hand; the run goes on in the background.'|i18n( 'extension/cjw_newsletter' )}</p>
            <div class="a4-actions">
                <form action={'newsletter/index'|ezurl} method="post" data-nl-confirm="{'Create the mail queue and send the mails now?'|i18n( 'extension/cjw_newsletter' )|wash}">
                    <input class="a4-btn a4-btn-primary" type="submit" name="RunQueueButton" value="{'Send now'|i18n( 'extension/cjw_newsletter' )|wash}"{if $can_send|not} disabled="disabled"{/if} />
                    <input class="a4-btn" type="submit" name="RunQueueDryButton" value="{'Count only'|i18n( 'extension/cjw_newsletter' )|wash}" data-nl-confirm-button="" title="{'Say what a run would do, change nothing'|i18n( 'extension/cjw_newsletter' )|wash}"{if $can_send|not} disabled="disabled"{/if} />
                </form>
                <form action={'newsletter/index'|ezurl} method="post" data-nl-confirm="{'Read the mail accounts and parse the mails now?'|i18n( 'extension/cjw_newsletter' )|wash}">
                    <input class="a4-btn" type="submit" name="RunMailboxButton" value="{'Process the mail accounts'|i18n( 'extension/cjw_newsletter' )|wash}"{if or( $can_mailbox|not, $summary.mailboxes.active|eq( 0 ) )} disabled="disabled"{/if} />
                </form>
            </div>
            <p class="a4-meta">{'In cron:'|i18n( 'extension/cjw_newsletter' )} <code>php runcronjobs.php cjw_newsletter</code> &middot; <code>php runcronjobs.php cjw_newsletter_mailbox</code></p>
        </section>
    </div>

    {* the blocks the feature areas add to the dashboard ([ExtensionPointSettings] DashboardBlocks[]); each gets
       summary, its own data is in summary.areas.<handler class> *}
    {foreach ezini( 'ExtensionPointSettings', 'DashboardBlocks', 'cjw_newsletter.ini' ) as $cjwnl_block}
    {include uri=$cjwnl_block summary=$summary can_send=$can_send can_admin=$can_admin}
    {/foreach}

    <section class="a4-section">
        <h2 class="a4-h2">{'Problems and hints'|i18n( 'extension/cjw_newsletter' )}</h2>
        {if $summary.problems|count}
        <ul class="cjwnl-a4-list">
            {foreach $summary.problems as $problem}
            <li>
                <span class="a4-badge {cond( eq( $problem.level, 'error' ), 'is-bad', eq( $problem.level, 'warning' ), 'is-warn', 'is-info' )}">{cond( eq( $problem.level, 'error' ), 'Problem'|i18n( 'extension/cjw_newsletter' ), eq( $problem.level, 'warning' ), 'Warning'|i18n( 'extension/cjw_newsletter' ), 'Hint'|i18n( 'extension/cjw_newsletter' ) )}</span>
                <span>{$problem.text|wash}{if $problem.url} <a href={$problem.url|ezurl}>{'Open'|i18n( 'extension/cjw_newsletter' )}</a>{/if}</span>
                {if and( is_set( $problem.action ), $problem.action|eq( 'repair' ), $can_admin )}
                <form action={'newsletter/index'|ezurl} method="post" data-nl-confirm="{'Remove these subscriptions and mails? This cannot be undone.'|i18n( 'extension/cjw_newsletter' )|wash}">
                    <input class="a4-btn a4-btn-danger a4-btn-small" type="submit" name="RepairButton" value="{'Remove them'|i18n( 'extension/cjw_newsletter' )|wash}" />
                </form>
                {/if}
            </li>
            {/foreach}
        </ul>
        {else}
        <p class="a4-notice is-ok" role="status">{'Nothing to report: the lists have subscribers, the mail queue runs and nothing is stuck.'|i18n( 'extension/cjw_newsletter' )}</p>
        {/if}
    </section>

    <section class="a4-section">
        <h2 class="a4-h2">{'Lists'|i18n( 'extension/cjw_newsletter' )}</h2>
        {if $summary.lists|count}
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                    <tr><th scope="col">{'Name'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col" class="a4-num">{'Approved'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col" class="a4-num">{'Waiting'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col" class="a4-num">{'All'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col">{'Actions'|i18n( 'extension/cjw_newsletter' )}</th></tr>
                </thead>
                <tbody>
                {foreach $summary.lists as $list}
                    <tr>
                        <td><a href={concat( 'newsletter/subscription_list/', $list.node_id )|ezurl}>{$list.name|wash}</a></td>
                        <td class="a4-num">{$list.approved}</td><td class="a4-num">{$list.pending}</td><td class="a4-num">{$list.total}</td>
                        <td><div class="a4-actions"><a class="a4-btn a4-btn-small" href={concat( 'newsletter/subscription_list/', $list.node_id )|ezurl}>{'Subscriptions'|i18n( 'extension/cjw_newsletter' )}</a>
                            <a class="a4-btn a4-btn-small" href={concat( 'newsletter/subscription_list_csvimport/', $list.node_id )|ezurl}>{'Import CSV'|i18n( 'extension/cjw_newsletter' )}</a></div></td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        {else}
        <p class="a4-empty"><strong>{'There is no newsletter list yet.'|i18n( 'extension/cjw_newsletter' )}</strong></p>
        {/if}
    </section>

    <section class="a4-section">
        <h2 class="a4-h2">{'Last sends'|i18n( 'extension/cjw_newsletter' )}</h2>
        {if $summary.last_sends|count}
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                    <tr><th scope="col">{'Edition'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col">{'Status'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col" class="a4-num">{'Mails'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col" class="a4-num">{'Sent'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col" class="a4-num">{'Failed'|i18n( 'extension/cjw_newsletter' )}</th><th scope="col">{'Created'|i18n( 'extension/cjw_newsletter' )}</th></tr>
                </thead>
                <tbody>
                {foreach $summary.last_sends as $send}
                    <tr>
                        <td>{if $send.node_id}<a href={concat( 'content/view/full/', $send.node_id )|ezurl}>{$send.name|wash}</a>{else}{$send.name|wash}{/if}</td>
                        <td><span class="a4-badge {cond( eq( $send.status_code, 3 ), 'is-ok', eq( $send.status_code, 9 ), 'is-bad', 'is-warn' )}">{$send.status|i18n( 'extension/cjw_newsletter' )}</span></td>
                        <td class="a4-num">{$send.items.all}</td><td class="a4-num">{$send.items.sent}</td>
                        <td class="a4-num">{if $send.items.failed}<strong class="nl-danger a4-bad-text">{$send.items.failed}</strong>{else}0{/if}</td>
                        <td>{$send.created|l10n( 'shortdatetime' )}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        {else}
        <p class="a4-empty">{'Nothing was sent yet.'|i18n( 'extension/cjw_newsletter' )}</p>
        {/if}
    </section>

    {if $newsletter_root_node_id|gt( 1 )}
    <section class="a4-section">
        <h2 class="a4-h2">{'Newsletter systems'|i18n( 'extension/cjw_newsletter' )}</h2>
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
    <section class="a4-section">
        <h2 class="a4-h2">{'Last actions'|i18n( 'cjw_newsletter/index' )}</h2>
        {include uri='design:includes/cjwnewsletteredition_statistic_list.tpl'
                 name='EditionList'
                 edition_node_list=$last_edition_node_list
                 edition_node_list_count=$last_edition_node_list_count
                 show_actions_colum=false()}
        <div class="a4-pager">
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
{include uri='design:parts/newsletter/script.tpl'}
