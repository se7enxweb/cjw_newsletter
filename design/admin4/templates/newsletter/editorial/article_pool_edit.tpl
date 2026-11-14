{* Create or edit an article pool, admin4. Same data and actions as design/standard/templates/newsletter/editorial/article_pool_edit.tpl.
   Variables: pool, pool_id, lists, classes (identifier => name), sections (id => name), states (id => name), tag_names
   (id => keyword), has_tags, sort_fields, found (the articles the pool finds, after "Show the articles", else null), errors. *}
{def $chosen_classes = $pool.class_identifier_array
     $chosen_sections = $pool.section_id_array
     $chosen_states = $pool.state_id_array}
<div class="a4-page newsletter newsletter-article_pool_edit">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{if $pool_id}{'Edit the article pool "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $pool.label ) )|wash}{else}{'New article pool'|i18n( 'cjw_newsletter/editorial' )}{/if}</h1>
        </div>
    </div>

    {if $errors|count}
    <div class="a4-notice is-bad a4-error-summary" role="alert"><h2 class="a4-notice-title">{'Please check the form'|i18n( 'cjw_newsletter/editorial' )}</h2>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}

    <form method="post" action={concat( 'newsletter/article_pool_edit/', $pool_id )|ezurl}>
    <div class="a4-card">
        <div class="a4-form is-wide">
            <div class="a4-field">
                <label for="nl-ed-name">{'Name'|i18n( 'cjw_newsletter/editorial' )}</label>
                <input id="nl-ed-name" type="text" name="Name" value="{$pool.name|wash}" maxlength="255"{if is_set( $errors.name )} aria-invalid="true"{/if} />
            </div>

            <div class="a4-field">
                <label for="nl-ed-list">{'For'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-list" name="ListId">
                    <option value="0">{'Every list (a global pool)'|i18n( 'cjw_newsletter/editorial' )}</option>
                    {foreach $lists as $l}<option value="{$l.id}"{if eq( $l.id, $pool.list_contentobject_id )} selected="selected"{/if}>{$l.name|wash}</option>{/foreach}
                </select>
                <label class="a4-check"><input type="checkbox" name="IsDefault" value="1"{if $pool.is_default} checked="checked"{/if} /> <span>{'The global default pool (only for a global pool)'|i18n( 'cjw_newsletter/editorial' )}</span></label>
            </div>

            <div class="a4-field">
                <label for="nl-ed-parents">{'Searched under the nodes'|i18n( 'cjw_newsletter/editorial' )}</label>
                <input id="nl-ed-parents" type="text" name="ParentNodeIds" value="{$pool.parent_node_id_array|implode( ', ' )}" size="30"{if is_set( $errors.parents )} aria-invalid="true"{/if} />
                <p class="a4-help">{'Node ids, separated by commas. Now:'|i18n( 'cjw_newsletter/editorial' )} {foreach $pool.parent_nodes as $n}<a href={$n.url_alias|ezurl}>{$n.name|wash}</a> ({$n.node_id}){delimiter}, {/delimiter}{/foreach}</p>
            </div>

            <div class="a4-field">
                <label for="nl-ed-classes">{'Classes'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-classes" name="ClassIdentifiers[]" multiple="multiple" size="8">
                    {foreach $classes as $identifier => $name}<option value="{$identifier|wash}"{if $chosen_classes|contains( $identifier )} selected="selected"{/if}>{$name|wash} ({$identifier|wash})</option>{/foreach}
                </select>
                <p class="a4-help">{'None chosen = every class.'|i18n( 'cjw_newsletter/editorial' )}</p>
            </div>

            <div class="a4-field">
                <label for="nl-ed-sections">{'Sections'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-sections" name="SectionIds[]" multiple="multiple" size="5">
                    {foreach $sections as $id => $name}<option value="{$id}"{if $chosen_sections|contains( $id )} selected="selected"{/if}>{$name|wash}</option>{/foreach}
                </select>
                <p class="a4-help">{'None chosen = every section.'|i18n( 'cjw_newsletter/editorial' )}</p>
            </div>

            <div class="a4-field">
                <label for="nl-ed-tags">{'Tags'|i18n( 'cjw_newsletter/editorial' )}</label>
                <input id="nl-ed-tags" type="text" name="TagIds" value="{$pool.tag_id_array|implode( ', ' )}" size="30"{if $has_tags|not} disabled="disabled"{/if}{if is_set( $errors.tags )} aria-invalid="true"{/if} />
                <p class="a4-help">{if $has_tags}{'Tag ids, separated by commas; an article needs one of them. Now:'|i18n( 'cjw_newsletter/editorial' )} {$tag_names|implode( ', ' )|wash}{else}{'The tag extension is not active.'|i18n( 'cjw_newsletter/editorial' )}{/if}</p>
            </div>

            <div class="a4-field">
                <label for="nl-ed-states">{'Object states'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-states" name="StateIds[]" multiple="multiple" size="4">
                    {foreach $states as $id => $name}<option value="{$id}"{if $chosen_states|contains( $id )} selected="selected"{/if}>{$name|wash}</option>{/foreach}
                </select>
            </div>

            <div class="a4-field">
                <label for="nl-ed-age">{'Age'|i18n( 'cjw_newsletter/editorial' )}</label>
                <div class="cjwnl-a4-inline"><input id="nl-ed-age" class="cjwnl-a4-narrow" type="number" min="0" max="3650" name="MaxAgeDays" value="{$pool.max_age_days}" size="5" /> <span>{'days (0 = any age)'|i18n( 'cjw_newsletter/editorial' )}</span></div>
            </div>

            <div class="a4-field">
                <label for="nl-ed-max">{'Articles per edition'|i18n( 'cjw_newsletter/editorial' )}</label>
                <div class="cjwnl-a4-inline"><input id="nl-ed-max" class="cjwnl-a4-narrow" type="number" min="1" max="200" name="MaxItems" value="{$pool.max_items}" size="5"{if is_set( $errors.max_items )} aria-invalid="true"{/if} /> <span class="a4-help">{'the most the auto-fill takes'|i18n( 'cjw_newsletter/editorial' )}</span></div>
            </div>

            <div class="a4-field">
                <label for="nl-ed-sort">{'Order'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-sort" name="SortBy">
                    {foreach $sort_fields as $f}<option value="{$f}"{if eq( $f, $pool.sort_by )} selected="selected"{/if}>{switch match=$f}{case match='published'}{'newest first'|i18n( 'cjw_newsletter/editorial' )}{/case}{case match='modified'}{'last changed first'|i18n( 'cjw_newsletter/editorial' )}{/case}{case match='priority'}{'by priority'|i18n( 'cjw_newsletter/editorial' )}{/case}{case}{'by name'|i18n( 'cjw_newsletter/editorial' )}{/case}{/switch}</option>{/foreach}
                </select>
            </div>
        </div>
    </div>

    {if is_array( $found )}
    <section class="a4-card">
        <h2 class="a4-h2">{'The pool finds %count articles now'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $found|count ) )}</h2>
        {if $found|count}
        <ul class="cjwnl-a4-picks">
            {foreach $found as $n}<li><span class="cjwnl-a4-pick-title"><a href={$n.url_alias|ezurl}>{$n.name|wash}</a></span> <span class="a4-muted">{$n.class_name|wash}, {$n.object.published|l10n( 'shortdate' )}</span></li>{/foreach}
        </ul>
        {else}
        <p class="a4-empty">{'Nothing matches. Check the nodes, classes and filters.'|i18n( 'cjw_newsletter/editorial' )}</p>
        {/if}
    </section>
    {/if}

    <div class="a4-toolbar is-bottom">
        <div class="a4-actions">
            <input class="a4-btn a4-btn-primary" type="submit" name="StoreButton" value="{'Save'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <input class="a4-btn" type="submit" name="PreviewButton" value="{'Show the articles'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <input class="a4-btn" type="submit" name="DiscardButton" value="{'Cancel'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        </div>
    </div>
    </form>
</div>
{undef $chosen_classes $chosen_sections $chosen_states}
