{* The newsletter subscription attribute in the object edit form (a user's lists), admin4. Same fetches and POST names
   as design/standard/templates/content/datatype/edit/cjwnewslettersubscription.tpl: one row per list of every
   newsletter system the current siteaccess may show, a box to subscribe, and the output format where a list offers
   more than one. The table is admin4's a4-table instead of a borderless layout table. *}
{def $attribute_content = $attribute.content
     $newsletter_user = $attribute_content.existing_newsletter_user
     $subscription_array = array()
     $newsletter_list_node_list = array()
     $rows = array()}

{if is_object( $newsletter_user )}
    {set $subscription_array = $newsletter_user.subscription_array}
{/if}

{def $newsletter_system_node_list = fetch( 'content', 'tree', hash( 'parent_node_id', 2,
                                                                    'class_filter_type', 'include',
                                                                    'class_filter_array', array( 'cjw_newsletter_system' ),
                                                                    'sort_by', array( 'name', true() ),
                                                                    'limitation', hash() ) )}

{* the lists of every system that the current siteaccess may show *}
{foreach $newsletter_system_node_list as $system_node}
    {set $newsletter_list_node_list = fetch( 'content', 'tree', hash( 'parent_node_id', $system_node.node_id,
                                                                      'extended_attribute_filter', hash( 'id', 'CjwNewsletterListFilter',
                                                                                                         'params', hash( 'siteaccess', array( 'current_siteaccess' ) ) ),
                                                                      'class_filter_type', 'include',
                                                                      'class_filter_array', array( 'cjw_newsletter_list' ),
                                                                      'limitation', hash() ) )}
    {foreach $newsletter_list_node_list as $list_node}
        {if $list_node.data_map.newsletter_list.content.output_format_array|count|ne( 0 )}
            {set $rows = $rows|append( $list_node )}
        {/if}
    {/foreach}
{/foreach}

{if $rows|count|eq( 0 )}
<p class="a4-empty">{'No newsletters available.'|i18n( 'cjw_newsletter/datatype/cjwnewslettersubscription' )}</p>
{else}
<div class="a4-table-wrap"><table class="a4-table">
    <tbody>
    {foreach $rows as $list_node}
    {def $list_id = $list_node.contentobject_id
         $list_title = $list_node.data_map.title.content
         $subscribed = false()
         $list_selected_output_format_array = array()
         $list_output_format_array = $list_node.data_map.newsletter_list.content.output_format_array}
    {if is_set( $subscription_array[ $list_id ] )}
        {set $subscribed = $subscription_array[ $list_id ].is_removed|not
             $list_selected_output_format_array = $subscription_array[ $list_id ].output_format_array}
    {/if}
    <tr>
        <th scope="row">
            <input type="hidden" name="Subscription_IdArray[]" value="{$list_id}" />
            <label class="a4-check"><input type="checkbox" name="Subscription_ListArray[]" value="{$list_id}"{if $subscribed} checked="checked"{/if} /> <span>{$list_title|wash}</span></label>
        </th>
        <td>
        {if $list_output_format_array|count|gt( 1 )}
            <div class="a4-row" role="radiogroup" aria-label="{$list_title|wash}">
            {foreach $list_output_format_array as $output_format_id => $output_format_name}
                <label class="a4-check"><input type="radio" name="Subscription_OutputFormatArray_{$list_id}[]" value="{$output_format_id|wash}"{if is_set( $list_selected_output_format_array[ $output_format_id ] )} checked="checked"{/if} /> <span>{$output_format_name|wash}</span></label>
            {/foreach}
            </div>
        {else}
            {foreach $list_output_format_array as $output_format_id => $output_format_name}
            <input type="hidden" name="Subscription_OutputFormatArray_{$list_id}[]" value="{$output_format_id|wash}" /><span class="a4-muted">{$output_format_name|wash}</span>
            {/foreach}
        {/if}
        </td>
    </tr>
    {undef $list_id $list_title $subscribed $list_selected_output_format_array $list_output_format_array}
    {/foreach}
    </tbody>
</table></div>
{/if}

{undef $attribute_content $newsletter_user $subscription_array $newsletter_list_node_list $rows $newsletter_system_node_list}
