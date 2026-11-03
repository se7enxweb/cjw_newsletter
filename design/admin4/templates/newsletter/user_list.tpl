{* The newsletter users in admin4: filter on text, status and list, sort on a column, page.
   Same data and actions as design/standard/templates/newsletter/user_list.tpl *}
{def $page_uri = 'newsletter/user_list'}
<div class="a4-page newsletter newsletter-user_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-people cjwnl-a4-lead" aria-hidden="true"></span>{'Manage users'|i18n( 'cjw_newsletter/user_list' )}</h1>
        </div>
        <div class="a4-pagehead-actions">
            <form action={'newsletter/user_create/-1'|ezurl} method="post">
                <button class="a4-btn a4-btn-primary" type="submit" name="CreateNewsletterUserButton" value="{'Create Newsletter user'|i18n( 'cjw_newsletter/user_list' )|wash}"><span class="a4-icon a4-bi-plus-lg" aria-hidden="true"></span> {'Create Newsletter user'|i18n( 'cjw_newsletter/user_list' )|wash}</button>
            </form>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    <form class="a4-filterbar" action={'newsletter/user_list'|ezurl} method="post" role="search">
        <div class="a4-field a4-field-wide">
            <label for="nl-user-email">{'Email'|i18n( 'cjw_newsletter/user_list' )}</label>
            <input type="search" id="nl-user-email" name="SearchUserEmail" value="{$search_user_email|wash}" placeholder="{'Address, name or organisation'|i18n( 'extension/cjw_newsletter' )|wash}" />
        </div>
        <div class="a4-field">
            <label for="nl-user-status">{'Status'|i18n( 'cjw_newsletter/user_list' )}</label>
            <select id="nl-user-status" name="StatusFilter">
                <option value="">{'All'|i18n( 'extension/cjw_newsletter' )}</option>
                {foreach hash( 'confirmed', 'Confirmed', 'pending', 'Pending', 'removed', 'Removed', 'bounced', 'Bounced', 'blacklisted', 'Blacklisted' ) as $key => $label}
                <option value="{$key}"{if eq( $vp.status, $key )} selected="selected"{/if}>{$label|i18n( 'extension/cjw_newsletter' )}</option>
                {/foreach}
            </select>
        </div>
        {if $lists|count|gt( 1 )}
        <div class="a4-field">
            <label for="nl-user-list">{'List'|i18n( 'extension/cjw_newsletter' )}</label>
            <select id="nl-user-list" name="ListFilter">
                <option value="0">{'All'|i18n( 'extension/cjw_newsletter' )}</option>
                {foreach $lists as $list}<option value="{$list.object_id}"{if eq( $vp.list, $list.object_id )} selected="selected"{/if}>{$list.name|wash}</option>{/foreach}
            </select>
        </div>
        {/if}
        <div class="a4-actions">
            <button class="a4-btn a4-btn-primary" type="submit" name="SubmitUserSearch" value="{'Search for existing user'|i18n( 'cjw_newsletter/user_list' )|wash}">{'Search for existing user'|i18n( 'cjw_newsletter/user_list' )|wash}</button>
            {if or( $vp.q, $vp.status, $vp.list )}<a class="a4-btn" href={'newsletter/user_list'|ezurl}>{'Clear'|i18n( 'extension/cjw_newsletter' )}</a>{/if}
        </div>
    </form>

    {if $user_list|count}
    <div class="a4-table-wrap">
        <table class="a4-table nl-table">
            <thead>
            <tr>
                <th scope="col" class="a4-num">{'UID'|i18n( 'cjw_newsletter/user_list' )}</th>
                <th scope="col"><a href={$urls.sort_email|ezurl} class="{if eq( $vp.sort, 'email' )}is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'Email'|i18n( 'cjw_newsletter/user_list' )}</a></th>
                <th scope="col"><a href={$urls.sort_name|ezurl} class="{if eq( $vp.sort, 'name' )}is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'Name'|i18n( 'cjw_newsletter/user_list' )}</a></th>
                <th scope="col" title="{'Approved'|i18n( 'cjw_newsletter/user_list' )} / {'All'|i18n( 'cjw_newsletter/user_list' )}">{'Lists'|i18n( 'cjw_newsletter/user_list' )}</th>
                <th scope="col"><a href={$urls.sort_status|ezurl} class="{if eq( $vp.sort, 'status' )}is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'Status'|i18n( 'cjw_newsletter/user_list' )}</a></th>
                <th scope="col" class="a4-num">{'Bounce'|i18n( 'cjw_newsletter/user_list' )}</th>
                <th scope="col"><a href={$urls.sort_created|ezurl} class="{if eq( $vp.sort, 'created' )}is-sorted{if eq( $vp.order, 'desc' )} is-desc{/if}{/if}">{'Created'|i18n( 'extension/cjw_newsletter' )}</a></th>
                <th scope="col">{'Actions'|i18n( 'extension/cjw_newsletter' )}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $user_list as $newsletter_user}
            {def $subscription_array = $newsletter_user.subscription_array
                 $approved = 0}
            {foreach $subscription_array as $subscription}{if $subscription.status|eq( 2 )}{set $approved = $approved|inc}{/if}{/foreach}
            <tr>
                <td class="a4-num"><a href={concat( 'newsletter/user_view/', $newsletter_user.id )|ezurl}>{$newsletter_user.id}</a></td>
                <td class="a4-wrap"><a href={concat( 'newsletter/user_view/', $newsletter_user.id )|ezurl}>{$newsletter_user.email|wash}</a></td>
                <td class="a4-wrap">{concat( $newsletter_user.first_name, ' ', $newsletter_user.last_name )|trim|wash}</td>
                <td><strong>{$approved}</strong> / {$subscription_array|count}</td>
                <td><span class="a4-badge {cond( $newsletter_user.status|eq( 1 ), 'is-ok', or( $newsletter_user.status|eq( 0 ), $newsletter_user.status|eq( 20 ) ), 'is-info', 'is-warn' )}">{$newsletter_user.status_string|wash}</span></td>
                <td class="a4-num">{$newsletter_user.bounce_count|wash}</td>
                <td>{$newsletter_user.created|l10n( 'shortdate' )}</td>
                <td>
                    <div class="a4-actions">
                        <a class="a4-btn a4-btn-small" href={concat( 'newsletter/user_view/', $newsletter_user.id )|ezurl}>{'Details'|i18n( 'extension/cjw_newsletter' )}</a>
                        <a class="a4-btn a4-btn-small" href={concat( 'newsletter/user_edit/', $newsletter_user.id, '?RedirectUrl=', $urls.base )|ezurl}>{'Edit'|i18n( 'extension/cjw_newsletter' )}</a>
                    </div>
                </td>
            </tr>
            {undef $subscription_array $approved}
            {/foreach}
            </tbody>
        </table>
    </div>

    <div class="a4-listfoot">
        <span>{'%count of %all users'|i18n( 'extension/cjw_newsletter',, hash( '%count', $user_list_count, '%all', $all_count ) )}</span>
        <span class="table-preferences">{'Per page'|i18n( 'extension/cjw_newsletter' )}:
            {foreach array( 10, 25, 50, 100 ) as $n}{if eq( $vp.limit, $n )}<span class="nl-page is-current">{$n}</span>{else}<a href={concat( 'newsletter/user_list/(limit)/', $n, cond( $vp.q, concat( '/(q)/', $view_parameters.q ), '' ), cond( $vp.status, concat( '/(status)/', $vp.status ), '' ), cond( $vp.list, concat( '/(list)/', $vp.list ), '' ), '/(sort)/', $vp.sort, '/(order)/', $vp.order )|ezurl}>{$n}</a>{/if} {/foreach}
        </span>
        {include name='Navigator'
                 uri='design:navigator/google.tpl'
                 page_uri=$page_uri
                 item_count=$user_list_count
                 view_parameters=$view_parameters
                 item_limit=$vp.limit}
    </div>
    {else}
    <div class="a4-empty">
        {if $all_count}
        <p><strong>{'No user matches the filter.'|i18n( 'extension/cjw_newsletter' )}</strong></p>
        <p><a href={'newsletter/user_list'|ezurl}>{'Show all users'|i18n( 'extension/cjw_newsletter' )}</a></p>
        {else}
        <p><strong>{'There is no newsletter user yet.'|i18n( 'extension/cjw_newsletter' )}</strong></p>
        <p>{'Users subscribe through the subscription form, or you create them here or import a CSV file into a list.'|i18n( 'extension/cjw_newsletter' )}</p>
        {/if}
    </div>
    {/if}
</div>
{include uri='design:parts/newsletter/script.tpl'}
