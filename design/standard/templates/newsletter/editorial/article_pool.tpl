{*  newsletter/editorial/article_pool.tpl (cjw_newsletter 4.2.0, area editorial)

    The article picker of an edition. Variables: node (the edition's node), edition, pool, picks
    (CjwNewsletterEditionArticle), picked_ids, articles (the page of nodes), total, page_size, filters, filter_url,
    classes, sections, states, tags (the pool's tags, id => keyword), has_tags, filter_tag_name, locked (the edition
    is being sent or was sent), approval_state ('' = the list needs no approval), notices.
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_editorial.css' ) )}
{def $base = concat( 'newsletter/article_pool/', $node.node_id )}
<div class="newsletter newsletter-article_pool">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Pick articles for "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $edition.name ) )|wash}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        <div class="nl-status">
            <div class="nl-status-facts">
                <span>{'Pool'|i18n( 'cjw_newsletter/editorial' )}: <strong>{$pool.label|wash}</strong></span>
                <span><strong>{$picks|count}</strong> {'articles taken'|i18n( 'cjw_newsletter/editorial' )}</span>
                {if $approval_state}<span>{'Approval'|i18n( 'cjw_newsletter/editorial' )}: {include uri='design:newsletter/editorial/approval_pill.tpl' state=$approval_state}</span>{/if}
            </div>
            <div>
                <a class="button" href={$node.url_alias|ezurl}>{'Back to the edition'|i18n( 'cjw_newsletter/editorial' )}</a>
                {if $approval_state}<a class="button" href={concat( 'newsletter/approval/', $edition.id, '/', $edition.current_version )|ezurl}>{'Approval'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
            </div>
        </div>
        {if $locked}<div class="message-warning"><h2>{'The edition is being sent or was sent: its articles cannot change.'|i18n( 'cjw_newsletter/editorial' )}</h2></div>{/if}

        <section>
            <h2>{'In the edition'|i18n( 'cjw_newsletter/editorial' )}</h2>
            {if $picks|count}
            <form method="post" action={concat( $base, $filter_url )|ezurl}>
            <ul class="nl-ed-picks">
                {foreach $picks as $pick}
                <li>
                    <span class="nl-ed-title">{if $pick.article_node}<a href={$pick.article_node.url_alias|ezurl}>{$pick.article_node.name|wash}</a>{else}<span class="nl-muted">{'removed content %id'|i18n( 'cjw_newsletter/editorial',, hash( '%id', $pick.contentobject_id ) )}</span>{/if}</span>
                    <span class="nl-pill is-muted">{$pick.added_by_name|wash}</span>
                    {if $pick.copy_node}<a href={concat( 'content/edit/', $pick.copy_node.contentobject_id )|ezurl} title="{'Edit the text the edition shows'|i18n( 'cjw_newsletter/editorial' )|wash}">{'Edit the text'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
                    {if $locked|not}<input class="button" type="submit" name="RemoveButton[{$pick.contentobject_id}]" value="{'Take out'|i18n( 'cjw_newsletter/editorial' )|wash}" />{/if}
                </li>
                {/foreach}
            </ul>
            </form>
            {else}
            <p class="nl-muted">{'No article of the pool is in the edition yet.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {/if}
        </section>

        <section>
            <h2>{'Articles of the pool'|i18n( 'cjw_newsletter/editorial' )} [{$total}]</h2>
            <form class="nl-filter" method="post" action={concat( $base, $filter_url )|ezurl}>
                <label>{'Class'|i18n( 'cjw_newsletter/editorial' )}
                    <select name="FilterClass"><option value="">{'any'|i18n( 'cjw_newsletter/editorial' )}</option>{foreach $classes as $identifier => $name}<option value="{$identifier|wash}"{if eq( $identifier, $filters.class )} selected="selected"{/if}>{$name|wash}</option>{/foreach}</select></label>
                <label>{'Published from'|i18n( 'cjw_newsletter/editorial' )}<input type="date" name="FilterFrom" value="{$filters.from|wash}" /></label>
                <label>{'to'|i18n( 'cjw_newsletter/editorial' )}<input type="date" name="FilterTo" value="{$filters.to|wash}" /></label>
                <label>{'Section'|i18n( 'cjw_newsletter/editorial' )}
                    <select name="FilterSection"><option value="0">{'any'|i18n( 'cjw_newsletter/editorial' )}</option>{foreach $sections as $id => $name}<option value="{$id}"{if eq( $id, $filters.section )} selected="selected"{/if}>{$name|wash}</option>{/foreach}</select></label>
                {if $has_tags}<label>{'Tag id'|i18n( 'cjw_newsletter/editorial' )}<input type="text" name="FilterTag" value="{if $filters.tag}{$filters.tag}{/if}" size="6" inputmode="numeric" />{if $filter_tag_name}<span class="nl-hint">{$filter_tag_name|wash}</span>{/if}</label>{/if}
                <label>{'State'|i18n( 'cjw_newsletter/editorial' )}
                    <select name="FilterState"><option value="0">{'any'|i18n( 'cjw_newsletter/editorial' )}</option>{foreach $states as $id => $name}<option value="{$id}"{if eq( $id, $filters.state )} selected="selected"{/if}>{$name|wash}</option>{/foreach}</select></label>
                <span class="nl-spacer"></span>
                <input class="button" type="submit" name="FilterButton" value="{'Filter'|i18n( 'cjw_newsletter/editorial' )|wash}" />
                <input class="button" type="submit" name="ResetButton" value="{'Reset'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            </form>

            {if $articles|count}
            <form method="post" action={concat( $base, $filter_url )|ezurl}>
            <table class="list nl-table nl-ed-articles nl-ed-responsive">
                <tr class="nl-ed-head">
                    <th class="tight">{if $locked|not}<input type="checkbox" data-nl-check-all="" title="{'Select all'|i18n( 'cjw_newsletter/editorial' )|wash}" />{/if}</th>
                    <th>{'Title'|i18n( 'cjw_newsletter/editorial' )}</th>
                    <th>{'Class'|i18n( 'cjw_newsletter/editorial' )}</th>
                    <th>{'Section'|i18n( 'cjw_newsletter/editorial' )}</th>
                    <th>{'Published'|i18n( 'cjw_newsletter/editorial' )}</th>
                </tr>
                {foreach $articles as $a sequence array( 'bglight', 'bgdark' ) as $style}
                <tr class="{$style}">
                    <td class="nl-ed-check">{if $picked_ids|contains( $a.contentobject_id )}<span class="nl-pill is-ok" title="{'in the edition'|i18n( 'cjw_newsletter/editorial' )|wash}">&#10003;</span>{elseif $locked|not}<input type="checkbox" name="AddNodeIds[]" value="{$a.node_id}" aria-label="{$a.name|wash}" />{/if}</td>
                    <td class="nl-ed-title" data-label="{'Title'|i18n( 'cjw_newsletter/editorial' )|wash}"><a href={$a.url_alias|ezurl}>{$a.name|wash}</a></td>
                    <td data-label="{'Class'|i18n( 'cjw_newsletter/editorial' )|wash}">{$a.class_name|wash}</td>
                    <td data-label="{'Section'|i18n( 'cjw_newsletter/editorial' )|wash}">{if is_set( $sections[$a.object.section_id] )}{$sections[$a.object.section_id]|wash}{/if}</td>
                    <td data-label="{'Published'|i18n( 'cjw_newsletter/editorial' )|wash}"><time>{$a.object.published|l10n( 'shortdate' )}</time></td>
                </tr>
                {/foreach}
            </table>
            {if $locked|not}<p><input class="defaultbutton" type="submit" name="AddButton" value="{'Take the chosen articles into the edition'|i18n( 'cjw_newsletter/editorial' )|wash}" /></p>{/if}
            </form>
            {if $total|gt( $page_size )}
            <div class="nl-pager">
                <span>{'%from to %to of %total'|i18n( 'cjw_newsletter/editorial',, hash( '%from', sum( $filters.offset, 1 ), '%to', min( sum( $filters.offset, $page_size ), $total ), '%total', $total ) )}</span>
                <span>
                    {if $filters.offset|gt( 0 )}<a href={concat( $base, $filter_url, '/(offset)/', max( 0, sub( $filters.offset, $page_size ) ) )|ezurl}>{'Previous'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
                    {if sum( $filters.offset, $page_size )|lt( $total )}<a href={concat( $base, $filter_url, '/(offset)/', sum( $filters.offset, $page_size ) )|ezurl}>{'Next'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
                </span>
            </div>
            {/if}
            {else}
            <div class="nl-empty"><p>{'No article of the pool matches the filters.'|i18n( 'cjw_newsletter/editorial' )}</p></div>
            {/if}
        </section>
    </div>
</div>
</div>
{include uri='design:parts/newsletter/script.tpl'}
{undef $base}
