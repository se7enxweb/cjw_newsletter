{*  newsletter/blacklist_item_remove.tpl

    the confirmation step before addresses leave the blacklist
*}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-blacklist_item_remove">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Remove from the blacklist'|i18n( 'cjw_newsletter/blacklist_item_remove' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        <form class="nl-confirm" action={'newsletter/blacklist_item_remove'|ezurl} method="post">
            <h2>{'Remove these addresses from the blacklist?'|i18n( 'cjw_newsletter/blacklist_item_remove' )}</h2>
            <ul>
            {foreach $items as $item}
                <li>{$item.email|wash}<input type="hidden" name="BlacklistIDArray[]" value="{$item.id}" /></li>
            {/foreach}
            </ul>
            <p>{'A newsletter user with such an address is set back to "confirmed" and gets mail again.'|i18n( 'cjw_newsletter/blacklist_item_remove' )}</p>
            <input type="hidden" name="RedirectURI" value="{$redirect_uri|wash}" />
            <input class="button nl-danger" type="submit" name="ConfirmRemoveButton" value="{'Yes, remove'|i18n( 'extension/cjw_newsletter' )|wash}" />
            <input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'extension/cjw_newsletter' )|wash}" />
        </form>
    </div>
</div>
</div>
