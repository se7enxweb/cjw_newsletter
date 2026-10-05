{*  newsletter/import_list.tpl

    the CSV imports into the lists
*}
{ezcss_require( 'newsletter_ui.css' )}
{def $page_uri = 'newsletter/import_list'}
<div class="newsletter newsletter-import_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Imports'|i18n( 'cjw_newsletter/import_list' )} [{$import_list_count}]</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        <p class="nl-muted">{'Subscribers are imported from a CSV file on the subscriptions page of a list. Each upload is listed here.'|i18n( 'extension/cjw_newsletter' )}
            {'The import with column mapping starts from the CSV import page of a list; sites that ran eznewsletter use the migration.'|i18n( 'cjw_newsletter/importexport' )}
            <a href={'newsletter/migration_log'|ezurl}>{'Migration log'|i18n( 'cjw_newsletter/importexport' )}</a></p>

        {if $import_list|count}
        <table class="list nl-table">
            <tr>
                <th class="nl-num">{'ID'|i18n( 'cjw_newsletter/import_list' )}</th>
                <th>{'Type'|i18n( 'cjw_newsletter/import_list' )}</th>
                <th>{'List'|i18n( 'cjw_newsletter/import_list' )}</th>
                <th>{'Creator'|i18n( 'cjw_newsletter/import_list' )}</th>
                <th>{'Note'|i18n( 'cjw_newsletter/import_list' )}</th>
                <th>{'Created'|i18n( 'cjw_newsletter/import_list' )}</th>
                <th>{'Imported'|i18n( 'cjw_newsletter/import_list' )}</th>
                <th class="nl-num" title="{'Subscription count after import'|i18n( 'cjw_newsletter/import_list' )|wash} | {'Subscriptions in current system with import id %importId'|i18n( 'cjw_newsletter/import_list',, hash( '%importId', '' ) )|wash}">{'Count'|i18n( 'cjw_newsletter/import_list' )}</th>
            </tr>
            {foreach $import_list as $import_item sequence array( 'bglight', 'bgdark' ) as $style}
            {def $list_contentobject = $import_item.list_contentobject}
            <tr class="{$style}">
                <td class="nl-num"><a href={cond( eq( $import_item.type, 'cjwnl_csv_mapped' ), concat( 'newsletter/import_mapping/', $import_item.id ), concat( 'newsletter/import_view/', $import_item.id ) )|ezurl}>{$import_item.id|wash}</a></td>
                <td>{if eq( $import_item.type, 'cjwnl_csv_mapped' )}{'CSV with mapping'|i18n( 'cjw_newsletter/importexport' )}{if $import_item.skipped_count|gt( 0 )} <span class="nl-pill is-warn">{'%count skipped'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $import_item.skipped_count ) )}</span>{/if}{if and( $import_item.is_dry_run|eq( 1 ), $import_item.imported|eq( 0 ) )} <span class="nl-pill is-info">{'dry run'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}{else}{$import_item.type|wash}{/if}</td>
                <td class="nl-wrap">{if is_object( $list_contentobject )}<a href={concat( 'newsletter/subscription_list/', $list_contentobject.main_node_id )|ezurl}>{$list_contentobject.name|wash}</a>{/if}</td>
                <td>{if is_object( $import_item.creator )}{$import_item.creator.name|wash}{/if}</td>
                <td class="nl-wrap">{$import_item.note|wash}</td>
                <td>{$import_item.created|l10n( 'shortdatetime' )}</td>
                <td>{if $import_item.imported|gt( 0 )}<span class="nl-pill is-ok">{$import_item.imported|l10n( 'shortdatetime' )}</span>{else}<span class="nl-pill is-warn">{'not imported'|i18n( 'extension/cjw_newsletter' )}</span>{/if}</td>
                <td class="nl-num">{if $import_item.is_imported}{$import_item.imported_subscription_count|wash} | {$import_item.imported_subscription_count_live|wash} | <strong>{$import_item.imported_subscription_count_live_approved|wash}</strong>{/if}</td>
            </tr>
            {undef $list_contentobject}
            {/foreach}
        </table>
        <div class="nl-pager">
            <span>{'%count imports'|i18n( 'extension/cjw_newsletter',, hash( '%count', $import_list_count ) )}</span>
            <span class="table-preferences">{'Per page'|i18n( 'extension/cjw_newsletter' )}:
                {foreach array( 10, 25, 50, 100 ) as $n}{if eq( $limit, $n )}<span class="nl-page is-current">{$n}</span>{else}<a href={concat( 'newsletter/import_list/(limit)/', $n )|ezurl}>{$n}</a>{/if} {/foreach}
            </span>
            {include name='Navigator'
                     uri='design:navigator/google.tpl'
                     page_uri=$page_uri
                     item_count=$import_list_count
                     view_parameters=$view_parameters
                     item_limit=$limit}
        </div>
        {else}
        <div class="nl-empty">
            <p><strong>{'There is no import yet.'|i18n( 'extension/cjw_newsletter' )}</strong></p>
            <p>{'Open the subscriptions of a list and choose "Import CSV".'|i18n( 'extension/cjw_newsletter' )}</p>
        </div>
        {/if}
    </div>
</div>
</div>
