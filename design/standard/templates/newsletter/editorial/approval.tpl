{*  newsletter/editorial/approval.tpl (cjw_newsletter 4.2.0, area editorial)

    The approval of an edition version. Variables: edition, version, current_version, required, approval (the
    current request or null), state, history, can_request, can_decide, approver_count, picks, notices.
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_editorial.css' ) )}
{def $self = concat( 'newsletter/approval/', $edition.id, '/', $version )}
<div class="newsletter newsletter-approval">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Approval of "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $edition.name ) )|wash}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        <div class="nl-ed-state">
            <span>{'Version %version'|i18n( 'cjw_newsletter/editorial',, hash( '%version', $version ) )}{if ne( $version, $current_version )} <span class="nl-pill is-warn">{'not the current version (%current)'|i18n( 'cjw_newsletter/editorial',, hash( '%current', $current_version ) )}</span>{/if}</span>
            {include uri='design:newsletter/editorial/approval_pill.tpl' state=$state}
            {if $required|not}<span class="nl-pill is-info">{'The list needs no approval: the edition can be sent as it is.'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}
            <span class="nl-spacer"></span>
            {if $edition.main_node_id}<a class="button" href={concat( 'content/view/full/', $edition.main_node_id )|ezurl}>{'The edition'|i18n( 'cjw_newsletter/editorial' )}</a>
            <a class="button" href={concat( 'newsletter/article_pool/', $edition.main_node_id )|ezurl}>{'Pick articles'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
            {if and( $approval, $approval.collaboration_item_id )}<a class="button" href={concat( 'collaboration/item/full/', $approval.collaboration_item_id )|ezurl}>{'In the inbox'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
        </div>

        {if $approval}
        <dl class="nl-kv">
            <dt>{'Asked by'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$approval.requester_name|wash}, <time>{$approval.requested|l10n( 'shortdatetime' )}</time></dd>
            {if $approval.decided}<dt>{'Decided by'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$approval.decider_name|wash}, <time>{$approval.decided|l10n( 'shortdatetime' )}</time></dd>{/if}
            {if $approval.comment}<dt>{'Comment'|i18n( 'cjw_newsletter/editorial' )}</dt><dd class="nl-ed-comment">{$approval.comment|wash}</dd>{/if}
            <dt>{'Articles from pools'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$picks|count}</dd>
        </dl>
        {/if}

        {if and( $approval, eq( $state, 'pending' ), $can_decide )}
        <form class="nl-ed-part" method="post" action={$self|ezurl}>
            <h3>{'Your decision'|i18n( 'cjw_newsletter/editorial' )}</h3>
            <p><label for="nl-ed-comment">{'Comment'|i18n( 'cjw_newsletter/editorial' )}</label><br />
               <textarea id="nl-ed-comment" name="Comment" rows="3" cols="60"></textarea></p>
            <input class="defaultbutton" type="submit" name="ApproveButton" value="{'Approve'|i18n( 'cjw_newsletter/editorial' )|wash}" data-nl-confirm-button="{'Approve this edition? It can be sent then.'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <input class="button nl-danger" type="submit" name="RejectButton" value="{'Reject'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        </form>
        {elseif and( $can_request, $required, or( eq( $state, 'none' ), eq( $state, 'rejected' ) ) )}
        <form class="nl-ed-part" method="post" action={$self|ezurl}>
            <h3>{'Ask for the approval'|i18n( 'cjw_newsletter/editorial' )}</h3>
            {if eq( $approver_count, 0 )}<p class="nl-danger">{'Nobody gets the request yet: set [ApprovalSettings] ApproverUserIds[] or ApproverGroupIds[].'|i18n( 'cjw_newsletter/editorial' )}</p>
            {else}<p class="nl-muted">{'%count approvers get it in their collaboration inbox.'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $approver_count ) )}</p>{/if}
            <p><label for="nl-ed-request">{'Message to the approvers'|i18n( 'cjw_newsletter/editorial' )}</label><br />
               <textarea id="nl-ed-request" name="Comment" rows="3" cols="60"></textarea></p>
            <input class="defaultbutton" type="submit" name="RequestButton" value="{'Ask for approval'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        </form>
        {elseif and( $approval, eq( $state, 'pending' ) )}
        <p class="nl-muted">{'The request waits for an approver.'|i18n( 'cjw_newsletter/editorial' )}</p>
        {/if}

        <section>
            <h2>{'History'|i18n( 'cjw_newsletter/editorial' )}</h2>
            {if $history|count}
            <ul class="nl-ed-history">
                {foreach $history as $h}
                <li>
                    {include uri='design:newsletter/editorial/approval_pill.tpl' state=$h.status_identifier}
                    {'Version %version, asked %time by %name'|i18n( 'cjw_newsletter/editorial',, hash( '%version', $h.edition_contentobject_version, '%time', $h.requested|l10n( 'shortdatetime' ), '%name', $h.requester_name ) )|wash}{if $h.decided}; {'decided %time by %name'|i18n( 'cjw_newsletter/editorial',, hash( '%time', $h.decided|l10n( 'shortdatetime' ), '%name', $h.decider_name ) )|wash}{/if}
                    {if $h.comment}<p class="nl-ed-comment">{$h.comment|wash}</p>{/if}
                </li>
                {/foreach}
            </ul>
            {else}
            <p class="nl-muted">{'The approval of this edition was never asked for.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {/if}
        </section>
    </div>
</div>
</div>
{include uri='design:parts/newsletter/script.tpl'}
{undef $self}
