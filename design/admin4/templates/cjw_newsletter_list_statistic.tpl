{* The subscription figures of a newsletter list in admin4: links with status icons (icons=true()) or a table of figures. Same data and actions as design/standard/templates/cjw_newsletter_list_statistic.tpl. *}
{def $user_count_statistic = $list_node.data_map.newsletter_list.content.user_count_statistic}

{if is_set( $icons )}


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

{def $string = "Subscriptions statistic"|i18n( 'cjw_newsletter/subscription_list' )}

<div class="a4-table-wrap">
<table class="a4-table">
<tbody>
<tr>
<td>
    <span class="a4-icon a4-bi-person is-medium" role="img" aria-label="{"Subscriptions statistic"|i18n( 'cjw_newsletter/subscription_list' )}" title="{"Subscriptions statistic"|i18n( 'cjw_newsletter/subscription_list' )}"></span>
    <a href={concat( 'newsletter/subscription_list/', $list_node.node_id )|ezurl}>{'Subscriptions'|i18n('cjw_newsletter/contentstructuremenu')} ({$user_count_statistic.all|wash})</a>
</td>
<td>
    {* Pending *}
    <span class="a4-icon {$subscription_icon_array[0]} is-medium" role="img" aria-label="{'Pending'|i18n('cjw_newsletter/subscription_list')}" title="{'Pending'|i18n('cjw_newsletter/subscription_list')}"></span>
    <a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/pending' )|ezurl}>{'Pending'|i18n('cjw_newsletter/subscription_list')} ({$user_count_statistic.pending|wash})</a>
</td>
<td>
    {* Confirmed *}
    <span class="a4-icon {$subscription_icon_array[1]} is-medium" role="img" aria-label="{'Confirmed'|i18n('cjw_newsletter/subscription_list')}" title="{'Confirmed'|i18n('cjw_newsletter/subscription_list')}"></span>
    <a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/confirmed' )|ezurl}>{'Confirmed'|i18n('cjw_newsletter/subscription_list')} ({$user_count_statistic.confirmed|wash})</a>
</td>
<td>
    {* Approved *}
    <span class="a4-icon {$subscription_icon_array[2]} is-medium" role="img" aria-label="{'Approved'|i18n('cjw_newsletter/subscription_list')}" title="{'Approved'|i18n('cjw_newsletter/subscription_list')}"></span>
    <a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/approved' )|ezurl}>{'Approved'|i18n('cjw_newsletter/subscription_list')} ({$user_count_statistic.approved|wash})</a>
</td>
<td>
    <span class="a4-icon {$subscription_icon_array['bounced']} is-medium" role="img" aria-label="{'Bounced'|i18n('cjw_newsletter/subscription_list')}" title="{'Bounced'|i18n('cjw_newsletter/subscription_list')}"></span>
    {* Bounced *}
    <a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/bounced' )|ezurl}>{'Bounced'|i18n('cjw_newsletter/subscription_list')} ({$user_count_statistic.bounced|wash})</a>
</td>
<td>
    <span class="a4-icon {$subscription_icon_array['removed']} is-medium" role="img" aria-label="{'Removed'|i18n('cjw_newsletter/subscription_list')}" title="{'Removed'|i18n('cjw_newsletter/subscription_list')}"></span>
    {* Removed *}
    <a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/removed' )|ezurl}>{'Removed'|i18n('cjw_newsletter/subscription_list')} ({$user_count_statistic.removed|wash})</a>
</td>
<td>
    <span class="a4-icon {$subscription_icon_array[8]} is-medium" role="img" aria-label="{'Blacklisted'|i18n('cjw_newsletter/subscription_list')}" title="{'Blacklisted'|i18n('cjw_newsletter/subscription_list')}"></span>
    {* Blacklisted *}
    <a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/blacklisted' )|ezurl}>{'Blacklisted'|i18n('cjw_newsletter/subscription_list')} ({$user_count_statistic.blacklisted|wash})</a>
</td>
</tr>
</tbody>
</table>
</div>

{else}

<div class="a4-table-wrap">
    <table class="a4-table">
    <thead>
    <tr>
        <th scope="col">{'All'|i18n('cjw_newsletter/subscription_list')}</th>
        <th scope="col">{'Pending'|i18n('cjw_newsletter/subscription_list')}</th>
        <th scope="col">{'Confirmed'|i18n('cjw_newsletter/subscription_list')}</th>
        <th scope="col">{'Approved'|i18n('cjw_newsletter/subscription_list')}</th>
        <th scope="col">{'Bounced'|i18n('cjw_newsletter/subscription_list')}</th>
        <th scope="col">{'Removed'|i18n('cjw_newsletter/subscription_list')}</th>
        <th scope="col">{'Blacklisted'|i18n('cjw_newsletter/subscription_list')}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><a href={concat( 'newsletter/subscription_list/', $list_node.node_id )|ezurl}>{$user_count_statistic.all|wash}</a></td>
        <td><a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/pending' )|ezurl}>{$user_count_statistic.pending|wash}</a></td>
        <td><a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/confirmed' )|ezurl}>{$user_count_statistic.confirmed|wash}</a></td>
        <td><a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/approved' )|ezurl}><b>{$user_count_statistic.approved|wash}</b></a></td>
        <td><a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/bounced' )|ezurl}>{$user_count_statistic.bounced|wash}</a></td>
        <td><a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/removed' )|ezurl}>{$user_count_statistic.removed|wash}</a></td>
        <td><a href={concat( 'newsletter/subscription_list/', $list_node.node_id, '/(status)/blacklisted' )|ezurl}>{$user_count_statistic.blacklisted|wash}</a></td>
    </tr>
    </tbody>
    </table>
</div>

{/if}