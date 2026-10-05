{*  newsletter/editorial/article_pool_list.tpl (cjw_newsletter 4.2.0, area editorial)

    The article pools. Variables: pools, usage (pool id => list names that name it), settings_pool (the pool of the
    settings when no default pool is stored, else null), confirm_remove, notices.
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_editorial.css' ) )}
<div class="newsletter newsletter-article_pool_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Article pools'|i18n( 'cjw_newsletter/editorial' )} [{$pools|count}]</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        {if $confirm_remove}
        <form class="nl-confirm" action={'newsletter/article_pool_list'|ezurl} method="post">
            <h2>{'Remove the article pool "%name"?'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $confirm_remove.label ) )|wash}</h2>
            <p>{'The lists and recurring sends that use it fall back to the default pool. The articles taken into editions stay.'|i18n( 'cjw_newsletter/editorial' )}</p>
            <input class="button nl-danger" type="submit" name="ConfirmRemoveButton[{$confirm_remove.id}]" value="{'Yes, remove'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <a class="button" href={'newsletter/article_pool_list'|ezurl}>{'Cancel'|i18n( 'cjw_newsletter/editorial' )}</a>
        </form>
        {/if}

        <p class="nl-muted">{'A pool says where the articles for editions come from: the nodes searched, the classes, sections, tags, states and age. A list uses the pool it names, else a pool made for it, else the global default.'|i18n( 'cjw_newsletter/editorial' )}</p>
        <p><a class="defaultbutton" href={'newsletter/article_pool_edit/0'|ezurl}>{'New article pool'|i18n( 'cjw_newsletter/editorial' )}</a></p>

        {if $settings_pool}
        <div class="nl-ed-part">
            <h3>{'No default pool is stored: the settings are used'|i18n( 'cjw_newsletter/editorial' )}</h3>
            <p class="nl-muted">{'Nodes'|i18n( 'cjw_newsletter/editorial' )}: {$settings_pool.parent_node_id_array|implode( ', ' )}; {'classes'|i18n( 'cjw_newsletter/editorial' )}: {if $settings_pool.class_identifier_array|count}{$settings_pool.class_identifier_array|implode( ', ' )|wash}{else}{'any'|i18n( 'cjw_newsletter/editorial' )}{/if}; {'at most %count articles'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $settings_pool.max_items ) )}</p>
        </div>
        {/if}

        {if $pools|count}
        <form action={'newsletter/article_pool_list'|ezurl} method="post">
        <table class="list nl-table nl-ed-responsive">
            <tr class="nl-ed-head">
                <th class="nl-num">{'ID'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'Name'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'For'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'Searched under'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'Classes'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th>{'Filters'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th class="nl-num">{'Articles'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th class="tight">{'Actions'|i18n( 'cjw_newsletter/editorial' )}</th>
            </tr>
            {foreach $pools as $p sequence array( 'bglight', 'bgdark' ) as $style}
            <tr class="{$style}">
                <td class="nl-num" data-label="{'ID'|i18n( 'cjw_newsletter/editorial' )|wash}">{$p.id}</td>
                <td class="nl-wrap" data-label="{'Name'|i18n( 'cjw_newsletter/editorial' )|wash}"><strong>{$p.label|wash}</strong>{if $p.is_default} <span class="nl-pill is-info">{'default'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}</td>
                <td class="nl-wrap" data-label="{'For'|i18n( 'cjw_newsletter/editorial' )|wash}">{if $p.list_contentobject_id}{$p.list_name|wash}{else}{'every list'|i18n( 'cjw_newsletter/editorial' )}{/if}{if is_set( $usage[$p.id] )}<br /><span class="nl-muted">{'named by %lists'|i18n( 'cjw_newsletter/editorial',, hash( '%lists', $usage[$p.id]|implode( ', ' ) ) )|wash}</span>{/if}</td>
                <td class="nl-wrap" data-label="{'Searched under'|i18n( 'cjw_newsletter/editorial' )|wash}">{foreach $p.parent_nodes as $n}<a href={$n.url_alias|ezurl}>{$n.name|wash}</a>{delimiter}, {/delimiter}{/foreach}</td>
                <td class="nl-wrap" data-label="{'Classes'|i18n( 'cjw_newsletter/editorial' )|wash}">{if $p.class_identifier_array|count}<code>{$p.class_identifier_array|implode( ', ' )|wash}</code>{else}{'any'|i18n( 'cjw_newsletter/editorial' )}{/if}</td>
                <td class="nl-wrap" data-label="{'Filters'|i18n( 'cjw_newsletter/editorial' )|wash}">
                    {if $p.section_id_array|count}<span class="nl-pill is-muted">{'%count sections'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $p.section_id_array|count ) )}</span>{/if}
                    {if $p.tag_id_array|count}<span class="nl-pill is-muted">{'%count tags'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $p.tag_id_array|count ) )}</span>{/if}
                    {if $p.state_id_array|count}<span class="nl-pill is-muted">{'%count states'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $p.state_id_array|count ) )}</span>{/if}
                    {if $p.max_age_days}<span class="nl-pill is-muted">{'%days days'|i18n( 'cjw_newsletter/editorial',, hash( '%days', $p.max_age_days ) )}</span>{/if}
                </td>
                <td class="nl-num" data-label="{'Articles'|i18n( 'cjw_newsletter/editorial' )|wash}">{$p.max_items}</td>
                <td class="nl-actions">
                    <a class="button" href={concat( 'newsletter/article_pool_edit/', $p.id )|ezurl}>{'Edit'|i18n( 'cjw_newsletter/editorial' )}</a>
                    <input class="button" type="submit" name="RemoveButton[{$p.id}]" value="{'Remove'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                </td>
            </tr>
            {/foreach}
        </table>
        </form>
        {else}
        <div class="nl-empty">
            <p><strong>{'There is no article pool yet.'|i18n( 'cjw_newsletter/editorial' )}</strong></p>
            <p>{'Make a global default pool, or one for a list.'|i18n( 'cjw_newsletter/editorial' )}</p>
        </div>
        {/if}
    </div>
</div>
</div>
