<?php
/**
 * Create or edit an article pool: the nodes searched, the classes, sections, tags and states, the age, how many
 * articles the auto-fill takes and their order; "Show the articles" lists what the pool finds now.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class ArticlePoolEdit extends \Exponential\Runnable\ModuleView
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
        $id = isset( $Params['ArticlePoolId'] ) ? (int)$Params['ArticlePoolId'] : 0;

        if ( $id > 0 )
        {
            $pool = \CjwNewsletterArticlePool::fetch( $id );
            if ( !$pool )
                return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }
        else
        {
            $defaults = \CjwNewsletterArticlePool::fromSettings();
            $pool = \CjwNewsletterArticlePool::create( array(
                'parent_node_id_array_string' => $defaults->attribute( 'parent_node_id_array_string' ),
                'class_identifier_array_string' => $defaults->attribute( 'class_identifier_array_string' ),
                'max_age_days' => $defaults->attribute( 'max_age_days' ),
                'max_items' => $defaults->attribute( 'max_items' ),
                'is_default' => \CjwNewsletterArticlePool::fetchDefault() ? 0 : 1 ) );
        }

        if ( $http->hasPostVariable( 'DiscardButton' ) )
            return $this->viewResult( null, $module->redirectTo( '/newsletter/article_pool_list' ) );

        $lists = \CjwNewsletterEditorialUI::lists();
        $classes = \CjwNewsletterEditorialUI::classes();
        $sections = \CjwNewsletterEditorialUI::sections();
        $states = \CjwNewsletterEditorialUI::states();
        $errors = array();
        $found = null;

        if ( $http->hasPostVariable( 'StoreButton' ) || $http->hasPostVariable( 'PreviewButton' ) )
        {
            $name = \CjwNewsletterEditorialUI::postedText( $http, 'Name' );
            if ( $name === '' )
                $errors['name'] = \ezpI18n::tr( $tr, 'Give the pool a name.' );
            $pool->setAttribute( 'name', $name );

            $listId = \CjwNewsletterEditorialUI::postedInt( $http, 'ListId' );
            if ( $listId > 0 && !isset( $lists[$listId] ) )
            {
                $errors['list'] = \ezpI18n::tr( $tr, 'Choose a newsletter list, or none for a global pool.' );
                $listId = 0;
            }
            $pool->setAttribute( 'list_contentobject_id', max( 0, $listId ) );
            $pool->setAttribute( 'is_default', $listId === 0 && \CjwNewsletterEditorialUI::postedInt( $http, 'IsDefault' ) ? 1 : 0 );

            $parents = array();
            foreach ( \CjwNewsletterEditorialUI::postedIds( $http, 'ParentNodeIds' ) as $nodeId )
            {
                if ( \eZContentObjectTreeNode::fetch( $nodeId ) )
                    $parents[] = $nodeId;
                else
                    $errors['parents'] = \ezpI18n::tr( $tr, 'The node %id does not exist.', null, array( '%id' => $nodeId ) );
            }
            if ( !$parents && !isset( $errors['parents'] ) )
                $errors['parents'] = \ezpI18n::tr( $tr, 'Enter at least one node the articles are searched under.' );
            $pool->setAttribute( 'parent_node_id_array_string', \CjwNewsletterArticlePool::toArrayString( $parents ) );

            $chosenClasses = array();
            foreach ( $http->hasPostVariable( 'ClassIdentifiers' ) ? (array)$http->postVariable( 'ClassIdentifiers' ) : array() as $identifier )
                if ( is_string( $identifier ) && isset( $classes[$identifier] ) )
                    $chosenClasses[] = $identifier;
            $pool->setAttribute( 'class_identifier_array_string', \CjwNewsletterArticlePool::toArrayString( $chosenClasses ) );

            $pool->setAttribute( 'section_id_array_string', \CjwNewsletterArticlePool::toArrayString(
                array_intersect( \CjwNewsletterEditorialUI::postedIds( $http, 'SectionIds' ), array_keys( $sections ) ) ) );
            $pool->setAttribute( 'state_id_array_string', \CjwNewsletterArticlePool::toArrayString(
                array_intersect( \CjwNewsletterEditorialUI::postedIds( $http, 'StateIds' ), array_keys( $states ) ) ) );

            $tags = \CjwNewsletterEditorialUI::postedIds( $http, 'TagIds' );
            $known = \CjwNewsletterEditorialUI::tagNames( $tags );
            if ( count( $known ) !== count( $tags ) )
                $errors['tags'] = \ezpI18n::tr( $tr, 'Some of the tag ids do not exist.' );
            $pool->setAttribute( 'tag_id_array_string', \CjwNewsletterArticlePool::toArrayString( array_keys( $known ) ) );

            $pool->setAttribute( 'max_age_days', max( 0, min( 3650, \CjwNewsletterEditorialUI::postedInt( $http, 'MaxAgeDays' ) ) ) );
            $maxItems = \CjwNewsletterEditorialUI::postedInt( $http, 'MaxItems', 10 );
            if ( $maxItems < 1 || $maxItems > \CjwNewsletterArticlePoolFinder::MAX_LIMIT )
                $errors['max_items'] = \ezpI18n::tr( $tr, 'The number of articles is between 1 and %max.', null, array( '%max' => \CjwNewsletterArticlePoolFinder::MAX_LIMIT ) );
            $pool->setAttribute( 'max_items', max( 1, min( \CjwNewsletterArticlePoolFinder::MAX_LIMIT, $maxItems ) ) );
            $sort = \CjwNewsletterEditorialUI::postedText( $http, 'SortBy', 50 );
            $pool->setAttribute( 'sort_by', in_array( $sort, \CjwNewsletterArticlePool::$sortFields, true ) ? $sort : 'published' );

            if ( $http->hasPostVariable( 'PreviewButton' ) )
            {
                $found = \CjwNewsletterArticlePoolFinder::find( $pool );
            }
            else if ( !$errors )
            {
                $now = time();
                if ( !$pool->isStored() )
                {
                    $pool->setAttribute( 'creator_contentobject_id', (int)\eZUser::currentUserID() );
                    $pool->setAttribute( 'created', $now );
                }
                $pool->setAttribute( 'modified', $now );
                $db = \eZDB::instance();
                $db->begin();
                if ( (int)$pool->attribute( 'is_default' ) )
                    $pool->makeDefault();
                $pool->store();
                $db->commit();
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The article pool "%name" was saved.', null, array( '%name' => $pool->label() ) ) );
                return $this->viewResult( null, $module->redirectTo( '/newsletter/article_pool_list' ) );
            }
        }

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'pool', $pool );
        $tpl->setVariable( 'pool_id', $id );
        $tpl->setVariable( 'lists', $lists );
        $tpl->setVariable( 'classes', $classes );
        $tpl->setVariable( 'sections', $sections );
        $tpl->setVariable( 'states', $states );
        $tpl->setVariable( 'tag_names', \CjwNewsletterEditorialUI::tagNames( $pool->tagIds() ) );
        $tpl->setVariable( 'has_tags', class_exists( 'eZTagsObject' ) );
        $tpl->setVariable( 'sort_fields', \CjwNewsletterArticlePool::$sortFields );
        $tpl->setVariable( 'found', $found );
        $tpl->setVariable( 'errors', $errors );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/editorial/article_pool_edit.tpl' );
        $Result['path'] = \CjwNewsletterEditorialUI::path( array( 'newsletter/article_pool_list' => \ezpI18n::tr( $tr, 'Article pools' ),
            $id > 0 ? $pool->label() : \ezpI18n::tr( $tr, 'New article pool' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
