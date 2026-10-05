<?php
/**
 * File containing the CjwNewsletterEditionBuilder class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * Makes and fills editions: finds the latest unsent edition of a list, copies a template edition under its list, and
 * takes articles from a pool into an edition.
 *
 * An article taken from a pool becomes a newsletter article (class cjw_newsletter_article) under the edition, with
 * the title of the content, its intro and a link to it, so that every skin shows it as it shows the articles an
 * editor writes. The pick itself is a row of cjwnl_edition_article (the content object id of the source), which the
 * article statistics read. The newsletter article has the remote id cjwnl-pick-<edition>-<source>, which ties it to
 * the row.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterEditionBuilder
{
    /**
     * The newest edition under the list that was never sent: no send of any version, not in process.
     *
     * @param int $listObjectId
     * @return eZContentObject|null
     */
    static function latestUnsentEdition( $listObjectId )
    {
        $list = eZContentObject::fetch( (int)$listObjectId );
        if ( !$list || !$list->attribute( 'main_node_id' ) )
            return null;
        $nodes = eZContentObjectTreeNode::subTreeByNodeID( array( 'Depth' => 1, 'DepthOperator' => 'eq',
            'ClassFilterType' => 'include', 'ClassFilterArray' => array( 'cjw_newsletter_edition' ),
            'SortBy' => array( array( 'published', false ), array( 'node_id', false ) ),
            'Limitation' => array(), 'IgnoreVisibility' => true, 'Limit' => 50 ), (int)$list->attribute( 'main_node_id' ) );
        foreach ( (array)$nodes as $node )
        {
            $id = (int)$node->attribute( 'contentobject_id' );
            if ( count( (array)CjwNewsletterEditionSend::fetchByEditionContentObjectId( $id ) ) > 0 )
                continue;
            $object = $node->attribute( 'object' );
            if ( $object && self::editionContent( $object ) )
                return $object;
        }
        return null;
    }

    /**
     * @return CjwNewsletterEdition|null the datatype content of the edition's current version
     */
    static function editionContent( $object )
    {
        if ( !$object instanceof eZContentObject )
            return null;
        $map = $object->attribute( 'data_map' );
        if ( !isset( $map['newsletter_edition'] ) )
            return null;
        $content = $map['newsletter_edition']->attribute( 'content' );
        return $content instanceof CjwNewsletterEdition ? $content : null;
    }

    /**
     * Copies the template edition (its current version and its newsletter articles) under the list and publishes
     * the copy.
     *
     * @param eZContentObject $template a cjw_newsletter_edition
     * @param int $listObjectId the list the copy goes to
     * @param string $title the title of the copy ('' = the template's)
     * @return eZContentObject|false the published copy
     */
    static function copyEdition( $template, $listObjectId, $title = '' )
    {
        if ( !$template instanceof eZContentObject || $template->attribute( 'class_identifier' ) !== 'cjw_newsletter_edition' )
            return false;
        $list = eZContentObject::fetch( (int)$listObjectId );
        $parentNodeId = $list ? (int)$list->attribute( 'main_node_id' ) : 0;
        if ( !$parentNodeId )
            return false;

        $copy = self::copyObject( $template, $parentNodeId, array( 'title' => $title, 'short_title' => $title ) );
        if ( !$copy )
            return false;

        // the newsletter articles of the template come along (the content an editor wrote for every issue)
        $templateNode = $template->attribute( 'main_node' );
        if ( $templateNode )
        {
            $children = eZContentObjectTreeNode::subTreeByNodeID( array( 'Depth' => 1, 'DepthOperator' => 'eq',
                'ClassFilterType' => 'include', 'ClassFilterArray' => array( 'cjw_newsletter_article' ),
                'SortBy' => array( array( 'priority', true ), array( 'node_id', true ) ), 'Limitation' => array(), 'IgnoreVisibility' => true ),
                (int)$templateNode->attribute( 'node_id' ) );
            $priority = 0;
            foreach ( (array)$children as $child )
            {
                $childCopy = self::copyObject( $child->attribute( 'object' ), (int)$copy->attribute( 'main_node_id' ), array() );
                if ( $childCopy && $childCopy->attribute( 'main_node' ) )
                {
                    $childNode = $childCopy->attribute( 'main_node' );
                    $childNode->setAttribute( 'priority', $priority++ );
                    $childNode->store();
                }
            }
        }
        // the edition row of the datatype is made for the copy's version
        $copy = eZContentObject::fetch( $copy->attribute( 'id' ) );
        if ( !self::editionContent( $copy ) )
            self::ensureEditionRow( $copy );
        return eZContentObject::fetch( $copy->attribute( 'id' ) );
    }

    /**
     * Copies the current version of $object under the node, changes the given ezstring attributes, publishes.
     *
     * @return eZContentObject|false
     */
    protected static function copyObject( $object, $parentNodeId, $strings )
    {
        if ( !$object instanceof eZContentObject )
            return false;
        $db = eZDB::instance();
        $db->begin();
        $newObject = $object->copy( false );
        $newObject->setAttribute( 'section_id', 0 );
        $newObject->store();
        $version = (int)$newObject->attribute( 'current_version' );
        $versionObject = $newObject->attribute( 'current' );
        foreach ( (array)$versionObject->attribute( 'node_assignments' ) as $assignment )
            $assignment->purge();
        $assignment = eZNodeAssignment::create( array( 'contentobject_id' => $newObject->attribute( 'id' ),
            'contentobject_version' => $version, 'parent_node' => (int)$parentNodeId, 'is_main' => 1 ) );
        $assignment->store();
        $map = $versionObject->attribute( 'data_map' );
        foreach ( (array)$strings as $identifier => $value )
        {
            if ( (string)$value !== '' && isset( $map[$identifier] ) && $map[$identifier]->attribute( 'data_type_string' ) === 'ezstring' )
            {
                $map[$identifier]->setAttribute( 'data_text', mb_substr( (string)$value, 0, 255 ) );
                $map[$identifier]->store();
            }
        }
        $db->commit();
        $result = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $newObject->attribute( 'id' ), 'version' => $version ) );
        $newObject = eZContentObject::fetch( $newObject->attribute( 'id' ) );
        if ( !$newObject || (int)$newObject->attribute( 'status' ) !== eZContentObject::STATUS_PUBLISHED || !$newObject->attribute( 'main_node_id' ) )
        {
            eZDebug::writeError( 'The copy of object ' . $object->attribute( 'id' ) . ' was not published (a workflow may hold it)', __METHOD__ );
            return false;
        }
        $newNode = $newObject->attribute( 'main_node' );
        $parentNode = eZContentObjectTreeNode::fetch( (int)$parentNodeId );
        if ( $newNode && $parentNode )
            eZContentObjectTreeNode::updateNodeVisibility( $newNode, $parentNode );
        return $newObject;
    }

    /** Writes the cjwnl_edition row for the current version of an edition that lacks it. */
    protected static function ensureEditionRow( $object )
    {
        $map = $object->attribute( 'data_map' );
        if ( !isset( $map['newsletter_edition'] ) )
            return;
        $attribute = $map['newsletter_edition'];
        $row = new CjwNewsletterEdition( array(
            'contentobject_attribute_id' => $attribute->attribute( 'id' ),
            'contentobject_attribute_version' => $attribute->attribute( 'version' ),
            'contentobject_id' => $object->attribute( 'id' ),
            'contentclass_id' => $object->attribute( 'contentclass_id' ) ) );
        $row->store();
    }

    /**
     * @return int[] the content object ids of the articles the edition carries from pools
     */
    static function pickedObjectIds( $editionObjectId )
    {
        $ids = array();
        foreach ( CjwNewsletterEditionArticle::fetchByEdition( $editionObjectId ) as $row )
            $ids[] = (int)$row->attribute( 'contentobject_id' );
        return $ids;
    }

    /**
     * Takes a content node into the edition: a newsletter article under the edition and the cjwnl_edition_article row.
     * Taking the same content twice does nothing.
     *
     * @param eZContentObject $edition
     * @param eZContentObjectTreeNode $source
     * @param int $addedBy CjwNewsletterEditionArticle::ADDED_BY_*
     * @param int $poolId
     * @return CjwNewsletterEditionArticle|false the row, false when the article could not be made
     */
    static function addArticle( $edition, $source, $addedBy = 0, $poolId = 0 )
    {
        if ( !$edition instanceof eZContentObject || !$source instanceof eZContentObjectTreeNode || !$edition->attribute( 'main_node_id' ) )
            return false;
        $editionId = (int)$edition->attribute( 'id' );
        $sourceId = (int)$source->attribute( 'contentobject_id' );
        if ( $sourceId === $editionId )
            return false;
        $existing = CjwNewsletterEditionArticle::fetchByEditionContentobjectIdAndContentobjectId( $editionId, $sourceId );
        if ( $existing )
            return $existing;

        $class = eZContentClass::fetchByIdentifier( 'cjw_newsletter_article' );
        if ( !$class )
            return false;
        $position = count( CjwNewsletterEditionArticle::fetchByEdition( $editionId ) ) + 1;

        $db = eZDB::instance();
        $db->begin();
        $object = $class->instantiate( (int)eZUser::currentUserID(), (int)$edition->attribute( 'section_id' ), false );
        $object->setAttribute( 'remote_id', CjwNewsletterEditionArticle::copyRemoteId( $editionId, $sourceId ) );
        $object->store();
        $assignment = eZNodeAssignment::create( array( 'contentobject_id' => $object->attribute( 'id' ),
            'contentobject_version' => 1, 'parent_node' => (int)$edition->attribute( 'main_node_id' ), 'is_main' => 1 ) );
        $assignment->store();
        $map = $object->attribute( 'current' )->attribute( 'data_map' );
        if ( isset( $map['title'] ) )
        {
            $map['title']->setAttribute( 'data_text', mb_substr( (string)$source->attribute( 'name' ), 0, 255 ) );
            $map['title']->store();
        }
        if ( isset( $map['short_description'] ) )
        {
            $map['short_description']->setAttribute( 'data_text', self::introXml( $source ) );
            $map['short_description']->store();
        }
        $db->commit();
        eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $object->attribute( 'id' ), 'version' => 1 ) );
        $object = eZContentObject::fetch( $object->attribute( 'id' ) );
        if ( !$object || !$object->attribute( 'main_node_id' ) )
            return false;
        $node = $object->attribute( 'main_node' );
        // the picks come after the articles the edition had, in the order they were taken
        $node->setAttribute( 'priority', 1000 + $position );
        $node->store();

        $row = CjwNewsletterEditionArticle::create( array(
            'edition_contentobject_id' => $editionId,
            'contentobject_id' => $sourceId,
            'article_pool_id' => (int)$poolId,
            'position' => $position,
            'added_by' => (int)$addedBy,
            'creator_contentobject_id' => (int)eZUser::currentUserID(),
            'created' => time() ) );
        $row->store();
        return $row;
    }

    /**
     * Takes an article out of the edition: the row and the newsletter article made for it.
     *
     * @return bool true when there was something to remove
     */
    static function removeArticle( $editionObjectId, $sourceObjectId )
    {
        $row = CjwNewsletterEditionArticle::fetchByEditionContentobjectIdAndContentobjectId( $editionObjectId, $sourceObjectId );
        $copy = eZContentObject::fetchByRemoteID( CjwNewsletterEditionArticle::copyRemoteId( $editionObjectId, $sourceObjectId ) );
        if ( $copy )
            eZContentObjectOperations::remove( (int)$copy->attribute( 'id' ), false );
        if ( $row )
            $row->remove();
        return (bool)( $row || $copy );
    }

    /**
     * The intro of the article as ezxmltext of the newsletter article: the first attribute of
     * [ArticlePoolSettings] IntroAttributes[] with content (ezxmltext taken as it is, other text as a paragraph),
     * then a paragraph with a link to the content.
     *
     * @return string the XML
     */
    static function introXml( $source )
    {
        $dom = new DOMDocument( '1.0', 'utf-8' );
        $section = null;
        $map = $source->attribute( 'data_map' );
        foreach ( self::introAttributes() as $identifier )
        {
            if ( !isset( $map[$identifier] ) || !$map[$identifier]->attribute( 'has_content' ) )
                continue;
            $attribute = $map[$identifier];
            if ( $attribute->attribute( 'data_type_string' ) === 'ezxmltext' )
            {
                $loaded = new DOMDocument( '1.0', 'utf-8' );
                if ( @$loaded->loadXML( (string)$attribute->attribute( 'data_text' ) ) && $loaded->documentElement && $loaded->documentElement->nodeName === 'section' )
                {
                    $dom = $loaded;
                    $section = $dom->documentElement;
                    break;
                }
            }
            else if ( in_array( $attribute->attribute( 'data_type_string' ), array( 'eztext', 'ezstring' ), true ) )
            {
                $section = self::newSection( $dom );
                $paragraph = $dom->createElement( 'paragraph' );
                $paragraph->appendChild( $dom->createTextNode( mb_substr( trim( (string)$attribute->attribute( 'data_text' ) ), 0, 2000 ) ) );
                $section->appendChild( $paragraph );
                break;
            }
        }
        if ( !$section )
            $section = self::newSection( $dom );
        $paragraph = $dom->createElement( 'paragraph' );
        $link = $dom->createElement( 'link' );
        $link->setAttribute( 'node_id', (int)$source->attribute( 'node_id' ) );
        $link->appendChild( $dom->createTextNode( ezpI18n::tr( 'cjw_newsletter/editorial', 'Read more' ) ) );
        $paragraph->appendChild( $link );
        $section->appendChild( $paragraph );
        return $dom->saveXML();
    }

    /** @return DOMElement a new section root with the namespaces of ezxmltext */
    protected static function newSection( $dom )
    {
        $section = $dom->createElement( 'section' );
        $section->setAttribute( 'xmlns:image', 'http://ez.no/namespaces/ezpublish3/image/' );
        $section->setAttribute( 'xmlns:xhtml', 'http://ez.no/namespaces/ezpublish3/xhtml/' );
        $section->setAttribute( 'xmlns:custom', 'http://ez.no/namespaces/ezpublish3/custom/' );
        $dom->appendChild( $section );
        return $section;
    }

    /** @return string[] [ArticlePoolSettings] IntroAttributes[] */
    static function introAttributes()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $list = $ini->hasVariable( 'ArticlePoolSettings', 'IntroAttributes' ) ? (array)$ini->variable( 'ArticlePoolSettings', 'IntroAttributes' ) : array();
        $result = array();
        foreach ( $list as $identifier )
            if ( preg_match( '/^[a-z0-9_]+$/', (string)$identifier ) )
                $result[] = (string)$identifier;
        return $result ? $result : array( 'short_description', 'teaser_intro', 'full_intro', 'intro', 'description', 'summary' );
    }
}
