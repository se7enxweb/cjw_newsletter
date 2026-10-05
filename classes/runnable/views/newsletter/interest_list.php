<?php
/**
 * The interests subscribers can pick (cjwnl_interest): the list with the newsletter list, the source, the tag, how
 * many subscribers picked it, and remove (with a confirmation).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class InterestList extends \Exponential\Runnable\ModuleView
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
        $confirm = null;

        foreach ( array( 'RemoveButton', 'ConfirmRemoveButton' ) as $button )
        {
            if ( !$http->hasPostVariable( $button ) || !is_array( $http->postVariable( $button ) ) )
                continue;
            $keys = array_keys( $http->postVariable( $button ) );
            $interest = \CjwNewsletterInterest::fetch( (int)$keys[0] );
            if ( !$interest )
                break;
            if ( $button === 'RemoveButton' )
            {
                $confirm = $interest;
                break;
            }
            \CjwNewsletterInterests::removeInterest( $interest );
            \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The interest "%name" was removed, with the picks of its subscribers.', null,
                array( '%name' => $interest->attribute( 'name' ) ) ) );
            return $this->viewResult( null, $module->redirectTo( '/newsletter/interest_list' ) );
        }

        $rows = array();
        foreach ( \CjwNewsletterInterest::fetchList( null, 0, 0, array( 'list_contentobject_id' => 'asc', 'priority' => 'asc', 'name' => 'asc' ) ) as $interest )
        {
            $listId = (int)$interest->attribute( 'list_contentobject_id' );
            $listObject = $listId > 0 ? \eZContentObject::fetch( $listId ) : null;
            $rows[] = array( 'interest' => $interest,
                             'list_name' => $listId === 0 ? \ezpI18n::tr( $tr, 'Every list' ) : ( $listObject ? (string)$listObject->attribute( 'name' ) : '#' . $listId ),
                             'list_node_id' => $listObject ? (int)$listObject->attribute( 'main_node_id' ) : 0,
                             'users' => \CjwNewsletterInterests::userCount( $interest->attribute( 'id' ) ) );
        }

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'rows', $rows );
        $tpl->setVariable( 'confirm_remove', $confirm );
        $tpl->setVariable( 'confirm_users', $confirm ? \CjwNewsletterInterests::userCount( $confirm->attribute( 'id' ) ) : 0 );
        $tpl->setVariable( 'eztags', class_exists( 'eZTagsObject' ) );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/rendering/interest_list.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( $tr, 'Interests' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
