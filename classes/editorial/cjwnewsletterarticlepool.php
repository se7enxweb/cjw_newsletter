<?php
/**
 * File containing the CjwNewsletterArticlePool class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * Where articles for editions come from (nodes, classes, sections, tags, states, age), per list or the global default.
 *
 * Table cjwnl_article_pool (cjw_newsletter 4.2.0, area N2 Editorial; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterArticlePool extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'list_contentobject_id' => array( 'name' => 'ListContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'name' => array( 'name' => 'Name', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'is_default' => array( 'name' => 'IsDefault', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'parent_node_id_array_string' => array( 'name' => 'ParentNodeIdArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'class_identifier_array_string' => array( 'name' => 'ClassIdentifierArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'section_id_array_string' => array( 'name' => 'SectionIdArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'tag_id_array_string' => array( 'name' => 'TagIdArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'state_id_array_string' => array( 'name' => 'StateIdArrayString', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'max_age_days' => array( 'name' => 'MaxAgeDays', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'max_items' => array( 'name' => 'MaxItems', 'datatype' => 'integer', 'default' => 10, 'required' => false ),
                'sort_by' => array( 'name' => 'SortBy', 'datatype' => 'string', 'default' => 'published', 'required' => false ),
                'filter_data' => array( 'name' => 'FilterData', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'creator_contentobject_id' => array( 'name' => 'CreatorContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array( 'parent_node_id_array' => 'parentNodeIds',
                                            'class_identifier_array' => 'classIdentifiers',
                                            'section_id_array' => 'sectionIds',
                                            'tag_id_array' => 'tagIds',
                                            'state_id_array' => 'stateIds',
                                            'parent_nodes' => 'parentNodes',
                                            'list_name' => 'listName',
                                            'label' => 'label',
                                            'is_stored' => 'isStored' ),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterArticlePool',
            'name' => 'cjwnl_article_pool' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterArticlePool
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterArticlePool( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterArticlePool|null
     */
    static function fetch( $id )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'id' => (int)$id ), true );
        return $object ? $object : null;
    }

    /**
     * @param array|null $conditions field => value (eZPersistentObject conditions)
     * @param int $limit 0 = all
     * @param int $offset
     * @param array|null $sorts field => asc|desc, null = newest id first
     * @return CjwNewsletterArticlePool[]
     */
    static function fetchList( $conditions = null, $limit = 0, $offset = 0, $sorts = null )
    {
        $limits = (int)$limit > 0 ? array( 'limit' => (int)$limit, 'offset' => (int)$offset ) : null;
        $list = eZPersistentObject::fetchObjectList( self::definition(), null, $conditions,
            $sorts === null ? array( 'id' => 'desc' ) : $sorts, $limits, true );
        return is_array( $list ) ? $list : array();
    }

    /**
     * @param array|null $conditions
     * @return int
     */
    static function fetchListCount( $conditions = null )
    {
        return (int)eZPersistentObject::count( self::definition(), $conditions );
    }

    /**
     * @return CjwNewsletterArticlePool[] the rows with these values (index cjwnl_article_pool_list), newest id first
     */
    static function fetchListByListContentobjectId( $listContentobjectId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'list_contentobject_id' => (int)$listContentobjectId ), $limit, $offset );
    }

    // ------------------------------------------------------------------ behaviour (4.2.0 N2 Editorial)

    /** the values of sort_by */
    static $sortFields = array( 'published', 'modified', 'priority', 'name' );

    /**
     * The pool of a list: the pool the list names (cjwnl_list.article_pool_id), else a pool made for the list, else
     * the global default (list 0, is_default 1), else one built from [ArticlePoolSettings] (not stored, id 0).
     *
     * @param int $listObjectId content object id of the newsletter list (0 = the global default)
     * @return CjwNewsletterArticlePool never null
     */
    static function forList( $listObjectId )
    {
        $listObjectId = (int)$listObjectId;
        if ( $listObjectId > 0 )
        {
            $list = CjwNewsletterList::fetchByListObjectVersion( $listObjectId, 0 );
            if ( is_object( $list ) && (int)$list->attribute( 'article_pool_id' ) > 0 )
            {
                $pool = self::fetch( $list->attribute( 'article_pool_id' ) );
                if ( $pool )
                    return $pool;
            }
            $own = self::fetchList( array( 'list_contentobject_id' => $listObjectId ), 1, 0, array( 'id' => 'asc' ) );
            if ( $own )
                return $own[0];
        }
        $default = self::fetchDefault();
        return $default ? $default : self::fromSettings();
    }

    /**
     * @return CjwNewsletterArticlePool|null the stored global default pool (list 0, is_default 1), the oldest first
     */
    static function fetchDefault()
    {
        $rows = self::fetchList( array( 'list_contentobject_id' => 0, 'is_default' => 1 ), 1, 0, array( 'id' => 'asc' ) );
        return $rows ? $rows[0] : null;
    }

    /**
     * @return CjwNewsletterArticlePool the pool of [ArticlePoolSettings], not stored (id 0)
     */
    static function fromSettings()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $get = function ( $name, $default ) use ( $ini ) {
            return $ini->hasVariable( 'ArticlePoolSettings', $name ) ? $ini->variable( 'ArticlePoolSettings', $name ) : $default;
        };
        $parents = array();
        foreach ( (array)$get( 'DefaultParentNodes', array() ) as $id )
            if ( (int)$id > 0 )
                $parents[] = (int)$id;
        if ( !$parents )
            $parents[] = (int)eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' );
        $pool = self::create( array(
            'name' => ezpI18n::tr( 'cjw_newsletter/editorial', 'Default pool (settings)' ),
            'is_default' => 1,
            'parent_node_id_array_string' => self::toArrayString( $parents ),
            'class_identifier_array_string' => self::toArrayString( (array)$get( 'DefaultClassIdentifiers', array() ) ),
            'max_age_days' => (int)$get( 'DefaultMaxAgeDays', 0 ),
            'max_items' => max( 1, (int)$get( 'DefaultMaxItems', 10 ) ),
            'sort_by' => 'published' ) );
        return $pool;
    }

    /**
     * @param string $string a ';a;b;' list
     * @param bool $integers true = cast every value to int and drop what is not > 0
     * @return array
     */
    static function fromArrayString( $string, $integers = true )
    {
        $result = array();
        foreach ( explode( ';', (string)$string ) as $value )
        {
            $value = trim( $value );
            if ( $value === '' )
                continue;
            if ( $integers )
            {
                if ( ctype_digit( $value ) && (int)$value > 0 && !in_array( (int)$value, $result, true ) )
                    $result[] = (int)$value;
            }
            else if ( preg_match( '/^[a-zA-Z0-9_]+$/', $value ) && !in_array( $value, $result, true ) )
            {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * @param array $values
     * @return string the ';a;b;' form, '' for none
     */
    static function toArrayString( $values )
    {
        $clean = array();
        foreach ( (array)$values as $value )
        {
            $value = trim( (string)$value );
            if ( $value !== '' && preg_match( '/^[a-zA-Z0-9_]+$/', $value ) && !in_array( $value, $clean, true ) )
                $clean[] = $value;
        }
        return $clean ? ';' . implode( ';', $clean ) . ';' : '';
    }

    /** @return int[] */
    function parentNodeIds()
    {
        return self::fromArrayString( $this->attribute( 'parent_node_id_array_string' ) );
    }

    /** @return string[] */
    function classIdentifiers()
    {
        return self::fromArrayString( $this->attribute( 'class_identifier_array_string' ), false );
    }

    /** @return int[] */
    function sectionIds()
    {
        return self::fromArrayString( $this->attribute( 'section_id_array_string' ) );
    }

    /** @return int[] */
    function tagIds()
    {
        return self::fromArrayString( $this->attribute( 'tag_id_array_string' ) );
    }

    /** @return int[] */
    function stateIds()
    {
        return self::fromArrayString( $this->attribute( 'state_id_array_string' ) );
    }

    /**
     * The further filters of filter_data (JSON), cast here: only the known keys with values of the right type.
     *
     * @return array exclude_class_identifiers (string[]), only_main_language (bool)
     */
    function filterArray()
    {
        $data = json_decode( (string)$this->attribute( 'filter_data' ), true );
        $data = is_array( $data ) ? $data : array();
        return array(
            'exclude_class_identifiers' => self::fromArrayString( self::toArrayString( isset( $data['exclude_class_identifiers'] ) ? (array)$data['exclude_class_identifiers'] : array() ), false ),
            'only_main_language' => !empty( $data['only_main_language'] ) );
    }

    /** @return eZContentObjectTreeNode[] the parent nodes that exist */
    function parentNodes()
    {
        $nodes = array();
        foreach ( $this->parentNodeIds() as $id )
        {
            $node = eZContentObjectTreeNode::fetch( $id );
            if ( $node instanceof eZContentObjectTreeNode )
                $nodes[] = $node;
        }
        return $nodes;
    }

    /** @return string the name of the list of the pool, '' for the global pools */
    function listName()
    {
        $id = (int)$this->attribute( 'list_contentobject_id' );
        if ( $id <= 0 )
            return '';
        $object = eZContentObject::fetch( $id );
        return $object ? (string)$object->attribute( 'name' ) : '#' . $id;
    }

    /** @return string the name, or a made-up one */
    function label()
    {
        $name = trim( (string)$this->attribute( 'name' ) );
        if ( $name !== '' )
            return $name;
        return ezpI18n::tr( 'cjw_newsletter/editorial', 'Pool %id', null, array( '%id' => (int)$this->attribute( 'id' ) ) );
    }

    /** @return bool false for the pool made from the settings */
    function isStored()
    {
        return (int)$this->attribute( 'id' ) > 0;
    }

    /**
     * Makes this pool the only global default (clears is_default on the other global pools).
     */
    function makeDefault()
    {
        foreach ( self::fetchList( array( 'list_contentobject_id' => 0, 'is_default' => 1 ) ) as $other )
        {
            if ( (int)$other->attribute( 'id' ) !== (int)$this->attribute( 'id' ) )
            {
                $other->setAttribute( 'is_default', 0 );
                $other->store();
            }
        }
        $this->setAttribute( 'list_contentobject_id', 0 );
        $this->setAttribute( 'is_default', 1 );
    }

    /**
     * Removes the pool; lists and schedules that named it fall back to the default.
     */
    function removePool()
    {
        $id = (int)$this->attribute( 'id' );
        if ( $id <= 0 )
            return;
        $db = eZDB::instance();
        $db->begin();
        foreach ( array( CjwNewsletterList::definition(), CjwNewsletterSchedule::definition() ) as $definition )
            eZPersistentObject::updateObjectList( array( 'definition' => $definition,
                                                         'update_fields' => array( 'article_pool_id' => 0 ),
                                                         'conditions' => array( 'article_pool_id' => $id ) ) );
        $this->remove();
        $db->commit();
    }
}
