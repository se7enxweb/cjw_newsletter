{* One newsletter system in admin4: its lists with their draft editions and the button to create an edition, also for the
   virtual lists below a list. Parameter: newsletter_system_node.
   Same data and actions as design/standard/templates/newsletter/index_newsletter_system_info_box.tpl *}
<div class="a4-table-wrap cjwnl-a4-system">
    <table class="a4-table">
        <thead>
            <tr><th scope="col" colspan="2">{$newsletter_system_node.name|wash()}</th></tr>
        </thead>
        <tbody>
        {def $nl_list_class_filter_array = array( 'cjw_newsletter_list',
                                                  'cjw_newsletter_list_virtual' )

             $newsletter_list_node_list = fetch('content', 'list',
                                                hash( 'parent_node_id', $newsletter_system_node.node_id,
                                                      'class_filter_type', 'include',
                                                      'class_filter_array', $nl_list_class_filter_array
                                                     ))}

        {foreach $newsletter_list_node_list as $newsletter_list_node}

            <tr>
                <td>{$newsletter_list_node.class_identifier|class_icon( 'small' )} <a href={$newsletter_list_node.url_alias|ezurl}>{$newsletter_list_node.name|wash()} / {$newsletter_list_node.data_map.title.content|wash()}</a></td>
                <td>
                    {if $newsletter_list_node.can_create}
                    <form action={'content/action'|ezurl()} name="CreateNewNewsletterEdition" method="post">
                        <input type="hidden" value="{ezini( 'RegionalSettings', 'ContentObjectLocale' )}" name="ContentLanguageCode"/>
                        <input type="hidden" value="{$newsletter_list_node.node_id}" name="ContentNodeID"/>
                        <input type="hidden" value="{$newsletter_list_node.node_id}" name="NodeID"/>
                        <input type="hidden" value="cjw_newsletter_edition" name="ClassIdentifier"/>
                        <input class="a4-btn a4-btn-small" type="submit" name="NewButton" value="{'Create newsletter here'|i18n( 'cjw_newsletter/index' )}" />
                    </form>
                    {/if}
                </td>
            </tr>

            {* edition *}
            {def $edition_draft_node_list = fetch('content','list',
                                                   hash('parent_node_id', $newsletter_list_node.node_id,
                                                        'class_filter_type', 'include',
                                                        'class_filter_array', array( 'cjw_newsletter_edition' ),
                                                        'extended_attribute_filter',
                                                           hash( 'id', 'CjwNewsletterEditionFilter',
                                                                 'params', hash( 'status', 'draft' ) )
                                                                    ) )}
            {if $edition_draft_node_list|count|gt(0)}
                {foreach $edition_draft_node_list as $edition_draft_node}
                    <tr>
                    <td class="cjwnl-a4-level-1"><span class="a4-icon a4-bi-pencil-square" role="img" aria-label="{'draft'|i18n( 'cjw_newsletter/index' )}" title="{'draft'|i18n( 'cjw_newsletter/index' )}"></span> <a href={$edition_draft_node.url_alias|ezurl}>{$edition_draft_node.name|wash()}</a></td>
                    <td>
                        {if $edition_draft_node.can_edit}
                        <form action={'content/action'|ezurl()} method="post">
                            <input type="hidden" value="{$edition_draft_node.node_id}" name="TopLevelNode"/>
                            <input type="hidden" value="{$edition_draft_node.node_id}" name="ContentNodeID"/>
                            <input type="hidden" value="{$edition_draft_node.contentobject_id}" name="ContentObjectID" />
                            <input type="hidden" value="{'newsletter/index'}" name="RedirectIfDiscarded" />
                            <input type="hidden" name="ContentObjectLanguageCode" value="{$edition_draft_node.object.current_language}" />
                            <input class="a4-btn a4-btn-small" type="submit" title="{'Edit newsletter'|i18n( 'cjw_newsletter/index' )}" value="{'Edit'|i18n( 'cjw_newsletter/index' )}" name="EditButton" />
                        </form>
                        {/if}
                    </td>
                    </tr>
                {/foreach}
            {/if}
            {undef $edition_draft_node_list}

            {* virtual list *}
            {def $newsletter_v_list_node_list = fetch('content', 'list',
                                                       hash( 'parent_node_id',$newsletter_list_node.node_id,
                                                             'class_filter_type', 'include',
                                                             'class_filter_array', array( 'cjw_newsletter_list_virtual' )
                                                           ))}

            {foreach $newsletter_v_list_node_list as $newsletter_v_list_node}

            <tr>
                <td class="cjwnl-a4-level-1">{$newsletter_v_list_node.class_identifier|class_icon( 'small' )} <a href={$newsletter_v_list_node.url_alias|ezurl}>{$newsletter_v_list_node.name|wash()} / {$newsletter_v_list_node.data_map.title.content|wash()}</a></td>
                <td>
                    {if $newsletter_v_list_node.can_create}
                    <form action={'content/action'|ezurl()} name="CreateNewNewsletterEdition" method="post">
                        <input type="hidden" value="{ezini( 'RegionalSettings', 'ContentObjectLocale' )}" name="ContentLanguageCode"/>
                        <input type="hidden" value="{$newsletter_v_list_node.node_id}" name="ContentNodeID"/>
                        <input type="hidden" value="{$newsletter_v_list_node.node_id}" name="NodeID"/>
                        <input type="hidden" value="cjw_newsletter_edition" name="ClassIdentifier"/>
                        <input class="a4-btn a4-btn-small" type="submit" name="NewButton" value="{'Create newsletter here'|i18n( 'cjw_newsletter/index' )}" />
                    </form>
                    {/if}
                </td>
            </tr>

            {* virtual edition *}
            {def $edition_draft_node_list = fetch('content','list',
                                                   hash('parent_node_id', $newsletter_v_list_node.node_id,
                                                        'class_filter_type', 'include',
                                                        'class_filter_array', array( 'cjw_newsletter_edition' ),
                                                        'extended_attribute_filter',
                                                           hash( 'id', 'CjwNewsletterEditionFilter',
                                                                 'params', hash( 'status', 'draft' ) )
                                                                    ) )}
            {if $edition_draft_node_list|count|gt(0)}
                {foreach $edition_draft_node_list as $edition_draft_node}
                    <tr>
                    <td class="cjwnl-a4-level-2"><span class="a4-icon a4-bi-pencil-square" role="img" aria-label="{'draft'|i18n( 'cjw_newsletter/index' )}" title="{'draft'|i18n( 'cjw_newsletter/index' )}"></span> <a href={$edition_draft_node.url_alias|ezurl}>{$edition_draft_node.name|wash()}</a></td>
                    <td>
                        {if $edition_draft_node.can_edit}
                        <form action={'content/action'|ezurl()} method="post">
                            <input type="hidden" value="{$edition_draft_node.node_id}" name="TopLevelNode"/>
                            <input type="hidden" value="{$edition_draft_node.node_id}" name="ContentNodeID"/>
                            <input type="hidden" value="{$edition_draft_node.contentobject_id}" name="ContentObjectID" />
                            <input type="hidden" value="{'newsletter/index'}" name="RedirectIfDiscarded" />
                            <input type="hidden" name="ContentObjectLanguageCode" value="{$edition_draft_node.object.current_language}" />
                            <input class="a4-btn a4-btn-small" type="submit" title="{'Edit newsletter'|i18n( 'cjw_newsletter/index' )}" value="{'Edit'|i18n( 'cjw_newsletter/index' )}" name="EditButton" />
                        </form>
                        {/if}
                    </td>
                    </tr>
                {/foreach}
            {/if}
            {undef $edition_draft_node_list}

            {/foreach}
            {undef $newsletter_v_list_node_list}

        {/foreach}
        {undef $nl_list_class_filter_array $newsletter_list_node_list}
        </tbody>
    </table>
</div>
