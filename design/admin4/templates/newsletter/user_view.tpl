{* A newsletter user in admin4: the full view with all related data, the subscriptions and the received newsletters.
   Same data and actions as design/standard/templates/newsletter/user_view.tpl. The rows of the feature areas
   ([ExtensionPointSettings] UserViewParts[]) are still <tr><th>..</th><td>..</td></tr>. *}
<div class="a4-page newsletter newsletter-user_view">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-people cjwnl-a4-lead" aria-hidden="true"></span>{$newsletter_user.name|wash} &lt;{$newsletter_user.email|wash}&gt; [{'Newsletter user'|i18n( 'cjw_newsletter/user_view',, hash() )|wash}]</h1>
        </div>
    </div>

    <div class="a4-table-wrap">
        <table class="a4-table cjwnl-a4-details">
            <tbody>
            <tr><th scope="row">{'Name'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.name|wash}</td></tr>
            <tr><th scope="row">{'Id'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.id|wash}</td></tr>
            <tr><th scope="row">{'Email'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.email|wash}</td></tr>
            <tr><th scope="row">{'Salutation'|i18n( 'cjw_newsletter/user_view' )}</th><td title="{$newsletter_user.salutation|wash}">{$newsletter_user.salutation_name|wash}</td></tr>
            <tr><th scope="row">{'First name'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.first_name|wash}</td></tr>
            <tr><th scope="row">{'Last name'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.last_name|wash}</td></tr>
            <tr><th scope="row">{'Status'|i18n( 'cjw_newsletter/user_view' )}</th><td title="{$newsletter_user.status|wash}">{$newsletter_user.status_string|wash}</td></tr>
            <tr>
                <th scope="row">{'eZ user id'|i18n( 'cjw_newsletter/user_view' )}</th>
                <td title="{$newsletter_user.ez_user_id|wash}">
                    {if is_object($newsletter_user.ez_user)}
                    <a href={concat('content/view/full/', $newsletter_user.ez_user.contentobject.main_node_id )|ezurl}>{$newsletter_user.ez_user.contentobject.name|wash}</a> ({if $newsletter_user.ez_user.is_enabled}{'enabled'|i18n( 'cjw_newsletter/user_view' )}{else}{'disabled'|i18n( 'cjw_newsletter/user_view' )}{/if})
                    {elseif $newsletter_user.ez_user_id|ne( 0 )}
                    <strong>{'Ez user with id %ez_user_id does not exist anymore!'|i18n( 'cjw_newsletter/user_view', '', hash('%ez_user_id', $newsletter_user.ez_user_id ) )}</strong>
                    {/if}
                </td>
            </tr>
            <tr><th scope="row">{'Creator'|i18n( 'cjw_newsletter/user_view' )}</th><td title="{$newsletter_user.creator_contentobject_id|wash}">{if is_object($newsletter_user.creator)}{$newsletter_user.creator.name|wash}{/if}</td></tr>
            <tr><th scope="row">{'Created'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.created|l10n( shortdatetime )}</td></tr>
            <tr><th scope="row">{'Modifier'|i18n( 'cjw_newsletter/user_view' )}</th><td title="{$newsletter_user.modifier_contentobject_id|wash}">{if is_object($newsletter_user.modifier)}{$newsletter_user.modifier.name|wash}{/if}</td></tr>
            <tr><th scope="row">{'Modified'|i18n( 'cjw_newsletter/user_view' )}</th><td>{if $newsletter_user.modified|ne(0)}{$newsletter_user.modified|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Confirmed'|i18n( 'cjw_newsletter/user_view' )}</th><td>{if $newsletter_user.confirmed|ne(0)}{$newsletter_user.confirmed|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Removed'|i18n( 'cjw_newsletter/user_view' )}</th><td>{if $newsletter_user.removed|ne(0)}{$newsletter_user.removed|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Bounced'|i18n( 'cjw_newsletter/user_view' )}</th><td>{if $newsletter_user.bounced|ne(0)}{$newsletter_user.bounced|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Blacklisted'|i18n( 'cjw_newsletter/user_view' )}</th><td>{if $newsletter_user.blacklisted|ne(0)}{$newsletter_user.blacklisted|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Hash'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.hash|wash}</td></tr>
            <tr><th scope="row">{'Bounce count'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.bounce_count|wash}</td></tr>
            <tr><th scope="row">{'Remote id'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.remote_id|wash}</td></tr>
            <tr><th scope="row">{'Import id'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.import_id|wash}</td></tr>
            <tr><th scope="row">{'External user id'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.external_user_id|wash}</td></tr>
            <tr><th scope="row">{'Note'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.note|wash|nl2br}</td></tr>
            {* the rows the feature areas add ([ExtensionPointSettings] UserViewParts[]): <tr><th>..</th><td>..</td></tr> *}
            {if is_set( $user_view_parts )}{foreach $user_view_parts as $user_view_part}{include uri=$user_view_part newsletter_user=$newsletter_user}{/foreach}{/if}
            <tr><th scope="row">{'Data xml'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.data_xml|wash}</td></tr>
            <tr><th scope="row">{'Data text'|i18n( 'cjw_newsletter/user_view' )}</th><td>{$newsletter_user.data_text|wash}</td></tr>
            {* Get CustomFieldMappingArray - exists a public name ? *}
            <tr>
                <th scope="row">{if is_set( $custom_field_names.custom_data_text_1 )}{$custom_field_names.custom_data_text_1|i18n( 'cjw_newsletter/user_view' )|wash}{else}{'Custom Data text 1'|i18n( 'cjw_newsletter/user_view' )}{/if}</th>
                <td>{$newsletter_user.custom_data_text_1|wash}</td>
            </tr>
            <tr>
                <th scope="row">{if is_set( $custom_field_names.custom_data_text_2 )}{$custom_field_names.custom_data_text_2|i18n( 'cjw_newsletter/user_view' )|wash}{else}{'Custom Data text 2'|i18n( 'cjw_newsletter/user_view' )}{/if}</th>
                <td>{$newsletter_user.custom_data_text_2|wash}</td>
            </tr>
            <tr>
                <th scope="row">{if is_set( $custom_field_names.custom_data_text_3 )}{$custom_field_names.custom_data_text_3|i18n( 'cjw_newsletter/user_view' )|wash}{else}{'Custom Data text 3'|i18n( 'cjw_newsletter/user_view' )}{/if}</th>
                <td>{$newsletter_user.custom_data_text_3|wash}</td>
            </tr>
            <tr>
                <th scope="row">{if is_set( $custom_field_names.custom_data_text_4 )}{$custom_field_names.custom_data_text_4|i18n( 'cjw_newsletter/user_view' )|wash}{else}{'Custom Data text 4'|i18n( 'cjw_newsletter/user_view' )}{/if}</th>
                <td>{$newsletter_user.custom_data_text_4|wash}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="a4-toolbar cjwnl-a4-forms">
        {* edit current user; status is not blacklisted *}
        {if $newsletter_user.status|ne(8)}
        <form method="post" action={concat( 'newsletter/user_edit/', $newsletter_user.id )|ezurl}>
            <button class="a4-btn" type="submit" name="EditUserButton" value="{'Edit'|i18n( 'cjw_newsletter/user_view' )}" title="{'Edit by admin'|i18n( 'cjw_newsletter/user_view' )}"><span class="a4-icon a4-bi-pencil" aria-hidden="true"></span> {'Edit'|i18n( 'cjw_newsletter/user_view' )}</button>
        </form>
        {else}
        <button class="a4-btn" type="button" disabled="disabled" title="{'Edit by admin'|i18n( 'cjw_newsletter/user_view' )}"><span class="a4-icon a4-bi-pencil" aria-hidden="true"></span> {'Edit'|i18n( 'cjw_newsletter/user_view' )}</button>
        {/if}
        <form method="post" action={concat( 'newsletter/user_remove/', $newsletter_user.id )|ezurl}>
            <input type="hidden" name="RedirectUrlActionRemove" value="newsletter/user_list/" />
            <input type="hidden" name="RedirectUrlActionCancel" value="{concat( 'newsletter/user_view/', $newsletter_user.id )}" />
            <button class="a4-btn a4-btn-danger" type="submit" name="DeleteUserButton" value="{'Remove'|i18n( 'cjw_newsletter/user_view' )}" title="{'Delete newsletter user and all subscriptions from database'|i18n( 'cjw_newsletter/user_view' )}"><span class="a4-icon a4-bi-trash" aria-hidden="true"></span> {'Remove'|i18n( 'cjw_newsletter/user_view' )}</button>
        </form>
        {* status is not blacklisted *}
        {if $newsletter_user.status|ne(8)}
        <form method="post" action={'newsletter/blacklist_item_add'|ezurl}>
            <input type="hidden" name="Email" value="{$newsletter_user.email|wash}" />
            <button class="a4-btn" type="submit" name="CreateBlacklistEntryButton" value="{'Add to blacklist'|i18n( 'cjw_newsletter/user_view' )}" title="{'Add to blacklist'|i18n( 'cjw_newsletter/user_view' )}"><span class="a4-icon a4-bi-lock-fill" aria-hidden="true"></span> {'Add to blacklist'|i18n( 'cjw_newsletter/user_view' )}</button>
        </form>
        {else}
        <form method="post" action={'newsletter/blacklist_item_remove'|ezurl}>
            <input type="hidden" name="Email" value="{$newsletter_user.email|wash}" />
            <button class="a4-btn" type="submit" name="RemoveBlacklistEntryButton" value="{'Remove from blacklist'|i18n( 'cjw_newsletter/user_view' )}" title="{'Remove from blacklist'|i18n( 'cjw_newsletter/user_view' )}">{'Remove from blacklist'|i18n( 'cjw_newsletter/user_view' )}</button>
        </form>
        {/if}
    </div>

    {* =============  list of all subscriptions ============ *}
    {def $subscription_array = $newsletter_user.subscription_array
         $subscription_array_count = $subscription_array|count}
    <section class="a4-section" aria-labelledby="nl-user-subscriptions">
        <h2 class="a4-h2" id="nl-user-subscriptions">{'Newsletter subscriptions'|i18n( 'cjw_newsletter/user_view',, hash( ) )} [{$subscription_array_count}]</h2>
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                <tr>
                    <th scope="col">{'Id'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'List name'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Format'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Status'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Created'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Modified'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Confirmed'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Approved'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Removed'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $subscription_array as $subscription}
                <tr>
                    <td><a href={concat('newsletter/subscription_view/', $subscription.id)|ezurl}>{$subscription.id|wash}</a></td>
                    <td><a href={concat('content/view/full/', $subscription.newsletter_list.main_node_id)|ezurl}>{$subscription.newsletter_list.name|wash}</a></td>
                    <td>{$subscription.output_format_array|implode(', ')}</td>
                    <td title="{$subscription.status|wash}">{$subscription.status_string|wash}</td>
                    <td>{cond( $subscription.created|gt(0), $subscription.created|l10n( shortdatetime ), 'n/a'|i18n( 'cjw_newsletter/subscription_list' ) )}</td>
                    <td>{cond( $subscription.modified|gt(0), $subscription.modified|l10n( shortdatetime ), 'n/a'|i18n( 'cjw_newsletter/subscription_list' ) )}</td>
                    <td>{cond( $subscription.confirmed|gt(0), $subscription.confirmed|l10n( shortdatetime ), 'n/a'|i18n( 'cjw_newsletter/subscription_list' ) )}</td>
                    <td>{cond( $subscription.approved|gt(0), $subscription.approved|l10n( shortdatetime ), 'n/a'|i18n( 'cjw_newsletter/subscription_list' ) )}</td>
                    <td>{cond( $subscription.removed|gt(0), $subscription.removed|l10n( shortdatetime ), 'n/a'|i18n( 'cjw_newsletter/subscription_list' ) )}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </section>

    {* =============  list of all send items ============ *}
    {def $edition_send_item_array = fetch( 'newsletter', 'edition_send_item_list', hash('newsletter_user_id', $newsletter_user.id ) )
         $edition_send_item_count = fetch( 'newsletter', 'edition_send_item_list_count', hash('newsletter_user_id', $newsletter_user.id ) )}
    <section class="a4-section" aria-labelledby="nl-user-received">
        <h2 class="a4-h2" id="nl-user-received">{'Newsletter received'|i18n( 'cjw_newsletter/user_view',, hash() )} [{$edition_send_item_count}]</h2>
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                <tr>
                    <th scope="col">{'Id'|i18n( 'cjw_newsletter/user_view' )}</th>
                    <th scope="col">{'Edition sent id'|i18n( 'cjw_newsletter/user_view' )}</th>
                    <th scope="col">{'Format'|i18n( 'cjw_newsletter/user_view' )}</th>
                    <th scope="col">{'Status'|i18n( 'cjw_newsletter/user_view' )}</th>
                    <th scope="col">{'Created'|i18n( 'cjw_newsletter/user_view' )}</th>
                    <th scope="col">{'Processed'|i18n( 'cjw_newsletter/user_view' )}</th>
                    <th scope="col">{'Opened'|i18n( 'cjw_newsletter/user_view' )}</th>
                    <th scope="col">{'Bounced'|i18n( 'cjw_newsletter/user_view' )}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $edition_send_item_array as $send_item}
                <tr>
                    <td><a target="_blank" href={concat( 'newsletter/preview_archive/', $send_item.edition_send_id,  '/', $send_item.output_format_id,  '/', $send_item.newsletter_user_id )|ezurl}>{$send_item.id|wash}</a></td>
                    <td>{$send_item.edition_send_id|wash}</td>
                    <td>{$send_item.output_format_id|wash}</td>
                    <td title="{$send_item.status|wash}">{$send_item.status_string|wash}</td>
                    <td>{cond( $send_item.created|gt(0), $send_item.created|l10n( shortdatetime ), '-' )}</td>
                    <td>{cond( $send_item.processed|gt(0), $send_item.processed|l10n( shortdatetime ), '-' )}</td>
                    <td>{* TODO opened *}-</td>
                    <td>{cond( $send_item.bounced|gt(0), $send_item.bounced|l10n( shortdatetime ), '-' )}</td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </section>
</div>
