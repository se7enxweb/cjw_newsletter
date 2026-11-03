{* The recurring sends, admin4. Same data and actions as design/standard/templates/newsletter/editorial/schedule_list.tpl.
   Variables: schedules, log, schedule_names (id => list name), result_names, enabled, last_run, confirm_remove (a
   schedule or null), notices. *}
<div class="a4-page newsletter newsletter-schedule_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Recurring sends'|i18n( 'cjw_newsletter/editorial' )} [{$schedules|count}]</h1>
        </div>
        <div class="a4-pagehead-actions">
            <a class="a4-btn a4-btn-primary" href={'newsletter/schedule_edit/0'|ezurl}>{'New recurring send'|i18n( 'cjw_newsletter/editorial' )}</a>
            <a class="a4-btn" href={'newsletter/article_pool_list'|ezurl}>{'Article pools'|i18n( 'cjw_newsletter/editorial' )}</a>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    {if $confirm_remove}
    <form class="a4-notice is-warn" action={'newsletter/schedule_list'|ezurl} method="post">
        <h2 class="a4-notice-title">{'Remove this recurring send?'|i18n( 'cjw_newsletter/editorial' )}</h2>
        <p>{$confirm_remove.list_name|wash}: {$confirm_remove.recurrence_text|wash}</p>
        <p>{'The editions and sends it made stay; its log is removed.'|i18n( 'cjw_newsletter/editorial' )}</p>
        <div class="a4-actions">
            <input class="a4-btn a4-btn-danger nl-danger" type="submit" name="ConfirmRemoveButton[{$confirm_remove.id}]" value="{'Yes, remove'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <a class="a4-btn" href={'newsletter/schedule_list'|ezurl}>{'Cancel'|i18n( 'cjw_newsletter/editorial' )}</a>
        </div>
    </form>
    {/if}

    <div class="cjwnl-a4-state">
        {if $enabled}<span class="a4-badge is-ok">{'The cronjob runs the due sends'|i18n( 'cjw_newsletter/editorial' )}</span>
        {else}<span class="a4-badge is-warn">{'[ScheduleSettings] Schedules is disabled: the cronjob does not run them'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}
        <span>{if $last_run}{'Last run'|i18n( 'cjw_newsletter/editorial' )}: <time>{$last_run.time|l10n( 'shortdatetime' )}</time> ({$last_run.by|wash}){else}{'The recurring sends have not run yet.'|i18n( 'cjw_newsletter/editorial' )}{/if}</span>
    </div>
    <p class="a4-help">{'In cron: the part cjw_newsletter_mailqueue_create runs them. By hand:'|i18n( 'cjw_newsletter/editorial' )} <code>./console ext:cjw_newsletter:schedule run --dry-run</code></p>

    {if $schedules|count}
    <form action={'newsletter/schedule_list'|ezurl} method="post">
    <div class="a4-table-wrap">
    <table class="a4-table">
        <thead>
        <tr>
            <th scope="col" class="a4-num">{'ID'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col">{'What'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col">{'When'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col">{'Runs'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col" class="tight">{'Actions'|i18n( 'cjw_newsletter/editorial' )}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $schedules as $s}
        <tr>
            <td class="a4-num">{$s.id}</td>
            <td class="a4-wrap">
                <strong>{if $s.list_node_id}<a href={concat( 'content/view/full/', $s.list_node_id )|ezurl}>{$s.list_name|wash}</a>{else}<span class="a4-bad-text nl-danger">{'list removed'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}</strong><br />
                {if eq( $s.mode, 'copy' )}{'Copy of "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $s.template_edition_name ) )|wash}{if $s.auto_fill}, {'filled from "%pool"'|i18n( 'cjw_newsletter/editorial',, hash( '%pool', $s.article_pool.label ) )|wash}{/if}
                {else}{'The latest unsent edition'|i18n( 'cjw_newsletter/editorial' )}{/if}
                {if $s.condition_handler}<br /><span class="a4-muted">{'with a condition'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}
            </td>
            <td>{$s.recurrence_text|wash}<br /><span class="a4-muted">{$s.timezone_name|wash}</span></td>
            <td>
                {if $s.is_active}<span class="a4-badge is-ok">{$s.status_name|wash}</span>{else}<span class="a4-badge">{$s.status_name|wash}</span>{/if}{if $s.is_due} <span class="a4-badge is-warn">{'due'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}<br />
                {'Next run'|i18n( 'cjw_newsletter/editorial' )}: {if $s.next_run}<time>{$s.next_run_text|wash}</time>{else}-{/if}<br />
                {'Last run'|i18n( 'cjw_newsletter/editorial' )}: {if $s.last_run}<time>{$s.last_run|l10n( 'shortdatetime' )}</time> <span class="a4-badge{cond( eq( $s.last_result, 'sent' ), ' is-ok', eq( $s.last_result, 'failed' ), ' is-bad', '' )}">{if is_set( $result_names[$s.last_result] )}{$result_names[$s.last_result]|wash}{else}{$s.last_result|wash}{/if}</span>{else}-{/if}
            </td>
            <td>
                <div class="a4-actions">
                    <a class="a4-btn a4-btn-small" href={concat( 'newsletter/schedule_edit/', $s.id )|ezurl}>{'Edit'|i18n( 'cjw_newsletter/editorial' )}</a>
                    {if $s.is_active}<input class="a4-btn a4-btn-small" type="submit" name="PauseButton[{$s.id}]" value="{'Pause'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                    {else}<input class="a4-btn a4-btn-small" type="submit" name="ResumeButton[{$s.id}]" value="{'Resume'|i18n( 'cjw_newsletter/editorial' )|wash}" />{/if}
                    <input class="a4-btn a4-btn-small" type="submit" name="DryRunButton[{$s.id}]" value="{'Try'|i18n( 'cjw_newsletter/editorial' )|wash}" title="{'Say what a run would do now, change nothing'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                    <input class="a4-btn a4-btn-small" type="submit" name="RunButton[{$s.id}]" value="{'Run now'|i18n( 'cjw_newsletter/editorial' )|wash}" data-nl-confirm-button="{'Run this recurring send now? It makes the send at once.'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                    <input class="a4-btn a4-btn-small" type="submit" name="RemoveButton[{$s.id}]" value="{'Remove'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                </div>
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
    </div>
    </form>
    {else}
    <div class="a4-empty">
        <p><strong>{'There is no recurring send yet.'|i18n( 'cjw_newsletter/editorial' )}</strong></p>
        <p>{'A recurring send sends a list on chosen weekdays, weekly or monthly: a copy of a template edition, or the latest edition that was not sent.'|i18n( 'cjw_newsletter/editorial' )}</p>
    </div>
    {/if}

    <section class="a4-section">
        <h2 class="a4-h2">{'Last runs'|i18n( 'cjw_newsletter/editorial' )}</h2>
        {if $log|count}
        <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr>
                <th scope="col">{'Time'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th scope="col" class="a4-num">{'Schedule'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th scope="col">{'Result'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th scope="col" class="a4-num">{'Articles'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th scope="col">{'Details'|i18n( 'cjw_newsletter/editorial' )}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $log as $row}
            <tr>
                <td><time>{$row.run_at|l10n( 'shortdatetime' )}</time></td>
                <td class="a4-num">#{$row.schedule_id}{if is_set( $schedule_names[$row.schedule_id] )} <span class="a4-muted">{$schedule_names[$row.schedule_id]|wash}</span>{/if}</td>
                <td><span class="a4-badge{cond( eq( $row.result, 'sent' ), ' is-ok', eq( $row.result, 'failed' ), ' is-bad', '' )}">{if is_set( $result_names[$row.result] )}{$result_names[$row.result]|wash}{else}{$row.result|wash}{/if}</span></td>
                <td class="a4-num">{$row.article_count}</td>
                <td class="a4-wrap">{$row.message|wash}{if $row.edition_send_id} <span class="a4-muted">({'send %id'|i18n( 'cjw_newsletter/editorial',, hash( '%id', $row.edition_send_id ) )})</span>{/if}</td>
            </tr>
            {/foreach}
            </tbody>
        </table>
        </div>
        {else}
        <p class="a4-empty">{'No run yet.'|i18n( 'cjw_newsletter/editorial' )}</p>
        {/if}
    </section>
</div>
{include uri='design:parts/newsletter/script.tpl'}
