{* The article pools, admin4. Same data and actions as design/standard/templates/newsletter/editorial/article_pool_list.tpl.
   Variables: pools, usage (pool id => list names that name it), settings_pool (the pool of the settings when no default
   pool is stored, else null), confirm_remove, notices. *}
<div class="a4-page newsletter newsletter-article_pool_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Article pools'|i18n( 'cjw_newsletter/editorial' )} [{$pools|count}]</h1>
        </div>
        <div class="a4-pagehead-actions">
            <a class="a4-btn a4-btn-primary" href={'newsletter/article_pool_edit/0'|ezurl}>{'New article pool'|i18n( 'cjw_newsletter/editorial' )}</a>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    {if $confirm_remove}
    <form class="a4-notice is-warn" action={'newsletter/article_pool_list'|ezurl} method="post">
        <h2 class="a4-notice-title">{'Remove the article pool "%name"?'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $confirm_remove.label ) )|wash}</h2>
        <p>{'The lists and recurring sends that use it fall back to the default pool. The articles taken into editions stay.'|i18n( 'cjw_newsletter/editorial' )}</p>
        <div class="a4-actions">
            <input class="a4-btn a4-btn-danger nl-danger" type="submit" name="ConfirmRemoveButton[{$confirm_remove.id}]" value="{'Yes, remove'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <a class="a4-btn" href={'newsletter/article_pool_list'|ezurl}>{'Cancel'|i18n( 'cjw_newsletter/editorial' )}</a>
        </div>
    </form>
    {/if}

    <p class="a4-intro">{'A pool says where the articles for editions come from: the nodes searched, the classes, sections, tags, states and age. A list uses the pool it names, else a pool made for it, else the global default.'|i18n( 'cjw_newsletter/editorial' )}</p>

    {if $settings_pool}
    <div class="a4-card">
        <h2 class="a4-h3">{'No default pool is stored: the settings are used'|i18n( 'cjw_newsletter/editorial' )}</h2>
        <p class="a4-help">{'Nodes'|i18n( 'cjw_newsletter/editorial' )}: {$settings_pool.parent_node_id_array|implode( ', ' )}; {'classes'|i18n( 'cjw_newsletter/editorial' )}: {if $settings_pool.class_identifier_array|count}{$settings_pool.class_identifier_array|implode( ', ' )|wash}{else}{'any'|i18n( 'cjw_newsletter/editorial' )}{/if}; {'at most %count articles'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $settings_pool.max_items ) )}</p>
    </div>
    {/if}

    {if $pools|count}
    <form action={'newsletter/article_pool_list'|ezurl} method="post">
    <div class="a4-table-wrap">
    <table class="a4-table">
        <thead>
        <tr>
            <th scope="col" class="a4-num">{'ID'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col">{'Name'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col">{'For'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col">{'Searched under'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col">{'Classes'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col">{'Filters'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col" class="a4-num">{'Articles'|i18n( 'cjw_newsletter/editorial' )}</th>
            <th scope="col" class="tight">{'Actions'|i18n( 'cjw_newsletter/editorial' )}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $pools as $p}
        <tr>
            <td class="a4-num">{$p.id}</td>
            <td class="a4-wrap"><strong>{$p.label|wash}</strong>{if $p.is_default} <span class="a4-badge is-info">{'default'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}</td>
            <td class="a4-wrap">{if $p.list_contentobject_id}{$p.list_name|wash}{else}{'every list'|i18n( 'cjw_newsletter/editorial' )}{/if}{if is_set( $usage[$p.id] )}<br /><span class="a4-muted">{'named by %lists'|i18n( 'cjw_newsletter/editorial',, hash( '%lists', $usage[$p.id]|implode( ', ' ) ) )|wash}</span>{/if}</td>
            <td class="a4-wrap">{foreach $p.parent_nodes as $n}<a href={$n.url_alias|ezurl}>{$n.name|wash}</a>{delimiter}, {/delimiter}{/foreach}</td>
            <td class="a4-wrap">{if $p.class_identifier_array|count}<code>{$p.class_identifier_array|implode( ', ' )|wash}</code>{else}{'any'|i18n( 'cjw_newsletter/editorial' )}{/if}</td>
            <td class="a4-wrap">
                {if $p.section_id_array|count}<span class="a4-badge">{'%count sections'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $p.section_id_array|count ) )}</span>{/if}
                {if $p.tag_id_array|count}<span class="a4-badge">{'%count tags'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $p.tag_id_array|count ) )}</span>{/if}
                {if $p.state_id_array|count}<span class="a4-badge">{'%count states'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $p.state_id_array|count ) )}</span>{/if}
                {if $p.max_age_days}<span class="a4-badge">{'%days days'|i18n( 'cjw_newsletter/editorial',, hash( '%days', $p.max_age_days ) )}</span>{/if}
            </td>
            <td class="a4-num">{$p.max_items}</td>
            <td>
                <div class="a4-actions">
                    <a class="a4-btn a4-btn-small" href={concat( 'newsletter/article_pool_edit/', $p.id )|ezurl}>{'Edit'|i18n( 'cjw_newsletter/editorial' )}</a>
                    <input class="a4-btn a4-btn-small" type="submit" name="RemoveButton[{$p.id}]" value="{'Remove'|i18n( 'cjw_newsletter/editorial' )|wash}" />
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
        <p><strong>{'There is no article pool yet.'|i18n( 'cjw_newsletter/editorial' )}</strong></p>
        <p>{'Make a global default pool, or one for a list.'|i18n( 'cjw_newsletter/editorial' )}</p>
    </div>
    {/if}
</div>
