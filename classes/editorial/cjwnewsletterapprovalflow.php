<?php
/**
 * File containing the CjwNewsletterApprovalFlow class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * The optional approval of editions before they are sent, per list (cjwnl_list.approval_required).
 *
 * An editor asks for the approval of one version of an edition. The request is a row of cjwnl_approval and an item
 * of the collaboration inbox (handler cjwnewsletterapproval) for the approvers: the users of
 * [ApprovalSettings] ApproverUserIds[] and the members of the groups ApproverGroupIds[]. A user decides when he has
 * the policy newsletter/approve; the user who asked cannot approve his own request unless AllowSelfApproval is
 * enabled. Until the version is approved, the send form refuses to send it and the queue holds the sends of it.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterApprovalFlow
{
    const COLLABORATION_TYPE = 'cjwnewsletterapproval';

    /**
     * @param int $listObjectId
     * @return bool true when the list wants an approval before a send
     */
    static function isRequired( $listObjectId )
    {
        $list = CjwNewsletterList::fetchByListObjectVersion( (int)$listObjectId, 0 );
        return is_object( $list ) && (int)$list->attribute( 'approval_required' ) === 1;
    }

    /**
     * @return int the content object id of the list of an edition, 0 when it has none
     */
    static function listOfEdition( $editionObjectId )
    {
        $object = eZContentObject::fetch( (int)$editionObjectId );
        $node = $object ? $object->attribute( 'main_node' ) : null;
        $parent = $node ? $node->attribute( 'parent' ) : null;
        return $parent ? (int)$parent->attribute( 'contentobject_id' ) : 0;
    }

    /**
     * @return CjwNewsletterApproval|null the newest request of the version that was not replaced
     */
    static function current( $editionObjectId, $version )
    {
        $rows = CjwNewsletterApproval::fetchList( array( 'edition_contentobject_id' => (int)$editionObjectId,
                                                        'edition_contentobject_version' => (int)$version,
                                                        'status' => array( '<', CjwNewsletterApproval::STATUS_WITHDRAWN ) ),
                                                 1, 0, array( 'id' => 'desc' ) );
        return $rows ? $rows[0] : null;
    }

    /** @return bool */
    static function isApproved( $editionObjectId, $version )
    {
        $row = self::current( $editionObjectId, $version );
        return $row !== null && (int)$row->attribute( 'status' ) === CjwNewsletterApproval::STATUS_APPROVED;
    }

    /**
     * @return string none, pending, approved or rejected
     */
    static function state( $editionObjectId, $version )
    {
        $row = self::current( $editionObjectId, $version );
        return $row ? $row->statusIdentifier() : 'none';
    }

    /**
     * May the version be sent? True when its list needs no approval or when the version is approved.
     */
    static function maySend( $editionObjectId, $version, $listObjectId = 0 )
    {
        $listObjectId = (int)$listObjectId > 0 ? (int)$listObjectId : self::listOfEdition( $editionObjectId );
        if ( !self::isRequired( $listObjectId ) )
            return true;
        return self::isApproved( $editionObjectId, $version );
    }

    /**
     * @return CjwNewsletterApproval[] the requests that wait for a decision, oldest first
     */
    static function pending( $limit = 0 )
    {
        return CjwNewsletterApproval::fetchList( array( 'status' => CjwNewsletterApproval::STATUS_PENDING ), $limit, 0, array( 'requested' => 'asc', 'id' => 'asc' ) );
    }

    /**
     * @return CjwNewsletterApproval[] every request of an edition, newest first
     */
    static function history( $editionObjectId )
    {
        return CjwNewsletterApproval::fetchList( array( 'edition_contentobject_id' => (int)$editionObjectId ), 0, 0, array( 'id' => 'desc' ) );
    }

    /**
     * @return int[] the user ids the request goes to (enabled users only)
     */
    static function approverUserIds()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $ids = array();
        foreach ( $ini->hasVariable( 'ApprovalSettings', 'ApproverUserIds' ) ? (array)$ini->variable( 'ApprovalSettings', 'ApproverUserIds' ) : array() as $id )
            if ( (int)$id > 0 )
                $ids[] = (int)$id;
        $groups = array();
        foreach ( $ini->hasVariable( 'ApprovalSettings', 'ApproverGroupIds' ) ? (array)$ini->variable( 'ApprovalSettings', 'ApproverGroupIds' ) : array() as $id )
            if ( (int)$id > 0 )
                $groups[] = (int)$id;
        foreach ( $groups as $groupId )
        {
            $group = eZContentObject::fetch( $groupId );
            if ( !$group )
                continue;
            foreach ( (array)$group->attribute( 'assigned_nodes' ) as $node )
            {
                $members = eZContentObjectTreeNode::subTreeByNodeID( array( 'Limitation' => array(), 'IgnoreVisibility' => true,
                    'ClassFilterType' => 'include', 'ClassFilterArray' => eZUser::fetchUserClassNames() ), (int)$node->attribute( 'node_id' ) );
                foreach ( (array)$members as $member )
                    $ids[] = (int)$member->attribute( 'contentobject_id' );
            }
        }
        $result = array();
        foreach ( array_unique( $ids ) as $id )
        {
            $user = eZUser::fetch( $id );
            if ( $user instanceof eZUser && $user->isEnabled() )
                $result[] = $id;
        }
        return $result;
    }

    /**
     * May the user decide this request? He needs the policy newsletter/approve, and he is not the one who asked
     * (unless AllowSelfApproval=enabled).
     */
    static function canDecide( $approval, $user = null )
    {
        $user = $user instanceof eZUser ? $user : eZUser::currentUser();
        $access = $user->hasAccessTo( 'newsletter', 'approve' );
        if ( $access['accessWord'] === 'no' )
            return false;
        if ( $approval instanceof CjwNewsletterApproval && (int)$approval->attribute( 'requested_by' ) === (int)$user->attribute( 'contentobject_id' ) )
            return self::setting( 'AllowSelfApproval', 'disabled' ) === 'enabled';
        return true;
    }

    /**
     * Asks for the approval of a version. A request of the version that waits or was approved is returned as it is;
     * the requests of the edition's other versions are replaced (withdrawn).
     *
     * @param eZContentObject $edition
     * @param int $version
     * @param int $userId who asks
     * @param string $comment
     * @return CjwNewsletterApproval|false
     */
    static function request( $edition, $version, $userId, $comment = '' )
    {
        if ( !$edition instanceof eZContentObject || $edition->attribute( 'class_identifier' ) !== 'cjw_newsletter_edition' )
            return false;
        $editionId = (int)$edition->attribute( 'id' );
        $version = (int)$version > 0 ? (int)$version : (int)$edition->attribute( 'current_version' );
        $existing = self::current( $editionId, $version );
        if ( $existing && (int)$existing->attribute( 'status' ) !== CjwNewsletterApproval::STATUS_REJECTED )
            return $existing;

        $db = eZDB::instance();
        $db->begin();
        self::withdraw( $editionId, 0 );
        $approval = CjwNewsletterApproval::create( array(
            'edition_contentobject_id' => $editionId,
            'edition_contentobject_version' => $version,
            'list_contentobject_id' => self::listOfEdition( $editionId ),
            'status' => CjwNewsletterApproval::STATUS_PENDING,
            'requested_by' => (int)$userId,
            'requested' => time(),
            'comment' => mb_substr( trim( (string)$comment ), 0, 2000 ) ) );
        $approval->store();
        $item = self::createCollaborationItem( $approval, (int)$userId );
        if ( $item )
        {
            $approval->setAttribute( 'collaboration_item_id', (int)$item->attribute( 'id' ) );
            $approval->store();
        }
        $db->commit();
        return $approval;
    }

    /**
     * Approves or rejects a request.
     *
     * @param CjwNewsletterApproval $approval
     * @param bool $approve
     * @param int $userId who decides
     * @param string $comment
     * @return bool false when the request was decided already or the user may not decide it
     */
    static function decide( $approval, $approve, $userId, $comment = '' )
    {
        if ( !$approval instanceof CjwNewsletterApproval || (int)$approval->attribute( 'status' ) !== CjwNewsletterApproval::STATUS_PENDING )
            return false;
        $user = eZUser::fetch( (int)$userId );
        if ( !$user instanceof eZUser || !self::canDecide( $approval, $user ) )
            return false;
        $db = eZDB::instance();
        $db->begin();
        $approval->setAttribute( 'status', $approve ? CjwNewsletterApproval::STATUS_APPROVED : CjwNewsletterApproval::STATUS_REJECTED );
        $approval->setAttribute( 'decided_by', (int)$userId );
        $approval->setAttribute( 'decided', time() );
        $comment = mb_substr( trim( (string)$comment ), 0, 2000 );
        if ( $comment !== '' )
            $approval->setAttribute( 'comment', $comment );
        $approval->store();
        self::closeCollaborationItem( $approval, $approve ? 1 : 2, $comment, (int)$userId );
        $db->commit();
        return true;
    }

    /**
     * Replaces the waiting and approved requests of an edition (a version changed, the picks changed).
     *
     * @param int $exceptVersion a version to leave alone, 0 = none
     * @return int how many requests were replaced
     */
    static function withdraw( $editionObjectId, $exceptVersion = 0 )
    {
        $count = 0;
        foreach ( CjwNewsletterApproval::fetchList( array( 'edition_contentobject_id' => (int)$editionObjectId,
                                                          'status' => array( array( CjwNewsletterApproval::STATUS_PENDING, CjwNewsletterApproval::STATUS_APPROVED ) ) ) ) as $row )
        {
            if ( (int)$exceptVersion > 0 && (int)$row->attribute( 'edition_contentobject_version' ) === (int)$exceptVersion )
                continue;
            $row->setAttribute( 'status', CjwNewsletterApproval::STATUS_WITHDRAWN );
            $row->store();
            self::closeCollaborationItem( $row, 3, '', 0 );
            ++$count;
        }
        return $count;
    }

    /**
     * The inbox item of a request: the author (who asked) and the approvers as participants.
     *
     * @return eZCollaborationItem|null
     */
    protected static function createCollaborationItem( $approval, $authorId )
    {
        $approvers = array_values( array_diff( self::approverUserIds(), array( (int)$authorId ) ) );
        if ( !$approvers && self::setting( 'AllowSelfApproval', 'disabled' ) === 'enabled' )
            $approvers = array( (int)$authorId );
        if ( !$approvers || (int)$authorId <= 0 )
            return null;
        $item = eZCollaborationItem::create( self::COLLABORATION_TYPE, (int)$authorId );
        $item->setAttribute( 'data_int1', (int)$approval->attribute( 'edition_contentobject_id' ) );
        $item->setAttribute( 'data_int2', (int)$approval->attribute( 'edition_contentobject_version' ) );
        $item->setAttribute( 'data_int3', 0 );
        $item->setAttribute( 'data_text1', (string)(int)$approval->attribute( 'id' ) );
        $item->store();
        $itemId = (int)$item->attribute( 'id' );
        $participants = array( array( array( (int)$authorId ), eZCollaborationItemParticipantLink::ROLE_AUTHOR ),
                               array( $approvers, eZCollaborationItemParticipantLink::ROLE_APPROVER ) );
        foreach ( $participants as $entry )
        {
            foreach ( array_unique( $entry[0] ) as $participantId )
            {
                $link = eZCollaborationItemParticipantLink::create( $itemId, $participantId, $entry[1], eZCollaborationItemParticipantLink::TYPE_USER );
                $link->store();
                $profile = eZCollaborationProfile::instance( $participantId );
                eZCollaborationItemGroupLink::addItem( $profile->attribute( 'main_group' ), $itemId, $participantId );
            }
        }
        if ( trim( (string)$approval->attribute( 'comment' ) ) !== '' )
            self::addMessage( $item, (string)$approval->attribute( 'comment' ), (int)$authorId );
        if ( self::setting( 'Notification', 'enabled' ) === 'enabled' )
            $item->createNotificationEvent();
        return $item;
    }

    /** Closes the inbox item of a request with the decision (data_int3: 1 approved, 2 rejected, 3 replaced). */
    protected static function closeCollaborationItem( $approval, $state, $comment, $userId )
    {
        $item = (int)$approval->attribute( 'collaboration_item_id' ) ? eZCollaborationItem::fetch( (int)$approval->attribute( 'collaboration_item_id' ) ) : null;
        if ( !$item instanceof eZCollaborationItem )
            return;
        $item->setAttribute( 'data_int3', (int)$state );
        $item->setAttribute( 'status', eZCollaborationItem::STATUS_INACTIVE );
        $item->setAttribute( 'modified', time() );
        $item->store();
        foreach ( eZCollaborationItemParticipantLink::fetchParticipantList( array( 'item_id' => (int)$item->attribute( 'id' ) ) ) as $link )
            $item->setIsActive( false, (int)$link->attribute( 'participant_id' ) );
        if ( trim( (string)$comment ) !== '' && $userId > 0 )
            self::addMessage( $item, $comment, $userId );
    }

    /** A message of the thread of the inbox item, written as $userId. */
    protected static function addMessage( $item, $text, $userId )
    {
        $message = eZCollaborationSimpleMessage::create( 'ezapprove_comment', $text, (int)$userId );
        $message->store();
        eZCollaborationItemMessageLink::addMessage( $item, $message, 1, (int)$userId );
    }

    /**
     * Removes the inbox item of a request and what hangs on it (participants, groups, status, messages); used when
     * the request itself is removed.
     */
    static function removeCollaborationItem( $itemId )
    {
        $itemId = (int)$itemId;
        if ( $itemId <= 0 )
            return;
        $item = eZCollaborationItem::fetch( $itemId );
        if ( !$item instanceof eZCollaborationItem || $item->attribute( 'type_identifier' ) !== self::COLLABORATION_TYPE )
            return;
        $db = eZDB::instance();
        $db->begin();
        foreach ( eZCollaborationItemMessageLink::fetchItemList( array( 'item_id' => $itemId ) ) as $link )
        {
            $message = $link->attribute( 'simple_message' );
            if ( $message )
                $message->remove();
            $link->remove();
        }
        eZPersistentObject::removeObject( eZCollaborationItemParticipantLink::definition(), array( 'collaboration_id' => $itemId ) );
        eZPersistentObject::removeObject( eZCollaborationItemGroupLink::definition(), array( 'collaboration_id' => $itemId ) );
        eZPersistentObject::removeObject( eZCollaborationItemStatus::definition(), array( 'collaboration_id' => $itemId ) );
        if ( class_exists( 'eZNotificationEvent' ) )
            eZPersistentObject::removeObject( eZNotificationEvent::definition(), array( 'event_type_string' => 'ezcollaboration', 'data_int1' => $itemId ) );
        $item->remove();
        $db->commit();
    }

    /** @return string a value of [ApprovalSettings] */
    static function setting( $name, $default )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'ApprovalSettings', $name ) ? (string)$ini->variable( 'ApprovalSettings', $name ) : $default;
    }
}
