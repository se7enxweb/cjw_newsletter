{* The blacklist: filter, sort, page, remove with a confirmation step, admin4. Same data and actions as design/standard/templates/newsletter/blacklist_item_list.tpl *}
{def $qpart = cond( $vp.q, concat( '/(q)/', $view_parameters.q ), '' )
     $other_order = cond( eq( $vp.order, 'asc' ), 'desc', 'asc' )
     $page_uri = 'newsletter/blacklist_item_list'}
<div class="a4-page newsletter newsletter-blacklist_item_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-lock cjwnl-a4-lead" aria-hidden="true"></span>{'Blacklisted email addresses'|i18n( 'cjw_newsletter/blacklist_item_list' )}</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    <p class="a4-intro">{'Mail is never sent to an address on the blacklist, whatever list it subscribes to.'|i18n( 'extension/cjw_newsletter' )}</p>

    <form class="a4-filterbar" action={'newsletter/blacklist_item_list'|ezurl} method="post" role="search">
        <div class="a4-field a4-field-wide">
            <label for="nl-blacklist-filter">{'Find an address'|i18n( 'extension/cjw_newsletter' )}</label>
            <div class="a4-searchrow">
                <input id="nl-blacklist-filter" type="search" name="Filter" value="{$vp.q|wash}" placeholder="{'Address or note'|i18n( 'extension/cjw_newsletter' )|wash}" />
                <input class="a4-btn a4-btn-primary" type="submit" name="FilterButton" value="{'Filter'|i18n( 'extension/cjw_newsletter' )|wash}" />
                {if $vp.q}<a class="a4-btn" href={'newsletter/blacklist_item_list'|ezurl}>{'Clear'|i18n( 'extension/cjw_newsletter' )}</a>{/if}
            </div>
        </div>
    </form>

    <form class="a4-toolbar" action={'newsletter/blacklist_item_add'|ezurl} method="post">
        <input class="a4-btn a4-btn-primary" type="submit" name="CreateBlacklistEntryButton" value="{'Add email address to blacklist'|i18n( 'cjw_newsletter/blacklist_item_list' )|wash}" />
    </form>

    {if $blacklist_item_list|count}
    <form name="Blacklist" method="post" action={'newsletter/blacklist_item_remove'|ezurl}>
    <input type="hidden" name="RedirectURI" value="newsletter/blacklist_item_list" />
    <div class="a4-table-wrap">
    <table class="a4-table">
        <thead>
        <tr>
            <th scope="col"><input type="checkbox" data-nl-check-all="1" title="{'Select all'|i18n( 'extension/cjw_newsletter' )|wash}" aria-label="{'Select all'|i18n( 'extension/cjw_newsletter' )|wash}" /></th>
            <th scope="col" class="a4-num"><a href={concat( 'newsletter/blacklist_item_list', $qpart, '/(sort)/id/(order)/', cond( eq( $vp.sort, 'id' ), $other_order, 'desc' ) )|ezurl} class="cjwnl-a4-sort{if eq( $vp.sort, 'id' )} is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'ID'|i18n( 'cjw_newsletter/blacklist_item_list' )}</a></th>
            <th scope="col"><a href={concat( 'newsletter/blacklist_item_list', $qpart, '/(sort)/email/(order)/', cond( eq( $vp.sort, 'email' ), $other_order, 'asc' ) )|ezurl} class="cjwnl-a4-sort{if eq( $vp.sort, 'email' )} is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'Email'|i18n( 'cjw_newsletter/blacklist_item_list' )}</a></th>
            <th scope="col">{'Newsletter UID'|i18n( 'cjw_newsletter/blacklist_item_list' )}</th>
            <th scope="col"><a href={concat( 'newsletter/blacklist_item_list', $qpart, '/(sort)/created/(order)/', cond( eq( $vp.sort, 'created' ), $other_order, 'desc' ) )|ezurl} class="cjwnl-a4-sort{if eq( $vp.sort, 'created' )} is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'Created'|i18n( 'cjw_newsletter/blacklist_item_list' )}</a></th>
            <th scope="col">{'Creator'|i18n( 'cjw_newsletter/blacklist_item_list' )}</th>
            <th scope="col">{'Note'|i18n( 'cjw_newsletter/blacklist_item_list' )}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $blacklist_item_list as $blacklist_item}
        <tr>
            <td><input type="checkbox" name="BlacklistIDArray[]" value="{$blacklist_item.id|wash}" aria-label="{'Select'|i18n( 'extension/cjw_newsletter' )|wash} {$blacklist_item.email|wash}" /></td>
            <td class="a4-num">{$blacklist_item.id|wash}</td>
            <td class="a4-wrap">{$blacklist_item.email|wash}</td>
            <td>{if $blacklist_item.newsletter_user_id|ne( 0 )}<a href={concat( 'newsletter/user_view/', $blacklist_item.newsletter_user_id )|ezurl}>{$blacklist_item.newsletter_user_id|wash}</a>{else}-{/if}</td>
            <td>{$blacklist_item.created|l10n( 'shortdatetime' )}</td>
            <td>{if is_object( $blacklist_item.creator )}{$blacklist_item.creator.name|wash}{/if}</td>
            <td class="a4-wrap">{$blacklist_item.note|wash}</td>
        </tr>
        {/foreach}
        </tbody>
    </table>
    </div>
    <div class="a4-toolbar">
        <input class="a4-btn" type="submit" name="RemoveBlacklistedButton" value="{'Remove selected'|i18n( 'cjw_newsletter/blacklist_item_list' )|wash}" />
    </div>
    </form>

    <div class="a4-listfoot">
        <span class="a4-meta">{'%count of %all addresses'|i18n( 'extension/cjw_newsletter',, hash( '%count', $blacklist_item_list_count, '%all', $all_count ) )}</span>
        <div class="a4-pager">
        {include name='Navigator'
                 uri='design:navigator/google.tpl'
                 page_uri=$page_uri
                 item_count=$blacklist_item_list_count
                 view_parameters=$view_parameters
                 item_limit=$limit}
        </div>
    </div>
    {else}
        {if $all_count}
        <p class="a4-empty">{'No address matches "%q".'|i18n( 'extension/cjw_newsletter',, hash( '%q', $vp.q ) )|wash}
            <a href={'newsletter/blacklist_item_list'|ezurl}>{'Show all addresses'|i18n( 'extension/cjw_newsletter' )}</a></p>
        {else}
        <p class="a4-empty">{'The blacklist is empty.'|i18n( 'extension/cjw_newsletter' )}</p>
        {/if}
    {/if}
</div>
{include uri='design:parts/newsletter/script.tpl'}
