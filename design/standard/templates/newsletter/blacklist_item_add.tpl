{*  newsletter/blacklist_item_add.tpl

    put an email address on the blacklist
*}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-blacklist_item_add">
<form action={'newsletter/blacklist_item_add'|ezurl} method="post">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Add a new Blacklist item'|i18n( 'cjw_newsletter/blacklist_item_add' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {if $errors|count}
        <div class="message-error"><h2>{'Input did not validate'|i18n( 'cjw_newsletter/subscribe' )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}

        <div class="block {if is_set( $errors.email )}nl-field-error{/if}">
            <label for="nl-blacklist-email">{'Email'|i18n( 'cjw_newsletter/blacklist_item_add' )}</label>
            <input id="nl-blacklist-email" class="halfbox" type="text" name="Email" value="{$email|wash}" maxlength="150" />
        </div>
        <div class="block {if is_set( $errors.note )}nl-field-error{/if}">
            <label for="nl-blacklist-note">{'Note'|i18n( 'cjw_newsletter/blacklist_item_add' )}</label>
            <textarea id="nl-blacklist-note" class="box" name="Note" cols="60" rows="4">{$note|wash}</textarea>
        </div>
        <p class="nl-hint">{'A newsletter user with this address is set to "blacklisted" and gets no more mail.'|i18n( 'extension/cjw_newsletter' )}</p>
    </div>
    <div class="controlbar">
        <input class="defaultbutton" type="submit" name="AddButton" value="{'Add to Blacklist'|i18n( 'cjw_newsletter/blacklist_item_add' )|wash}" />
        <input class="button" type="submit" name="DiscardButton" value="{'Discard'|i18n( 'cjw_newsletter/blacklist_item_add' )|wash}" />
    </div>
</div>
</form>
</div>
