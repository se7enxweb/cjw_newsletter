{* Edit a newsletter user in admin4: the master data, the rows of the feature areas and the subscriptions per list.
   Same data and actions as design/standard/templates/newsletter/user_edit.tpl. The rows of the feature areas
   ([ExtensionPointSettings] UserEditParts[]) are still <tr><th>..</th><td>..</td></tr>. *}
<div class="a4-page newsletter newsletter-user_edit">

    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-people cjwnl-a4-lead" aria-hidden="true"></span>{$newsletter_user.name|wash} &lt;{$newsletter_user.email|wash}&gt; [{'Newsletter user'|i18n( 'cjw_newsletter/user_view',, hash() )|wash}]</h1>
        </div>
    </div>

    {* feedback message *}
    {if and( is_set( $message_feedback ), $message_feedback|ne( '' ) )}
    <div class="a4-notice is-ok" role="status">
        <p class="a4-notice-title">{$message_feedback|wash}</p>
    </div>
    {/if}

    {* warnings *}
    {if and( is_set( $warning_array ), $warning_array|count|ne( 0 ) )}
    <div class="a4-notice is-warn" role="alert">
        <h2 class="a4-notice-title">{'Input did not validate'|i18n('cjw_newsletter/subscribe')}</h2>
        <ul>
        {foreach $warning_array as $index => $messageArrayItem}
            <li><span class="key">{$messageArrayItem.field_key|wash}: </span><span class="text">{$messageArrayItem.message|wash()}</span></li>
        {/foreach}
        </ul>
    </div>
    {/if}

<form action={concat( 'newsletter/user_edit/', $newsletter_user_id )|ezurl} method="post">
<input type="hidden" name="RedirectUrlActionCancel" value="{$redirect_url_action_cancel|wash}" />
<input type="hidden" name="RedirectUrlActionStore" value="{$redirect_url_action_store|wash}" />

    <div class="a4-table-wrap">
        <table class="a4-table cjwnl-a4-details">
            <tbody>
            <tr><th scope="row">{'Name'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.name|wash}</td></tr>
            <tr><th scope="row">{'Id'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.id|wash}</td></tr>
            <tr><th scope="row">{'Status'|i18n( 'cjw_newsletter/user_view' )}</th><td title="{$newsletter_user.status|wash}">{$newsletter_user.status_string|wash}</td></tr>
            <tr>
                <th scope="row"><label for="nl-user-email">{'Email'|i18n( 'cjw_newsletter/user_view' )}</label></th>
                <td><input id="nl-user-email" class="a4-input halfbox" type="text" name="Subscription_Email" value="{$newsletter_user.email|wash}" /></td>
            </tr>
            <tr>
                <th scope="row" id="nl-user-salutation">{'Salutation'|i18n( 'cjw_newsletter/user_view' )}</th>
                <td title="{'Salutation'|i18n( 'cjw_newsletter/user_view' )}">
                    <div role="radiogroup" aria-labelledby="nl-user-salutation">
                    {foreach $available_salutation_array as $salutation_id => $salutataion_name}
                    <label class="a4-inline-label"><input type="radio" name="Subscription_Salutation" value="{$salutation_id|wash}"{if $newsletter_user.salutation|eq( $salutation_id )} checked="checked"{/if} title="{$salutataion_name|wash}" /> {$salutataion_name|wash}</label>&nbsp;
                    {/foreach}
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="nl-user-first-name">{'First name'|i18n( 'cjw_newsletter/user_view' )}</label></th>
                <td><input id="nl-user-first-name" class="a4-input halfbox" type="text" name="Subscription_FirstName" value="{$newsletter_user.first_name|wash}" title="{'First name of newsletter user.'|i18n( 'cjw_newsletter/user_edit' )}" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="nl-user-last-name">{'Last name'|i18n( 'cjw_newsletter/user_view' )}</label></th>
                <td><input id="nl-user-last-name" class="a4-input halfbox" type="text" name="Subscription_LastName" value="{$newsletter_user.last_name|wash}" title="{'Last name of newsletter user.'|i18n( 'cjw_newsletter/user_edit' )}" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="nl-user-note">{'Note'|i18n( 'cjw_newsletter/user_view' )}</label></th>
                <td><textarea id="nl-user-note" class="a4-input box" name="Subscription_Note" cols="50" rows="10">{$newsletter_user.note|wash}</textarea></td>
            </tr>
            {* the rows the feature areas add ([ExtensionPointSettings] UserEditParts[]): <tr><th>..</th><td>..</td></tr> *}
            {if is_set( $user_edit_parts )}{foreach $user_edit_parts as $user_edit_part}{include uri=$user_edit_part newsletter_user=$newsletter_user}{/foreach}{/if}
            </tbody>
        </table>
    </div>

    <section class="a4-section" aria-labelledby="nl-user-subscriptions">
        <h2 class="a4-h2" id="nl-user-subscriptions">{'Subscriptions'|i18n( 'cjw_newsletter/user_edit' )}</h2>
        {* list subscribe, remove *}
        {* fetch all available newsletter systems *}
        {def $newsletter_root_node_id = 2
             $newsletter_system_node_list = fetch( 'content', 'tree', hash('parent_node_id', $newsletter_root_node_id,
                                                                           'class_filter_type', 'include',
                                                                           'class_filter_array', array( 'cjw_newsletter_system' ),
                                                                           'sort_by', array( 'name', true() ),
                                                                           'limitation', hash( ) ))
             $available_subscription_status_id_name_array = cjw_newsletter_variable( 'available_subscription_status_id_name_array' )
             $status_id_array_enabled =  array()}

        {* set available status id for status selection list *}
        {if $newsletter_user.status|eq( 8 )}
            {set $status_id_array_enabled = array()}
        {else}
            {* as admin only allow set subscription status approved, remove by admin *}
            {set $status_id_array_enabled = array( 2, 4 )}
        {/if}

        {foreach $newsletter_system_node_list as $system_node}
        {def $newsletter_list_node_list = fetch( 'content', 'tree',
                                                 hash('parent_node_id', $system_node.node_id,
                                                      'class_filter_type', 'include',
                                                      'class_filter_array', array( 'cjw_newsletter_list' ),
                                                      'limitation', hash( ) )) }
        <div class="newsletter-system-design">
            <h3 class="a4-h3">{$system_node.data_map.title.content|wash}</h3>
            <div class="a4-table-wrap">
                <table class="a4-table">
                    <tbody>
                    {foreach $newsletter_list_node_list as $list_node}
                    <tr>
                    {def $list_id = $list_node.contentobject_id
                         $list_content = $list_node.data_map.newsletter_list.content
                         $subscription_array = $newsletter_user_subscription_array
                         $created = 0
                         $confirmed = 0
                         $approved = 0
                         $removed = 0
                         $bounced = 0
                         $blacklisted = 0
                         $subscription = null
                         $list_selected_output_format_array = array()
                         $selected_output_format_array = array()
                         $status = -1
                         $is_removed = false()
                         $subscription_hash = ''
                         $td_counter = 0
                         $modified = 0}
                    {if is_set( $subscription_array[ $list_id ] )}
                        {set $subscription = $subscription_array[ $list_id ]
                             $list_selected_output_format_array = $subscription.output_format_array
                             $selected_output_format_array = $list_selected_output_format_array[ $list_id ]
                             $created = $subscription.created
                             $confirmed = $subscription.confirmed
                             $removed = $subscription.removed
                             $approved = $subscription.approved
                             $bounced = $subscription.bounced
                             $blacklisted = $subscription.blacklisted
                             $status = $subscription.status
                             $is_removed = $subscription.is_removed
                             $subscription_hash = $subscription.hash
                             $modified = $subscription.modified
                             $status_id_array_enabled = $status_id_array_enabled|append( $status )|unique
                        }
                    {/if}
                        <td>
                            <input type="hidden" name="Subscription_IdArray[]" value="{$list_id}" />
                            <label class="a4-inline-label"><input type="checkbox" name="Subscription_ListArray[]" value="{$list_id}"{if and( $is_removed|not , is_set( $subscription_array[ $list_id ] ) )} checked="checked"{/if} title="{$list_node.data_map.title.content|wash}" disabled="disabled" /> {$list_node.data_map.title.content|wash}</label>
                        </td>
                        {* default value *}
                        {if $list_selected_output_format_array|count|eq(0)}
                            {set $list_selected_output_format_array = array( 0 )}
                        {/if}
                        <td class="newsletter-list">
                            {foreach $list_node.data_map.newsletter_list.content.output_format_array as $output_format_id => $output_format_name}
                            <label class="a4-inline-label"><input type="radio" name="Subscription_OutputFormatArray_{$list_id}[]" value="{$output_format_id|wash}" {if is_set( $list_selected_output_format_array[ $output_format_id ] )} checked="checked"{/if} title="{$output_format_name|wash}" /> {$output_format_name|wash}</label>
                            {/foreach}
                        </td>
                        <td>{* status selection box *}
                            <select class="a4-input" name="Subscription_StatusId_{$list_id}" aria-label="{'Status'|i18n( 'cjw_newsletter/user_view' )}: {$list_node.data_map.title.content|wash}">
                            {if $status|eq(-1)}<option value="-1">-</option>{/if}
                            {def $status_already_selected = false()}
                            {foreach $available_subscription_status_id_name_array as $status_id => $status_name}
                                {def $status_timestamp = 0
                                     $extra_string = false()
                                     $status_is_disabled = false()
                                     $status_is_selected = false()}
                                {if $status_id_array_enabled|contains($status_id)|not}
                                    {set $status_is_disabled = true()}
                                {/if}
                                {* if subscription create for current list => set status approve *}
                                {if and( $status_id|eq( 2 ),
                                         $list_id|eq( $add_subscription_for_list_id ),
                                         $status_already_selected|not )}
                                    {set $status_is_selected = true()}
                                    {set $status_already_selected = true()}
                                {elseif and( $status_id|eq( $status ),
                                             $status_already_selected|not )}
                                    {set $status_is_selected = true()}
                                    {set $status_already_selected = true()}
                                {/if}
                                {*pending*}
                                {if $status_id|eq( 0 )}
                                    {set $status_timestamp = $created}
                                {*confirmed*}
                                {elseif $status_id|eq( 1 )}
                                    {set $status_timestamp = $confirmed}
                                    {if $list_content.auto_approve_registered_user}
                                        {set $extra_string = concat(' [', 'auto approve'|i18n( 'cjw_newsletter/user_edit' ), ']')}
                                        {set $status_is_disabled = true()}
                                    {/if}
                                {*approved*}
                                {elseif $status_id|eq( 2 )}
                                    {set $status_timestamp = $approved}
                                {*removed self*}
                                {elseif $status_id|eq( 3 )}
                                    {set $status_timestamp = $removed}
                                {*removed by admin*}
                                {elseif $status_id|eq( 4 )}
                                    {set $status_timestamp = $removed}
                                {/if}
                                <option value="{$status_id}"{if $status_is_selected} selected="selected"{/if}{if $status_is_disabled} disabled="disabled"{/if}>{if $status|eq( $status_id )}[{/if}{$status_name}{if $status|eq( $status_id )}]{/if}{$extra_string}{if $status_timestamp|ne(0)} - {$status_timestamp|datetime( 'custom', '%j.%m.%Y %H:%i' )}{/if}</option>
                                {undef $status_timestamp
                                       $extra_string
                                       $status_is_disabled
                                       $status_is_selected}
                            {/foreach}
                            {undef $status_already_selected}
                            </select>
                        </td>
                        <td>
                            {if $modified|ne(0)}{'Modified'|i18n( 'cjw_newsletter/user_edit' )}: {$modified|datetime( 'custom', '%j.%m.%Y %H:%i' )}{/if}
                        </td>
                    {undef $list_id
                           $list_content
                           $list_selected_output_format_array
                           $subscription_array
                           $subscription
                           $created
                           $confirmed
                           $removed
                           $approved
                           $bounced
                           $blacklisted
                           $status
                           $is_removed
                           $subscription_hash
                           $td_counter
                           $selected_output_format_array
                           $modified}
                    </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
        {undef $newsletter_list_node_list}
        {/foreach}
    </section>

    <div class="a4-toolbar">
        {* if blacklisted - do not edit *}
        {if $newsletter_user.status|eq(8)}
        <button class="a4-btn a4-btn-primary" type="button" disabled="disabled">{'Store and exit'|i18n( 'cjw_newsletter/user_edit' )}</button>
        {else}
        <button class="a4-btn a4-btn-primary" type="submit" name="StoreButton" value="{'Store and exit'|i18n( 'cjw_newsletter/user_edit' )}">{'Store and exit'|i18n( 'cjw_newsletter/user_edit' )}</button>
        {/if}
        <button class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/user_edit' )}">{'Cancel'|i18n( 'cjw_newsletter/user_edit' )}</button>
    </div>
</form>
</div>
