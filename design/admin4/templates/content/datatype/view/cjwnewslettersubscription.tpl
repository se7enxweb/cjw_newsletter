{* The newsletter subscription attribute in the object view (a user's lists), admin4. Same data as
   design/standard/templates/content/datatype/view/cjwnewslettersubscription.tpl: every list of the newsletter root
   that the current siteaccess may show, with yes or no as admin4's badge. *}
{def $newsletter_root_node_id = ezini( 'NewsletterSettings', 'RootFolderNodeId', 'cjw_newsletter.ini' )
     $newsletter_user = $attribute.content.existing_newsletter_user
     $subscription_array = array()}
{if is_object( $newsletter_user )}
    {set $subscription_array = $newsletter_user.subscription_array}
{/if}
{def $newsletter_list_node_list = fetch( 'content', 'tree', hash( 'parent_node_id', $newsletter_root_node_id,
                                                                  'extended_attribute_filter', hash( 'id', 'CjwNewsletterListFilter',
                                                                                                     'params', hash( 'siteaccess', array( 'current_siteaccess' ) ) ),
                                                                  'class_filter_type', 'include',
                                                                  'class_filter_array', array( 'cjw_newsletter_list' ),
                                                                  'limitation', hash() ) )}
{if $newsletter_list_node_list|count}
<ul class="a4-plain">
{foreach $newsletter_list_node_list as $list_node}
    {if and( is_set( $subscription_array[ $list_node.contentobject_id ] ), $subscription_array[ $list_node.contentobject_id ].is_removed|not )}
    <li>{$list_node.data_map.title.content|wash} <span class="a4-badge is-ok">{'yes'|i18n( 'cjw_newsletter/datatype/cjwnewslettersubscription' )}</span></li>
    {else}
    <li>{$list_node.data_map.title.content|wash} <span class="a4-badge">{'no'|i18n( 'cjw_newsletter/datatype/cjwnewslettersubscription' )}</span></li>
    {/if}
{/foreach}
</ul>
{/if}
{undef $newsletter_root_node_id $newsletter_user $subscription_array $newsletter_list_node_list}
