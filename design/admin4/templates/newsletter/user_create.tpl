{* Create a newsletter user in admin4.
   Same data and actions as design/standard/templates/newsletter/user_create.tpl *}
<div class="a4-page newsletter newsletter-user_create">

    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-people cjwnl-a4-lead" aria-hidden="true"></span>{'Create a new newsletter user'|i18n( 'cjw_newsletter/user_create',, hash() )|wash}</h1>
        </div>
    </div>

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

<form action={'newsletter/user_create/'|ezurl} method="post">

<input type="hidden" name="RedirectUrlActionCancel" value="{$redirect_url_action_cancel|wash}" />
<input type="hidden" name="RedirectUrlActionStore" value="{$redirect_url_action_store|wash}" />

<input type="hidden" name="OldPostVarSerialized" value="{$old_post_var_serialized|wash}" />

    <div class="a4-form">
        <div class="a4-field">
            <label for="nl-user-create-email">{'Email'|i18n( 'cjw_newsletter/user_view' )}</label>
            <input id="nl-user-create-email" class="a4-input" type="text" name="Subscription_Email" value="{$subscription_data_array['email']|wash}" />
        </div>
    </div>

    <div class="a4-toolbar">
        <button class="a4-btn a4-btn-primary" type="submit" name="CreateEditButton" value="{'Create and edit'|i18n( 'cjw_newsletter/user_create' )}">{'Create and edit'|i18n( 'cjw_newsletter/user_create' )}</button>
        <button class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/user_create' )}">{'Cancel'|i18n( 'cjw_newsletter/user_create' )}</button>
    </div>
</form>
</div>
