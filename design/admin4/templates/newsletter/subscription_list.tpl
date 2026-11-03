{* All subscriptions of a newsletter list in admin4: figures, create/import/export, status filter, page size, table.
   Same data and actions as design/standard/templates/newsletter/subscription_list.tpl.
   The subscription status sprites are Bootstrap icons (icon_s_pending hourglass-split, confirmed person-check,
   approved check-circle-fill, removed person-slash, bounced exclamation-triangle, blacklisted lock-fill). *}
<div class="a4-page newsletter newsletter-subscription_list">

{if $node.class_identifier|eq( 'cjw_newsletter_list' )}

{def $subscription_icon_array = hash( '0', 'a4-bi-hourglass-split',
                                      '1', 'a4-bi-person-check',
                                      '2', 'a4-bi-check-circle-fill',
                                      '3', 'a4-bi-person-slash',
                                      '4', 'a4-bi-person-slash',
                                      '6', 'a4-bi-exclamation-triangle',
                                      '7', 'a4-bi-exclamation-triangle',
                                      '8', 'a4-bi-lock-fill',
                                      'bounced', 'a4-bi-exclamation-triangle',
                                      'removed', 'a4-bi-person-slash' )}
{def $limit = 50}

{if ezpreference( 'admin_subscription_list_limit' )}
    {switch match=ezpreference( 'admin_subscription_list_limit' )}
        {case match=1}
            {set $limit=10}
        {/case}
        {case match=2}
            {set $limit=25}
        {/case}
        {case match=3}
            {set $limit=50}
        {/case}
    {/switch}
{/if}

{* subscription list *}
{def $newsletter_list_node = $node}
{def $status = ''}

{if is_set( $view_parameters.status )}
    {set $status = $view_parameters.status}
{/if}

{def $subscription_list = fetch('newsletter', 'subscription_list', hash( 'list_contentobject_id', $node.contentobject_id,
                                                                         'offset', $view_parameters.offset,
                                                                         'status', $status,
                                                                         'limit', $limit ))
     $subscription_list_count = fetch('newsletter', 'subscription_list_count', hash( 'list_contentobject_id', $node.contentobject_id,
                                                                                     'status', $status ))
     $base_uri = concat( 'newsletter/subscription_list/', $node.node_id )
     $uri_csv_import = concat( 'newsletter/subscription_list_csvimport/', $node.node_id )
     $uri_csv_export = concat( 'newsletter/subscription_list_csvexport/', $node.node_id )
     }

    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-person is-large" aria-hidden="true"></span> {'Subscription list <%subscription_list_name>'|i18n( 'cjw_newsletter/subscription_list',, hash( '%subscription_list_name', $newsletter_list_node.name ) )|wash}</h1>
        </div>
    </div>

    {include uri='design:cjw_newsletter_list_statistic.tpl'
             name='Statistic'
             list_node=$node
             icons=true()}

    <div class="a4-toolbar cjwnl-a4-forms">
        <form name="CreateNewNewsletterUser" method="post" action={'newsletter/user_create'|ezurl}>
            <input type="hidden" name="AddSubscriptionForListId" value="{$node.contentobject_id}" />
            <input type="hidden" name="RedirectUrlActionCancel" value="newsletter/subscription_list/{$node.node_id}" />
            <input type="hidden" name="RedirectUrlActionStore" value="newsletter/subscription_list/{$node.node_id}" />
            <button class="a4-btn a4-btn-primary" type="submit" name="NewSubscriptionButton" value="{'Create new Subscription'|i18n( 'cjw_newsletter/subscription_list' )}"><span class="a4-icon a4-bi-plus-lg" aria-hidden="true"></span> {'Create new Subscription'|i18n( 'cjw_newsletter/subscription_list' )}</button>
        </form>

        <form name="CsvImport" method="post" action={$uri_csv_import|ezurl}>
            <button class="a4-btn" type="submit" name="importcsv" value="{'Import CSV'|i18n( 'cjw_newsletter/subscription_list' )}" title="{'Import contact from CSV file.'|i18n( 'cjw_newsletter/newsletter_list_subscription' )}"><span class="a4-icon a4-bi-upload" aria-hidden="true"></span> {'Import CSV'|i18n( 'cjw_newsletter/subscription_list' )}</button>
        </form>

        <form name="CsvExport" method="post" action={$uri_csv_export|ezurl}>
            <button class="a4-btn" type="submit" name="importcsv" value="{'Export CSV'|i18n( 'cjw_newsletter/subscription_list' )}" title="{'Export to CSV file.'|i18n( 'cjw_newsletter/newsletter_list_subscription' )}"><span class="a4-icon a4-bi-download" aria-hidden="true"></span> {'Export CSV'|i18n( 'cjw_newsletter/subscription_list' )}</button>
        </form>
    </div>

    <section class="a4-section" aria-labelledby="nl-subscribers">
        <h2 class="a4-h2" id="nl-subscribers">{'Subscribers'|i18n( 'cjw_newsletter/subscription_list' )} [{$subscription_list_count}]</h2>

        <div class="a4-filterbar">
            <div class="a4-field">
                <ul class="a4-tabs">
                {switch match=$limit}
                    {case match=25}
                        <li><a href={'/user/preferences/set/admin_subscription_list_limit/1'|ezurl} title="{'Show 10 items per page.'|i18n( 'design/admin/node/view/full' )}">10</a></li>
                        <li><span class="current" aria-current="true">25</span></li>
                        <li><a href={'/user/preferences/set/admin_subscription_list_limit/3'|ezurl} title="{'Show 50 items per page.'|i18n( 'design/admin/node/view/full' )}">50</a></li>
                    {/case}
                    {case match=50}
                        <li><a href={'/user/preferences/set/admin_subscription_list_limit/1'|ezurl} title="{'Show 10 items per page.'|i18n( 'design/admin/node/view/full' )}">10</a></li>
                        <li><a href={'/user/preferences/set/admin_subscription_list_limit/2'|ezurl} title="{'Show 25 items per page.'|i18n( 'design/admin/node/view/full' )}">25</a></li>
                        <li><span class="current" aria-current="true">50</span></li>
                    {/case}
                    {case}
                        <li><span class="current" aria-current="true">10</span></li>
                        <li><a href={'/user/preferences/set/admin_subscription_list_limit/2'|ezurl} title="{'Show 25 items per page.'|i18n( 'design/admin/node/view/full' )}">25</a></li>
                        <li><a href={'/user/preferences/set/admin_subscription_list_limit/3'|ezurl} title="{'Show 50 items per page.'|i18n( 'design/admin/node/view/full' )}">50</a></li>
                    {/case}
                {/switch}
                </ul>
            </div>

            <div class="a4-field">
                {* newsletter list selection *}
                <ul class="a4-tabs">
                    <li>{if $status|eq('')}<span class="current" aria-current="true">{'All'|i18n('cjw_newsletter/subscription_list')}</span>{else}<a href={concat( $base_uri,'')|ezurl}>{'All'|i18n('cjw_newsletter/subscription_list')}</a>{/if}</li>
                    <li>{if $status|eq('pending')}<span class="current" aria-current="true"><span class="a4-icon a4-bi-hourglass-split" aria-hidden="true"></span> {'Pending'|i18n('cjw_newsletter/subscription_list')}</span>{else}<a href={concat($base_uri, '/(status)/pending' )|ezurl}><span class="a4-icon a4-bi-hourglass-split" aria-hidden="true"></span> {'Pending'|i18n('cjw_newsletter/subscription_list')}</a>{/if}</li>
                    <li>{if $status|eq('confirmed')}<span class="current" aria-current="true"><span class="a4-icon a4-bi-person-check" aria-hidden="true"></span> {'Confirmed'|i18n('cjw_newsletter/subscription_list')}</span>{else}<a href={concat($base_uri, '/(status)/confirmed' )|ezurl}><span class="a4-icon a4-bi-person-check" aria-hidden="true"></span> {'Confirmed'|i18n('cjw_newsletter/subscription_list')}</a>{/if}</li>
                    <li>{if $status|eq('approved')}<span class="current" aria-current="true"><span class="a4-icon a4-bi-check-circle-fill" aria-hidden="true"></span> {'Approved'|i18n('cjw_newsletter/subscription_list')}</span>{else}<a href={concat($base_uri, '/(status)/approved' )|ezurl}><span class="a4-icon a4-bi-check-circle-fill" aria-hidden="true"></span> {'Approved'|i18n('cjw_newsletter/subscription_list')}</a>{/if}</li>
                    <li>{if $status|eq('bounced')}<span class="current" aria-current="true"><span class="a4-icon a4-bi-exclamation-triangle" aria-hidden="true"></span> {'Bounced'|i18n('cjw_newsletter/subscription_list')}</span>{else}<a href={concat($base_uri, '/(status)/bounced' )|ezurl}><span class="a4-icon a4-bi-exclamation-triangle" aria-hidden="true"></span> {'Bounced'|i18n('cjw_newsletter/subscription_list')}</a>{/if}</li>
                    <li>{if $status|eq('removed')}<span class="current" aria-current="true"><span class="a4-icon a4-bi-person-slash" aria-hidden="true"></span> {'Removed'|i18n('cjw_newsletter/subscription_list')}</span>{else}<a href={concat($base_uri, '/(status)/removed' )|ezurl}><span class="a4-icon a4-bi-person-slash" aria-hidden="true"></span> {'Removed'|i18n('cjw_newsletter/subscription_list')}</a>{/if}</li>
                    <li>{if $status|eq('blacklisted')}<span class="current" aria-current="true"><span class="a4-icon a4-bi-lock-fill" aria-hidden="true"></span> {'Blacklisted'|i18n('cjw_newsletter/subscription_list')}</span>{else}<a href={concat($base_uri, '/(status)/blacklisted' )|ezurl}><span class="a4-icon a4-bi-lock-fill" aria-hidden="true"></span> {'Blacklisted'|i18n('cjw_newsletter/subscription_list')}</a>{/if}</li>
                </ul>
            </div>
        </div>

        {* Subscription list table. *}
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                <tr>
                    <th scope="col">{'ID'|i18n('cjw_newsletter/subscription_list')}</th>
                    <th scope="col">{'Email'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'First name'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Last name'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Exponential User'|i18n('cjw_newsletter/subscription_list')}</th>
                    <th scope="col">{'Format'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Status'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col">{'Modified'|i18n( 'cjw_newsletter/subscription_list' )}</th>
                    <th scope="col"><span class="a4-sr-only">{'Actions'|i18n( 'extension/cjw_newsletter' )}</span></th>
                </tr>
                </thead>
                <tbody>
                {foreach $subscription_list as $subscription}
                <tr>
                    <td>{$subscription.id|wash}</td>
                    <td class="a4-wrap"><a href={concat('newsletter/user_view/',$subscription.newsletter_user.id)|ezurl} title="{$subscription.newsletter_user.first_name|wash} {$subscription.newsletter_user.last_name|wash}">{$subscription.newsletter_user.email|wash}</a></td>
                    <td>{$subscription.newsletter_user.first_name|wash}</td>
                    <td>{$subscription.newsletter_user.last_name|wash}</td>
                    <td>
                        {if $subscription.newsletter_user.ez_user_id|gt( '0' )}
                            {def $user_object = fetch( 'content', 'object', hash( 'object_id', $subscription.newsletter_user.ez_user_id ) )}
                            {if $user_object}
                                <a href="{$user_object.main_node.url_alias|ezurl( 'no' )}">{$user_object.name|wash}</a>
                            {/if}
                            {undef $user_object}
                        {/if}
                    </td>
                    <td>{$subscription.output_format_array|implode(', ')}</td>
                    <td><span class="a4-icon {$subscription_icon_array[$subscription.status]}" role="img" aria-label="{$subscription.status_string|wash}" title="{$subscription.status_string|wash} ({$subscription.status|wash})"></span></td>
                    <td>{cond( $subscription.modified|gt(0), $subscription.modified|l10n( shortdatetime ), 'n/a'|i18n( 'cjw_newsletter/subscription_list' ) )}</td>
                    <td>
                        <div class="a4-actions">
                            <form action="{concat('newsletter/subscription_view/', $subscription.id )|ezurl( 'no' )}">
                                <button class="a4-btn a4-btn-small" type="submit" name="SubscriptionDetails" value="{'Details'|i18n( 'cjw_newsletter/user_list' )}" title="{'Subscription details'|i18n( 'cjw_newsletter/user_list' )}">{'Details'|i18n( 'cjw_newsletter/user_list' )}</button>
                            </form>
                            <form action="{concat( '/newsletter/subscription_view/', $subscription.id )|ezurl( 'no' )}" method="post">
                                <button class="a4-btn a4-btn-small" type="submit" name="SubscriptionApproveButton" value="{'Approve'|i18n( 'cjw_newsletter/subscription_list' )}" title="{'Approve subscription'|i18n( 'cjw_newsletter/subscription_list' )}"{if or( $subscription.status|eq(2), $subscription.status|eq(3), $subscription.status|eq(8) )} disabled="disabled"{/if}>{'Approve'|i18n( 'cjw_newsletter/subscription_list' )}</button>
                            </form>
                            <form action="{concat( 'newsletter/user_edit/', $subscription.newsletter_user.id, '?RedirectUrl=', $base_uri, '/(offset)/', $view_parameters.offset )|ezurl( 'no' )}" method="post">
                                <button class="a4-btn a4-btn-small" type="submit" name="EditNewsletterUser" value="{'Edit'|i18n( 'cjw_newsletter/user_list' )}" title="{'Edit newsletter user'|i18n( 'cjw_newsletter/user_list' )}">{'Edit'|i18n( 'cjw_newsletter/user_list' )}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {* Navigator. *}
        <div class="a4-listfoot">
            {include name='Navigator'
                     uri='design:navigator/google.tpl'
                     page_uri=$base_uri
                     item_count=$subscription_list_count
                     view_parameters=$view_parameters
                     item_limit=$limit}
        </div>
    </section>

{else}
    <p class="a4-empty">{'This View is only available for \'Newsletter List\' objects'|i18n( 'cjw_newsletter/subscription_list' )}</p>
{/if}

</div>
