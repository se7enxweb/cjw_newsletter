<?php
/**
 * newsletter/test_group_list: the test groups of the lists (cjw_newsletter 4.2.0, area deliverability).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class TestGroupList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );
        $tpl = templateInit();
        $lists = array();
        foreach ( \CjwNewsletterTestSend::listChoices() as $list )
            $lists[$list['id']] = $list['name'];
        $groups = array();
        foreach ( \CjwNewsletterTestGroup::fetchList( null, 0, 0, array( 'name' => 'asc' ) ) as $group )
        {
            $listId = (int)$group->attribute( 'list_contentobject_id' );
            $groups[] = array( 'id' => (int)$group->attribute( 'id' ), 'name' => (string)$group->attribute( 'name' ),
                               'list_id' => $listId, 'list_name' => isset( $lists[$listId] ) ? $lists[$listId] : '',
                               'address_count' => count( \CjwNewsletterTestSend::groupAddresses( $group ) ),
                               'modified' => (int)$group->attribute( 'modified' ) );
        }
        $tpl->setVariable( 'groups', $groups );
        $tpl->setVariable( 'max_size', \CjwNewsletterTestSend::maxSize() );
        $tpl->setVariable( 'subject_prefix', \CjwNewsletterTestSend::subjectPrefix() );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/deliverability/test_group_list.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/deliverability', 'Test groups' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
