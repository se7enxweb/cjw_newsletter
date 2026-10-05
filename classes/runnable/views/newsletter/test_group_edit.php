<?php
/**
 * newsletter/test_group_edit/<id>: create, edit or remove a test group (cjw_newsletter 4.2.0, area deliverability).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class TestGroupEdit extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $id = isset( $Params['TestGroupId'] ) ? (int)$Params['TestGroupId'] : 0;
        $group = $id > 0 ? \CjwNewsletterTestGroup::fetch( $id ) : \CjwNewsletterTestGroup::create( array() );
        if ( !$group )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $back = '/newsletter/test_group_list';
        $errors = array();
        $confirmRemove = false;

        if ( $http->hasPostVariable( 'CancelButton' ) )
            return $this->viewResult( null, $module->redirectTo( $back ) );
        if ( $http->hasPostVariable( 'RemoveButton' ) && $id > 0 )
            $confirmRemove = true;
        else if ( $http->hasPostVariable( 'ConfirmRemoveButton' ) && $id > 0 )
        {
            $group->remove();
            \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/deliverability', 'The test group was removed.' ) );
            return $this->viewResult( null, $module->redirectTo( $back ) );
        }
        else if ( $http->hasPostVariable( 'StoreButton' ) )
        {
            $input = array();
            foreach ( array( 'name', 'email_list', 'list_contentobject_id' ) as $field )
                $input[$field] = $http->hasPostVariable( $field ) ? (string)$http->postVariable( $field ) : '';
            $errors = \CjwNewsletterTestSend::storeGroup( $group, $input );
            if ( !$errors )
            {
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/deliverability', 'The test group "%name" was stored.', null,
                                                                   array( '%name' => $group->attribute( 'name' ) ) ) );
                return $this->viewResult( null, $module->redirectTo( $back ) );
            }
        }

        include_once( 'kernel/common/template.php' );
        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'group', array( 'id' => (int)$group->attribute( 'id' ), 'name' => (string)$group->attribute( 'name' ),
                                           'email_list' => (string)$group->attribute( 'email_list' ),
                                           'list_contentobject_id' => (int)$group->attribute( 'list_contentobject_id' ) ) );
        $tpl->setVariable( 'lists', \CjwNewsletterTestSend::listChoices() );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'confirm_remove', $confirmRemove );
        $tpl->setVariable( 'max_size', \CjwNewsletterTestSend::maxSize() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/deliverability/test_group_edit.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => 'newsletter/test_group_list', 'text' => \ezpI18n::tr( 'cjw_newsletter/deliverability', 'Test groups' ) ),
                                 array( 'url' => false, 'text' => $id ? (string)$group->attribute( 'name' ) : \ezpI18n::tr( 'cjw_newsletter/deliverability', 'New test group' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
