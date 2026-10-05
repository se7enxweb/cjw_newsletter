{* newsletter/migration_log/<run id>: the rows of one run of ext:cjw_newsletter:import-eznewsletter *}
{ezcss_require( 'newsletter_ui.css' )}
{ezcss_require( 'newsletter_importexport.css' )}
{def $run_uri = concat( 'newsletter/migration_log/', $run_id )}
<div class="newsletter newsletter-migration_log">
<div class="context-block nl nl-ie">
    <div class="box-header">
        <h1 class="context-title">{'Migration run'|i18n( 'cjw_newsletter/importexport' )} <code>{$run_id|wash}</code></h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {if $summary}
        <div class="nl-status">
            <div class="nl-status-facts">
                <span>{'Started'|i18n( 'cjw_newsletter/importexport' )} <strong>{$summary.started|l10n( 'shortdatetime' )}</strong></span>
                <span><strong>{$summary.rows}</strong> {'rows read'|i18n( 'cjw_newsletter/importexport' )}</span>
            </div>
            <div>{if $summary.dry_run}<span class="nl-pill is-info">{'dry run: nothing was written'|i18n( 'cjw_newsletter/importexport' )}</span>{else}<span class="nl-pill is-ok">{'run'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}
                 {if $summary.failed}<span class="nl-pill is-bad">{'%count failed'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $summary.failed ) )}</span>{/if}</div>
        </div>
        {/if}

        <section class="nl-ie-section">
            <h2>{'By old table'|i18n( 'cjw_newsletter/importexport' )}</h2>
            <div class="nl-ie-scroll">
            <table class="list nl-table">
                <tr><th>{'Old table'|i18n( 'cjw_newsletter/importexport' )}</th>{foreach $actions as $a}<th class="nl-num">{$a|wash}</th>{/foreach}</tr>
                {foreach $matrix as $matrix_table => $counts sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td><a href={concat( $run_uri, '/(table)/', $matrix_table )|ezurl}><code>{$matrix_table|wash}</code></a></td>
                    {foreach $actions as $a}<td class="nl-num">{if is_set( $counts[$a] )}<a href={concat( $run_uri, '/(table)/', $matrix_table, '/(action)/', $a )|ezurl}>{$counts[$a]}</a>{else}0{/if}</td>{/foreach}
                </tr>
                {/foreach}
            </table>
            </div>
        </section>

        <section class="nl-ie-section">
            <h2>{'Rows'|i18n( 'cjw_newsletter/importexport' )} <span class="nl-muted">[{$row_count}]</span></h2>
            <div class="nl-filter">
                <span>{'Action'|i18n( 'cjw_newsletter/importexport' )}:</span>
                <a href={$run_uri|ezurl} class="nl-pill{if eq( $action, '' )} is-info{/if}">{'all'|i18n( 'cjw_newsletter/importexport' )}</a>
                {foreach $actions as $a}<a href={concat( $run_uri, cond( $table, concat( '/(table)/', $table ), '' ), '/(action)/', $a )|ezurl} class="nl-pill{if eq( $action, $a )} is-info{/if}">{$a|wash}</a>{/foreach}
                {if $table}<span class="nl-spacer"></span><span>{'Old table'|i18n( 'cjw_newsletter/importexport' )}: <code>{$table|wash}</code> <a href={concat( $run_uri, cond( $action, concat( '/(action)/', $action ), '' ) )|ezurl}>{'all tables'|i18n( 'cjw_newsletter/importexport' )}</a></span>{/if}
            </div>
            {if $rows|count}
            <div class="nl-ie-scroll">
            <table class="list nl-table">
                <tr>
                    <th>{'Old table'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th class="nl-num">{'Old ID'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th>{'Action'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th>{'Written to'|i18n( 'cjw_newsletter/importexport' )}</th>
                    <th>{'Details'|i18n( 'cjw_newsletter/importexport' )}</th>
                </tr>
                {foreach $rows as $row sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td><code>{$row.source_table|wash}</code></td>
                    <td class="nl-num nl-wrap">{$row.source_id|wash}</td>
                    <td><span class="nl-pill {cond( eq( $row.action, 'created' ), 'is-ok', eq( $row.action, 'merged' ), 'is-info', eq( $row.action, 'failed' ), 'is-bad', eq( $row.action, 'skipped' ), 'is-warn', 'is-muted' )}">{$row.action|wash}</span></td>
                    <td class="nl-wrap">{if $row.target_table}<code>{$row.target_table|wash}</code>{if $row.target_id|gt( 0 )} {$row.target_id}{/if}{/if}</td>
                    <td class="nl-wrap nl-muted">{$row.details|wash}</td>
                </tr>
                {/foreach}
            </table>
            </div>
            <div class="nl-pager">
                {include name='Navigator' uri='design:navigator/google.tpl' page_uri=$page_uri item_count=$row_count view_parameters=hash( 'offset', $view_parameters.offset ) item_limit=$limit}
            </div>
            {else}
            <div class="nl-empty"><p>{'No rows.'|i18n( 'cjw_newsletter/importexport' )}</p></div>
            {/if}
            <p><a class="button" href={'newsletter/migration_log'|ezurl}>{'All runs'|i18n( 'cjw_newsletter/importexport' )}</a></p>
        </section>
    </div>
</div>
</div>
{undef $run_uri}
