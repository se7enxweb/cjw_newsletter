{*  newsletter/editorial/article_pool_edit.tpl (cjw_newsletter 4.2.0, area editorial)

    Create or edit an article pool. Variables: pool, pool_id, lists, classes (identifier => name), sections
    (id => name), states (id => name), tag_names (id => keyword), has_tags, sort_fields, found (the articles the
    pool finds, after "Show the articles", else null), errors.
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_editorial.css' ) )}
{def $chosen_classes = $pool.class_identifier_array
     $chosen_sections = $pool.section_id_array
     $chosen_states = $pool.state_id_array}
<div class="newsletter newsletter-article_pool_edit">
<form method="post" action={concat( 'newsletter/article_pool_edit/', $pool_id )|ezurl}>
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{if $pool_id}{'Edit the article pool "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $pool.label ) )|wash}{else}{'New article pool'|i18n( 'cjw_newsletter/editorial' )}{/if}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {if $errors|count}
        <div class="message-error" role="alert"><h2>{'Please check the form'|i18n( 'cjw_newsletter/editorial' )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}

        <div class="nl-ed-form">
            <label for="nl-ed-name">{'Name'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div{if is_set( $errors.name )} class="nl-field-error"{/if}><input id="nl-ed-name" class="halfbox" type="text" name="Name" value="{$pool.name|wash}" maxlength="255" /></div>

            <label for="nl-ed-list">{'For'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div>
                <select id="nl-ed-list" name="ListId">
                    <option value="0">{'Every list (a global pool)'|i18n( 'cjw_newsletter/editorial' )}</option>
                    {foreach $lists as $l}<option value="{$l.id}"{if eq( $l.id, $pool.list_contentobject_id )} selected="selected"{/if}>{$l.name|wash}</option>{/foreach}
                </select>
                <p><label><input type="checkbox" name="IsDefault" value="1"{if $pool.is_default} checked="checked"{/if} /> {'The global default pool (only for a global pool)'|i18n( 'cjw_newsletter/editorial' )}</label></p>
            </div>

            <label for="nl-ed-parents">{'Searched under the nodes'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div{if is_set( $errors.parents )} class="nl-field-error"{/if}>
                <input id="nl-ed-parents" type="text" name="ParentNodeIds" value="{$pool.parent_node_id_array|implode( ', ' )}" size="30" />
                <p class="nl-hint">{'Node ids, separated by commas. Now:'|i18n( 'cjw_newsletter/editorial' )} {foreach $pool.parent_nodes as $n}<a href={$n.url_alias|ezurl}>{$n.name|wash}</a> ({$n.node_id}){delimiter}, {/delimiter}{/foreach}</p>
            </div>

            <label for="nl-ed-classes">{'Classes'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div>
                <select id="nl-ed-classes" name="ClassIdentifiers[]" multiple="multiple" size="8">
                    {foreach $classes as $identifier => $name}<option value="{$identifier|wash}"{if $chosen_classes|contains( $identifier )} selected="selected"{/if}>{$name|wash} ({$identifier|wash})</option>{/foreach}
                </select>
                <p class="nl-hint">{'None chosen = every class.'|i18n( 'cjw_newsletter/editorial' )}</p>
            </div>

            <label for="nl-ed-sections">{'Sections'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div>
                <select id="nl-ed-sections" name="SectionIds[]" multiple="multiple" size="5">
                    {foreach $sections as $id => $name}<option value="{$id}"{if $chosen_sections|contains( $id )} selected="selected"{/if}>{$name|wash}</option>{/foreach}
                </select>
                <p class="nl-hint">{'None chosen = every section.'|i18n( 'cjw_newsletter/editorial' )}</p>
            </div>

            <label for="nl-ed-tags">{'Tags'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div{if is_set( $errors.tags )} class="nl-field-error"{/if}>
                <input id="nl-ed-tags" type="text" name="TagIds" value="{$pool.tag_id_array|implode( ', ' )}" size="30"{if $has_tags|not} disabled="disabled"{/if} />
                <p class="nl-hint">{if $has_tags}{'Tag ids, separated by commas; an article needs one of them. Now:'|i18n( 'cjw_newsletter/editorial' )} {$tag_names|implode( ', ' )|wash}{else}{'The tag extension is not active.'|i18n( 'cjw_newsletter/editorial' )}{/if}</p>
            </div>

            <label for="nl-ed-states">{'Object states'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div>
                <select id="nl-ed-states" name="StateIds[]" multiple="multiple" size="4">
                    {foreach $states as $id => $name}<option value="{$id}"{if $chosen_states|contains( $id )} selected="selected"{/if}>{$name|wash}</option>{/foreach}
                </select>
            </div>

            <label for="nl-ed-age">{'Age'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div><input id="nl-ed-age" type="number" min="0" max="3650" name="MaxAgeDays" value="{$pool.max_age_days}" size="5" /> {'days (0 = any age)'|i18n( 'cjw_newsletter/editorial' )}</div>

            <label for="nl-ed-max">{'Articles per edition'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div{if is_set( $errors.max_items )} class="nl-field-error"{/if}><input id="nl-ed-max" type="number" min="1" max="200" name="MaxItems" value="{$pool.max_items}" size="5" /> <span class="nl-hint">{'the most the auto-fill takes'|i18n( 'cjw_newsletter/editorial' )}</span></div>

            <label for="nl-ed-sort">{'Order'|i18n( 'cjw_newsletter/editorial' )}</label>
            <div>
                <select id="nl-ed-sort" name="SortBy">
                    {foreach $sort_fields as $f}<option value="{$f}"{if eq( $f, $pool.sort_by )} selected="selected"{/if}>{switch match=$f}{case match='published'}{'newest first'|i18n( 'cjw_newsletter/editorial' )}{/case}{case match='modified'}{'last changed first'|i18n( 'cjw_newsletter/editorial' )}{/case}{case match='priority'}{'by priority'|i18n( 'cjw_newsletter/editorial' )}{/case}{case}{'by name'|i18n( 'cjw_newsletter/editorial' )}{/case}{/switch}</option>{/foreach}
                </select>
            </div>
        </div>

        {if is_array( $found )}
        <section class="nl-ed-part">
            <h3>{'The pool finds %count articles now'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $found|count ) )}</h3>
            {if $found|count}
            <ul class="nl-ed-picks">
                {foreach $found as $n}<li><span class="nl-ed-title"><a href={$n.url_alias|ezurl}>{$n.name|wash}</a></span> <span class="nl-muted">{$n.class_name|wash}, {$n.object.published|l10n( 'shortdate' )}</span></li>{/foreach}
            </ul>
            {else}
            <p class="nl-muted">{'Nothing matches. Check the nodes, classes and filters.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {/if}
        </section>
        {/if}
    </div>
    <div class="controlbar">
        <input class="defaultbutton" type="submit" name="StoreButton" value="{'Save'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        <input class="button" type="submit" name="PreviewButton" value="{'Show the articles'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        <input class="button" type="submit" name="DiscardButton" value="{'Cancel'|i18n( 'cjw_newsletter/editorial' )|wash}" />
    </div>
</div>
</form>
</div>
{undef $chosen_classes $chosen_sections $chosen_states}
