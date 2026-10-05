<?php
/**
 * The preview of an edition as one subscriber gets it: his language, the conditional parts, the interests block and
 * the placeholders, in a skin the list allows, HTML and text part. newsletter/preview_as/<edition node>/<user id>/<format>.
 *
 * Nothing is sent and nothing is stored. A sent edition is shown from its stored send (with the outputs per
 * language), an unsent one is rendered now.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class PreviewAs extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $tr = 'cjw_newsletter/rendering';
        $nodeId = (int)$Params['NodeId'];
        $node = \eZContentObjectTreeNode::fetch( $nodeId );
        if ( !$node || !$node->attribute( 'can_read' ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $object = $node->attribute( 'object' );
        $edition = null;
        foreach ( $object->dataMap() as $attribute )
            if ( $attribute->attribute( 'data_type_string' ) === 'cjwnewsletteredition' )
                $edition = $attribute->attribute( 'content' );
        if ( !$edition instanceof \CjwNewsletterEdition )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
        $list = $edition->attribute( 'list_attribute_content' );
        $listId = is_object( $list ) ? (int)$list->attribute( 'contentobject_id' ) : 0;

        // the subscriber: by id in the address, or by the address searched for
        $userId = (int)$Params['NewsletterUserId'];
        $search = $http->hasVariable( 'SubscriberSearch' ) ? trim( (string)$http->variable( 'SubscriberSearch' ) ) : '';
        $message = '';
        if ( $search !== '' )
        {
            $found = \CjwNewsletterUser::fetchByEmail( $search );
            if ( is_object( $found ) )
                return $this->viewResult( null, $module->redirectTo( '/newsletter/preview_as/' . $nodeId . '/' . (int)$found->attribute( 'id' ) . '/' . max( 0, (int)$Params['OutputFormatId'] )
                    . ( $http->hasVariable( 'SkinName' ) ? '?SkinName=' . rawurlencode( (string)$http->variable( 'SkinName' ) ) : '' ) ) );
            $message = \ezpI18n::tr( $tr, 'No subscriber has the address %email.', null, array( '%email' => $search ) );
        }
        $user = $userId > 0 ? \CjwNewsletterUser::fetch( $userId ) : null;
        if ( $userId > 0 && !is_object( $user ) )
            $message = \ezpI18n::tr( $tr, 'The subscriber %id does not exist.', null, array( '%id' => $userId ) );

        $skins = \CjwNewsletterRendering::allowedSkins( $list );
        $skin = $http->hasVariable( 'SkinName' ) ? (string)$http->variable( 'SkinName' ) : '';
        if ( !in_array( $skin, $skins, true ) )
            $skin = is_object( $list ) && (string)$list->attribute( 'skin_name' ) !== '' ? (string)$list->attribute( 'skin_name' ) : ( $skins ? $skins[0] : 'default' );
        $formatId = (int)$Params['OutputFormatId'] === 1 ? 1 : 0;

        $mail = null;
        $send = $edition->attribute( 'edition_send_current' );
        $fromSend = false;
        if ( is_object( $user ) )
        {
            if ( is_object( $send ) && ( !$http->hasVariable( 'SkinName' ) || $skin === (string)$send->attribute( 'skin_name' ) ) )
            {
                $mail = \CjwNewsletterRendering::mailFor( $send, $user, array( 'output_format_id' => $formatId ) );
                $fromSend = true;
            }
            else
                $mail = \CjwNewsletterRendering::mailFor( null, $user, array( 'edition' => $edition, 'skin' => $skin, 'output_format_id' => $formatId ) );
        }

        // subscribers of the list to choose from
        $subscribers = array();
        if ( $listId > 0 )
            foreach ( (array)\CjwNewsletterSubscription::fetchSubscriptionListByListIdAndStatus( $listId, \CjwNewsletterSubscription::STATUS_APPROVED, 15, 0 ) as $subscription )
            {
                $subscriber = \CjwNewsletterUser::fetch( (int)$subscription->attribute( 'newsletter_user_id' ) );
                if ( is_object( $subscriber ) )
                    $subscribers[] = $subscriber;
            }

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'node', $node );
        $tpl->setVariable( 'edition', $edition );
        $tpl->setVariable( 'list', $list );
        $tpl->setVariable( 'newsletter_user', $user );
        $tpl->setVariable( 'interests', is_object( $user ) ? \CjwNewsletterInterests::forUser( $user->attribute( 'id' ) ) : array() );
        $tpl->setVariable( 'subscribers', $subscribers );
        $tpl->setVariable( 'skins', $skins );
        $tpl->setVariable( 'skin', $skin );
        $tpl->setVariable( 'format_id', $formatId );
        $tpl->setVariable( 'mail', $mail );
        $tpl->setVariable( 'from_send', $fromSend );
        $tpl->setVariable( 'search', $search );
        $tpl->setVariable( 'message', $message );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/rendering/preview_as.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => $node->attribute( 'url_alias' ), 'text' => (string)$node->attribute( 'name' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( $tr, 'Preview as a subscriber' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
