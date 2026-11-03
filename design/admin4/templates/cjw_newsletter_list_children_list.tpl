{* The editions of a newsletter list as a table (select, name, status, published, priority, edit), admin4. Same data and actions as design/standard/templates/cjw_newsletter_list_children_list.tpl (the admin2 copy). *}
<div class="content-navigation-childlist">
    <div class="a4-table-wrap">
    <table class="a4-table">
        <thead>
        <tr>
            {* Remove column *}
            <th scope="col" class="remove"><button type="button" class="a4-icon-btn" data-cjwnl-toggle-checkboxes="DeleteIDArray[]" data-cjwnl-form="children" title="{'Invert selection.'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}" aria-label="{'Invert selection.'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}"><span class="a4-icon a4-bi-check2-square is-medium" aria-hidden="true"></span></button></th>

            {* Name column *}
            <th scope="col" class="name">{'Name'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}</th>

            {* Status column *}
            <th scope="col" class="status">{'Status'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}</th>

            {* Class type column *}
            <th scope="col" class="published">{'Published'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}</th>

            {* Priority column *}
            {if eq( $node.sort_array[0][0], 'priority' )}
                <th scope="col" class="priority">{'Priority'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}</th>
            {/if}

            {* Edit column *}
            <th scope="col" class="edit"><span class="a4-sr-only">{'Edit'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}</span></th>
        </tr>
        </thead>
        <tbody>
        {foreach $children as $child}
           {def $child_name = $child.name|wash
                $node_name = $node.name
                $nodeContent = $child.object}


           {def $editionAttributeContent = $child.data_map.newsletter_edition.content
                $editionStatus = $editionAttributeContent.status}

           <tr>

                {* Remove checkbox *}
                <td>
                {if $child.can_remove}
                    <input type="checkbox" name="DeleteIDArray[]" value="{$child.node_id}" title="{'Use these checkboxes to select items for removal. Click the "Remove selected" button to  remove the selected items.'|i18n( 'design/admin/node/view/full' )|wash()}" aria-label="{'Use these checkboxes to select items for removal. Click the "Remove selected" button to  remove the selected items.'|i18n( 'design/admin/node/view/full' )|wash()}" />
                    {else}
                    <input type="checkbox" name="DeleteIDArray[]" value="{$child.node_id}" title="{'You do not have permission to remove this item.'|i18n( 'design/admin/node/view/full' )}" aria-label="{'You do not have permission to remove this item.'|i18n( 'design/admin/node/view/full' )}" disabled="disabled" />
                {/if}
                </td>

                {* Name *}
                <td>{node_view_gui view=line content_node=$child}
                    {if $nodeContent.class_identifier|eq('user')}
                        {if not($nodeContent.data_map['user_account'].content.is_enabled)}
                           <span class="userstatus-disabled">{'(disabled)'|i18n("cjw_newsletter/cjw_newsletter_list_children_list")}</span>
                        {/if}
                        {if $nodeContent.data_map['user_account'].content.is_locked}
                           <span class="userstatus-disabled">{'(locked)'|i18n("cjw_newsletter/cjw_newsletter_list_children_list")}</span>
                        {/if}
                    {/if}
                </td>

                {* Status *}
                <td>
                    {switch match=$editionStatus}
                        {case match='draft'}
                           <span class="a4-badge is-info" title="{$editionStatus|wash}"><span class="a4-icon a4-bi-pencil-square is-small" role="img" aria-label="{$editionStatus|wash}"></span></span>
                       {/case}
                       {case match='process'}
                           <span class="a4-badge is-warn" title="{$editionStatus|wash}"><span class="a4-icon a4-bi-hourglass-split is-small" role="img" aria-label="{$editionStatus|wash}"></span></span>
                       {/case}
                       {case match='archive'}
                           <span class="a4-badge is-ok" title="{$editionStatus|wash}"><span class="a4-icon a4-bi-archive is-small" role="img" aria-label="{$editionStatus|wash}"></span></span>
                       {/case}
                       {case match='abort'}
                           <span class="a4-badge is-bad" title="{$editionStatus|wash}"><span class="a4-icon a4-bi-x-circle-fill is-small" role="img" aria-label="{$editionStatus|wash}"></span></span>
                       {/case}
                    {/switch}
               </td>

               {* Published *}
               <td class="published">{$child.object.published|l10n( shortdatetime )}</td>

               {* Priority *}
                   {if eq( $node.sort_array[0][0], 'priority' )}
                       <td>
                           {if $node.can_edit}
                               <input class="a4-input" type="text" name="Priority[]" size="3" value="{$child.priority}" title="{'Use the priority fields to control the order in which the items appear. You can use both positive and negative integers. Click the "Update priorities" button to apply the changes.'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )|wash()}" aria-label="{'Priority'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}" />
                               <input type="hidden" name="PriorityID[]" value="{$child.node_id}" />
                           {else}
                               <input class="a4-input" type="text" name="Priority[]" size="3" value="{$child.priority}" title="{'You are not allowed to update the priorities because you do not have permission to edit <%node_name>.'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list',, hash( '%node_name', $node_name ) )|wash}" aria-label="{'Priority'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}" disabled="disabled" />
                           {/if}
                       </td>
                   {/if}

                {* Edit button *}
                <td>
                   {if $child.can_edit}
                       {* show edit button only if nl edition is not in sending process *}
                       {if $editionAttributeContent.is_draft|not}
                           <span class="a4-icon-btn is-disabled" aria-disabled="true" role="img" aria-label="{'Edit'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}" title="{'The edition %child_name is already in sending process.'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list',, hash( '%child_name', $child_name ) )|wash}"><span class="a4-icon a4-bi-pencil is-medium" aria-hidden="true"></span></span>
                       {else}
                           <a class="a4-icon-btn" href={concat( 'content/edit/', $child.contentobject_id )|ezurl} aria-label="{'Edit'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}" title="{'Edit <%child_name>.'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list',, hash( '%child_name', $child_name ) )|wash}"><span class="a4-icon a4-bi-pencil is-medium" aria-hidden="true"></span></a>
                       {/if}
                   {else}
                       <span class="a4-icon-btn is-disabled" aria-disabled="true" role="img" aria-label="{'Edit'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}" title="{'You do not have permission to edit "%child_name".'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list',, hash( '%child_name', $child_name ) )|wash}"><span class="a4-icon a4-bi-pencil is-medium" aria-hidden="true"></span></span>
                   {/if}
               </td>
           </tr>
           {undef $child_name $node_name $nodeContent $editionAttributeContent $editionStatus}

       {/foreach}
        </tbody>
    </table>
    </div>
</div>
