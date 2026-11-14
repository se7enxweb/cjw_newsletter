{* The runs of ext:cjw_newsletter:import-eznewsletter, admin4: same data and actions as design/standard/templates/newsletter/importexport/migration_log.tpl *}
<div class="a4-page newsletter newsletter-migration_log">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'eznewsletter migration'|i18n( 'cjw_newsletter/importexport' )} [{$run_count}]</h1>
            <p class="a4-pagehead-sub">{'A site that ran the old eznewsletter extension can take its lists, subscribers and subscriptions over with a console command. The old tables are only read, never changed; every row read is logged here, and a second run skips what an earlier run took over.'|i18n( 'cjw_newsletter/importexport' )}</p>
        </div>
    </div>

    <section class="a4-section" aria-labelledby="nl-ml-tables">
        <h2 class="a4-h2" id="nl-ml-tables">{'Old tables in this database'|i18n( 'cjw_newsletter/importexport' )}</h2>
        {if $tables_found|count}
        <dl class="a4-facts is-rows">
            {foreach $tables_found as $found_table => $found_rows}<dt><code>{$found_table|wash}</code></dt><dd>{'%count rows'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $found_rows ) )}</dd>{/foreach}
        </dl>
        {else}
        <p class="a4-muted">{'None: the old extension did not run on this database. A copy of the old data can be read from an SQLite file with --source-sqlite.'|i18n( 'cjw_newsletter/importexport' )}</p>
        {/if}
        <p class="a4-help">{'First a dry run, then the run:'|i18n( 'cjw_newsletter/importexport' )}</p>
        <pre class="a4-pre cjwnl-a4-command">./console ext:cjw_newsletter:import-eznewsletter --dry-run --list-map=1:{'<list object id>'|i18n( 'cjw_newsletter/importexport' )|wash}
./console ext:cjw_newsletter:import-eznewsletter --list-map=1:{'<list object id>'|i18n( 'cjw_newsletter/importexport' )|wash}
./console ext:cjw_newsletter:import-eznewsletter --create-lists --system-node={'<node id>'|i18n( 'cjw_newsletter/importexport' )|wash}</pre>
    </section>

    <section class="a4-section" aria-labelledby="nl-ml-runs">
        <h2 class="a4-h2" id="nl-ml-runs">{'Runs'|i18n( 'cjw_newsletter/importexport' )}</h2>
        {if $runs|count}
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                <tr>
                    <th scope="col">{'Run'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col">{'Started'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col">{'Kind'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col" class="a4-num">{'Created'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col" class="a4-num">{'Merged'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col" class="a4-num">{'Skipped'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col" class="a4-num">{'Failed'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col" class="a4-num">{'Sends'|i18n( 'cjw_newsletter/importexport' )}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $runs as $run}
                <tr>
                    <td class="a4-wrap"><a href={concat( 'newsletter/migration_log/', $run.run_id )|ezurl}><code>{$run.run_id|wash}</code></a></td>
                    <td>{$run.started|l10n( 'shortdatetime' )}</td>
                    <td>{if $run.dry_run}<span class="a4-badge is-info">{'dry run'|i18n( 'cjw_newsletter/importexport' )}</span>{else}<span class="a4-badge is-ok">{'run'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}</td>
                    <td class="a4-num">{$run.created}</td>
                    <td class="a4-num">{$run.merged}</td>
                    <td class="a4-num">{$run.skipped}</td>
                    <td class="a4-num">{if $run.failed}<strong class="a4-bad-text nl-danger">{$run.failed}</strong>{else}0{/if}</td>
                    <td class="a4-num">{$run.recorded}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        {include name='Navigator' uri='design:navigator/google.tpl' page_uri='newsletter/migration_log' item_count=$run_count view_parameters=$view_parameters item_limit=$limit}
        {else}
        <p class="a4-empty"><strong>{'No run yet.'|i18n( 'cjw_newsletter/importexport' )}</strong></p>
        {/if}
    </section>
</div>
