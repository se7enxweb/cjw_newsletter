<?php
/**
 * File containing the CjwNewsletterEditorialUI class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * Small helpers of the admin views of the area Editorial: the lists and editions to choose from, the path, the
 * reading of posted values.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterEditorialUI
{
    /**
     * @return array[] the newsletter lists: id (content object id), node_id, name, approval_required, article_pool_id
     */
    static function lists()
    {
        $result = array();
        $nodes = eZContentObjectTreeNode::subTreeByNodeID( array( 'ClassFilterType' => 'include',
            'ClassFilterArray' => array( 'cjw_newsletter_list' ), 'Limitation' => array(), 'IgnoreVisibility' => true,
            'SortBy' => array( 'name', true ) ), self::rootNodeId() );
        foreach ( (array)$nodes as $node )
        {
            $id = (int)$node->attribute( 'contentobject_id' );
            $list = CjwNewsletterList::fetchByListObjectVersion( $id, 0 );
            $result[$id] = array( 'id' => $id, 'node_id' => (int)$node->attribute( 'node_id' ), 'name' => (string)$node->attribute( 'name' ),
                                  'approval_required' => is_object( $list ) ? (int)$list->attribute( 'approval_required' ) : 0,
                                  'article_pool_id' => is_object( $list ) ? (int)$list->attribute( 'article_pool_id' ) : 0 );
        }
        return $result;
    }

    /**
     * @return array[] the editions of every list: id, node_id, name, list_id, list_name, sent (bool)
     */
    static function editions()
    {
        $result = array();
        foreach ( self::lists() as $list )
        {
            $nodes = eZContentObjectTreeNode::subTreeByNodeID( array( 'Depth' => 1, 'DepthOperator' => 'eq',
                'ClassFilterType' => 'include', 'ClassFilterArray' => array( 'cjw_newsletter_edition' ),
                'Limitation' => array(), 'IgnoreVisibility' => true, 'SortBy' => array( 'published', false ), 'Limit' => 100 ), $list['node_id'] );
            foreach ( (array)$nodes as $node )
            {
                $id = (int)$node->attribute( 'contentobject_id' );
                $result[$id] = array( 'id' => $id, 'node_id' => (int)$node->attribute( 'node_id' ), 'name' => (string)$node->attribute( 'name' ),
                                      'list_id' => $list['id'], 'list_name' => $list['name'],
                                      'sent' => count( (array)CjwNewsletterEditionSend::fetchByEditionContentObjectId( $id ) ) > 0 );
            }
        }
        return $result;
    }

    /** @return int [NewsletterSettings] RootFolderNodeId, else the content root */
    static function rootNodeId()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $id = $ini->hasVariable( 'NewsletterSettings', 'RootFolderNodeId' ) ? (int)$ini->variable( 'NewsletterSettings', 'RootFolderNodeId' ) : 0;
        return $id > 0 && eZContentObjectTreeNode::fetch( $id ) ? $id : 1;
    }

    /**
     * @return array[] the content classes to choose from: identifier => name
     */
    static function classes()
    {
        $result = array();
        foreach ( eZContentClass::fetchAllClasses( true, false, false ) as $class )
            $result[$class->attribute( 'identifier' )] = $class->attribute( 'name' );
        asort( $result );
        return $result;
    }

    /** @return array id => name of the sections */
    static function sections()
    {
        $result = array();
        foreach ( eZSection::fetchList() as $section )
            $result[(int)$section->attribute( 'id' )] = (string)$section->attribute( 'name' );
        asort( $result );
        return $result;
    }

    /** @return array id => "group / state" of the object states */
    static function states()
    {
        $result = array();
        foreach ( eZContentObjectStateGroup::fetchByOffset( 100, 0 ) as $group )
            foreach ( $group->states() as $state )
                $result[(int)$state->attribute( 'id' )] = $group->attribute( 'current_translation' )->attribute( 'name' ) . ' / ' . $state->attribute( 'current_translation' )->attribute( 'name' );
        return $result;
    }

    /** @return array id => keyword of the tags of the given ids (eztags), the ids that exist only */
    static function tagNames( $ids )
    {
        $result = array();
        if ( !class_exists( 'eZTagsObject' ) )
            return $result;
        foreach ( (array)$ids as $id )
        {
            $tag = eZTagsObject::fetch( (int)$id );
            if ( $tag )
                $result[(int)$id] = (string)$tag->attribute( 'keyword' );
        }
        return $result;
    }

    /**
     * @return int[] the integers > 0 of a posted value (an array, or a text with ids separated by commas or spaces)
     */
    static function postedIds( $http, $name )
    {
        if ( !$http->hasPostVariable( $name ) )
            return array();
        $value = $http->postVariable( $name );
        $parts = is_array( $value ) ? $value : preg_split( '/[\s,;]+/', (string)$value );
        $ids = array();
        foreach ( $parts as $part )
            if ( is_scalar( $part ) && ctype_digit( trim( (string)$part ) ) && (int)$part > 0 && !in_array( (int)$part, $ids, true ) )
                $ids[] = (int)$part;
        return $ids;
    }

    /** @return string the posted text, trimmed and cut */
    static function postedText( $http, $name, $max = 255 )
    {
        return $http->hasPostVariable( $name ) && is_scalar( $http->postVariable( $name ) ) ? mb_substr( trim( (string)$http->postVariable( $name ) ), 0, $max ) : '';
    }

    /** @return int */
    static function postedInt( $http, $name, $default = 0 )
    {
        return $http->hasPostVariable( $name ) && is_scalar( $http->postVariable( $name ) ) ? (int)$http->postVariable( $name ) : (int)$default;
    }

    /**
     * @return int|false the seconds after midnight of "HH:MM", false when it is no time
     */
    static function parseTime( $text )
    {
        if ( !preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', trim( (string)$text ), $m ) )
            return false;
        return (int)$m[1] * 3600 + (int)$m[2] * 60;
    }

    /** @return string the Unix time as text in the schedule's time zone, for the forms */
    static function formatTime( $time, $timezone )
    {
        if ( (int)$time <= 0 )
            return '';
        $date = new DateTime( '@' . (int)$time );
        $date->setTimezone( new DateTimeZone( CjwNewsletterSchedule::isTimezone( $timezone ) ? $timezone : date_default_timezone_get() ) );
        return $date->format( 'Y-m-d H:i T' );
    }

    /** @return array[] the path of a view: Newsletter, then the given steps (url => text) */
    static function path( $steps )
    {
        $path = array( array( 'url' => 'newsletter/index', 'text' => ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ) );
        foreach ( $steps as $url => $text )
            $path[] = array( 'url' => is_string( $url ) ? $url : false, 'text' => $text );
        return $path;
    }

    /** @return bool the current user has the function of the newsletter module */
    static function can( $function )
    {
        $access = eZUser::currentUser()->hasAccessTo( 'newsletter', $function );
        return $access['accessWord'] !== 'no';
    }
}
