{* The details of one import and the subscriptions it created, admin4. Same data and actions as design/standard/templates/newsletter/import_view.tpl *}
{def $page_uri = concat( 'newsletter/import_view/', $import_object.id )
     $list_contentobject = $import_object.list_contentobject}
<div class="a4-page newsletter newsletter-import_view">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-upload cjwnl-a4-lead" aria-hidden="true"></span>{$import_object.id|wash} [{'Import details'|i18n( 'cjw_newsletter/import_view',, hash() )|wash}]</h1>
        </div>
    </div>

    <div class="a4-card"><div class="a4-card-body">
        <dl class="a4-facts is-rows">
            <div><dt>{'Id'|i18n( 'cjw_newsletter/import_view' )}</dt><dd>{$import_object.id|wash}</dd></div>
            <div><dt>{'Subscription list'|i18n( 'cjw_newsletter/import_view' )}</dt><dd><span title="{$import_object.list_contentobject_id|wash}">{if is_object( $list_contentobject )}<a href={concat( 'content/view/full/', $list_contentobject.main_node_id)|ezurl}>{$list_contentobject.name|wash}</a>{/if}</span></dd></div>
            <div><dt>{'Import type'|i18n( 'cjw_newsletter/import_view' )}</dt><dd>{if and( $import_object.type|eq( 'cjwnl_csv' ), is_object( $list_contentobject ))}<a href={concat('newsletter/subscription_list_csvimport/', $list_contentobject.main_node_id, '/', $import_object.id )|ezurl}>{$import_object.type|wash}</a>{else}{$import_object.type|wash}{/if}</dd></div>
            <div><dt>{'Created'|i18n( 'cjw_newsletter/import_view' )}</dt><dd>{$import_object.created|l10n( shortdatetime )}</dd></div>
            <div><dt>{'Creator'|i18n( 'cjw_newsletter/import_view' )}</dt><dd><span title="{$import_object.creator.id|wash}">{$import_object.creator.name|wash}</span></dd></div>
            <div><dt>{'Import note'|i18n( 'cjw_newsletter/import_view' )}</dt><dd>{$import_object.note|wash}</dd></div>
            <div><dt>{'Data text'|i18n( 'cjw_newsletter/import_view' )}</dt><dd>{$import_object.data_text|wash}</dd></div>
            <div><dt>{'Remote id'|i18n( 'cjw_newsletter/import_view' )}</dt><dd>{$import_object.remote_id|wash}</dd></div>
            <div><dt>{'Imported'|i18n( 'cjw_newsletter/import_view' )}</dt><dd>{if $import_object.imported|gt(0)}{$import_object.imported|l10n( shortdatetime )|wash}{/if}</dd></div>
            {def $imported_subscription_count_live_approved = $import_object.imported_subscription_count_live_approved}
            <div><dt>{'Imported subscription count'|i18n( 'cjw_newsletter/import_view' )} | {'Live count'|i18n( 'cjw_newsletter/import_view' )} | {'Live count approved'|i18n( 'cjw_newsletter/import_view' )}</dt><dd><span title="{'Subscription count after import'|i18n( 'cjw_newsletter/import_view',, hash('%importId', $import_object.id ) )}">{$import_object.imported_subscription_count|wash}</span> |
                <span title="{'Subscriptions in current system with import id %importId'|i18n( 'cjw_newsletter/import_view',, hash('%importId', $import_object.id ) )}">{$import_object.imported_subscription_count_live|wash}</span> |
                <span title="{'Approved subscriptions in current system with import id %importId'|i18n( 'cjw_newsletter/import_view',, hash('%importId', $import_object.id ) )}"><strong>{$import_object.imported_subscription_count_live_approved|wash}</strong></span></dd></div>
            <div><dt>{'Imported user count'|i18n( 'cjw_newsletter/import_view' )} | {'Live count'|i18n( 'cjw_newsletter/import_view' )} | {'Live count confirmed'|i18n( 'cjw_newsletter/import_view' )}</dt><dd><span title="{'Newsletter user count after import'|i18n( 'cjw_newsletter/import_view',, hash('%importId', $import_object.id ) )}">{$import_object.imported_user_count|wash}</span> |
                <span title="{'Newsletter user in current system with import id %importId'|i18n( 'cjw_newsletter/import_view',, hash('%importId', $import_object.id ) )}">{$import_object.imported_user_count_live|wash}</span> |
                <span title="{'Confirmed Newsletter user in current system with import id %importId'|i18n( 'cjw_newsletter/import_view',, hash('%importId', $import_object.id ) )}"><strong>{$import_object.imported_user_count_live_confirmed|wash}</strong></span></dd></div>
            {if eq( $import_object.type, 'cjwnl_csv_mapped' )}
            <div><dt>{'Consent source'|i18n( 'cjw_newsletter/importexport' )}</dt><dd>{$import_object.consent_source|wash}</dd></div>
            <div><dt>{'Skipped'|i18n( 'cjw_newsletter/importexport' )} | {'Failed'|i18n( 'cjw_newsletter/importexport' )}</dt>
                <dd>{$import_object.skipped_count|wash} | {$import_object.error_count|wash}{if $import_object.is_dry_run|eq( 1 )} ({'dry run'|i18n( 'cjw_newsletter/importexport' )}){/if}
                    <a href={concat( 'newsletter/import_mapping/', $import_object.id, '#nl-ie-report' )|ezurl}>{'Report'|i18n( 'cjw_newsletter/importexport' )}</a></dd></div>
            {/if}
        </dl>
    </div></div>
    {undef $list_contentobject}

    <form class="a4-toolbar" action={concat( 'newsletter/import_view/', $import_object.id )|ezurl} method="post">
        <input class="a4-btn a4-btn-danger" type="submit" name="RemoveSubsciptionsByAdminButton" value="{'Remove %count_active_subscriptions active subscriptions by admin'|i18n( 'cjw_newsletter/import_view', ,hash( '%count_active_subscriptions', $imported_subscription_count_live_approved))}" data-nl-confirm-button="{'Do you really want to set status removed by admin to all active subscriptions (%count_active_subscriptions)?'|i18n( 'cjw_newsletter/import_view', ,hash( '%count_active_subscriptions', $imported_subscription_count_live_approved ) )|wash}" />
    </form>
    {include uri='design:parts/newsletter/script.tpl'}

    {def $import_limit = 50
         $import_subscription_list = fetch( 'newsletter', 'import_subscription_list', hash( 'import_id' , $import_object.id,
                                                                                            'limit', $import_limit,
                                                                                            'offset', $view_parameters.offset ) )
         $import_subscription_list_count = fetch( 'newsletter', 'import_subscription_list_count', hash( 'import_id' , $import_object.id ) )}

    <section class="a4-section">
        <h2 class="a4-h2">{'Subscriptions created by import'|i18n( 'cjw_newsletter/import_view',, hash(  ) )} [{$import_subscription_list_count|wash}]</h2>
        <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr>
            <th scope="col">{'Id'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'List name'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'Newsletter User'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'Format'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'Status'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'Created'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'Modified'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'Remote id'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'Import Id'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            <th scope="col">{'NL user import id'|i18n( 'cjw_newsletter/subscription_list' )}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $import_subscription_list as $subscription}
            {def $newsletter_user = $subscription.newsletter_user}
            <tr>
                <td><a href={concat('newsletter/subscription_view/', $subscription.id)|ezurl}>{$subscription.id|wash}</a></td>
                <td><a href={concat('content/view/full/', $subscription.newsletter_list.main_node_id)|ezurl}>{$subscription.newsletter_list.name|wash}</a></td>
                <td class="a4-wrap"><a href={concat('newsletter/user_view/', $subscription.newsletter_user_id)|ezurl}>{$newsletter_user.email|wash} ({$newsletter_user.name|wash})</a></td>
                <td>{$subscription.output_format_array|implode(', ')}</td>
                <td title="{$subscription.status|wash}">{$subscription.status_string|wash}</td>
                <td>{cond( $subscription.created|gt(0), $subscription.created|l10n( shortdatetime ), 'n/a'|i18n( 'cjw_newsletter/import_view' ) )}</td>
                <td>{cond( $subscription.modified|gt(0), $subscription.modified|l10n( shortdatetime ), 'n/a'|i18n( 'cjw_newsletter/import_view' ) )}</td>
                <td>{$subscription.remote_id|wash}</td>
                <td>{$subscription.import_id|wash}</td>
                <td>{$newsletter_user.import_id|wash}</td>
            </tr>
            {undef $newsletter_user}
            {/foreach}
            </tbody>
        </table>
        </div>
        <div class="a4-listfoot">
            <div class="a4-pager">
            {include name='Navigator'
                     uri='design:navigator/google.tpl'
                     page_uri=$page_uri
                     item_count=$import_subscription_list_count
                     view_parameters=$view_parameters
                     item_limit=$import_limit}
            </div>
        </div>
    </section>
</div>
