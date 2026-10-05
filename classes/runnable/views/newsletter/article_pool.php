<?php
/**
 * The article picker of an edition: the articles of the pool of the edition's list, filtered by class, date,
 * section, tag and state; the chosen ones are taken into the edition (a newsletter article under it) and can be
 * taken out again. A change of the picks replaces an approval of the edition (it must be approved again).
 *
 * The filters are view parameters, (class)/<identifier>/(section)/<id>/(state)/<id>/(tag)/<id>/(from)/<Y-m-d>/
 * (to)/<Y-m-d>/(offset)/<n>, each one checked and cast before it is used.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class ArticlePool extends \Exponential\Runnable\ModuleView
{
    const PAGE = 20;

    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $tr = 'cjw_newsletter/editorial';
        $nodeId = isset( $Params['NodeId'] ) ? (int)$Params['NodeId'] : 0;
        $node = $nodeId > 0 ? \eZContentObjectTreeNode::fetch( $nodeId ) : null;
        if ( !$node instanceof \eZContentObjectTreeNode || $node->attribute( 'class_identifier' ) !== 'cjw_newsletter_edition' )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $edition = $node->attribute( 'object' );
        $editionId = (int)$edition->attribute( 'id' );
        $list = $node->attribute( 'parent' );
        $listId = $list ? (int)$list->attribute( 'contentobject_id' ) : 0;
        $pool = \CjwNewsletterArticlePool::forList( $listId );
        $content = \CjwNewsletterEditionBuilder::editionContent( $edition );
        $locked = $content && ( $content->isProcess() || $content->isArchive() );

        $classes = array_intersect_key( \CjwNewsletterEditorialUI::classes(), array_flip( $pool->classIdentifiers() ? $pool->classIdentifiers() : array_keys( \CjwNewsletterEditorialUI::classes() ) ) );
        $sections = \CjwNewsletterEditorialUI::sections();
        $states = \CjwNewsletterEditorialUI::states();
        $filters = self::filters( isset( $Params['UserParameters'] ) ? (array)$Params['UserParameters'] : array(), $classes, $sections, $states );
        $base = '/newsletter/article_pool/' . $nodeId;

        if ( $http->hasPostVariable( 'FilterButton' ) )
        {
            $posted = array( 'class' => \CjwNewsletterEditorialUI::postedText( $http, 'FilterClass', 100 ),
                             'section' => \CjwNewsletterEditorialUI::postedInt( $http, 'FilterSection' ),
                             'state' => \CjwNewsletterEditorialUI::postedInt( $http, 'FilterState' ),
                             'tag' => \CjwNewsletterEditorialUI::postedInt( $http, 'FilterTag' ),
                             'from' => \CjwNewsletterEditorialUI::postedText( $http, 'FilterFrom', 10 ),
                             'to' => \CjwNewsletterEditorialUI::postedText( $http, 'FilterTo', 10 ) );
            return $this->viewResult( null, $module->redirectTo( $base . self::filterUrl( self::filters( $posted, $classes, $sections, $states ) ) ) );
        }
        if ( $http->hasPostVariable( 'ResetButton' ) )
            return $this->viewResult( null, $module->redirectTo( $base ) );

        if ( !$locked && $http->hasPostVariable( 'AddButton' ) )
        {
            $added = 0;
            foreach ( \CjwNewsletterEditorialUI::postedIds( $http, 'AddNodeIds' ) as $addId )
            {
                $source = \eZContentObjectTreeNode::fetch( $addId );
                // only what the pool offers can be taken
                if ( $source && self::inPool( $pool, $source ) &&\CjwNewsletterEditionBuilder::addArticle( $edition, $source, \CjwNewsletterEditionArticle::ADDED_BY_EDITOR, (int)$pool->attribute( 'id' ) ) )
                    ++$added;
            }
            if ( $added )
            {
                $replaced = \CjwNewsletterApprovalFlow::withdraw( $editionId );
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, '%count articles were taken into the edition.', null, array( '%count' => $added ) )
                    . ( $replaced ? ' ' . \ezpI18n::tr( $tr, 'The edition must be approved again.' ) : '' ) );
            }
            return $this->viewResult( null, $module->redirectTo( $base . self::filterUrl( $filters ) ) );
        }
        if ( !$locked && $http->hasPostVariable( 'RemoveButton' ) && is_array( $http->postVariable( 'RemoveButton' ) ) )
        {
            $keys = array_keys( $http->postVariable( 'RemoveButton' ) );
            if ( \CjwNewsletterEditionBuilder::removeArticle( $editionId, (int)$keys[0] ) )
            {
                $replaced = \CjwNewsletterApprovalFlow::withdraw( $editionId );
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $tr, 'The article was taken out of the edition.' )
                    . ( $replaced ? ' ' . \ezpI18n::tr( $tr, 'The edition must be approved again.' ) : '' ) );
            }
            return $this->viewResult( null, $module->redirectTo( $base . self::filterUrl( $filters ) ) );
        }

        $options = self::options( $filters );
        $total = \CjwNewsletterArticlePoolFinder::count( $pool, $options );
        $options['limit'] = self::PAGE;
        $options['offset'] = $filters['offset'];
        $articles = \CjwNewsletterArticlePoolFinder::find( $pool, $options );

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'node', $node );
        $tpl->setVariable( 'edition', $edition );
        $tpl->setVariable( 'pool', $pool );
        $tpl->setVariable( 'picks', \CjwNewsletterEditionArticle::fetchByEdition( $editionId ) );
        $tpl->setVariable( 'picked_ids', \CjwNewsletterEditionBuilder::pickedObjectIds( $editionId ) );
        $tpl->setVariable( 'articles', $articles );
        $tpl->setVariable( 'total', $total );
        $tpl->setVariable( 'page_size', self::PAGE );
        $tpl->setVariable( 'filters', $filters );
        $tpl->setVariable( 'filter_url', self::filterUrl( array_merge( $filters, array( 'offset' => 0 ) ) ) );
        $tpl->setVariable( 'classes', $classes );
        $tpl->setVariable( 'sections', $sections );
        $tpl->setVariable( 'states', $states );
        $tpl->setVariable( 'tags', \CjwNewsletterEditorialUI::tagNames( $pool->tagIds() ) );
        $tpl->setVariable( 'has_tags', class_exists( 'eZTagsObject' ) );
        $tpl->setVariable( 'filter_tag_name', $filters['tag'] ? implode( '', \CjwNewsletterEditorialUI::tagNames( array( $filters['tag'] ) ) ) : '' );
        $tpl->setVariable( 'locked', $locked );
        $tpl->setVariable( 'approval_state', \CjwNewsletterApprovalFlow::isRequired( $listId ) ? \CjwNewsletterApprovalFlow::state( $editionId, (int)$edition->attribute( 'current_version' ) ) : '' );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/editorial/article_pool.tpl' );
        $Result['path'] = \CjwNewsletterEditorialUI::path( array( $list ? $list->attribute( 'url_alias' ) : 'newsletter/index' => $list ? $list->attribute( 'name' ) : '',
            $node->attribute( 'url_alias' ) => $node->attribute( 'name' ), \ezpI18n::tr( $tr, 'Pick articles' ) ) );
        return $this->viewResult( $Result, null );
    }

    /** @return bool the node is content of the pool (under its nodes, of its classes; the other filters are the editor's) */
    static function inPool( $pool, $node )
    {
        $classes = $pool->classIdentifiers();
        if ( $classes && !in_array( $node->attribute( 'class_identifier' ), $classes, true ) )
            return false;
        $path = (string)$node->attribute( 'path_string' );
        foreach ( $pool->parentNodeIds() as $parent )
            if ( strpos( $path, '/' . $parent . '/' ) !== false && (int)$node->attribute( 'node_id' ) !== $parent )
                return true;
        return false;
    }

    /**
     * @return array class, section, state, tag, from, to (Y-m-d), offset: every value checked, '' / 0 = no filter
     */
    static function filters( $input, $classes, $sections, $states )
    {
        $class = isset( $input['class'] ) && is_string( $input['class'] ) && isset( $classes[$input['class']] ) ? $input['class'] : '';
        $section = isset( $input['section'] ) && isset( $sections[(int)$input['section']] ) ? (int)$input['section'] : 0;
        $state = isset( $input['state'] ) && isset( $states[(int)$input['state']] ) ? (int)$input['state'] : 0;
        $tag = isset( $input['tag'] ) && (int)$input['tag'] > 0 ? (int)$input['tag'] : 0;
        $date = function ( $value ) {
            return is_string( $value ) && preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) && checkdate( (int)$m[2], (int)$m[3], (int)$m[1] ) ? $value : '';
        };
        return array( 'class' => $class, 'section' => $section, 'state' => $state, 'tag' => $tag,
                      'from' => $date( isset( $input['from'] ) ? $input['from'] : '' ),
                      'to' => $date( isset( $input['to'] ) ? $input['to'] : '' ),
                      'offset' => isset( $input['offset'] ) ? max( 0, (int)$input['offset'] ) : 0 );
    }

    /** @return array the finder options of the filters */
    static function options( $filters )
    {
        $options = array();
        if ( $filters['class'] !== '' )
            $options['class_identifiers'] = array( $filters['class'] );
        if ( $filters['section'] )
            $options['section_ids'] = array( $filters['section'] );
        if ( $filters['state'] )
            $options['state_ids'] = array( $filters['state'] );
        if ( $filters['tag'] )
            $options['tag_ids'] = array( $filters['tag'] );
        if ( $filters['from'] !== '' )
            $options['since'] = strtotime( $filters['from'] . ' 00:00:00' ) - 1;
        if ( $filters['to'] !== '' )
            $options['until'] = strtotime( $filters['to'] . ' 23:59:59' ) + 1;
        return $options;
    }

    /** @return string the view parameters of the filters, '' when there are none */
    static function filterUrl( $filters )
    {
        $url = '';
        foreach ( array( 'class', 'section', 'state', 'tag', 'from', 'to', 'offset' ) as $key )
            if ( isset( $filters[$key] ) && $filters[$key] !== '' && $filters[$key] !== 0 )
                $url .= '/(' . $key . ')/' . rawurlencode( (string)$filters[$key] );
        return $url;
    }
}

}
