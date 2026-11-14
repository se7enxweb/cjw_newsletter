{* The editions of a newsletter list (filter by status, items per page, the list of editions, alphabetical navigator), admin4. Same data and actions as design/standard/templates/cjw_newsletter_list_children.tpl (the admin2 copy). *}
<div class="content-view-children">
{* The language choice of "Create here", the sorting button and the selection: cjw_newsletter.js *}
{ezscript( array( 'cjw_newsletter.js' ) )}

{* Generic children list for admin interface. *}
{def $item_type    = ezpreference( 'admin_list_limit' )
     $number_of_items = min( $item_type, 3)|choose( 10, 10, 25, 50 )
     $can_remove   = false()
     $can_move     = false()
     $can_edit     = false()
     $can_create   = false()
     $can_copy     = false()
     $current_path = first_set( $node.path_array[1], 1 )
     $admin_children_viewmode = ezpreference( 'admin_children_viewmode' )
     $children_count = fetch( content, list_count, hash( 'parent_node_id', $node.node_id,
                                                         'objectname_filter', $view_parameters.namefilter ) )
     $children    = array()
     $priority    = and( eq( $node.sort_array[0][0], 'priority' ), $node.can_edit, $children_count )
     $priority_dd = and( $priority, $admin_children_viewmode|ne( 'thumbnail' ), $view_parameters.offset|eq( 0 ) )}


<!-- Children START -->

<div class="context-block">

    {def $cjwnl_status = ''}

    {if is_set( $view_parameters.status )}
        {set $cjwnl_status = $view_parameters.status}
    {/if}

    {set $children_count=fetch( 'content', 'list_count', hash( 'parent_node_id', $node.node_id,
                                                               'class_filter_type', 'include',
                                                               'class_filter_array', array( 'cjw_newsletter_edition' ),
                                                                  'objectname_filter', $view_parameters.namefilter,
                                                                  'extended_attribute_filter',
                                                                   hash( 'id', 'CjwNewsletterEditionFilter',
                                                                         'params', hash( 'status', $cjwnl_status )
                                                                   ) ) )
                 $children=fetch( 'content', 'list', hash( 'parent_node_id', $node.node_id,
                                                      'class_filter_type', 'include',
                                                      'class_filter_array', array( 'cjw_newsletter_edition' ),
                                                      'sort_by', $node.sort_array,
                                                      'limit', $number_of_items,
                                                      'offset', $view_parameters.offset,
                                                      'sort_by', array( 'modified', false() ),
                                                      'objectname_filter', $view_parameters.namefilter,
                                                      'extended_attribute_filter',
                                                       hash( 'id', 'CjwNewsletterEditionFilter',
                                                             'params', hash( 'status', $cjwnl_status ) )
                                                       ) )}

    <h2 class="a4-h2 context-title"><a class="a4-icon-btn" href={$node.depth|gt(1)|choose('/'|ezurl,$node.parent.url_alias|ezurl )}><span class="a4-icon a4-bi-arrow-90deg-up is-medium" role="img" aria-label="{'Up one level.'|i18n( 'design/admin/node/view/full' )|wash}" title="{'Up one level.'|i18n( 'design/admin/node/view/full' )|wash}"></span></a> {'Newsletter editions'|i18n( 'cjw_newsletter/cjw_newsletter_list' )} [{$children_count}]</h2>

    {* Items per page and the selection of the status. *}
    <div class="a4-toolbar context-toolbar">
        <ul class="a4-tabs">
        {switch match=$number_of_items}
        {case match=25}
            <li><a href={'/user/preferences/set/admin_list_limit/1'|ezurl} title="{'Show 10 items per page.'|i18n( 'design/admin/node/view/full' )}">10</a></li>
            <li><span class="current">25</span></li>
            <li><a href={'/user/preferences/set/admin_list_limit/3'|ezurl} title="{'Show 50 items per page.'|i18n( 'design/admin/node/view/full' )}">50</a></li>
        {/case}

        {case match=50}
            <li><a href={'/user/preferences/set/admin_list_limit/1'|ezurl} title="{'Show 10 items per page.'|i18n( 'design/admin/node/view/full' )}">10</a></li>
            <li><a href={'/user/preferences/set/admin_list_limit/2'|ezurl} title="{'Show 25 items per page.'|i18n( 'design/admin/node/view/full' )}">25</a></li>
            <li><span class="current">50</span></li>
        {/case}

        {case}
            <li><span class="current">10</span></li>
            <li><a href={'/user/preferences/set/admin_list_limit/2'|ezurl} title="{'Show 25 items per page.'|i18n( 'design/admin/node/view/full' )}">25</a></li>
            <li><a href={'/user/preferences/set/admin_list_limit/3'|ezurl} title="{'Show 50 items per page.'|i18n( 'design/admin/node/view/full' )}">50</a></li>
        {/case}
        {/switch}
        </ul>

        {* newsletter list selection *}
        <ul class="a4-tabs">
            <li>{if $cjwnl_status|eq('')}<span class="current">{'All'|i18n('cjw_newsletter/cjw_newsletter_list')}</span>{else}<a href={concat( $node.url_alias,'')|ezurl}>{'All'|i18n('cjw_newsletter/cjw_newsletter_list')}</a>{/if}</li>

            <li>{if $cjwnl_status|eq('draft')}<span class="current"><span class="a4-icon a4-bi-pencil-square is-small" aria-hidden="true"></span>{'Draft'|i18n('cjw_newsletter/contentstructuremenu')}</span>{else}<a href={concat( $node.url_alias, '/(status)/draft' )|ezurl}><span class="a4-icon a4-bi-pencil-square is-small" aria-hidden="true"></span>{'Draft'|i18n('cjw_newsletter/contentstructuremenu')}</a>{/if}</li>

            <li>{if $cjwnl_status|eq('process')}<span class="current"><span class="a4-icon a4-bi-hourglass-split is-small" aria-hidden="true"></span>{'Sending'|i18n('cjw_newsletter/contentstructuremenu')}</span>{else}<a href={concat( $node.url_alias, '/(status)/process' )|ezurl}><span class="a4-icon a4-bi-hourglass-split is-small" aria-hidden="true"></span>{'Sending'|i18n('cjw_newsletter/contentstructuremenu')}</a>{/if}</li>

            <li>{if $cjwnl_status|eq('archive')}<span class="current"><span class="a4-icon a4-bi-archive is-small" aria-hidden="true"></span>{'Archived'|i18n('cjw_newsletter/contentstructuremenu')}</span>{else}<a href={concat( $node.url_alias, '/(status)/archive' )|ezurl}><span class="a4-icon a4-bi-archive is-small" aria-hidden="true"></span>{'Archived'|i18n('cjw_newsletter/contentstructuremenu')}</a>{/if}</li>

            <li>{if $cjwnl_status|eq('abort')}<span class="current"><span class="a4-icon a4-bi-x-circle-fill is-small" aria-hidden="true"></span>{'Aborted'|i18n('cjw_newsletter/contentstructuremenu')}</span>{else}<a href={concat( $node.url_alias, '/(status)/abort' )|ezurl}><span class="a4-icon a4-bi-x-circle-fill is-small" aria-hidden="true"></span>{'Aborted'|i18n('cjw_newsletter/contentstructuremenu')}</a>{/if}</li>
        </ul>
    </div>

{if $children_count}

    {* Copying operation is allowed if the user can create stuff under the current node. *}
    {set can_copy=$node.can_create}

    {* Check if the current user is allowed to *}
    {* edit or delete any of the children.     *}
    {section var=Children loop=$children}
        {if $Children.item.can_remove}
            {set can_remove=true()}
        {/if}
        {if $Children.item.can_move}
            {set $can_move=true()}
        {/if}
        {if $Children.item.can_edit}
            {set can_edit=true()}
        {/if}
        {if $Children.item.can_create}
            {set can_create=true()}
        {/if}
    {/section}


    {* Display the actual list of nodes. *}
    {include uri = 'design:includes/cjwnewsletteredition_statistic_list.tpl'
                 name = 'EditionList'
                 edition_node_list = $children
                 edition_node_list_count = $children_count
                 show_actions_colum = true()}

        {def $viewmode_newsletter=true()}

{* Else: there are no children. *}
{else}

    <p class="a4-empty">{'The current selection has no result.'|i18n( 'cjw_newsletter/cjw_newsletter_list' )}</p>

{/if}

<div class="context-toolbar subitems-context-toolbar">
{* Alphabetical navigation can be enabled with content.ini [AlphabeticalFilterSettings] ContentFilterList[]  *}
{include name=navigator
         uri='design:navigator/alphabetical.tpl'
         page_uri=$node.url_alias
         item_count=$children_count
         view_parameters=$view_parameters
         node_id=$node.node_id
         item_limit=$number_of_items}
</div>

{* hide subitems functions *}
{if and( $children_count|ne( 0 ), is_set( $viewmode_newsletter )|not )}
<div class="a4-toolbar subitems-controlbar">
    {* Remove and move button *}
    <div class="a4-actions">
        {if $can_remove}
            <input class="a4-btn" type="submit" name="RemoveButton" value="{'Remove selected'|i18n( 'design/admin/node/view/full' )}" title="{'Remove the selected items from the list above.'|i18n( 'design/admin/node/view/full' )}" />
        {else}
            <input class="a4-btn" type="submit" name="RemoveButton" value="{'Remove selected'|i18n( 'design/admin/node/view/full' )}" title="{'You do not have permission to remove any of the items from the list above.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
        {/if}
        {if $can_move}
            <input class="a4-btn" type="submit" name="MoveButton" value="{'Move selected'|i18n( 'design/admin/node/view/full' )}" title="{'Move the selected items from the list above.'|i18n( 'design/admin/node/view/full' )}" />
        {else}
            <input class="a4-btn" type="submit" name="MoveButton" value="{'Move selected'|i18n( 'design/admin/node/view/full' )}" title="{'You do not have permission to move any of the items from the list above.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
        {/if}
    </div>
    <div class="a4-actions">
        {* Update priorities button *}
        {if $priority}
            <input id="ezasi-update-priority" class="a4-btn" type="submit" name="UpdatePriorityButton" value="{'Update priorities'|i18n( 'design/admin/node/view/full' )}" title="{'Apply changes to the priorities of the items in the list above.'|i18n( 'design/admin/node/view/full' )}" />
        {else}
            <input id="ezasi-update-priority" class="a4-btn" type="submit" name="UpdatePriorityButton" value="{'Update priorities'|i18n( 'design/admin/node/view/full' )}" title="{'You cannot update the priorities because you do not have permission to edit the current item or because a non-priority sorting method is used.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
        {/if}
    </div>
</div>

<div class="a4-card">
    <div class="a4-card-body">
        <h3 class="a4-h3">{'Create'|i18n( 'design/admin/node/view/full' )}</h3>
        <div class="a4-toolbar">
            {* The "Create new here" thing: *}
            {if and( $node.is_container,  $node.can_create)}
                <input type="hidden" name="NodeID" value="{$node.node_id}" />
                {if $node.path_array|contains( ezini( 'NodeSettings', 'MediaRootNode', 'content.ini' ) )}
                    {def $group_id = array( ezini( 'ClassGroupIDs', 'Users', 'content.ini' ),
                                            ezini( 'ClassGroupIDs', 'Setup', 'content.ini' ) )}
                {elseif $node.path_array|contains( ezini( 'NodeSettings', 'UserRootNode', 'content.ini' ) )}
                    {def $group_id = array( ezini( 'ClassGroupIDs', 'Setup', 'content.ini' ),
                                            ezini( 'ClassGroupIDs', 'Content', 'content.ini' ),
                                            ezini( 'ClassGroupIDs', 'Media', 'content.ini' ) )}
                {else}
                    {def $group_id = false()}
                {/if}

                {def $can_create_classes = fetch( 'content', 'can_instantiate_class_list', hash( 'parent_node', $node,
                                                                                                 'filter_type', 'exclude',
                                                                                                 'group_id', $group_id,
                                                                                                 'group_by_class_group', true() ) )}


                {def $can_create_languages = fetch( 'content', 'prioritized_languages' )
                     $content_locale = ezini( 'RegionalSettings', 'ContentObjectLocale' )
                     $cjwnl_class_languages = array()}

                {if and( is_set( $can_create_languages[0] ), eq( $can_create_languages|count, 1 ) )}
                    <select class="a4-input" id="ClassID" name="ClassID" title="{'Use this menu to select the type of item you want to create then click the "Create here" button. The item will be created in the current location.'|i18n( 'design/admin/node/view/full' )|wash()}" aria-label="{'Use this menu to select the type of item you want to create then click the "Create here" button. The item will be created in the current location.'|i18n( 'design/admin/node/view/full' )|wash()}">
                {else}
                    {* the languages each class can be created in, for cjw_newsletter.js: {"<class id>": ["<locale>", ...]} *}
                    {foreach $can_create_classes as $group}
                        {foreach $group.items as $class}
                            {set $cjwnl_class_languages = $cjwnl_class_languages|append( concat( '"', $class.id, '":', json_encode( $class.can_instantiate_languages ) ) )}
                        {/foreach}
                    {/foreach}
                    <select class="a4-input" id="ClassID" name="ClassID" data-cjwnl-languages="{ldelim}{$cjwnl_class_languages|implode( ',' )|wash}{rdelim}" title="{'Use this menu to select the type of item you want to create then click the "Create here" button. The item will be created in the current location.'|i18n( 'design/admin/node/view/full' )|wash()}" aria-label="{'Use this menu to select the type of item you want to create then click the "Create here" button. The item will be created in the current location.'|i18n( 'design/admin/node/view/full' )|wash()}">
                {/if}
                    {foreach $can_create_classes as $group}
                        <optgroup label="{$group.group_name}">
                        {foreach $group.items as $can_create_class}
                            {if $can_create_class.can_instantiate_languages}
                            <option value="{$can_create_class.id}">{$can_create_class.name|wash()}</option>
                            {/if}
                        {/foreach}
                        </optgroup>
                    {/foreach}
                </select>

                {if and( is_set( $can_create_languages[0] ), eq( $can_create_languages|count, 1 ) )}
                    <input name="ContentLanguageCode" value="{$can_create_languages[0].locale}" type="hidden" />
                {else}
                    <label for="ClassContentLanguageCode" class="a4-inline-label">{'in'|i18n( 'design/admin/node/view/full' )}</label>
                    <select class="a4-input" id="ClassContentLanguageCode" name="ContentLanguageCode" data-cjwnl-language-select="" title="{'Use this menu to select the language you want to use for the creation then click the "Create here" button. The item will be created in the current location.'|i18n( 'design/admin/node/view/full' )|wash()}">
                        {foreach $can_create_languages as $tmp_language}
                            <option value="{$tmp_language.locale|wash()}"{if $content_locale|eq( $tmp_language.locale )} selected="selected"{/if}>{$tmp_language.name|wash()}</option>
                        {/foreach}
                   </select>
                {/if}

                <input class="a4-btn" type="submit" name="NewButton" value="{'Here'|i18n( 'design/admin/node/view/full' )}" title="{'Create a new item in the current location. Use the menu on the left to select the type of  item.'|i18n( 'design/admin/node/view/full' )}" />
                <input type="hidden" name="ViewMode" value="full" />

                {undef $can_create_languages $can_create_classes $cjwnl_class_languages}
            {else}
                <select class="a4-input" id="ClassID" name="ClassID" disabled="disabled" aria-label="{'Not available'|i18n( 'design/admin/node/view/full' )}">
                <option value="">{'Not available'|i18n( 'design/admin/node/view/full' )}</option>
                </select>
                <input class="a4-btn" type="submit" name="NewButton" value="{'Here'|i18n( 'design/admin/node/view/full' )}" title="{'You do not have permission to create new items in the current location.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
            {/if}
        </div>
    </div>
</div>
    {if $children_count}
<div class="a4-card">
    <div class="a4-card-body">
        <h3 class="a4-h3">{'Published order'|i18n( 'design/admin/node/view/full' )}</h3>

            {let sort_fields=hash( 6, 'Class identifier'|i18n( 'design/admin/node/view/full' ),
                                   7, 'Class name'|i18n( 'design/admin/node/view/full' ),
                                   5, 'Depth'|i18n( 'design/admin/node/view/full' ),
                                   3, 'Modified'|i18n( 'design/admin/node/view/full' ),
                                   9, 'Name'|i18n( 'design/admin/node/view/full' ),
                                   8, 'Priority'|i18n( 'design/admin/node/view/full' ),
                                   2, 'Published'|i18n( 'design/admin/node/view/full' ),
                                   4, 'Section'|i18n( 'design/admin/node/view/full' ) )
                title='You cannot set the sorting method for the current location because you do not have permission to edit the current item.'|i18n( 'design/admin/node/view/full' )
                disabled=' disabled="disabled"' }

            {if $node.can_edit}
                {set title='Use these controls to set the sorting method for the sub items of the current location.'|i18n( 'design/admin/node/view/full' )}
                {set disabled=''}
                <input type="hidden" name="ContentObjectID" value="{$node.contentobject_id}" />
            {/if}

        <div class="a4-toolbar">
            <select class="a4-input" id="ezasi-sort-field" name="SortingField" title="{$title}" aria-label="{$title}"{$disabled}>
            {section var=Sort loop=$sort_fields}
                <option value="{$Sort.key}" {if eq( $Sort.key, $node.sort_field )}selected="selected"{/if}>{$Sort.item}</option>
            {/section}
            </select>

            <select class="a4-input" id="ezasi-sort-order" name="SortingOrder" title="{$title}" aria-label="{$title}"{$disabled}>
                <option value="0"{if eq($node.sort_order, 0)} selected="selected"{/if}>{'Descending'|i18n( 'design/admin/node/view/full' )}</option>
                <option value="1"{if eq($node.sort_order, 1)} selected="selected"{/if}>{'Ascending'|i18n( 'design/admin/node/view/full' )}</option>
            </select>

            <input id="ezasi-sort-set"{if is_set( $viewmode_newsletter )|not} data-cjwnl-sort-highlight=""{/if} class="a4-btn" type="submit" name="SetSorting" value="{'Set'|i18n( 'design/admin/node/view/full' )}" title="{$title}" {$disabled} />
        </div>

            {/let}
    </div>
</div>
    {/if}
{/if}

</div>

<!-- Children END -->

{undef $item_type $number_of_items $can_remove $can_move $can_edit $can_create $can_copy $current_path $admin_children_viewmode $children_count $children $cjwnl_status}
</div>
