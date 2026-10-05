{* A newsletter approval in the collaboration inbox, the old admin designs (cjw_newsletter 4.2.0, area editorial).
   The decision posts to collaboration/action like the content approval; CjwNewsletterApprovalCollaborationHandler
   checks the policy newsletter/approve. Variables: collab_item. *}
{def $edition = fetch( 'content', 'object', hash( 'object_id', $collab_item.content.content_object_id ) )
     $version = $collab_item.content.content_object_version
     $waiting = eq( $collab_item.data_int3, 0 )
     $message_list = fetch( 'collaboration', 'message_list', hash( 'item_id', $collab_item.id, 'limit', 200, 'offset', 0 ) )
     $participant_list = fetch( 'collaboration', 'participant_map', hash( 'item_id', $collab_item.id ) )
     $current_participant = fetch( 'collaboration', 'participant', hash( 'item_id', $collab_item.id ) )}
<div class="context-block">
<div class="box-header"><h1 class="context-title">{'Newsletter approval'|i18n( 'cjw_newsletter/editorial' )}</h1><div class="header-mainline"></div></div>
<div class="box-content">
    <div class="block">
    {if $edition}
        <p><strong>{$edition.name|wash}</strong> ({'version %version'|i18n( 'cjw_newsletter/editorial',, hash( '%version', $version ) )})</p>
        <p>
            <a href={concat( 'content/view/full/', $edition.main_node_id )|ezurl}>{'The edition'|i18n( 'cjw_newsletter/editorial' )}</a> |
            <a href={concat( 'newsletter/preview/', $edition.id, '/', $version, '/0' )|ezurl} target="_blank">{'Preview'|i18n( 'cjw_newsletter/editorial' )}</a> |
            <a href={concat( 'newsletter/approval/', $edition.id, '/', $version )|ezurl}>{'Approval page'|i18n( 'cjw_newsletter/editorial' )}</a>
        </p>
    {else}
        <p>{'The edition %id was removed.'|i18n( 'cjw_newsletter/editorial',, hash( '%id', $collab_item.content.content_object_id ) )}</p>
    {/if}
    {switch match=$collab_item.data_int3}
    {case match=1}<p>{'The edition was approved. It can be sent now.'|i18n( 'cjw_newsletter/editorial' )}</p>{/case}
    {case match=2}<p>{'The edition was rejected.'|i18n( 'cjw_newsletter/editorial' )}</p>{/case}
    {case match=3}<p>{'The request was replaced by a newer one (the edition changed).'|i18n( 'cjw_newsletter/editorial' )}</p>{/case}
    {case}<p>{if $collab_item.is_creator}{'The edition waits for an approver.'|i18n( 'cjw_newsletter/editorial' )}{else}{'The edition waits for your approval before it is sent.'|i18n( 'cjw_newsletter/editorial' )}{/if}</p>{/case}
    {/switch}
    </div>

    {if $waiting}
    <form method="post" action={'collaboration/action/'|ezurl}>
        <div class="block">
            <label for="Collaboration_ApproveComment">{'Comment'|i18n( 'cjw_newsletter/editorial' )}</label>
            <textarea id="Collaboration_ApproveComment" class="box" name="Collaboration_ApproveComment" cols="40" rows="5"></textarea>
        </div>
        <input type="hidden" name="CollaborationActionCustom" value="custom" />
        <input type="hidden" name="CollaborationTypeIdentifier" value="cjwnewsletterapproval" />
        <input type="hidden" name="CollaborationItemID" value="{$collab_item.id}" />
        <div class="block">
            <input class="button" type="submit" name="CollaborationAction_Comment" value="{'Add comment'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            {if $collab_item.is_creator|not}
            <input class="defaultbutton" type="submit" name="CollaborationAction_Accept" value="{'Approve'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <input class="button" type="submit" name="CollaborationAction_Deny" value="{'Reject'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            {/if}
        </div>
    </form>
    {/if}

    <h2>{'Participants'|i18n( 'cjw_newsletter/editorial' )}</h2>
    <ul>
    {foreach $participant_list as $role}{foreach $role.items as $participant}
        <li>{collaboration_participation_view view=text_linked collaboration_participant=$participant} ({$role.name|wash})</li>
    {/foreach}{/foreach}
    </ul>

    <h2>{'Messages'|i18n( 'cjw_newsletter/editorial' )} ({$message_list|count})</h2>
    {if $message_list|count}
    <table class="list" cellspacing="0">
    {foreach $message_list as $link sequence array( 'bglight', 'bgdark' ) as $seq}
        {collaboration_simple_message_view view=element sequence=$seq is_read=$current_participant.last_read|gt( $link.modified ) item_link=$link collaboration_message=$link.simple_message}
    {/foreach}
    </table>
    {/if}
</div>
</div>
{undef $edition $version $waiting $message_list $participant_list $current_participant}
