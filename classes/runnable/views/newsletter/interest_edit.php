<?php
/**
 * Creates (newsletter/interest_edit/0) or edits an interest.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class InterestEdit extends \Exponential\Runnable\ModuleView
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
        $id = (int)$Params['InterestId'];
        $interest = $id > 0 ? \CjwNewsletterInterest::fetch( $id ) : \CjwNewsletterInterest::create( array( 'is_active' => 1 ) );
        if ( !$interest )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
        if ( $http->hasPostVariable( 'CancelButton' ) )
            return $this->viewResult( null, $module->redirectTo( '/newsletter/interest_list' ) );

        $errors = array();
        $data = array( 'list_contentobject_id' => (int)$interest->attribute( 'list_contentobject_id' ), 'identifier' => (string)$interest->attribute( 'identifier' ),
                       'name' => (string)$interest->attribute( 'name' ), 'source' => (string)$interest->attribute( 'source' ),
                       'eztags_id' => (int)$interest->attribute( 'eztags_id' ), 'priority' => (int)$interest->attribute( 'priority' ),
                       'is_active' => (int)$interest->attribute( 'is_active' ) );
        if ( $http->hasPostVariable( 'StoreButton' ) )
        {
            foreach ( array( 'list_contentobject_id', 'identifier', 'name', 'source', 'eztags_id', 'priority' ) as $field )
                if ( $http->hasPostVariable( 'Interest_' . $field ) )
                    $data[$field] = trim( (string)$http->postVariable( 'Interest_' . $field ) );
            $data['is_active'] = $http->hasPostVariable( 'Interest_is_active' ) ? 1 : 0;
            $errors = \CjwNewsletterInterests::storeFromForm( $interest, $data );
            if ( !$errors )
            {
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The interest "%name" was saved.', null, array( '%name' => $interest->attribute( 'name' ) ) ) );
                return $this->viewResult( null, $module->redirectTo( '/newsletter/interest_list' ) );
            }
        }

        $lists = array();
        foreach ( (array)\eZDB::instance()->arrayQuery( 'SELECT DISTINCT contentobject_id FROM cjwnl_list' ) as $row )
        {
            $object = \eZContentObject::fetch( (int)$row['contentobject_id'] );
            if ( $object )
                $lists[(int)$row['contentobject_id']] = (string)$object->attribute( 'name' );
        }
        asort( $lists );

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'interest', $interest );
        $tpl->setVariable( 'data', $data );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'lists', $lists );
        $tpl->setVariable( 'eztags', class_exists( 'eZTagsObject' ) );
        $tpl->setVariable( 'users', $id > 0 ? \CjwNewsletterInterests::userCount( $id ) : 0 );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/rendering/interest_edit.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => 'newsletter/interest_list', 'text' => \ezpI18n::tr( $tr, 'Interests' ) ),
                                 array( 'url' => false, 'text' => $id > 0 ? (string)$interest->attribute( 'name' ) : \ezpI18n::tr( $tr, 'New interest' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
