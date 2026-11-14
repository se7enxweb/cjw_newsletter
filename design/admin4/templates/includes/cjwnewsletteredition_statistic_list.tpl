{* A table of newsletter editions with status, send statistic and modified date, admin4. Same data and actions as design/standard/templates/includes/cjwnewsletteredition_statistic_list.tpl.
   {include uri='design:includes/cjwnewsletteredition_statistic_list.tpl'
            page_uri=$page_uri
            edition_node_list=$edition_node_list
            edition_node_list_count=$edition_node_list_count
            view_parameters=$view_parameters
            limit=$limit} *}

{if is_set( $show_actions_colum )|not}
    {def $show_actions_colum = false()}
{/if}

{if is_set( $can_copy )|not}
    {def $can_copy = true()}
{/if}

<div class="a4-table-wrap">
    <table class="a4-table">
        <thead>
        <tr>
            {* Name column *}
            <th scope="col" class="name">{'Name'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}</th>

            {* Status column *}
            <th scope="col" class="status">{'Status'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}</th>

            <th scope="col">{'Mails'|i18n( 'cjw_newsletter/index' )}</th>
            <th scope="col">{'Bounced'|i18n( 'cjw_newsletter/index' )}</th>
            <th scope="col" class="modified">{'Modified'|i18n( 'cjw_newsletter/cjw_newsletter_list_children_list' )}</th>

            {if $show_actions_colum}

            {* Copy column *}
            <th scope="col" class="copy"><span class="a4-sr-only">{'Copy'|i18n( 'design/admin/node/view/full' )}</span></th>

            {* Edit column *}
            <th scope="col" class="edit"><span class="a4-sr-only">{'Edit newsletter'|i18n( 'cjw_newsletter/index' )}</span></th>
            {/if}
        </tr>
        </thead>
        <tbody>

        {foreach $edition_node_list as $edition_node}
        {def $child_name = $edition_node.name|wash
             $node_content = $edition_node.object
             $newsletter_edition_attribute_content = $edition_node.data_map.newsletter_edition.content
             $edition_status = $newsletter_edition_attribute_content.status
             $nl_status_is_draft = $newsletter_edition_attribute_content.is_draft
             $current_send_statistic = false()}
        {if $edition_status|ne('draft')}
            {set $current_send_statistic = $newsletter_edition_attribute_content.edition_send_array.current.0.send_items_statistic}
        {/if}

         <tr>
            {* Name *}
            <td>
                {$edition_node.class_identifier|class_icon( 'small' )} <a href={$edition_node.url_alias|ezurl}>{$edition_node.name|wash()}</a>
            </td>
            {* Status *}
            <td>
                {switch match=$edition_status}
                    {case match='draft'}
                        <span class="a4-badge is-info" title="{$edition_status|wash}"><span class="a4-icon a4-bi-pencil-square is-small" role="img" aria-label="{$edition_status|wash}"></span></span>
                    {/case}
                    {case match='process'}
                        <span class="a4-badge is-warn" title="{$edition_status|wash}"><span class="a4-icon a4-bi-hourglass-split is-small" role="img" aria-label="{$edition_status|wash}"></span></span>
                    {/case}
                    {case match='archive'}
                        <span class="a4-badge is-ok" title="{$edition_status|wash}"><span class="a4-icon a4-bi-archive is-small" role="img" aria-label="{$edition_status|wash}"></span></span>
                    {/case}
                    {case match='abort'}
                        <span class="a4-badge is-bad" title="{$edition_status|wash}"><span class="a4-icon a4-bi-x-circle-fill is-small" role="img" aria-label="{$edition_status|wash}"></span></span>
                    {/case}
                {/switch}
            </td>
            <td class="a4-nowrap">
                {if $edition_status|ne('draft')}
                    {$current_send_statistic.items_send|wash}/{$current_send_statistic.items_count|wash} ({$current_send_statistic.items_send_in_percent|wash}%)
                {/if}
            </td>
            <td class="a4-nowrap">
                {if $edition_status|ne('draft')}
                    {$current_send_statistic.items_bounced|wash}
                {/if}
            </td>

            {* Published *}
            <td class="published a4-nowrap">{$edition_node.object.modified|l10n( 'shortdatetime' )}</td>

            {if $show_actions_colum}
            {* Copy button *}
            <td>
            {if $can_copy}
                <a class="a4-icon-btn" href={concat( 'content/copysubtree/', $edition_node.node_id )|ezurl()} aria-label="{'Copy'|i18n( 'design/admin/node/view/full' )}" title="{'Create a copy of <%child_name>.'|i18n( 'design/admin/node/view/full',, hash( '%child_name', $child_name ) )|wash}"><span class="a4-icon a4-bi-file-earmark is-medium" aria-hidden="true"></span></a>
            {else}
                <span class="a4-icon-btn is-disabled" aria-disabled="true" role="img" aria-label="{'Copy'|i18n( 'design/admin/node/view/full' )}" title=""><span class="a4-icon a4-bi-file-earmark is-medium" aria-hidden="true"></span></span>
            {/if}
            </td>
            {* Edit button *}
            <td>
            {if and( $edition_node.can_edit, $nl_status_is_draft )}
                <form action={'content/action'|ezurl()} method="post">
                    <input type="hidden" value="{$edition_node.node_id}" name="TopLevelNode"/>
                    <input type="hidden" value="{$edition_node.node_id}" name="ContentNodeID"/>
                    <input type="hidden" value="{$edition_node.contentobject_id}" name="ContentObjectID" />
                    <input type="hidden" name="ContentObjectLanguageCode" value="{$edition_node.object.current_language}">
                    <button class="a4-icon-btn" type="submit" name="EditButton" aria-label="{'Edit newsletter'|i18n( 'cjw_newsletter/index' )}" title="{'Edit newsletter'|i18n( 'cjw_newsletter/index' )}"><span class="a4-icon a4-bi-pencil is-medium" aria-hidden="true"></span></button>
                </form>
            {else}
                <span class="a4-icon-btn is-disabled" aria-disabled="true" role="img" aria-label="{'Edit newsletter'|i18n( 'cjw_newsletter/index' )}" title=""><span class="a4-icon a4-bi-pencil is-medium" aria-hidden="true"></span></span>
            {/if}
            </td>
            {/if}
        </tr>

        {undef $child_name $node_content $newsletter_edition_attribute_content $edition_status $nl_status_is_draft $current_send_statistic}
        {/foreach}
        </tbody>
    </table>
</div>
