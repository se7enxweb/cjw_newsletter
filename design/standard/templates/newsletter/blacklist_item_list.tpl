{*  newsletter/blacklist_item_list.tpl

    the blacklist: filter, sort, page, remove (with a confirmation step)
*}
{ezcss_require( 'newsletter_ui.css' )}
{def $qpart = cond( $vp.q, concat( '/(q)/', $view_parameters.q ), '' )
     $other_order = cond( eq( $vp.order, 'asc' ), 'desc', 'asc' )
     $page_uri = 'newsletter/blacklist_item_list'}
<div class="newsletter newsletter-blacklist_item_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Blacklisted email addresses'|i18n( 'cjw_newsletter/blacklist_item_list' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        <p class="nl-muted">{'Mail is never sent to an address on the blacklist, whatever list it subscribes to.'|i18n( 'extension/cjw_newsletter' )}</p>

        <form class="nl-filter" action={'newsletter/blacklist_item_list'|ezurl} method="post">
            <label>{'Find an address'|i18n( 'extension/cjw_newsletter' )}
                <input type="search" name="Filter" value="{$vp.q|wash}" placeholder="{'Address or note'|i18n( 'extension/cjw_newsletter' )|wash}" /></label>
            <input class="button" type="submit" name="FilterButton" value="{'Filter'|i18n( 'extension/cjw_newsletter' )|wash}" />
            {if $vp.q}<a class="button" href={'newsletter/blacklist_item_list'|ezurl}>{'Clear'|i18n( 'extension/cjw_newsletter' )}</a>{/if}
        </form>
        <form action={'newsletter/blacklist_item_add'|ezurl} method="post" style="margin: 0 0 0.9em 0">
            <input class="defaultbutton" type="submit" name="CreateBlacklistEntryButton" value="{'Add email address to blacklist'|i18n( 'cjw_newsletter/blacklist_item_list' )|wash}" />
        </form>

        {if $blacklist_item_list|count}
        <form name="Blacklist" method="post" action={'newsletter/blacklist_item_remove'|ezurl}>
        <input type="hidden" name="RedirectURI" value="newsletter/blacklist_item_list" />
        <table class="list nl-table">
            <tr>
                <th class="tight"><input type="checkbox" data-nl-check-all="1" title="{'Select all'|i18n( 'extension/cjw_newsletter' )|wash}" /></th>
                <th class="nl-num"><a href={concat( 'newsletter/blacklist_item_list', $qpart, '/(sort)/id/(order)/', cond( eq( $vp.sort, 'id' ), $other_order, 'desc' ) )|ezurl} class="{if eq( $vp.sort, 'id' )}is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'ID'|i18n( 'cjw_newsletter/blacklist_item_list' )}</a></th>
                <th><a href={concat( 'newsletter/blacklist_item_list', $qpart, '/(sort)/email/(order)/', cond( eq( $vp.sort, 'email' ), $other_order, 'asc' ) )|ezurl} class="{if eq( $vp.sort, 'email' )}is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'Email'|i18n( 'cjw_newsletter/blacklist_item_list' )}</a></th>
                <th>{'Newsletter UID'|i18n( 'cjw_newsletter/blacklist_item_list' )}</th>
                <th><a href={concat( 'newsletter/blacklist_item_list', $qpart, '/(sort)/created/(order)/', cond( eq( $vp.sort, 'created' ), $other_order, 'desc' ) )|ezurl} class="{if eq( $vp.sort, 'created' )}is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'Created'|i18n( 'cjw_newsletter/blacklist_item_list' )}</a></th>
                <th>{'Creator'|i18n( 'cjw_newsletter/blacklist_item_list' )}</th>
                <th>{'Note'|i18n( 'cjw_newsletter/blacklist_item_list' )}</th>
            </tr>
            {foreach $blacklist_item_list as $blacklist_item sequence array( 'bglight', 'bgdark' ) as $style}
            <tr class="{$style}">
                <td><input type="checkbox" name="BlacklistIDArray[]" value="{$blacklist_item.id|wash}" aria-label="{'Select'|i18n( 'extension/cjw_newsletter' )|wash} {$blacklist_item.email|wash}" /></td>
                <td class="nl-num">{$blacklist_item.id|wash}</td>
                <td class="nl-wrap">{$blacklist_item.email|wash}</td>
                <td>{if $blacklist_item.newsletter_user_id|ne( 0 )}<a href={concat( 'newsletter/user_view/', $blacklist_item.newsletter_user_id )|ezurl}>{$blacklist_item.newsletter_user_id|wash}</a>{else}-{/if}</td>
                <td>{$blacklist_item.created|l10n( 'shortdatetime' )}</td>
                <td>{if is_object( $blacklist_item.creator )}{$blacklist_item.creator.name|wash}{/if}</td>
                <td class="nl-wrap">{$blacklist_item.note|wash}</td>
            </tr>
            {/foreach}
        </table>
        <p><input class="button" type="submit" name="RemoveBlacklistedButton" value="{'Remove selected'|i18n( 'cjw_newsletter/blacklist_item_list' )|wash}" /></p>
        </form>

        <div class="nl-pager">
            <span>{'%count of %all addresses'|i18n( 'extension/cjw_newsletter',, hash( '%count', $blacklist_item_list_count, '%all', $all_count ) )}</span>
            {include name='Navigator'
                     uri='design:navigator/google.tpl'
                     page_uri=$page_uri
                     item_count=$blacklist_item_list_count
                     view_parameters=$view_parameters
                     item_limit=$limit}
        </div>
        {else}
        <div class="nl-empty">
            {if $all_count}
            <p><strong>{'No address matches "%q".'|i18n( 'extension/cjw_newsletter',, hash( '%q', $vp.q ) )|wash}</strong></p>
            <p><a href={'newsletter/blacklist_item_list'|ezurl}>{'Show all addresses'|i18n( 'extension/cjw_newsletter' )}</a></p>
            {else}
            <p><strong>{'The blacklist is empty.'|i18n( 'extension/cjw_newsletter' )}</strong></p>
            {/if}
        </div>
        {/if}
    </div>
</div>
</div>
{include uri='design:parts/newsletter/script.tpl'}
