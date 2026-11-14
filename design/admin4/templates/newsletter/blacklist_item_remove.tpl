{* The confirmation step before addresses leave the blacklist, admin4. Same data and actions as design/standard/templates/newsletter/blacklist_item_remove.tpl *}
<div class="a4-page newsletter newsletter-blacklist_item_remove">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-lock cjwnl-a4-lead" aria-hidden="true"></span>{'Remove from the blacklist'|i18n( 'cjw_newsletter/blacklist_item_remove' )}</h1>
        </div>
    </div>
    <form action={'newsletter/blacklist_item_remove'|ezurl} method="post">
        <div class="a4-card"><div class="a4-card-body">
            <h2 class="a4-h2">{'Remove these addresses from the blacklist?'|i18n( 'cjw_newsletter/blacklist_item_remove' )}</h2>
            <ul>
            {foreach $items as $item}
                <li>{$item.email|wash}<input type="hidden" name="BlacklistIDArray[]" value="{$item.id}" /></li>
            {/foreach}
            </ul>
            <p>{'A newsletter user with such an address is set back to "confirmed" and gets mail again.'|i18n( 'cjw_newsletter/blacklist_item_remove' )}</p>
            <input type="hidden" name="RedirectURI" value="{$redirect_uri|wash}" />
        </div></div>
        <div class="a4-toolbar">
            <input class="a4-btn a4-btn-danger nl-danger" type="submit" name="ConfirmRemoveButton" value="{'Yes, remove'|i18n( 'extension/cjw_newsletter' )|wash}" />
            <input class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'extension/cjw_newsletter' )|wash}" />
        </div>
    </form>
</div>
