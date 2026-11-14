{* One newsletter subscription in admin4: the full view with all related data, approve and remove.
   Same data and actions as design/standard/templates/newsletter/subscription_view.tpl *}
<div class="a4-page newsletter newsletter-subscription_view">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Newsletter subscription'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</h1>
        </div>
    </div>

    {if $message|ne('')}
    <div class="a4-notice is-info" role="status">
        <p class="a4-notice-title">{$message|wash}</p>
    </div>
    {/if}

    <div class="a4-table-wrap">
        <table class="a4-table cjwnl-a4-details">
            <tbody>
            <tr><th scope="row">{'Subscription Id'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{$subscription.id|wash}</td></tr>
            <tr><th scope="row">{'Subscription list'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td><a href={concat( 'newsletter/subscription_list/',$newsletter_list_node_id )|ezurl}>{$newsletter_list_node.name|wash}</a></td></tr>
            <tr><th scope="row">{'Newsletter user'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td><a href={concat('newsletter/user_view/',$subscription.newsletter_user_id )|ezurl} title="{$subscription.newsletter_user_id|wash}">{$subscription.newsletter_user.name|wash} &lt;{$subscription.newsletter_user.email|wash}&gt;</a> ({$subscription.newsletter_user.status_string})</td></tr>
            <tr><th scope="row">{'Status'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td title="{$subscription.status|wash}">{$subscription.status_string|wash}</td></tr>
            <tr><th scope="row">{'Format'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td title="{$subscription.output_format_array_string|wash}">{$subscription.output_format_array|implode(',')}</td></tr>
            <tr><th scope="row">{'Created'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{$subscription.created|l10n( shortdatetime )}</td></tr>
            <tr><th scope="row">{'Creator'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td title="{$subscription.creator_contentobject_id|wash}">{if is_object($subscription.creator)}{$subscription.creator.name|wash}{/if}</td></tr>
            <tr><th scope="row">{'Modified'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{if $subscription.modified|ne(0)}{$subscription.modified|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Modifier'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td title="{$subscription.modifier_contentobject_id|wash}">{if $subscription.modifier_contentobject_id|ne(0)}{if is_object( $subscription.modifier )}{$subscription.modifier.name|wash}{/if}{/if}</td></tr>
            <tr><th scope="row">{'Confirmed'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{if $subscription.confirmed|ne(0)}{$subscription.confirmed|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Approved'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{if $subscription.approved|ne(0)}{$subscription.approved|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Removed'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{if $subscription.removed|ne(0)}{$subscription.removed|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Bounced'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{if $subscription.bounced|ne(0)}{$subscription.bounced|l10n( shortdatetime )}{/if}</td></tr>
            <tr><th scope="row">{'Hash'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{$subscription.hash|wash}</td></tr>
            <tr><th scope="row">{'Remote id'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{$subscription.remote_id|wash}</td></tr>
            <tr><th scope="row">{'Import id'|i18n( 'cjw_newsletter/subscription_view',, hash() )}</th><td>{if $subscription.import_id|ne(0)}<a href={concat( 'newsletter/import_view/', $subscription.import_id )|ezurl}>{$subscription.import_id|wash}</a>{/if}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="a4-toolbar cjwnl-a4-forms">
        {* only can approve if status not approve and not remove_self or blacklisted *}
        {if or( $subscription.status|eq(2), $subscription.status|eq(3), $subscription.status|eq(8) )}
            <button class="a4-btn" type="button" name="SubscriptionApproveButton" disabled="disabled">{'Approve subscription'|i18n( 'cjw_newsletter/subscription_view' )}</button>
        {else}
        <form name="ApproveForm" method="post" action="">
            <button class="a4-btn a4-btn-primary" type="submit" name="SubscriptionApproveButton" value="{'Approve subscription'|i18n( 'cjw_newsletter/subscription_view' )}"><span class="a4-icon a4-bi-check-lg" aria-hidden="true"></span> {'Approve subscription'|i18n( 'cjw_newsletter/subscription_view' )}</button>
        </form>
        {/if}
        {* only can remove if status not remove self / admin 3 our 4, or blacklisted 8 *}
        {if or( $subscription.status|eq(3), $subscription.status|eq(4), $subscription.status|eq(8) )}
            <button class="a4-btn" type="button" name="SubscriptionRemoveButton" disabled="disabled">{'Remove subscription'|i18n( 'cjw_newsletter/subscription_view' )}</button>
        {else}
        <form name="RemoveForm" method="post" action="">
            <button class="a4-btn a4-btn-danger" type="submit" name="SubscriptionRemoveButton" value="{'Remove subscription'|i18n( 'cjw_newsletter/subscription_view' )}"><span class="a4-icon a4-bi-trash" aria-hidden="true"></span> {'Remove subscription'|i18n( 'cjw_newsletter/subscription_view' )}</button>
        </form>
        {/if}
    </div>
</div>
