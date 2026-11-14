{* The rows of one run of ext:cjw_newsletter:import-eznewsletter, admin4: same data and actions as design/standard/templates/newsletter/importexport/migration_run.tpl *}
{def $run_uri = concat( 'newsletter/migration_log/', $run_id )}
<div class="a4-page newsletter newsletter-migration_log">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'Migration run'|i18n( 'cjw_newsletter/importexport' )} <code>{$run_id|wash}</code></h1>
        </div>
    </div>

    {if $summary}
    <div class="a4-card">
        <dl class="a4-facts">
            <div><dt>{'Started'|i18n( 'cjw_newsletter/importexport' )}</dt><dd>{$summary.started|l10n( 'shortdatetime' )}</dd></div>
            <div><dt>{'rows read'|i18n( 'cjw_newsletter/importexport' )}</dt><dd>{$summary.rows}</dd></div>
        </dl>
        <p class="a4-badges">{if $summary.dry_run}<span class="a4-badge is-info">{'dry run: nothing was written'|i18n( 'cjw_newsletter/importexport' )}</span>{else}<span class="a4-badge is-ok">{'run'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}
            {if $summary.failed}<span class="a4-badge is-bad">{'%count failed'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $summary.failed ) )}</span>{/if}</p>
    </div>
    {/if}

    <section class="a4-section" aria-labelledby="nl-mr-tables">
        <h2 class="a4-h2" id="nl-mr-tables">{'By old table'|i18n( 'cjw_newsletter/importexport' )}</h2>
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead><tr><th scope="col">{'Old table'|i18n( 'cjw_newsletter/importexport' )}</th>{foreach $actions as $a}<th scope="col" class="a4-num">{$a|wash}</th>{/foreach}</tr></thead>
                <tbody>
                {foreach $matrix as $matrix_table => $counts}
                <tr>
                    <td><a href={concat( $run_uri, '/(table)/', $matrix_table )|ezurl}><code>{$matrix_table|wash}</code></a></td>
                    {foreach $actions as $a}<td class="a4-num">{if is_set( $counts[$a] )}<a href={concat( $run_uri, '/(table)/', $matrix_table, '/(action)/', $a )|ezurl}>{$counts[$a]}</a>{else}0{/if}</td>{/foreach}
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </section>

    <section class="a4-section" aria-labelledby="nl-mr-rows">
        <h2 class="a4-h2" id="nl-mr-rows">{'Rows'|i18n( 'cjw_newsletter/importexport' )} <span class="a4-muted">[{$row_count}]</span></h2>
        <div class="a4-row">
            <span>{'Action'|i18n( 'cjw_newsletter/importexport' )}:</span>
            <a href={$run_uri|ezurl} class="a4-badge{if eq( $action, '' )} is-info{/if}">{'all'|i18n( 'cjw_newsletter/importexport' )}</a>
            {foreach $actions as $a}<a href={concat( $run_uri, cond( $table, concat( '/(table)/', $table ), '' ), '/(action)/', $a )|ezurl} class="a4-badge{if eq( $action, $a )} is-info{/if}">{$a|wash}</a>{/foreach}
            {if $table}<span>{'Old table'|i18n( 'cjw_newsletter/importexport' )}: <code>{$table|wash}</code> <a href={concat( $run_uri, cond( $action, concat( '/(action)/', $action ), '' ) )|ezurl}>{'all tables'|i18n( 'cjw_newsletter/importexport' )}</a></span>{/if}
        </div>
        {if $rows|count}
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                <tr>
                    <th scope="col">{'Old table'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col" class="a4-num">{'Old ID'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col">{'Action'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col">{'Written to'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th scope="col">{'Details'|i18n( 'cjw_newsletter/importexport' )}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $rows as $row}
                <tr>
                    <td><code>{$row.source_table|wash}</code></td>
                    <td class="a4-num a4-wrap">{$row.source_id|wash}</td>
                    <td><span class="a4-badge{cond( eq( $row.action, 'created' ), ' is-ok', eq( $row.action, 'merged' ), ' is-info', eq( $row.action, 'failed' ), ' is-bad', eq( $row.action, 'skipped' ), ' is-warn', '' )}">{$row.action|wash}</span></td>
                    <td class="a4-wrap">{if $row.target_table}<code>{$row.target_table|wash}</code>{if $row.target_id|gt( 0 )} {$row.target_id}{/if}{/if}</td>
                    <td class="a4-wrap a4-muted">{$row.details|wash}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        {include name='Navigator' uri='design:navigator/google.tpl' page_uri=$page_uri item_count=$row_count view_parameters=hash( 'offset', $view_parameters.offset ) item_limit=$limit}
        {else}
        <p class="a4-empty">{'No rows.'|i18n( 'cjw_newsletter/importexport' )}</p>
        {/if}
        <p><a class="a4-btn" href={'newsletter/migration_log'|ezurl}>{'All runs'|i18n( 'cjw_newsletter/importexport' )}</a></p>
    </section>
</div>
{undef $run_uri}
