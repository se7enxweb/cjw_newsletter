<?php
/**
 * File containing the CjwNewsletterInterests class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The interests subscribers pick (cjwnl_interest, cjwnl_user_interest).
 *
 * A list offers interests when its interest_source is set: "topics" offers the list's own topics and the topics of
 * every list (list_contentobject_id 0) with source "topic"; "eztags" offers the interests with source "eztags",
 * each standing for an eztags tag. A topic may also name a tag (eztags_id), so that the "articles for your
 * interests" block can find articles for it.
 *
 * @package cjw_newsletter
 */
class CjwNewsletterInterests
{
    /** @var array user id => keys */
    protected static $keyCache = array();

    /** @return string[] the interest sources a list can have: '' none, topics, eztags */
    static function sources()
    {
        return array( '', 'topics', 'eztags' );
    }

    /**
     * The interests a list offers, ordered as the page shows them.
     *
     * @param CjwNewsletterList|int $list a list, or the content object id of one
     * @param bool $activeOnly
     * @return CjwNewsletterInterest[]
     */
    static function forList( $list, $activeOnly = true )
    {
        if ( !is_object( $list ) )
            $list = CjwNewsletterList::fetchByListObjectVersion( (int)$list, 0 );
        if ( !is_object( $list ) )
            return array();
        $source = (string)$list->attribute( 'interest_source' );
        if ( $source === '' )
            return array();
        $rowSource = $source === 'eztags' ? 'eztags' : 'topic';
        $listId = (int)$list->attribute( 'contentobject_id' );
        $out = array();
        foreach ( array( $listId, 0 ) as $id )
        {
            $conditions = array( 'list_contentobject_id' => $id, 'source' => $rowSource );
            if ( $activeOnly )
                $conditions['is_active'] = 1;
            foreach ( CjwNewsletterInterest::fetchList( $conditions, 0, 0, array( 'priority' => 'asc', 'name' => 'asc' ) ) as $interest )
                $out[(int)$interest->attribute( 'id' )] = $interest;
        }
        return array_values( $out );
    }

    /** @return CjwNewsletterInterest[] the interests a subscriber picked (active ones) */
    static function forUser( $newsletterUserId )
    {
        $out = array();
        foreach ( CjwNewsletterUserInterest::fetchList( array( 'newsletter_user_id' => (int)$newsletterUserId ) ) as $row )
        {
            $interest = CjwNewsletterInterest::fetch( (int)$row->attribute( 'interest_id' ) );
            if ( $interest && (int)$interest->attribute( 'is_active' ) === 1 )
                $out[(int)$interest->attribute( 'id' )] = $interest;
        }
        return array_values( $out );
    }

    /** @return int[] the ids of the interests a subscriber picked */
    static function idsForUser( $newsletterUserId )
    {
        $ids = array();
        foreach ( self::forUser( $newsletterUserId ) as $interest )
            $ids[] = (int)$interest->attribute( 'id' );
        return $ids;
    }

    /**
     * What a condition on interests compares with: the identifiers of the subscriber's interests and their tag ids.
     *
     * @return string[]
     */
    static function keysForUser( $newsletterUserId )
    {
        $newsletterUserId = (int)$newsletterUserId;
        if ( isset( self::$keyCache[$newsletterUserId] ) )
            return self::$keyCache[$newsletterUserId];
        $keys = array();
        foreach ( self::forUser( $newsletterUserId ) as $interest )
        {
            $keys[] = (string)$interest->attribute( 'identifier' );
            if ( (int)$interest->attribute( 'eztags_id' ) > 0 )
                $keys[] = (string)(int)$interest->attribute( 'eztags_id' );
        }
        if ( count( self::$keyCache ) > 500 )
            self::$keyCache = array();
        return self::$keyCache[$newsletterUserId] = array_values( array_unique( $keys ) );
    }

    /** @return int[] the eztags ids of a subscriber's interests */
    static function tagIdsForUser( $newsletterUserId )
    {
        $ids = array();
        foreach ( self::forUser( $newsletterUserId ) as $interest )
            if ( (int)$interest->attribute( 'eztags_id' ) > 0 )
                $ids[] = (int)$interest->attribute( 'eztags_id' );
        return array_values( array_unique( $ids ) );
    }

    /**
     * Sets a subscriber's interests among those offered: the offered ones not wanted are removed, the wanted ones
     * added. Interests that are not offered are left as they are.
     *
     * @param int $newsletterUserId
     * @param int[] $wantedIds
     * @param int[] $offeredIds
     * @return int how many changed
     */
    static function setForUser( $newsletterUserId, $wantedIds, $offeredIds )
    {
        $newsletterUserId = (int)$newsletterUserId;
        if ( $newsletterUserId <= 0 )
            return 0;
        $offeredIds = array_map( 'intval', (array)$offeredIds );
        $wantedIds = array_values( array_intersect( array_map( 'intval', (array)$wantedIds ), $offeredIds ) );
        $changed = 0;
        foreach ( $offeredIds as $id )
        {
            $row = CjwNewsletterUserInterest::fetchByNewsletterUserIdAndInterestId( $newsletterUserId, $id );
            $want = in_array( $id, $wantedIds, true );
            if ( $want && !$row )
            {
                $row = CjwNewsletterUserInterest::create( array( 'newsletter_user_id' => $newsletterUserId, 'interest_id' => $id, 'created' => time() ) );
                $row->store();
                $changed++;
            }
            else if ( !$want && $row )
            {
                $row->remove();
                $changed++;
            }
        }
        unset( self::$keyCache[$newsletterUserId] );
        return $changed;
    }

    /** Removes every interest of a subscriber (the subscriber was removed or erased). @return int rows removed */
    static function removeForUser( $newsletterUserId )
    {
        $count = 0;
        foreach ( CjwNewsletterUserInterest::fetchList( array( 'newsletter_user_id' => (int)$newsletterUserId ) ) as $row )
        {
            $row->remove();
            $count++;
        }
        unset( self::$keyCache[(int)$newsletterUserId] );
        return $count;
    }

    /** Forgets the cached keys (tests). */
    static function clearCache()
    {
        self::$keyCache = array();
    }

    /**
     * Stores an interest from the admin form; returns error strings.
     *
     * @param CjwNewsletterInterest $interest
     * @param array $data hash( list_contentobject_id, identifier, name, source, eztags_id, priority, is_active )
     * @return string[]
     */
    static function storeFromForm( $interest, $data )
    {
        $errors = array();
        $context = 'cjw_newsletter/rendering';
        $identifier = strtolower( trim( isset( $data['identifier'] ) ? (string)$data['identifier'] : '' ) );
        $name = trim( isset( $data['name'] ) ? (string)$data['name'] : '' );
        $source = isset( $data['source'] ) && $data['source'] === 'eztags' ? 'eztags' : 'topic';
        $listId = isset( $data['list_contentobject_id'] ) ? max( 0, (int)$data['list_contentobject_id'] ) : 0;
        $tagId = isset( $data['eztags_id'] ) ? max( 0, (int)$data['eztags_id'] ) : 0;
        if ( $identifier === '' && $name !== '' )
            $identifier = trim( preg_replace( '/[^a-z0-9]+/', '_', strtolower( $name ) ), '_' );
        if ( !preg_match( '/^[a-z0-9_]{1,100}$/', $identifier ) )
            $errors[] = ezpI18n::tr( $context, 'The identifier may only contain the letters a-z, digits and underscores.' );
        if ( $name === '' || mb_strlen( $name, 'UTF-8' ) > 255 )
            $errors[] = ezpI18n::tr( $context, 'Enter a name of at most 255 characters.' );
        if ( $source === 'eztags' && $tagId === 0 )
            $errors[] = ezpI18n::tr( $context, 'An interest from eztags needs the id of its tag.' );
        if ( $listId > 0 && !CjwNewsletterList::fetchByListObjectVersion( $listId, 0 ) )
            $errors[] = ezpI18n::tr( $context, 'The newsletter list does not exist.' );
        $existing = $identifier !== '' ? CjwNewsletterInterest::fetchByListContentobjectIdAndIdentifier( $listId, $identifier ) : null;
        if ( $existing && (int)$existing->attribute( 'id' ) !== (int)$interest->attribute( 'id' ) )
            $errors[] = ezpI18n::tr( $context, 'This identifier is already used by another interest of the list.' );
        if ( $errors )
            return $errors;
        $interest->setAttribute( 'list_contentobject_id', $listId );
        $interest->setAttribute( 'identifier', $identifier );
        $interest->setAttribute( 'name', $name );
        $interest->setAttribute( 'source', $source );
        $interest->setAttribute( 'eztags_id', $tagId );
        $interest->setAttribute( 'priority', isset( $data['priority'] ) ? (int)$data['priority'] : 0 );
        $interest->setAttribute( 'is_active', !empty( $data['is_active'] ) ? 1 : 0 );
        if ( !(int)$interest->attribute( 'created' ) )
            $interest->setAttribute( 'created', time() );
        $interest->setAttribute( 'modified', time() );
        $interest->store();
        return array();
    }

    /** Removes an interest and every subscriber's pick of it. */
    static function removeInterest( $interest )
    {
        $db = eZDB::instance();
        $db->begin();
        foreach ( CjwNewsletterUserInterest::fetchListByInterestId( (int)$interest->attribute( 'id' ) ) as $row )
            $row->remove();
        $interest->remove();
        $db->commit();
        self::$keyCache = array();
    }

    /** @return int how many subscribers picked an interest */
    static function userCount( $interestId )
    {
        return (int)CjwNewsletterUserInterest::fetchListCount( array( 'interest_id' => (int)$interestId ) );
    }
}

?>
