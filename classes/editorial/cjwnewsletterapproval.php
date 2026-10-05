<?php
/**
 * File containing the CjwNewsletterApproval class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * The approval state of an edition version for a list that requires one (collaboration inbox).
 *
 * Table cjwnl_approval (cjw_newsletter 4.2.0, area N2 Editorial; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterApproval extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'edition_contentobject_id' => array( 'name' => 'EditionContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'edition_contentobject_version' => array( 'name' => 'EditionContentobjectVersion', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'list_contentobject_id' => array( 'name' => 'ListContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'collaboration_item_id' => array( 'name' => 'CollaborationItemId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'status' => array( 'name' => 'Status', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'requested_by' => array( 'name' => 'RequestedBy', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'requested' => array( 'name' => 'Requested', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'decided_by' => array( 'name' => 'DecidedBy', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'decided' => array( 'name' => 'Decided', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'comment' => array( 'name' => 'Comment', 'datatype' => 'string', 'default' => '', 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array( 'status_name' => 'statusName',
                                            'status_identifier' => 'statusIdentifier',
                                            'edition_name' => 'editionName',
                                            'edition_node_id' => 'editionNodeId',
                                            'list_name' => 'listName',
                                            'requester_name' => 'requesterName',
                                            'decider_name' => 'deciderName' ),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterApproval',
            'name' => 'cjwnl_approval' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterApproval
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterApproval( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterApproval|null
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
     * @return CjwNewsletterApproval[]
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
     * @return CjwNewsletterApproval[] the rows with these values (index cjwnl_approval_edition), newest id first
     */
    static function fetchListByEditionContentobjectIdAndEditionContentobjectVersion( $editionContentobjectId, $editionContentobjectVersion, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'edition_contentobject_id' => (int)$editionContentobjectId, 'edition_contentobject_version' => (int)$editionContentobjectVersion ), $limit, $offset );
    }

    /**
     * @return CjwNewsletterApproval[] the rows with these values (index cjwnl_approval_collaboration), newest id first
     */
    static function fetchListByCollaborationItemId( $collaborationItemId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'collaboration_item_id' => (int)$collaborationItemId ), $limit, $offset );
    }

    // ------------------------------------------------------------------ behaviour (4.2.0 N2 Editorial)

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_WITHDRAWN = 3;

    /** @return string pending, approved, rejected or withdrawn */
    function statusIdentifier()
    {
        $map = array( self::STATUS_PENDING => 'pending', self::STATUS_APPROVED => 'approved', self::STATUS_REJECTED => 'rejected', self::STATUS_WITHDRAWN => 'withdrawn' );
        $status = (int)$this->attribute( 'status' );
        return isset( $map[$status] ) ? $map[$status] : 'pending';
    }

    /** @return string */
    function statusName()
    {
        switch ( (int)$this->attribute( 'status' ) )
        {
            case self::STATUS_APPROVED: return ezpI18n::tr( 'cjw_newsletter/editorial', 'approved' );
            case self::STATUS_REJECTED: return ezpI18n::tr( 'cjw_newsletter/editorial', 'rejected' );
            case self::STATUS_WITHDRAWN: return ezpI18n::tr( 'cjw_newsletter/editorial', 'replaced' );
            default: return ezpI18n::tr( 'cjw_newsletter/editorial', 'waiting for approval' );
        }
    }

    /** @return string */
    function editionName()
    {
        $title = class_exists( 'expCollaborationInbox' ) ? expCollaborationInbox::approvalTitle( (int)$this->attribute( 'edition_contentobject_id' ), (int)$this->attribute( 'edition_contentobject_version' ) ) : '';
        if ( $title === '' )
        {
            $object = CjwNewsletterUtils::contentObject( (int)$this->attribute( 'edition_contentobject_id' ) );
            $title = $object ? (string)$object->attribute( 'name' ) : '#' . (int)$this->attribute( 'edition_contentobject_id' );
        }
        return $title;
    }

    /** @return int */
    function editionNodeId()
    {
        $object = CjwNewsletterUtils::contentObject( (int)$this->attribute( 'edition_contentobject_id' ) );
        return $object ? (int)$object->attribute( 'main_node_id' ) : 0;
    }

    /** @return string */
    function listName()
    {
        $object = CjwNewsletterUtils::contentObject( (int)$this->attribute( 'list_contentobject_id' ) );
        return $object ? (string)$object->attribute( 'name' ) : '';
    }

    /** @return string */
    function requesterName()
    {
        $object = CjwNewsletterUtils::contentObject( (int)$this->attribute( 'requested_by' ) );
        return $object ? (string)$object->attribute( 'name' ) : '';
    }

    /** @return string */
    function deciderName()
    {
        $object = (int)$this->attribute( 'decided_by' ) ? CjwNewsletterUtils::contentObject( (int)$this->attribute( 'decided_by' ) ) : null;
        return $object ? (string)$object->attribute( 'name' ) : '';
    }
}
