{* newsletter/migration_log: the runs of ext:cjw_newsletter:import-eznewsletter *}
{ezcss_require( 'newsletter_ui.css' )}
{ezcss_require( 'newsletter_importexport.css' )}
<div class="newsletter newsletter-migration_log">
<div class="context-block nl nl-ie">
    <div class="box-header">
        <h1 class="context-title">{'eznewsletter migration'|i18n( 'cjw_newsletter/importexport' )} [{$run_count}]</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        <p class="nl-muted">{'A site that ran the old eznewsletter extension can take its lists, subscribers and subscriptions over with a console command. The old tables are only read, never changed; every row read is logged here, and a second run skips what an earlier run took over.'|i18n( 'cjw_newsletter/importexport' )}</p>

        <section class="nl-ie-section">
            <h2>{'Old tables in this database'|i18n( 'cjw_newsletter/importexport' )}</h2>
            {if $tables_found|count}
            <dl class="nl-kv">
                {foreach $tables_found as $found_table => $found_rows}<dt><code>{$found_table|wash}</code></dt><dd>{'%count rows'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $found_rows ) )}</dd>{/foreach}
            </dl>
            {else}
            <p class="nl-muted">{'None: the old extension did not run on this database. A copy of the old data can be read from an SQLite file with --source-sqlite.'|i18n( 'cjw_newsletter/importexport' )}</p>
            {/if}
            <p class="nl-hint">{'First a dry run, then the run:'|i18n( 'cjw_newsletter/importexport' )}</p>
            <pre class="nl-log nl-ie-command">./console ext:cjw_newsletter:import-eznewsletter --dry-run --list-map=1:{'<list object id>'|i18n( 'cjw_newsletter/importexport' )|wash}
./console ext:cjw_newsletter:import-eznewsletter --list-map=1:{'<list object id>'|i18n( 'cjw_newsletter/importexport' )|wash}
./console ext:cjw_newsletter:import-eznewsletter --create-lists --system-node={'<node id>'|i18n( 'cjw_newsletter/importexport' )|wash}</pre>
        </section>

        <section class="nl-ie-section">
            <h2>{'Runs'|i18n( 'cjw_newsletter/importexport' )}</h2>
            {if $runs|count}
            <div class="nl-ie-scroll">
            <table class="list nl-table">
                <tr>
                    <th>{'Run'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th>{'Started'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th>{'Kind'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th class="nl-num">{'Created'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th class="nl-num">{'Merged'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th class="nl-num">{'Skipped'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th class="nl-num">{'Failed'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th class="nl-num">{'Sends'|i18n( 'cjw_newsletter/importexport' )}</th>
                </tr>
                {foreach $runs as $run sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td class="nl-wrap"><a href={concat( 'newsletter/migration_log/', $run.run_id )|ezurl}><code>{$run.run_id|wash}</code></a></td>
                    <td>{$run.started|l10n( 'shortdatetime' )}</td>
                    <td>{if $run.dry_run}<span class="nl-pill is-info">{'dry run'|i18n( 'cjw_newsletter/importexport' )}</span>{else}<span class="nl-pill is-ok">{'run'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}</td>
                    <td class="nl-num">{$run.created}</td>
                    <td class="nl-num">{$run.merged}</td>
                    <td class="nl-num">{$run.skipped}</td>
                    <td class="nl-num">{if $run.failed}<strong class="nl-danger">{$run.failed}</strong>{else}0{/if}</td>
                    <td class="nl-num">{$run.recorded}</td>
                </tr>
                {/foreach}
            </table>
            </div>
            <div class="nl-pager">
                {include name='Navigator' uri='design:navigator/google.tpl' page_uri='newsletter/migration_log' item_count=$run_count view_parameters=$view_parameters item_limit=$limit}
            </div>
            {else}
            <div class="nl-empty"><p><strong>{'No run yet.'|i18n( 'cjw_newsletter/importexport' )}</strong></p></div>
            {/if}
        </section>
    </div>
</div>
</div>
