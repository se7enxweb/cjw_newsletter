<?php
/**
 * File containing the CjwNewsletterExtensionPoints class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The places where the feature areas of cjw_newsletter hook into the shared code (the queue runner, the send form,
 * the list attribute, the dashboard) without editing it.
 *
 * A handler is a class named in cjw_newsletter.ini [ExtensionPointSettings] Handlers[]; each area registers its
 * own in its own block of the file. A handler implements any of the static methods below; a method it does not
 * have is skipped, a class that does not exist is skipped. Handlers run in the order of the setting.
 *
 *   queueCreateBefore( $cli )                                   start of the queue-create run (before scheduled sends are woken)
 *   sendQueueCreated( $sendObject, $cli )                       the items of a send were made (status MAILQUEUE_CREATED)
 *   queueProcessBefore( $cli )                                  start of the queue-process run
 *   sendProcessAllowed( $sendObject )                           false = the send is left for a later run (a pause, a wait)
 *   itemBeforeSend( $message, $sendItem, $sendObject, $user )   $message (ArrayObject): subject, bodies (html, text,
 *                                                               placeholders not yet replaced), values (placeholder =>
 *                                                               raw value), defer (true = leave the item and stop the
 *                                                               send for this run), abort (a reason = close the item)
 *   itemSent( $sendItem, $sendObject, $result )                 after the mail of an item went out or failed ($result of
 *                                                               CjwNewsletterMail::sendEmail())
 *   testSendRecipients( $emails, $http, $objectVersion )        filter: the test addresses (string), returns them
 *   sendFormValidate( $http, $objectVersion )                   returns error strings (empty = fine) before a send is made
 *   sendFormStored( $sendObject, $http, $objectVersion )        a send was made from the send form
 *   listAttributeInput( $list, $http, $prefix, $postfix, $contentObjectAttribute )
 *                                                               the list attribute's POST: set the area's columns on $list
 *                                                               (CjwNewsletterList), return error strings
 *   dashboardSummary( $summary )                                returns an array; it is in summary.areas.<handler class>
 *   userRemoved( $newsletterUserId )                            a subscriber is being removed (CjwNewsletterUser::remove()):
 *                                                               remove the area's own rows of him (erasure goes through
 *                                                               the category handlers' erased() instead)
 *
 * Template parts are listed in [ExtensionPointSettings] as design: template names, each area appending its own:
 * DashboardBlocks[] (dashboard, gets summary), SendFormParts[] (inside the send form, gets node, object_version),
 * TestFormParts[] (inside the test send form), ListEditParts[] (list attribute edit, gets attribute, prefix, postfix),
 * ListViewParts[] (list attribute view, gets attribute).
 *
 * @package cjw_newsletter
 */
class CjwNewsletterExtensionPoints
{
    /** the columns of cjwnl_list added in 4.2.0: kept from the stored version when the attribute is edited */
    static $listColumns42 = array( 'skin_name_array_string', 'main_language', 'language_array_string', 'interest_source',
                                   'approval_required', 'article_pool_id', 'tracking_mode', 'sms_enabled', 'sms_sender' );

    /**
     * @return string[] the handler classes that exist
     */
    static function handlers()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $classes = $ini->hasVariable( 'ExtensionPointSettings', 'Handlers' ) ? (array)$ini->variable( 'ExtensionPointSettings', 'Handlers' ) : array();
        $result = array();
        foreach ( $classes as $class )
        {
            $class = trim( (string)$class );
            if ( $class !== '' && !in_array( $class, $result ) && class_exists( $class ) )
                $result[] = $class;
        }
        return $result;
    }

    /**
     * Calls $point on every handler that has it.
     *
     * @param string $point
     * @param array $args
     * @return array handler class => what it returned
     */
    static function call( $point, $args = array() )
    {
        $results = array();
        foreach ( self::handlers() as $class )
            if ( method_exists( $class, $point ) )
                $results[$class] = call_user_func_array( array( $class, $point ), $args );
        return $results;
    }

    /**
     * @return bool false when a handler returned false
     */
    static function allows( $point, $args = array() )
    {
        foreach ( self::call( $point, $args ) as $result )
            if ( $result === false )
                return false;
        return true;
    }

    /**
     * @return string[] every error string the handlers returned
     */
    static function errors( $point, $args = array() )
    {
        $errors = array();
        foreach ( self::call( $point, $args ) as $result )
            foreach ( (array)$result as $error )
                if ( is_string( $error ) && $error !== '' )
                    $errors[] = $error;
        return $errors;
    }

    /**
     * Passes $value through every handler that has $point (each gets the value first, then $args).
     */
    static function filter( $point, $value, $args = array() )
    {
        foreach ( self::handlers() as $class )
            if ( method_exists( $class, $point ) )
                $value = call_user_func_array( array( $class, $point ), array_merge( array( $value ), $args ) );
        return $value;
    }

    /**
     * @param string $list DashboardBlocks, SendFormParts, TestFormParts, ListEditParts or ListViewParts
     * @return string[] the template names of the list (only design:...tpl names)
     */
    static function templates( $list )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $names = $ini->hasVariable( 'ExtensionPointSettings', $list ) ? (array)$ini->variable( 'ExtensionPointSettings', $list ) : array();
        $result = array();
        foreach ( $names as $name )
            if ( preg_match( '#^design:[a-z0-9_/]+\.tpl$#', (string)$name ) && !in_array( $name, $result ) )
                $result[] = $name;
        return $result;
    }

    /**
     * The list attribute is rebuilt from the form on every edit: the 4.2.0 columns are taken over from the stored
     * version first, then the handlers set what their form parts posted.
     *
     * @param CjwNewsletterList $list the object the datatype built from the form
     * @return string[] error strings of the handlers
     */
    static function listAttributeInput( $list, $http, $prefix, $postfix, $contentObjectAttribute )
    {
        $stored = CjwNewsletterList::fetch( $contentObjectAttribute->attribute( 'id' ), $contentObjectAttribute->attribute( 'version' ) );
        if ( is_object( $stored ) )
            foreach ( self::$listColumns42 as $column )
                $list->setAttribute( $column, $stored->attribute( $column ) );
        return self::errors( 'listAttributeInput', array( $list, $http, $prefix, $postfix, $contentObjectAttribute ) );
    }
}
