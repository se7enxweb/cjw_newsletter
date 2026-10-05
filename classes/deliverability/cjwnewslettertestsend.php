<?php
/**
 * File containing the CjwNewsletterTestSend class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * Test sends to a named group of addresses (cjwnl_test_group, cjw_newsletter.ini [TestSendSettings]).
 *
 * A group belongs to one list, or to every list (list_contentobject_id 0). The test send form of an edition offers
 * the groups of its list; the addresses of the chosen group are added to the addresses typed into the form. Test
 * mails are marked as tests: the subject starts with [TestSendSettings] SubjectPrefix and the header X-Cjwnl-Test
 * is set. They go through the preview transport, never through the mail gate, and are never counted in the
 * statistics. With OneMailPerAddress=enabled (the default) each address gets a mail of its own, so that the testers
 * do not see each other's addresses.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterTestSend
{
    /** the POST name of the group the test send form chose */
    const POST_GROUP = 'CjwNewsletterTestGroupId';

    /**
     * @return int [TestSendSettings] MaxTestGroupSize
     */
    static function maxSize()
    {
        return max( 1, (int)self::setting( 'MaxTestGroupSize', 25 ) );
    }

    /**
     * @return string the start of the subject of a test mail ('' = none)
     */
    static function subjectPrefix()
    {
        return trim( (string)self::setting( 'SubjectPrefix', '[Test]' ) );
    }

    /**
     * @return bool each address of a test send gets a mail of its own
     */
    static function oneMailPerAddress()
    {
        return self::setting( 'OneMailPerAddress', 'enabled' ) !== 'disabled';
    }

    /**
     * The addresses in a text: one per line, or separated by ";" or ",".
     *
     * @param string $text
     * @return array valid (lower case, without duplicates), invalid (the rest as typed)
     */
    static function parseAddresses( $text )
    {
        $valid = array();
        $invalid = array();
        foreach ( preg_split( '/[\r\n;,]+/', (string)$text ) as $part )
        {
            $part = trim( $part );
            if ( $part === '' )
                continue;
            if ( eZMail::validate( $part ) )
                $valid[strtolower( $part )] = true;
            else
                $invalid[] = $part;
        }
        return array( 'valid' => array_keys( $valid ), 'invalid' => $invalid );
    }

    /**
     * @param CjwNewsletterTestGroup $group
     * @return string[] the valid addresses of the group (at most maxSize())
     */
    static function groupAddresses( $group )
    {
        if ( !is_object( $group ) )
            return array();
        $parsed = self::parseAddresses( (string)$group->attribute( 'email_list' ) );
        return array_slice( $parsed['valid'], 0, self::maxSize() );
    }

    /**
     * @param int $listContentObjectId
     * @return CjwNewsletterTestGroup[] the groups of the list and those of every list, by name
     */
    static function groupsForList( $listContentObjectId )
    {
        $ids = array_unique( array( 0, (int)$listContentObjectId ) );
        $groups = CjwNewsletterTestGroup::fetchList( array( 'list_contentobject_id' => array( $ids ) ), 0, 0, array( 'name' => 'asc' ) );
        return $groups;
    }

    /**
     * @param eZContentObjectVersion|eZContentObjectTreeNode $versionOrNode an edition
     * @return int the content object id of its list, 0 when unknown
     */
    static function listIdOf( $versionOrNode )
    {
        if ( !is_object( $versionOrNode ) )
            return 0;
        $map = $versionOrNode->attribute( 'data_map' );
        if ( !isset( $map['newsletter_edition'] ) )
            return 0;
        $edition = $map['newsletter_edition']->attribute( 'content' );
        if ( !is_object( $edition ) )
            return 0;
        $list = $edition->attribute( 'list_attribute_content' );
        return is_object( $list ) ? (int)$list->attribute( 'contentobject_id' ) : 0;
    }

    /**
     * The addresses of a test send: those typed into the form, and those of the chosen group (extension point
     * testSendRecipients).
     *
     * @param string $emails the typed addresses (";" separated)
     * @param eZHTTPTool $http
     * @param eZContentObjectVersion $objectVersion the edition
     * @return string the addresses, ";" separated
     */
    static function recipients( $emails, $http, $objectVersion )
    {
        $groupId = ( is_object( $http ) && $http->hasPostVariable( self::POST_GROUP ) ) ? (int)$http->postVariable( self::POST_GROUP ) : 0;
        if ( $groupId <= 0 )
            return (string)$emails;
        $group = CjwNewsletterTestGroup::fetch( $groupId );
        $listId = self::listIdOf( $objectVersion );
        if ( !$group || !in_array( (int)$group->attribute( 'list_contentobject_id' ), array( 0, $listId ), true ) )
            return (string)$emails;
        $typed = self::parseAddresses( $emails );
        $all = array_values( array_unique( array_merge( $typed['valid'], self::groupAddresses( $group ) ) ) );
        return implode( ';', array_slice( $all, 0, self::maxSize() + count( $typed['valid'] ) ) );
    }

    /**
     * @param string $subject
     * @return string the subject of a test mail
     */
    static function markSubject( $subject )
    {
        $prefix = self::subjectPrefix();
        if ( $prefix === '' || strpos( (string)$subject, $prefix ) === 0 )
            return (string)$subject;
        return $prefix . ' ' . $subject;
    }

    /**
     * Checks and stores the form of a test group.
     *
     * @param CjwNewsletterTestGroup $group
     * @param array $input name, email_list, list_contentobject_id
     * @return array field => error text (empty: stored)
     */
    static function storeGroup( $group, array $input )
    {
        $errors = array();
        $name = trim( (string)( isset( $input['name'] ) ? $input['name'] : '' ) );
        if ( $name === '' )
            $errors['name'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'Give the group a name.' );
        $parsed = self::parseAddresses( isset( $input['email_list'] ) ? $input['email_list'] : '' );
        if ( $parsed['invalid'] )
            $errors['email_list'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'These are no valid addresses: %list', null, array( '%list' => implode( ', ', $parsed['invalid'] ) ) );
        else if ( !$parsed['valid'] )
            $errors['email_list'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'Enter at least one address.' );
        else if ( count( $parsed['valid'] ) > self::maxSize() )
            $errors['email_list'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'A test group has at most %max addresses.', null, array( '%max' => self::maxSize() ) );
        $listId = isset( $input['list_contentobject_id'] ) ? (int)$input['list_contentobject_id'] : 0;
        if ( $listId > 0 && !CjwNewsletterSubscription::isNewsletterListObject( $listId ) )
            $errors['list_contentobject_id'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'Choose a newsletter list.' );
        $group->setAttribute( 'name', mb_substr( $name, 0, 255 ) );
        $group->setAttribute( 'email_list', implode( "\n", $parsed['valid'] ? $parsed['valid'] : $parsed['invalid'] ) );
        $group->setAttribute( 'list_contentobject_id', max( 0, $listId ) );
        if ( $errors )
            return $errors;
        $now = time();
        if ( !(int)$group->attribute( 'id' ) )
        {
            $group->setAttribute( 'created', $now );
            $group->setAttribute( 'creator_contentobject_id', (int)eZUser::currentUserID() );
        }
        $group->setAttribute( 'modified', $now );
        $group->store();
        return array();
    }

    /**
     * @return array[] the newsletter lists for a choice: hash( id, name )
     */
    static function listChoices()
    {
        $out = array();
        $db = eZDB::instance();
        foreach ( (array)$db->arrayQuery( 'SELECT DISTINCT l.contentobject_id AS contentobject_id FROM cjwnl_list l, ezcontentobject o WHERE o.id = l.contentobject_id' ) as $row )
        {
            $object = eZContentObject::fetch( (int)$row['contentobject_id'] );
            if ( $object instanceof eZContentObject && $object->attribute( 'status' ) == eZContentObject::STATUS_PUBLISHED )
                $out[] = array( 'id' => (int)$row['contentobject_id'], 'name' => (string)$object->attribute( 'name' ) );
        }
        usort( $out, function ( $a, $b ) { return strcasecmp( $a['name'], $b['name'] ); } );
        return $out;
    }

    protected static function setting( $name, $default )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'TestSendSettings', $name ) ? $ini->variable( 'TestSendSettings', $name ) : $default;
    }
}

?>
