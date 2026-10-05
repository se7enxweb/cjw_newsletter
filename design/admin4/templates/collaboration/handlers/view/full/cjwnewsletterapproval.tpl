{* A newsletter approval in the collaboration inbox, admin4 (and admin4l): the cards of the admin4 inbox
   (design:collaboration/parts/style.tpl). The decision posts to collaboration/action; the handler checks the policy
   newsletter/approve. Variables: collab_item, notice. cjw_newsletter 4.2.0, area editorial. *}
{def $row = fetch( 'collaboration', 'inbox_row', hash( 'item_id', $collab_item.id ) )
     $edition = fetch( 'content', 'object', hash( 'object_id', $collab_item.content.content_object_id ) )
     $version = $collab_item.content.content_object_version
     $ap = fetch( 'newsletter', 'approval_state', hash( 'edition_contentobject_id', $collab_item.content.content_object_id, 'version', $version ) )
     $picks = fetch( 'newsletter', 'edition_articles', hash( 'edition_contentobject_id', $collab_item.content.content_object_id ) )
     $current_participant = fetch( 'collaboration', 'participant', hash( 'item_id', $collab_item.id ) )
     $participant_list = fetch( 'collaboration', 'participant_map', hash( 'item_id', $collab_item.id ) )
     $message_list = fetch( 'collaboration', 'message_list', hash( 'item_id', $collab_item.id, 'limit', 200, 'offset', 0 ) )
     $waiting = eq( $collab_item.data_int3, 0 )
     $title = cond( $edition, $edition.name, concat( '#', $collab_item.content.content_object_id ) )}
{include uri='design:collaboration/parts/style.tpl'}

<div class="cb" id="exp-collab">
    <div class="cb-head">
        <div>
            <p class="cb-crumb"><a href={'collaboration/view/summary'|ezurl}>{'Collaboration'|i18n( 'design/admin/collaboration/inbox' )}</a> / {'Newsletter approval'|i18n( 'cjw_newsletter/editorial' )}</p>
            <h1>{$title|wash}</h1>
        </div>
        <span class="cb-badge {switch match=$collab_item.data_int3}{case match=1}approved{/case}{case in=array( 2, 3 )}denied{/case}{case}waiting{/case}{/switch}">
            {switch match=$collab_item.data_int3}
            {case match=1}{'Approved'|i18n( 'cjw_newsletter/editorial' )}{/case}
            {case match=2}{'Rejected'|i18n( 'cjw_newsletter/editorial' )}{/case}
            {case match=3}{'Replaced'|i18n( 'cjw_newsletter/editorial' )}{/case}
            {case}{'Waiting for approval'|i18n( 'cjw_newsletter/editorial' )}{/case}
            {/switch}
        </span>
    </div>

    {include uri='design:collaboration/parts/notice.tpl' notice=first_set( $notice, false() )}

    <div class="cb-card">
        <h2>{'The edition'|i18n( 'cjw_newsletter/editorial' )}</h2>
        <dl class="cb-facts">
            <div><dt>{'Asked by'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{if $row}{$row.author_name|wash}{/if}</dd></div>
            <div><dt>{'Asked'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$collab_item.created|l10n( 'shortdatetime' )}</dd></div>
            <div><dt>{'Version'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$version}</dd></div>
            <div><dt>{'Articles from pools'|i18n( 'cjw_newsletter/editorial' )}</dt><dd>{$picks|count}</dd></div>
        </dl>
        {if $edition}
        <p>
            <a class="cb-btn" href={concat( 'content/view/full/', $edition.main_node_id )|ezurl}>{'Open the edition'|i18n( 'cjw_newsletter/editorial' )}</a>
            <a class="cb-btn" href={concat( 'newsletter/preview/', $edition.id, '/', $version, '/0' )|ezurl} target="_blank">{'Preview'|i18n( 'cjw_newsletter/editorial' )}</a>
            <a class="cb-btn" href={concat( 'newsletter/approval/', $edition.id, '/', $version )|ezurl}>{'Approval page'|i18n( 'cjw_newsletter/editorial' )}</a>
        </p>
        {else}
        <p class="cb-hint">{'The edition %id was removed.'|i18n( 'cjw_newsletter/editorial',, hash( '%id', $collab_item.content.content_object_id ) )}</p>
        {/if}
    </div>

    <div class="cb-card">
        <h2>{'Decision'|i18n( 'cjw_newsletter/editorial' )}</h2>
{switch match=$collab_item.data_int3}
{case match=1}        <p>{'The edition was approved. It can be sent now.'|i18n( 'cjw_newsletter/editorial' )}</p>{/case}
{case match=2}        <p>{'The edition was rejected.'|i18n( 'cjw_newsletter/editorial' )}</p>{/case}
{case match=3}        <p>{'The request was replaced by a newer one (the edition changed).'|i18n( 'cjw_newsletter/editorial' )}</p>{/case}
{case}        <p>{if $collab_item.is_creator}{'The edition waits for an approver.'|i18n( 'cjw_newsletter/editorial' )}{else}{'The edition waits for your approval before it is sent.'|i18n( 'cjw_newsletter/editorial' )}{/if}</p>{/case}
{/switch}
{if $waiting}
        <form class="cb-compose" method="post" action={'collaboration/action/'|ezurl}>
            <label for="Collaboration_ApproveComment">{'Comment'|i18n( 'cjw_newsletter/editorial' )}</label>
            <textarea id="Collaboration_ApproveComment" name="Collaboration_ApproveComment" rows="4"></textarea>
            <input type="hidden" name="CollaborationActionCustom" value="custom" />
            <input type="hidden" name="CollaborationTypeIdentifier" value="cjwnewsletterapproval" />
            <input type="hidden" name="CollaborationItemID" value="{$collab_item.id}" />
            <div class="cb-actions">
                <input class="cb-btn" type="submit" name="CollaborationAction_Comment" value="{'Add comment'|i18n( 'cjw_newsletter/editorial' )|wash}" />
    {if $collab_item.is_creator|not}
                <input class="cb-btn approve" type="submit" name="CollaborationAction_Accept" value="{'Approve'|i18n( 'cjw_newsletter/editorial' )|wash}"
                       data-cb-confirm="{'Approve "%title"? It can be sent then.'|i18n( 'cjw_newsletter/editorial',, hash( '%title', $title ) )|wash}" />
                <input class="cb-btn deny" type="submit" name="CollaborationAction_Deny" value="{'Reject'|i18n( 'cjw_newsletter/editorial' )|wash}"
                       data-cb-confirm="{'Reject "%title"?'|i18n( 'cjw_newsletter/editorial',, hash( '%title', $title ) )|wash}" />
    {/if}
            </div>
    {if $collab_item.is_creator}
            <p class="cb-hint">{'Only an approver can approve or reject this request.'|i18n( 'cjw_newsletter/editorial' )}</p>
    {/if}
        </form>
{/if}
    </div>

    <div class="cb-card">
        <h2>{'Participants'|i18n( 'cjw_newsletter/editorial' )}</h2>
        <ul class="cb-people">
{foreach $participant_list as $role}{foreach $role.items as $participant}
            <li>{collaboration_participation_view view=text_linked collaboration_participant=$participant} <small>{$role.name|wash}</small></li>
{/foreach}{/foreach}
        </ul>
    </div>

    <div class="cb-card">
        <h2 id="messages">{'Messages'|i18n( 'cjw_newsletter/editorial' )} <small>({$message_list|count})</small></h2>
{if $message_list|count}
        <ul class="cb-thread">
    {foreach $message_list as $link}
        {collaboration_simple_message_view view=element sequence='' is_read=$current_participant.last_read|gt( $link.modified ) item_link=$link collaboration_message=$link.simple_message}
    {/foreach}
        </ul>
{else}
        <p class="cb-hint">{'There are no messages yet.'|i18n( 'cjw_newsletter/editorial' )}</p>
{/if}
    </div>
</div>
<script type="text/javascript">
{literal}
(function () {
    var buttons = document.querySelectorAll('#exp-collab [data-cb-confirm]');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function (e) { if (!window.confirm(this.getAttribute('data-cb-confirm'))) e.preventDefault(); });
    }
})();
{/literal}
</script>
{undef $row $edition $version $ap $picks $current_participant $participant_list $message_list $waiting $title}
