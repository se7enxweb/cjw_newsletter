{* The approval of an edition version, admin4. Same data and actions as design/standard/templates/newsletter/editorial/approval.tpl.
   Variables: edition, version, current_version, required, approval (the current request or null), state, history,
   can_request, can_decide, approver_count, picks, notices. *}
{def $self = concat( 'newsletter/approval/', $edition.id, '/', $version )}
<div class="a4-page newsletter newsletter-approval">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Approval of "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%name', $edition.name ) )|wash}</h1>
        </div>
        <div class="a4-pagehead-actions">
            {if $edition.main_node_id}<a class="a4-btn" href={concat( 'content/view/full/', $edition.main_node_id )|ezurl}>{'The edition'|i18n( 'cjw_newsletter/editorial' )}</a>
            <a class="a4-btn" href={concat( 'newsletter/article_pool/', $edition.main_node_id )|ezurl}>{'Pick articles'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
            {if and( $approval, $approval.collaboration_item_id )}<a class="a4-btn" href={concat( 'collaboration/item/full/', $approval.collaboration_item_id )|ezurl}>{'In the inbox'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    <div class="cjwnl-a4-state">
        <span>{'Version %version'|i18n( 'cjw_newsletter/editorial',, hash( '%version', $version ) )}{if ne( $version, $current_version )} <span class="a4-badge is-warn">{'not the current version (%current)'|i18n( 'cjw_newsletter/editorial',, hash( '%current', $current_version ) )}</span>{/if}</span>
        {include uri='design:newsletter/editorial/approval_pill.tpl' state=$state}
        {if $required|not}<span class="a4-badge is-info">{'The list needs no approval: the edition can be sent as it is.'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}
    </div>

    {if $approval}
    <div class="a4-card">
        <dl class="a4-facts">
            <div><dt>{'Asked by'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$approval.requester_name|wash}, <time>{$approval.requested|l10n( 'shortdatetime' )}</time></dd></div>
            {if $approval.decided}<div><dt>{'Decided by'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$approval.decider_name|wash}, <time>{$approval.decided|l10n( 'shortdatetime' )}</time></dd></div>{/if}
            {if $approval.comment}<div><dt>{'Comment'|i18n( 'cjw_newsletter/editorial' )}</dt><dd class="cjwnl-a4-comment">{$approval.comment|wash}</dd></div>{/if}
            <div><dt>{'Articles from pools'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$picks|count}</dd></div>
        </dl>
    </div>
    {/if}

    {if and( $approval, eq( $state, 'pending' ), $can_decide )}
    <form class="a4-card" method="post" action={$self|ezurl}>
        <h2 class="a4-h2">{'Your decision'|i18n( 'cjw_newsletter/editorial' )}</h2>
        <div class="a4-field">
            <label for="nl-ed-comment">{'Comment'|i18n( 'cjw_newsletter/editorial' )}</label>
            <textarea id="nl-ed-comment" name="Comment" rows="3"></textarea>
        </div>
        <div class="a4-actions">
            <input class="a4-btn a4-btn-primary" type="submit" name="ApproveButton" value="{'Approve'|i18n( 'cjw_newsletter/editorial' )|wash}" data-nl-confirm-button="{'Approve this edition? It can be sent then.'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <input class="a4-btn a4-btn-danger nl-danger" type="submit" name="RejectButton" value="{'Reject'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        </div>
    </form>
    {elseif and( $can_request, $required, or( eq( $state, 'none' ), eq( $state, 'rejected' ) ) )}
    <form class="a4-card" method="post" action={$self|ezurl}>
        <h2 class="a4-h2">{'Ask for the approval'|i18n( 'cjw_newsletter/editorial' )}</h2>
        {if eq( $approver_count, 0 )}<p class="a4-notice is-bad nl-danger" role="alert">{'Nobody gets the request yet: set [ApprovalSettings] ApproverUserIds[] or ApproverGroupIds[].'|i18n( 'cjw_newsletter/editorial' )}</p>
        {else}<p class="a4-help">{'%count approvers get it in their collaboration inbox.'|i18n( 'cjw_newsletter/editorial',, hash( '%count', $approver_count ) )}</p>{/if}
        <div class="a4-field">
            <label for="nl-ed-request">{'Message to the approvers'|i18n( 'cjw_newsletter/editorial' )}</label>
            <textarea id="nl-ed-request" name="Comment" rows="3"></textarea>
        </div>
        <div class="a4-actions">
            <input class="a4-btn a4-btn-primary" type="submit" name="RequestButton" value="{'Ask for approval'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        </div>
    </form>
    {elseif and( $approval, eq( $state, 'pending' ) )}
    <p class="a4-help">{'The request waits for an approver.'|i18n( 'cjw_newsletter/editorial' )}</p>
    {/if}

    <section class="a4-section">
        <h2 class="a4-h2">{'History'|i18n( 'cjw_newsletter/editorial' )}</h2>
        {if $history|count}
        <ol class="a4-timeline">
            {foreach $history as $h}
            <li class="{switch match=$h.status_identifier}{case match='approved'}is-ok{/case}{case match='pending'}is-warn{/case}{case match='rejected'}is-bad{/case}{case}is-info{/case}{/switch}">
                <div class="a4-timeline-body">
                    {include uri='design:newsletter/editorial/approval_pill.tpl' state=$h.status_identifier}
                    {'Version %version, asked %time by %name'|i18n( 'cjw_newsletter/editorial',, hash( '%version', $h.edition_contentobject_version, '%time', $h.requested|l10n( 'shortdatetime' ), '%name', $h.requester_name ) )|wash}{if $h.decided}; {'decided %time by %name'|i18n( 'cjw_newsletter/editorial',, hash( '%time', $h.decided|l10n( 'shortdatetime' ), '%name', $h.decider_name ) )|wash}{/if}
                    {if $h.comment}<p class="a4-timeline-meta cjwnl-a4-comment">{$h.comment|wash}</p>{/if}
                </div>
            </li>
            {/foreach}
        </ol>
        {else}
        <p class="a4-empty">{'The approval of this edition was never asked for.'|i18n( 'cjw_newsletter/editorial' )}</p>
        {/if}
    </section>
</div>
{include uri='design:parts/newsletter/script.tpl'}
{undef $self}
