{* The form that puts an email address on the blacklist, admin4. Same data and actions as design/standard/templates/newsletter/blacklist_item_add.tpl *}
<div class="a4-page newsletter newsletter-blacklist_item_add">
<form action={'newsletter/blacklist_item_add'|ezurl} method="post">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-lock cjwnl-a4-lead" aria-hidden="true"></span>{'Add a new Blacklist item'|i18n( 'cjw_newsletter/blacklist_item_add' )}</h1>
        </div>
    </div>

    {if $errors|count}
    <div class="a4-notice is-bad" role="alert"><strong class="a4-notice-title">{'Input did not validate'|i18n( 'cjw_newsletter/subscribe' )}</strong>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}

    <div class="a4-card"><div class="a4-card-body cjwnl-a4-lists-form">
        <div class="a4-field">
            <label for="nl-blacklist-email">{'Email'|i18n( 'cjw_newsletter/blacklist_item_add' )}</label>
            <input id="nl-blacklist-email" class="a4-input" type="text" name="Email" value="{$email|wash}" maxlength="150"{if is_set( $errors.email )} aria-invalid="true"{/if} />
        </div>
        <div class="a4-field">
            <label for="nl-blacklist-note">{'Note'|i18n( 'cjw_newsletter/blacklist_item_add' )}</label>
            <textarea id="nl-blacklist-note" class="a4-input" name="Note" cols="60" rows="4"{if is_set( $errors.note )} aria-invalid="true"{/if}>{$note|wash}</textarea>
        </div>
        <p class="a4-help">{'A newsletter user with this address is set to "blacklisted" and gets no more mail.'|i18n( 'extension/cjw_newsletter' )}</p>
    </div></div>

    <div class="a4-toolbar">
        <input class="a4-btn a4-btn-primary" type="submit" name="AddButton" value="{'Add to Blacklist'|i18n( 'cjw_newsletter/blacklist_item_add' )|wash}" />
        <input class="a4-btn" type="submit" name="DiscardButton" value="{'Discard'|i18n( 'cjw_newsletter/blacklist_item_add' )|wash}" />
    </div>
</form>
</div>
