{* The article picker of an edition, admin4. Same data and actions as design/standard/templates/newsletter/editorial/article_pool.tpl.
   Variables: node (the edition's node), edition, pool, picks (CjwNewsletterEditionArticle), picked_ids, articles (the
   page of nodes), total, page_size, filters, filter_url, classes, sections, states, tags (the pool's tags, id => keyword),
   has_tags, filter_tag_name, locked (the edition is being sent or was sent), approval_state ('' = the list needs no
   approval), notices. *}
{def $base = concat( 'newsletter/article_pool/', $node.node_id )}
<div class="a4-page newsletter newsletter-article_pool">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Pick articles for "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $edition.name ) )|wash}</h1>
        </div>
        <div class="a4-pagehead-actions">
            <a class="a4-btn" href={$node.url_alias|ezurl}>{'Back to the edition'|i18n( 'cjw_newsletter/editorial' )}</a>
            {if $approval_state}<a class="a4-btn" href={concat( 'newsletter/approval/', $edition.id, '/', $edition.current_version )|ezurl}>{'Approval'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    <div class="a4-card">
        <dl class="a4-facts">
            <div><dt>{'Pool'|i18n( 'cjw_newsletter/editorial' )}</dt><dd><strong>{$pool.label|wash}</strong></dd></div>
            <div><dt>{'articles taken'|i18n( 'cjw_newsletter/editorial' )}</dt><dd><strong>{$picks|count}</strong></dd></div>
            {if $approval_state}<div><dt>{'Approval'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{include uri='design:newsletter/editorial/approval_pill.tpl' state=$approval_state}</dd></div>{/if}
        </dl>
    </div>
    {if $locked}<div class="a4-notice is-warn" role="status">{'The edition is being sent or was sent: its articles cannot change.'|i18n( 'cjw_newsletter/editorial' )}</div>{/if}

    <section class="a4-section">
        <h2 class="a4-h2">{'In the edition'|i18n( 'cjw_newsletter/editorial' )}</h2>
        {if $picks|count}
        <form method="post" action={concat( $base, $filter_url )|ezurl}>
        <ul class="cjwnl-a4-picks">
            {foreach $picks as $pick}
            <li>
                <span class="cjwnl-a4-pick-title">{if $pick.article_node}<a href={$pick.article_node.url_alias|ezurl}>{$pick.article_node.name|wash}</a>{else}<span class="a4-muted">{'removed content %id'|i18n( 'cjw_newsletter/editorial',, hash( '%id', $pick.contentobject_id ) )}</span>{/if}</span>
                <span class="a4-badge">{$pick.added_by_name|wash}</span>
                {if $pick.copy_node}<a href={concat( 'content/edit/', $pick.copy_node.contentobject_id )|ezurl} title="{'Edit the text the edition shows'|i18n( 'cjw_newsletter/editorial' )|wash}">{'Edit the text'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
                {if $locked|not}<input class="a4-btn a4-btn-small" type="submit" name="RemoveButton[{$pick.contentobject_id}]" value="{'Take out'|i18n( 'cjw_newsletter/editorial' )|wash}" />{/if}
            </li>
            {/foreach}
        </ul>
        </form>
        {else}
        <p class="a4-empty">{'No article of the pool is in the edition yet.'|i18n( 'cjw_newsletter/editorial' )}</p>
        {/if}
    </section>

    <section class="a4-section">
        <h2 class="a4-h2">{'Articles of the pool'|i18n( 'cjw_newsletter/editorial' )} [{$total}]</h2>
        <form class="a4-filterbar" method="post" action={concat( $base, $filter_url )|ezurl}>
            <div class="a4-field">
                <label for="nl-ed-filter-class">{'Class'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-filter-class" name="FilterClass"><option value="">{'any'|i18n( 'cjw_newsletter/editorial' )}</option>{foreach $classes as $identifier => $name}<option value="{$identifier|wash}"{if eq( $identifier, $filters.class )} selected="selected"{/if}>{$name|wash}</option>{/foreach}</select>
            </div>
            <div class="a4-field">
                <label for="nl-ed-filter-from">{'Published from'|i18n( 'cjw_newsletter/editorial' )}</label>
                <input id="nl-ed-filter-from" type="date" name="FilterFrom" value="{$filters.from|wash}" />
            </div>
            <div class="a4-field">
                <label for="nl-ed-filter-to">{'to'|i18n( 'cjw_newsletter/editorial' )}</label>
                <input id="nl-ed-filter-to" type="date" name="FilterTo" value="{$filters.to|wash}" />
            </div>
            <div class="a4-field">
                <label for="nl-ed-filter-section">{'Section'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-filter-section" name="FilterSection"><option value="0">{'any'|i18n( 'cjw_newsletter/editorial' )}</option>{foreach $sections as $id => $name}<option value="{$id}"{if eq( $id, $filters.section )} selected="selected"{/if}>{$name|wash}</option>{/foreach}</select>
            </div>
            {if $has_tags}
            <div class="a4-field">
                <label for="nl-ed-filter-tag">{'Tag id'|i18n( 'cjw_newsletter/editorial' )}</label>
                <input id="nl-ed-filter-tag" type="text" name="FilterTag" value="{if $filters.tag}{$filters.tag}{/if}" size="6" inputmode="numeric" />
                {if $filter_tag_name}<span class="a4-help">{$filter_tag_name|wash}</span>{/if}
            </div>
            {/if}
            <div class="a4-field">
                <label for="nl-ed-filter-state">{'State'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-filter-state" name="FilterState"><option value="0">{'any'|i18n( 'cjw_newsletter/editorial' )}</option>{foreach $states as $id => $name}<option value="{$id}"{if eq( $id, $filters.state )} selected="selected"{/if}>{$name|wash}</option>{/foreach}</select>
            </div>
            <div class="a4-actions">
                <input class="a4-btn a4-btn-primary" type="submit" name="FilterButton" value="{'Filter'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                <input class="a4-btn" type="submit" name="ResetButton" value="{'Reset'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            </div>
        </form>

        {if $articles|count}
        <form method="post" action={concat( $base, $filter_url )|ezurl}>
        <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr>
                <th scope="col" class="tight">{if $locked|not}<input type="checkbox" data-nl-check-all="" title="{'Select all'|i18n( 'cjw_newsletter/editorial' )|wash}" aria-label="{'Select all'|i18n( 'cjw_newsletter/editorial' )|wash}" />{/if}</th>
                <th scope="col">{'Title'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th scope="col">{'Class'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th scope="col">{'Section'|i18n( 'cjw_newsletter/editorial' )}</th>
                <th scope="col">{'Published'|i18n( 'cjw_newsletter/editorial' )}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $articles as $a}
            <tr>
                <td>{if $picked_ids|contains( $a.contentobject_id )}<span class="a4-badge is-ok" title="{'in the edition'|i18n( 'cjw_newsletter/editorial' )|wash}"><span class="a4-icon a4-bi-check-lg is-small" role="img" aria-label="{'in the edition'|i18n( 'cjw_newsletter/editorial' )|wash}"></span></span>{elseif $locked|not}<input type="checkbox" name="AddNodeIds[]" value="{$a.node_id}" aria-label="{$a.name|wash}" />{/if}</td>
                <td class="a4-wrap"><a href={$a.url_alias|ezurl}>{$a.name|wash}</a></td>
                <td>{$a.class_name|wash}</td>
                <td>{if is_set( $sections[$a.object.section_id] )}{$sections[$a.object.section_id]|wash}{/if}</td>
                <td><time>{$a.object.published|l10n( 'shortdate' )}</time></td>
            </tr>
            {/foreach}
            </tbody>
        </table>
        </div>
        {if $locked|not}<div class="a4-actions"><input class="a4-btn a4-btn-primary" type="submit" name="AddButton" value="{'Take the chosen articles into the edition'|i18n( 'cjw_newsletter/editorial' )|wash}" /></div>{/if}
        </form>
        {if $total|gt( $page_size )}
        <nav class="a4-pager cjwnl-a4-pager">
            <span>{'%from to %to of %total'|i18n( 'cjw_newsletter/editorial',, hash( '%from', sum( $filters.offset, 1 ), '%to', min( sum( $filters.offset, $page_size ), $total ), '%total', $total ) )}</span>
            <span>
                {if $filters.offset|gt( 0 )}<a class="a4-btn a4-btn-small" href={concat( $base, $filter_url, '/(offset)/', max( 0, sub( $filters.offset, $page_size ) ) )|ezurl}>{'Previous'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
                {if sum( $filters.offset, $page_size )|lt( $total )}<a class="a4-btn a4-btn-small" href={concat( $base, $filter_url, '/(offset)/', sum( $filters.offset, $page_size ) )|ezurl}>{'Next'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
            </span>
        </nav>
        {/if}
        {else}
        <p class="a4-empty">{'No article of the pool matches the filters.'|i18n( 'cjw_newsletter/editorial' )}</p>
        {/if}
    </section>
</div>
{include uri='design:parts/newsletter/script.tpl'}
{undef $base}
