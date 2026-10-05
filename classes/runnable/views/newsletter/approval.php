<?php
/**
 * The approval of an edition version: its state and history, "Ask for approval" for the editors, Approve and
 * Reject (with a comment) for the users with the policy newsletter/approve, and the link to the inbox item.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class Approval extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $tr = 'cjw_newsletter/editorial';
        // the view is for the editors (newsletter/editorial) and the approvers (newsletter/approve)
        if ( !\CjwNewsletterEditorialUI::can( 'editorial' ) && !\CjwNewsletterEditorialUI::can( 'approve' ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_ACCESS_DENIED, 'kernel' ) );
        $editionId = isset( $Params['EditionContentObjectId'] ) ? (int)$Params['EditionContentObjectId'] : 0;
        $edition = $editionId > 0 ? \eZContentObject::fetch( $editionId ) : null;
        if ( !$edition instanceof \eZContentObject || $edition->attribute( 'class_identifier' ) !== 'cjw_newsletter_edition' )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $version = isset( $Params['Version'] ) && (int)$Params['Version'] > 0 ? (int)$Params['Version'] : (int)$edition->attribute( 'current_version' );
        if ( !$edition->version( $version ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );

        $listId = \CjwNewsletterApprovalFlow::listOfEdition( $editionId );
        $current = \CjwNewsletterApprovalFlow::current( $editionId, $version );
        $self = '/newsletter/approval/' . $editionId . '/' . $version;
        $comment = \CjwNewsletterEditorialUI::postedText( $http, 'Comment', 2000 );
        $canRequest = \CjwNewsletterEditorialUI::can( 'editorial' ) || \CjwNewsletterEditorialUI::can( 'send' );

        if ( $http->hasPostVariable( 'RequestButton' ) && $canRequest )
        {
            $approval = \CjwNewsletterApprovalFlow::request( $edition, $version, (int)\eZUser::currentUserID(), $comment );
            if ( $approval && !(int)$approval->attribute( 'collaboration_item_id' ) && (int)$approval->attribute( 'status' ) === \CjwNewsletterApproval::STATUS_PENDING )
                \CjwNewsletterUI::notice( 'warning', \ezpI18n::tr( $tr, 'The approval was asked for, but nobody can approve it: set [ApprovalSettings] ApproverUserIds[] or ApproverGroupIds[].' ) );
            else if ( $approval )
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The approval was asked for. The approvers find it in their collaboration inbox.' ) );
            return $this->viewResult( null, $module->redirectTo( $self ) );
        }
        if ( ( $http->hasPostVariable( 'ApproveButton' ) || $http->hasPostVariable( 'RejectButton' ) ) && $current )
        {
            $approve = $http->hasPostVariable( 'ApproveButton' );
            if ( \CjwNewsletterApprovalFlow::decide( $current, $approve, (int)\eZUser::currentUserID(), $comment ) )
                \CjwNewsletterUI::notice( 'feedback', $approve ? \ezpI18n::tr( $tr, 'The edition was approved. It can be sent now.' ) : \ezpI18n::tr( $tr, 'The edition was rejected.' ) );
            else
                \CjwNewsletterUI::notice( 'error', \ezpI18n::tr( $tr, 'You cannot decide this request: it was decided already, or you may not approve newsletters, or you asked for it yourself.' ) );
            return $this->viewResult( null, $module->redirectTo( $self ) );
        }

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'edition', $edition );
        $tpl->setVariable( 'version', $version );
        $tpl->setVariable( 'current_version', (int)$edition->attribute( 'current_version' ) );
        $tpl->setVariable( 'required', \CjwNewsletterApprovalFlow::isRequired( $listId ) );
        $tpl->setVariable( 'approval', $current );
        $tpl->setVariable( 'state', $current ? $current->statusIdentifier() : 'none' );
        $tpl->setVariable( 'history', \CjwNewsletterApprovalFlow::history( $editionId ) );
        $tpl->setVariable( 'can_request', $canRequest );
        $tpl->setVariable( 'can_decide', $current ? \CjwNewsletterApprovalFlow::canDecide( $current ) : false );
        $tpl->setVariable( 'approver_count', count( \CjwNewsletterApprovalFlow::approverUserIds() ) );
        $tpl->setVariable( 'picks', \CjwNewsletterEditionArticle::fetchByEdition( $editionId ) );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/editorial/approval.tpl' );
        $node = $edition->attribute( 'main_node' );
        $Result['path'] = \CjwNewsletterEditorialUI::path( array( ( $node ? $node->attribute( 'url_alias' ) : 0 ) => $edition->attribute( 'name' ),
            \ezpI18n::tr( $tr, 'Approval' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
