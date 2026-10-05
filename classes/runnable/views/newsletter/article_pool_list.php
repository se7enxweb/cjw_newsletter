<?php
/**
 * The article pools: the global default and the pools of the lists, with remove (after a confirmation).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class ArticlePoolList extends \Exponential\Runnable\ModuleView
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
        $confirm = null;

        foreach ( array( 'RemoveButton', 'ConfirmRemoveButton' ) as $button )
        {
            if ( $http->hasPostVariable( $button ) && is_array( $http->postVariable( $button ) ) )
            {
                $keys = array_keys( $http->postVariable( $button ) );
                $pool = \CjwNewsletterArticlePool::fetch( (int)$keys[0] );
                if ( !$pool )
                    break;
                if ( $button === 'RemoveButton' )
                {
                    $confirm = $pool;
                    break;
                }
                $name = $pool->label();
                $pool->removePool();
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The article pool "%name" was removed. Its lists and recurring sends use the default pool now.', null, array( '%name' => $name ) ) );
                return $this->viewResult( null, $module->redirectTo( '/newsletter/article_pool_list' ) );
            }
        }

        $pools = \CjwNewsletterArticlePool::fetchList( null, 0, 0, array( 'list_contentobject_id' => 'asc', 'name' => 'asc' ) );
        $usage = array();
        foreach ( \CjwNewsletterEditorialUI::lists() as $list )
            if ( $list['article_pool_id'] > 0 )
                $usage[$list['article_pool_id']][] = $list['name'];

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'pools', $pools );
        $tpl->setVariable( 'usage', $usage );
        $tpl->setVariable( 'settings_pool', \CjwNewsletterArticlePool::fetchDefault() ? null : \CjwNewsletterArticlePool::fromSettings() );
        $tpl->setVariable( 'confirm_remove', $confirm );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/editorial/article_pool_list.tpl' );
        $Result['path'] = \CjwNewsletterEditorialUI::path( array( \ezpI18n::tr( $tr, 'Article pools' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
