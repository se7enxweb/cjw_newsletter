{* Confirm the removal of a newsletter user in admin4.
   Same data and actions as design/standard/templates/newsletter/user_remove.tpl *}
{def $base_uri = concat( 'newsletter/user_remove/', $newsletter_user_id )}
<div class="a4-page newsletter newsletter-user_remove">
<form enctype="multipart/form-data" name="user_remove" method="post" action={$base_uri|ezurl}>
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-people cjwnl-a4-lead" aria-hidden="true"></span>{'Remove newsletter user'|i18n( 'cjw_newsletter/user_remove',, hash() )|wash}</h1>
        </div>
    </div>

    {if is_set($warning)}
    <div class="a4-notice is-warn" role="alert">
        <p class="a4-notice-title">{$warning|wash}</p>
    </div>
    {/if}

    <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr>
                <th scope="col">{'Id'|i18n( 'cjw_newsletter/user_view' )}</th>
                <th scope="col">{'Email'|i18n( 'cjw_newsletter/user_view' )}</th>
                <th scope="col">{'Salutation'|i18n( 'cjw_newsletter/user_view' )}</th>
                <th scope="col">{'First name'|i18n( 'cjw_newsletter/user_view' )}</th>
                <th scope="col">{'Last name'|i18n( 'cjw_newsletter/user_view' )}</th>
                <th scope="col">{'Status'|i18n( 'cjw_newsletter/user_view' )}</th>
                <th scope="col" title="{'Subscription count'|i18n( 'cjw_newsletter/user_remove' )}">{'S'|i18n( 'cjw_newsletter/user_remove' )}</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{$newsletter_user.id|wash}</td>
                <td class="a4-wrap">{$newsletter_user.email|wash}</td>
                <td title="{$newsletter_user.salutation|wash}">{$newsletter_user.salutation_name|wash}</td>
                <td>{$newsletter_user.first_name|wash}</td>
                <td>{$newsletter_user.last_name|wash}</td>
                <td title="{$newsletter_user.status|wash}">{$newsletter_user.status_string|wash}</td>
                <td>{$newsletter_user.subscription_array|count}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="a4-toolbar">
        <input type="hidden" name="RedirectUrlActionRemove" value="{$redirect_url_action_remove}" />
        <input type="hidden" name="RedirectUrlActionCancel" value="{$redirect_url_action_cancel}" />
        <button class="a4-btn a4-btn-danger" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'cjw_newsletter/user_remove' )}" title="{'Remove'|i18n( 'cjw_newsletter/user_remove' )}"><span class="a4-icon a4-bi-trash" aria-hidden="true"></span> {'Remove'|i18n( 'cjw_newsletter/user_remove' )}</button>
        <button class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/user_remove' )}" title="{'Cancel'|i18n( 'cjw_newsletter/user_remove' )}">{'Cancel'|i18n( 'cjw_newsletter/user_remove' )}</button>
    </div>
</form>
</div>
