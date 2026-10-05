<?php
/**
 * File containing the CjwNewsletterEditorialFetch class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * The template fetch functions of the area Editorial (modules/newsletter/function_definition.php):
 *
 *   fetch( 'newsletter', 'approval_state', hash( 'edition_contentobject_id', $id, 'version', $v ) )
 *       hash: required (the list wants an approval), state (none, pending, approved, rejected), may_send, approval;
 *       false when the current user may not read the edition
 *   fetch( 'newsletter', 'edition_articles', hash( 'edition_contentobject_id', $id ) )
 *       the CjwNewsletterEditionArticle rows of the edition, in their order, only those whose article the current
 *       user may read (none when he may not read the edition)
 *   fetch( 'newsletter', 'list_article_pool', hash( 'list_contentobject_id', $id ) )   the pool a list uses
 *   fetch( 'newsletter', 'article_pool_list', hash() )                                  every stored pool
 *   fetch( 'newsletter', 'schedule_list', hash( 'list_contentobject_id', $id ) )        the recurring sends (of a list)
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterEditorialFetch
{
    /** @return eZContentObject|null the object when the current user may read it */
    protected static function readable( $objectId )
    {
        $object = eZContentObject::fetch( (int)$objectId );
        return ( $object instanceof eZContentObject && $object->canRead() ) ? $object : null;
    }

    static function fetchApprovalState( $editionContentobjectId, $version = 0 )
    {
        $object = self::readable( $editionContentobjectId );
        if ( !$object )
            return array( 'result' => false );
        $version = (int)$version > 0 ? (int)$version : (int)$object->attribute( 'current_version' );
        $listId = CjwNewsletterApprovalFlow::listOfEdition( $object->attribute( 'id' ) );
        $required = CjwNewsletterApprovalFlow::isRequired( $listId );
        $approval = CjwNewsletterApprovalFlow::current( $object->attribute( 'id' ), $version );
        return array( 'result' => array( 'required' => $required,
                                         'state' => $approval ? $approval->statusIdentifier() : 'none',
                                         'may_send' => !$required || ( $approval && (int)$approval->attribute( 'status' ) === CjwNewsletterApproval::STATUS_APPROVED ),
                                         'approval' => $approval,
                                         'version' => $version ) );
    }

    static function fetchEditionArticles( $editionContentobjectId )
    {
        if ( !self::readable( $editionContentobjectId ) )
            return array( 'result' => array() );
        $rows = array();
        foreach ( CjwNewsletterEditionArticle::fetchByEdition( (int)$editionContentobjectId ) as $row )
            if ( self::readable( $row->attribute( 'contentobject_id' ) ) )
                $rows[] = $row;
        return array( 'result' => $rows );
    }

    static function fetchListArticlePool( $listContentobjectId )
    {
        return array( 'result' => CjwNewsletterArticlePool::forList( (int)$listContentobjectId ) );
    }

    static function fetchArticlePoolList()
    {
        return array( 'result' => CjwNewsletterArticlePool::fetchList( null, 0, 0, array( 'name' => 'asc' ) ) );
    }

    static function fetchScheduleList( $listContentobjectId = 0 )
    {
        return array( 'result' => CjwNewsletterSchedule::fetchVisible( (int)$listContentobjectId ) );
    }
}
