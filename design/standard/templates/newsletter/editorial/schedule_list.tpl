{*  newsletter/editorial/schedule_list.tpl (cjw_newsletter 4.2.0, area editorial)

    The recurring sends. Variables: schedules, log, schedule_names (id => list name), result_names, enabled,
    last_run, confirm_remove (a schedule or null), notices.
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_editorial.css' ) )}
<div class="newsletter newsletter-schedule_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Recurring sends'|i18n( 'cjw_newsletter/editorial' )} [{$schedules|count}]</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        {if $confirm_remove}
        <form class="nl-confirm" action={'newsletter/schedule_list'|ezurl} method="post">
            <h2>{'Remove this recurring send?'|i18n( 'cjw_newsletter/editorial' )}</h2>
            <p>{$confirm_remove.list_name|wash}: {$confirm_remove.recurrence_text|wash}</p>
            <p>{'The editions and sends it made stay; its log is removed.'|i18n( 'cjw_newsletter/editorial' )}</p>
            <input class="button nl-danger" type="submit" name="ConfirmRemoveButton[{$confirm_remove.id}]" value="{'Yes, remove'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <a class="button" href={'newsletter/schedule_list'|ezurl}>{'Cancel'|i18n( 'cjw_newsletter/editorial' )}</a>
        </form>
        {/if}

        <div class="nl-status">
            <div class="nl-status-facts">
                {if $enabled}<span class="nl-pill is-ok">{'The cronjob runs the due sends'|i18n( 'cjw_newsletter/editorial' )}</span>
                {else}<span class="nl-pill is-warn">{'[ScheduleSettings] Schedules is disabled: the cronjob does not run them'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}
                <span>{if $last_run}{'Last run'|i18n( 'cjw_newsletter/editorial' )}: <time>{$last_run.time|l10n( 'shortdatetime' )}</time> ({$last_run.by|wash}){else}{'The recurring sends have not run yet.'|i18n( 'cjw_newsletter/editorial' )}{/if}</span>
            </div>
            <div>
                <a class="defaultbutton" href={'newsletter/schedule_edit/0'|ezurl}>{'New recurring send'|i18n( 'cjw_newsletter/editorial' )}</a>
                <a class="button" href={'newsletter/article_pool_list'|ezurl}>{'Article pools'|i18n( 'cjw_newsletter/editorial' )}</a>
            </div>
        </div>
        <p class="nl-hint">{'In cron: the part cjw_newsletter_mailqueue_create runs them. By hand:'|i18n( 'cjw_newsletter/editorial' )} <code>./console ext:cjw_newsletter:schedule run --dry-run</code></p>

        {if $schedules|count}
        <form action={'newsletter/schedule_list'|ezurl} method="post">
        <table class="list nl-table nl-ed-responsive">
            <tr class="nl-ed-head">
                <th class="nl-num">{'ID'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'List'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'What'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'When'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'Next run'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'Last run'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'State'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th class="tight">{'Actions'|i18n( 'cjw_newsletter/editorial' )}</th>
            </tr>
            {foreach $schedules as $s sequence array( 'bglight', 'bgdark' ) as $style}
            <tr class="{$style}">
                <td class="nl-num" data-label="{'ID'|i18n( 'cjw_newsletter/editorial' )|wash}">{$s.id}</td>
                <td class="nl-wrap" data-label="{'List'|i18n( 'cjw_newsletter/editorial' )|wash}">{if $s.list_node_id}<a href={concat( 'content/view/full/', $s.list_node_id )|ezurl}>{$s.list_name|wash}</a>{else}<span class="nl-danger">{'list removed'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}</td>
                <td class="nl-wrap" data-label="{'What'|i18n( 'cjw_newsletter/editorial' )|wash}">
                    {if eq( $s.mode, 'copy' )}{'Copy of "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $s.template_edition_name ) )|wash}{if $s.auto_fill}, {'filled from "%pool"'|i18n( 'cjw_newsletter/editorial',, hash( '%pool', $s.article_pool.label ) )|wash}{/if}
                    {else}{'The latest unsent edition'|i18n( 'cjw_newsletter/editorial' )}{/if}
                    {if $s.condition_handler}<br /><span class="nl-muted">{'with a condition'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}
                </td>
                <td data-label="{'When'|i18n( 'cjw_newsletter/editorial' )|wash}">{$s.recurrence_text|wash}<br /><span class="nl-muted">{$s.timezone_name|wash}</span></td>
                <td data-label="{'Next run'|i18n( 'cjw_newsletter/editorial' )|wash}">{if $s.next_run}<time>{$s.next_run|l10n( 'shortdatetime' )}</time>{if $s.is_due} <span class="nl-pill is-warn">{'due'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}{else}-{/if}</td>
                <td data-label="{'Last run'|i18n( 'cjw_newsletter/editorial' )|wash}">{if $s.last_run}<time>{$s.last_run|l10n( 'shortdatetime' )}</time><br /><span class="nl-pill {cond( eq( $s.last_result, 'sent' ), 'is-ok', eq( $s.last_result, 'failed' ), 'is-bad', 'is-muted' )}">{if is_set( $result_names[$s.last_result] )}{$result_names[$s.last_result]|wash}{else}{$s.last_result|wash}{/if}</span>{else}-{/if}</td>
                <td data-label="{'State'|i18n( 'cjw_newsletter/editorial' )|wash}">{if $s.is_active}<span class="nl-pill is-ok">{$s.status_name|wash}</span>{else}<span class="nl-pill is-muted">{$s.status_name|wash}</span>{/if}</td>
                <td class="nl-actions">
                    <a class="button" href={concat( 'newsletter/schedule_edit/', $s.id )|ezurl}>{'Edit'|i18n( 'cjw_newsletter/editorial' )}</a>
                    {if $s.is_active}<input class="button" type="submit" name="PauseButton[{$s.id}]" value="{'Pause'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                    {else}<input class="button" type="submit" name="ResumeButton[{$s.id}]" value="{'Resume'|i18n( 'cjw_newsletter/editorial' )|wash}" />{/if}
                    <input class="button" type="submit" name="DryRunButton[{$s.id}]" value="{'Try'|i18n( 'cjw_newsletter/editorial' )|wash}" title="{'Say what a run would do now, change nothing'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                    <input class="button" type="submit" name="RunButton[{$s.id}]" value="{'Run now'|i18n( 'cjw_newsletter/editorial' )|wash}" data-nl-confirm-button="{'Run this recurring send now? It makes the send at once.'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                    <input class="button" type="submit" name="RemoveButton[{$s.id}]" value="{'Remove'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                </td>
            </tr>
            {/foreach}
        </table>
        </form>
        {else}
        <div class="nl-empty">
            <p><strong>{'There is no recurring send yet.'|i18n( 'cjw_newsletter/editorial' )}</strong></p>
            <p>{'A recurring send sends a list on chosen weekdays, weekly or monthly: a copy of a template edition, or the latest edition that was not sent.'|i18n( 'cjw_newsletter/editorial' )}</p>
        </div>
        {/if}

        <section>
            <h2>{'Last runs'|i18n( 'cjw_newsletter/editorial' )}</h2>
            {if $log|count}
            <table class="list nl-table nl-ed-responsive">
                <tr class="nl-ed-head">
                    <th>{'Time'|i18n( 'cjw_newsletter/editorial' )}</th>
                    <th class="nl-num">{'Schedule'|i18n( 'cjw_newsletter/editorial' )}</th>
                    <th>{'Result'|i18n( 'cjw_newsletter/editorial' )}</th>
                    <th class="nl-num">{'Articles'|i18n( 'cjw_newsletter/editorial' )}</th>
                    <th>{'Details'|i18n( 'cjw_newsletter/editorial' )}</th>
                </tr>
                {foreach $log as $row sequence array( 'bglight', 'bgdark' ) as $style}
                <tr class="{$style}">
                    <td data-label="{'Time'|i18n( 'cjw_newsletter/editorial' )|wash}"><time>{$row.run_at|l10n( 'shortdatetime' )}</time></td>
                    <td class="nl-num" data-label="{'Schedule'|i18n( 'cjw_newsletter/editorial' )|wash}">#{$row.schedule_id}{if is_set( $schedule_names[$row.schedule_id] )} <span class="nl-muted">{$schedule_names[$row.schedule_id]|wash}</span>{/if}</td>
                    <td data-label="{'Result'|i18n( 'cjw_newsletter/editorial' )|wash}"><span class="nl-pill {cond( eq( $row.result, 'sent' ), 'is-ok', eq( $row.result, 'failed' ), 'is-bad', 'is-muted' )}">{if is_set( $result_names[$row.result] )}{$result_names[$row.result]|wash}{else}{$row.result|wash}{/if}</span></td>
                    <td class="nl-num" data-label="{'Articles'|i18n( 'cjw_newsletter/editorial' )|wash}">{$row.article_count}</td>
                    <td class="nl-wrap" data-label="{'Details'|i18n( 'cjw_newsletter/editorial' )|wash}">{$row.message|wash}{if $row.edition_send_id} <span class="nl-muted">({'send %id'|i18n( 'cjw_newsletter/editorial',, hash( '%id', $row.edition_send_id ) )})</span>{/if}</td>
                </tr>
                {/foreach}
            </table>
            {else}
            <p class="nl-muted">{'No run yet.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {/if}
        </section>
    </div>
</div>
</div>
{include uri='design:parts/newsletter/script.tpl'}
