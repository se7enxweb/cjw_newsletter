<?php
/**
 * File containing the CjwNewsletterApprovalCollaborationHandler class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * The approval of a newsletter edition in the collaboration inbox.
 *
 * The item carries data_int1 = the edition's content object id, data_int2 = the version, data_int3 = the state
 * (0 waiting, 1 approved, 2 rejected, 3 replaced by a newer request) and data_text1 = the id of the cjwnl_approval
 * row. Approve and Reject go through CjwNewsletterApprovalFlow::decide(), which checks the policy newsletter/approve.
 *
 * Registered by settings/collaboration.ini.append.php of the extension.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterApprovalCollaborationHandler extends eZCollaborationItemHandler
{
    const MESSAGE_TYPE_COMMENT = 1;

    public function __construct()
    {
        parent::__construct(
            CjwNewsletterApprovalFlow::COLLABORATION_TYPE,
            ezpI18n::tr( 'cjw_newsletter/editorial', 'Newsletter approval' ),
            array( 'use-messages' => true,
                   'notification-types' => true,
                   'notification-collection-handling' => eZCollaborationItemHandler::NOTIFICATION_COLLECTION_PER_PARTICIPATION_ROLE ) );
    }

    function title( $collaborationItem )
    {
        $approval = self::approval( $collaborationItem );
        $name = $approval ? $approval->editionName() : '';
        return $name !== '' ? ezpI18n::tr( 'cjw_newsletter/editorial', 'Newsletter approval: %name', null, array( '%name' => $name ) )
                            : ezpI18n::tr( 'cjw_newsletter/editorial', 'Newsletter approval' );
    }

    function content( $collaborationItem )
    {
        return array( 'content_object_id' => (int)$collaborationItem->attribute( 'data_int1' ),
                      'content_object_version' => (int)$collaborationItem->attribute( 'data_int2' ),
                      'approval_status' => (int)$collaborationItem->attribute( 'data_int3' ),
                      'approval_id' => (int)$collaborationItem->attribute( 'data_text1' ) );
    }

    function notificationParticipantTemplate( $participantRole )
    {
        if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_APPROVER )
            return 'approve.tpl';
        if ( $participantRole == eZCollaborationItemParticipantLink::ROLE_AUTHOR )
            return 'author.tpl';
        return false;
    }

    function readItem( $collaborationItem, $viewMode = false )
    {
        $collaborationItem->setLastRead();
    }

    function messageCount( $collaborationItem )
    {
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ) ) );
    }

    function unreadMessageCount( $collaborationItem )
    {
        $lastRead = 0;
        $status = $collaborationItem->attribute( 'user_status' );
        if ( $status )
            $lastRead = $status->attribute( 'last_read' );
        return eZCollaborationItemMessageLink::fetchItemCount( array( 'item_id' => $collaborationItem->attribute( 'id' ),
                                                                      'conditions' => array( 'modified' => array( '>', $lastRead ) ) ) );
    }

    /**
     * @return CjwNewsletterApproval|null the request of the item
     */
    static function approval( $collaborationItem )
    {
        $id = (int)$collaborationItem->attribute( 'data_text1' );
        return $id > 0 ? CjwNewsletterApproval::fetch( $id ) : null;
    }

    /**
     * Comment, Accept (approve) or Deny (reject).
     */
    function handleCustomAction( $module, $collaborationItem )
    {
        $redirectView = 'item';
        $redirectParameters = array( 'full', $collaborationItem->attribute( 'id' ) );
        $approval = self::approval( $collaborationItem );
        $comment = trim( (string)$this->customInput( 'ApproveComment' ) );
        $userId = (int)eZUser::currentUserID();
        $notice = false;

        if ( $this->isCustomAction( 'Comment' ) )
        {
            if ( $comment !== '' && (int)$collaborationItem->attribute( 'data_int3' ) === 0 )
            {
                $message = eZCollaborationSimpleMessage::create( 'ezapprove_comment', $comment );
                $message->store();
                eZCollaborationItemMessageLink::addMessage( $collaborationItem, $message, self::MESSAGE_TYPE_COMMENT );
                $notice = array( 'success', ezpI18n::tr( 'cjw_newsletter/editorial', 'Your comment was added.' ) );
            }
        }
        else if ( $this->isCustomAction( 'Accept' ) || $this->isCustomAction( 'Deny' ) )
        {
            $approve = $this->isCustomAction( 'Accept' );
            if ( $approval && CjwNewsletterApprovalFlow::decide( $approval, $approve, $userId, $comment ) )
            {
                $notice = array( 'success', $approve ? ezpI18n::tr( 'cjw_newsletter/editorial', 'The edition was approved. It can be sent now.' )
                                                     : ezpI18n::tr( 'cjw_newsletter/editorial', 'The edition was rejected.' ) );
                $redirectView = 'view';
                $redirectParameters = array( 'summary' );
            }
            else
            {
                $notice = array( 'error', ezpI18n::tr( 'cjw_newsletter/editorial', 'You cannot decide this request: it was decided already, or you may not approve newsletters, or you asked for it yourself.' ) );
            }
        }
        $collaborationItem->sync();
        if ( $notice && class_exists( 'expCollaborationGroupManager' ) )
            expCollaborationGroupManager::setNotice( $notice[0], $notice[1] );
        return $module->redirectToView( $redirectView, $redirectParameters );
    }
}
