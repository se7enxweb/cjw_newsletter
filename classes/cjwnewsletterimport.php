<?php
/**
 * File containing the CjwNewsletterImport class
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @filesource
 */
/**
 * Handle Import sets of Subscriptions
 *
 * @version //autogentag//
 * @package cjw_newsletter
 */
class CjwNewsletterImport extends eZPersistentObject
{

    /**
     * constructor
     *
     * @param array $row
     * @return void
     */
    function CjwNewsletterImport( $row )
    {
        $this->eZPersistentObject( $row );
    }

    /**
     * data fields...
     *
     * @return array
     */
    static function definition()
    {
        return array( 'fields' => array( 'id' => array( 'name'     => 'Id',
                                                        'datatype' => 'integer',
                                                        'default'  => 0,
                                                        'required' => true ),
                                         'type' => array( 'name'     => 'Type',
                                                          'datatype' => 'string',
                                                          'default'  => '',
                                                          'required' => true ),
                                         'list_contentobject_id' => array(
                                                        'name' => 'ListContentobjectId',
                                                        'datatype' => 'integer',
                                                        'default'  => 0,
                                                        'required' => true ),
                                         'created' => array( 'name'     => 'Created',
                                                             'datatype' => 'integer',
                                                             'default'  => 0,
                                                             'required' => true ),
                                         'creator_contentobject_id' => array(
                                                        'name'     => 'CreatorContentObjectId',
                                                        'datatype' => 'integer',
                                                        'default'  => 0,
                                                        'required' => true ),
                                         'note' => array( 'name'     => 'Note',
                                                          'datatype' => 'string',
                                                          'default'  => '',
                                                          'required' => false ),
                                         'data_xml' => array( 'name'     => 'DataXml',
                                                          'datatype' => 'string',
                                                          'default'  => '',
                                                          'required' => false ),
                                         'data_text' => array( 'name'     => 'DataText',
                                                          'datatype' => 'string',
                                                          'default'  => '',
                                                          'required' => false ),
                                         'remote_id' => array( 'name'     => 'RemoteId',
                                                          'datatype' => 'string',
                                                          'default'  => '',
                                                          'required' => true ),
                                         'imported' => array( 'name'     => 'Imported',
                                                             'datatype' => 'integer',
                                                             'default'  => 0,
                                                             'required' => true ),
                                         'imported_user_count' => array( 'name'  => 'ImportedUserCount',
                                                             'datatype' => 'integer',
                                                             'default'  => 0,
                                                             'required' => true ),
                                         'imported_subscription_count' => array( 'name'   => 'ImportedSubscriptionCount',
                                                             'datatype' => 'integer',
                                                             'default'  => 0,
                                                             'required' => true ),

                                        ),
                      'keys' => array( 'id' ),
                      'increment_key' => 'id',
                      'function_attributes' => array( 'list_contentobject' => 'getListContentObject',
                                                      'creator' => 'getCreatorUserObject',
                                                      'is_imported' => 'isImported',
                                                      'imported_user_count_live' => 'getImportedUserCountLive',
                                                      'imported_subscription_count_live' => 'getImportedSubscriptionCountLive',
                                                      'imported_user_count_live_confirmed' => 'getImportedUserCountLiveConfirmed',
                                                      'imported_subscription_count_live_approved' => 'getImportedSubscriptionCountLiveApproved'
                                                      ),
                      'class_name' => 'CjwNewsletterImport',
                      'name' => 'cjwnl_import' );
    }

     /**
     * create a new import set
     *
     * @param string $type import type e.g. csv
     * @param string $note personal note for import
     * @param string $dataText here you can store what you want
     * @param string $remoteId unique number to identify importset
     * @return object / false if not create
     */
    public static function create( $listContentObjectId, $type = 'default', $note = '', $dataText = '', $remoteId = false )
    {
        if( $remoteId === false )
        {
            $remoteId = $type . ':'. CjwNewsletterUtils::generateUniqueMd5Hash( $listContentObjectId );
        }

        $row = array( 'list_contentobject_id'    => $listContentObjectId,
                      'type'                     => $type,
                      'created'                  => time(),
                      'creator_contentobject_id' => eZUser::currentUserID(),
                      'note'                     => $note,
                      'data_text'                => $dataText,
                      'remote_id'                => $remoteId );


        $newObject = new CjwNewsletterImport( $row );

        return $newObject;
    }

    /**
     * fetch CjwNewsletterImport object by id
     * return false if not found
     *
     * @param integer $id
     * @param boolean $asObject
     * @return CjwNewsletterMailboxItem or false
     */
    public static function fetch( $id, $asObject = true )
    {
         return eZPersistentObject::fetchObject(
                                                    CjwNewsletterImport::definition(),
                                                    null,
                                                    array( 'id' => (int) $id ),
                                                    $asObject
                                                );
    }

    /**
     * fetch CjwNewsletterImport object by remoteId
     * return false if not found
     *
     * @param string $remoteId
     * @param boolean $asObject
     * @return CjwNewsletterMailboxItem or false
     */
    public static function fetchByRemoteId( $remoteId, $asObject = true )
    {
         return eZPersistentObject::fetchObject(
                                                    CjwNewsletterImport::definition(),
                                                    null,
                                                    array( 'remote_id' => $remoteId ),
                                                    $asObject
                                                );
    }

    /**
     * fetch all import items
     *
     * @param integer $limit
     * @param integer $offset
     * @param boolean $asObject
     * @return unknown_type
     */
    static public function fetchAllImportItems( $limit = 50, $offset = 0, $sortByArray = null, $asObject = true )
    {
        $limitArr = null;
        if ( (int) $limit != 0 )
        {
            $limitArr = array( 'limit' => $limit, 'offset' => $offset );
        }

        if( !is_array( $sortByArray ))
        {
            $sortByArray = array( 'id' => true );
        }
        $condArray = array( );
        $objectList = eZPersistentObject::fetchObjectList(
                                                    self::definition(),
                                                    null,
                                                    $condArray,
                                                    $sortByArray,
                                                    $limitArr,
                                                    $asObject,
                                                    null,
                                                    null,
                                                    null,
                                                    null );

        return $objectList;
    }

    /**
     * count all import items
     *
     * @return integer
     */
    static public function fetchAllImportItemsCount( )
    {
        $count = eZPersistentObject::count(
                             self::definition(),
                             array( ),
                             'id' );
        return $count;
    }

    /**
     * Get Creator user object
     *
     * @return unknown_type
     */
    function getCreatorUserObject()
    {
        $user = eZContentObject::fetch( $this->attribute( 'creator_contentobject_id' ) );
        return $user;
    }

    /**
     * Get List Content Object
     *
     * @return unknown_type
     */
    function getListContentObject()
    {
        if ( $this->attribute( 'list_contentobject_id' ) != 0 )
        {
            $object = eZContentObject::fetch( $this->attribute( 'list_contentobject_id' ) );
            return $object;
        }
        else
        {
            return false;
        }

    }

    /**
     * count all subcription objects which has the import_id of this object
     * @return int
     */
    function getImportedSubscriptionCountLive()
    {
        return CjwNewsletterSubscription::fetchSubscriptionListByImportIdCount( $this->attribute('id') );
    }

    /**
     * count all subcription objects which has the import_id of this object  and which has status Approved
     * @return int
     */
    function getImportedSubscriptionCountLiveApproved()
    {
        return CjwNewsletterSubscription::fetchSubscriptionListByImportIdAndStatusCount( $this->attribute('id'), CjwNewsletterSubscription::STATUS_APPROVED );
    }

    /**
     * count all nl user objects which has the import_id of this object
     * @return unknown_type
     */
    function getImportedUserCountLive()
    {
        return CjwNewsletterUser::fetchUserListByImportIdCount( $this->attribute('id') );
    }

    /**
     * count all nl user objects which has the import_id of this object and which has status Confirmed
     * @return unknown_type
     */
    function getImportedUserCountLiveConfirmed()
    {
        return CjwNewsletterUser::fetchUserListByImportIdAndStatusCount( $this->attribute('id'), CjwNewsletterUser::STATUS_CONFIRMED );
    }

    /**
     * If the importset is imported
     *
     * @return boolean
     */
    function isImported()
    {
        if ( $this->attribute( 'imported' ) > 0 )
            return true;
        else
            return false;
    }

    /**
     * when the import was done e.g. all csv data are imported
     * this function should be called to set the imported timestamp
     * and the count for imported users + subscriptions
     *
     * @return boolean
     */
    function setImported()
    {
        $this->setAttribute( 'imported_user_count', $this->getImportedUserCountLive() );
        $this->setAttribute( 'imported_subscription_count', $this->getImportedSubscriptionCountLive() );
        $this->setAttribute( 'imported', time() );
        $this->store();
    }

    /**
     * fetch all active subscriptions with current import id
     * and set status to remove by admin
     * @return array subscriptions => nl_user_id
     */
    public function removeActiveSubscriptionsByAdmin()
    {

        $count = CjwNewsletterSubscription::fetchSubscriptionListByImportIdAndStatusCount( $this->attribute('id'), CjwNewsletterSubscription::STATUS_APPROVED );

        CjwNewsletterLog::writeNotice(
                                            "CjwNewsletterImport::removeActiveSubscriptionsByAdmin",
                                            'import',
                                            'start',
                                             array( 'import_id' => $this->attribute( 'id' ),
                                                    'active_subscriptions' => $count,
                                                    'current_user'  => eZUser::currentUserID() ) );

        // count active subscriptions for import id
        $removeSubscriptionArray = array();
        $limit = 100;
        $loops = ceil($count / $limit);

        // get active subscriptions partly
        for ( $i = 0; $i < $loops; $i++ )
        {
            // get active subscriptions
            $subscriptionObjectList = CjwNewsletterSubscription::fetchSubscriptionListByImportIdAndStatus( $this->attribute('id'), CjwNewsletterSubscription::STATUS_APPROVED, $limit );
            foreach ( $subscriptionObjectList as $subscription )
            {
                $subscription->removeByAdmin();
                $removeSubscriptionArray[ $subscription->attribute('id') ] = $subscription->attribute('newsletter_user_id');
            }
        }

        $count = CjwNewsletterSubscription::fetchSubscriptionListByImportIdAndStatusCount( $this->attribute('id'), CjwNewsletterSubscription::STATUS_APPROVED );

        CjwNewsletterLog::writeNotice(
                                            "CjwNewsletterImport::removeActiveSubscriptionsByAdmin",
                                            'import',
                                            'end',
                                             array( 'import_id' => $this->attribute( 'id' ),
                                                    'subscriptions_remove_count' => count( $removeSubscriptionArray ),
                                                    'current_user'  => eZUser::currentUserID() ) );

        return $removeSubscriptionArray;
    }


    /**
     * The import of the rows of a CSV file into a newsletter list: one newsletter user (new or updated) and one
     * approved subscription per valid row. Used by the import view's "Import all" (as a background run) and by the
     * command ext:cjw_newsletter:import.
     *
     * @param CjwNewsletterImport $importObject
     * @param array $csvDataArray the rows from CjwNewsletterCsvParser
     * @param int $listContentObjectId
     * @param array $selectedOutputFormatArray
     * @param boolean $csvImportHasPrio [NewsletterCsvImportSettings] CsvImportHasPrio
     * @param object|false $out a sink with output() (eZCLI, CjwNewsletterJobOutput)
     * @return array row id => result of the row
     */
    public static function runCsvImport( $importObject, $csvDataArray, $listContentObjectId, $selectedOutputFormatArray, $csvImportHasPrio, $out = false )
    {
        $importId = (int)$importObject->attribute( 'id' );
        $listSubscriptionArray = array();
        \CjwNewsletterLog::writeNotice( 'subscription_list_csvimport', 'import', 'start',
                                        array( 'import_id' => $importId, 'csv_array_count' => count( $csvDataArray ), 'current_user' => \eZUser::currentUserID() ) );
        foreach ( $csvDataArray as $rowId => $item )
        {
            $remote_id = false;
            if( isset( $item[ 'remote_id' ] ) )
                $remote_id = trim( $item[ 'remote_id' ] );

            $email = '';
            if( isset( $item[ 'email' ] ) )
                $email = trim( $item[ 'email' ] );

            $salutation = 0;
            if( isset( $item[ 'salutation' ] ) )
                $salutation = (int) $item[ 'salutation' ];

            $firstName = '';
            if( isset( $item[ 'first_name' ] ) )
                $firstName = $item[ 'first_name' ];

            $lastName = '';
            if( isset( $item[ 'last_name' ] ) )
                $lastName = $item[ 'last_name' ];

            $customDataText1 = '';
            if( isset( $item[ 'custom_data_text_1' ] ) )
                $customDataText1 = $item[ 'custom_data_text_1' ];

            $customDataText2 = '';
            if( isset( $item[ 'custom_data_text_2' ] ) )
                $customDataText2 = $item[ 'custom_data_text_2' ];

            $customDataText3 = '';
            if( isset( $item[ 'custom_data_text_3' ] ) )
                $customDataText3 = $item[ 'custom_data_text_3' ];

            $customDataText4 = '';
            if( isset( $item[ 'custom_data_text_4' ] ) )
                $customDataText4 = $item[ 'custom_data_text_4' ];

            $eZUserId = false;
            $newsletterUserId = 0;

            $emailOk = \ezcMailTools::validateEmailAddress( $email );
            $subscriptionObject = null;
            $createNewUser = 0; // 0 - no, 1 - yes, 2 - updated
            $createNewSubscription = 0;

            // store status from existing objects and new stati after import/update
            $existingUserStatus = -1;
            $existingSubscriptionStatus = -1;
            $newUserStatus = -1;
            $newSubscriptionStatus = -1;

            $userIsBlacklistedOrRemoved = false;

            if ( !$emailOk )
            {
                $emailOk = 0;
            }
            else
            {
                $emailOk = 1;

                if ( $csvImportHasPrio == false )
                {
                    // 1. check if an nl user for email already exists
                    //    no   -> create new one with status """confirmed"""
                    //         -> subscribe to nl list with status """approved"""
                    //    yes  -> subscribe to nl list with status """approved"""
                    $existingNewsletterUserObject = \CjwNewsletterUser::fetchByEmail( $email );
                }
                else
                {
                    // user wurde bereits importiert?
                    // zuerst nach remote_id suchen
                    $existingNewsletterUserObject = \CjwNewsletterUser::fetchByRemoteId( $remote_id );
                    if ( is_object( $existingNewsletterUserObject ) )
                    {
                        // sicherstellen, dass wir keine duplicate emails haben
                        if ( $email != $existingNewsletterUserObject->attribute( 'email') )
                        {
                            $tmpUserObject = \CjwNewsletterUser::fetchByEmail( $email );
                            if ( is_object( $tmpUserObject ) )
                            {
                                // houston, we've got a problem - $tmpUserObject löschen???
// ToDo
                                \CjwNewsletterLog::writeError(
                                                        'CSV Import: duplicate E-Mail Adress',
                                                        'user',
                                                        'email',
                                                         array(
                                                                'email_cur' => $existingNewsletterUserObject->attribute( 'email'),
                                                                'email_imp' => $email,
                                                                'remote_id' => $remote_id )
                                                          );
                            }
                        }
                    }
                    // user hat sich selbst per subscription angelegt?
                    // sonst nach email suchen
                    if ( !is_object( $existingNewsletterUserObject ) )
                    {
                        $existingNewsletterUserObject = \CjwNewsletterUser::fetchByEmail( $email );
                    }
                }

                // update existing
                if ( is_object( $existingNewsletterUserObject ) )
                {
                    $userObject = $existingNewsletterUserObject;
                    $updateUserDataIfExists = true;
                    $existingUserStatus = $userObject->attribute( 'status' );

                    if ( $userObject->isOnBlacklist() ||
                         $userObject->isRemovedSelf() )
                    {
                        $userIsBlacklistedOrRemoved = true;
                    }

                    // only user which are not blacklisted or not self removed
                    // can get a new subscription
                    if ( $userIsBlacklistedOrRemoved === true )
                    {
                        // 0
                        $createNewUser = 0;
                    }
                    elseif ( $updateUserDataIfExists === true )
                    {
                        // updated
                        $createNewUser = 2;

                        if ( $csvImportHasPrio && $userObject->attribute( 'email' ) != $email )
                            $userObject->setAttribute( 'email', $email );

                        if ( $salutation != 0 )
                            $userObject->setAttribute( 'salutation', $salutation );
                        if ( $firstName != '' )
                            $userObject->setAttribute( 'first_name', $firstName );
                        if ( $lastName != '' )
                            $userObject->setAttribute( 'last_name', $lastName );
                        if ( $customDataText1 != '' )
                            $userObject->setAttribute( 'custom_data_text_1', $customDataText1 );
                        if ( $customDataText2 != '' )
                            $userObject->setAttribute( 'custom_data_text_2', $customDataText2 );
                        if ( $customDataText3 != '' )
                            $userObject->setAttribute( 'custom_data_text_3', $customDataText3 );
                        if ( $customDataText4 != '' )
                            $userObject->setAttribute( 'custom_data_text_4', $customDataText4 );

                        $userObject->setAttribute( 'status', \CjwNewsletterUser::STATUS_CONFIRMED );
                        $userObject->setAttribute( 'import_id', $importId );
                        
                        // set new remote_id
                        if ( $remote_id !== false )
                            $userObject->setAttribute( 'remote_id', $remote_id );
                        else
                            $userObject->setAttribute( 'remote_id', 'cjwnl:csvimport:'. \CjwNewsletterUtils::generateUniqueMd5Hash( $userObject->attribute( 'id' ) ) );
                        
                        $userObject->store();

                        $newUserStatus = $userObject->attribute('status');
                    }
                }
                // create new object
                else
                {
                    $createNewUser = 1;
                    $userObject = \CjwNewsletterUser::createUpdateNewsletterUser( $email,
                                                             $salutation,
                                                             $firstName,
                                                             $lastName,
                                                             $eZUserId,
                                                             \CjwNewsletterUser::STATUS_CONFIRMED,
                                                            'default',
                                                             $customDataText1,
                                                             $customDataText2,
                                                             $customDataText3,
                                                             $customDataText4 );
                    $userObject->setAttribute( 'import_id', $importId );
                    
                    // set new remote_id
                    if ( $remote_id !== false )
                        $userObject->setAttribute( 'remote_id', $remote_id );
                    else
                        $userObject->setAttribute( 'remote_id', 'cjwnl:csvimport:'. \CjwNewsletterUtils::generateUniqueMd5Hash( $userObject->attribute( 'id' ) ) );
                    
                    $userObject->store();
                    $newUserStatus = $userObject->attribute('status');
                }
                $newsletterUserId = $userObject->attribute( 'id' );
                $outputFormatArray = $selectedOutputFormatArray;

                // only user which are not blacklisted can get a new subscription
                if ( $newsletterUserId != null &&
                     $userIsBlacklistedOrRemoved === false )
                {
                    $existingSubscription = \CjwNewsletterSubscription::fetchByListIdAndNewsletterUserId( $listContentObjectId, $newsletterUserId );
                    // if subscription exists do nothing
                    if ( is_object( $existingSubscription )  )
                    {
                        $existingSubscriptionStatus = $existingSubscription->attribute('status');

                        // if user has removed a subscription by himself
                        // don't activate it again
                        if ( $existingSubscription->isRemovedSelf() ||
                             $existingSubscription->isBlacklisted() )
                        {
                            // no
                            $createNewSubscription = 0;
                        }
                        else
                        {
                            // 2 - update
                            $createNewSubscription = 2;
                            $subscriptionObject = $existingSubscription;

                            $subscriptionObject->setAttribute( 'status', \CjwNewsletterSubscription::STATUS_APPROVED );
                            $subscriptionObject->setAttribute( 'import_id', $importId );
                            // set new remote_id
                            $subscriptionObject->setAttribute( 'remote_id', 'cjwnl:csvimport:'. \CjwNewsletterUtils::generateUniqueMd5Hash( $newsletterUserId . $importId ) );
                            $subscriptionObject->store();
                        }
                    }
                    // create new subscription
                    else
                    {
                        $createNewSubscription = 1;
                        $newListSubscription =  \CjwNewsletterSubscription::create(
                                                 $listContentObjectId,
                                                 $newsletterUserId,
                                                 $outputFormatArray,
                                                 \CjwNewsletterSubscription::STATUS_APPROVED );
                        $newListSubscription->setAttribute( 'import_id', $importId );
                        // set new remote_id
                        $newListSubscription->setAttribute( 'remote_id', 'cjwnl:csvimport:'. \CjwNewsletterUtils::generateUniqueMd5Hash( $newsletterUserId . $importId ) );
                        $newListSubscription->store();
                        $subscriptionObject = $newListSubscription;
                        $newSubscriptionStatus = $subscriptionObject->attribute( 'status' );
                    }
               }
            }
            $listSubscriptionArray[ $rowId ] = array( 'subscription_object'  => $subscriptionObject,
                                                      'email_ok'             => $emailOk,
                                                      'user_created'         => $createNewUser,
                                                      'newsletter_user_id'   => $newsletterUserId,
                                                      'subscription_created' => $createNewSubscription,
                                                      'user_status_old'      => $existingUserStatus,
                                                      'user_status_new'      => $newUserStatus,
                                                      'subscription_status_old' => $existingSubscriptionStatus,
                                                      'subscription_status_new'  => $newSubscriptionStatus
                                                      //'user_object' => $userObject
            );
            if ( $out )
            {
                $words = array( 0 => 'kept', 1 => 'created', 2 => 'updated' );
                $out->output( 'Row ' . $rowId . ': ' . ( $emailOk ? $email : 'invalid address "' . $email . '"' ) .
                              ', user ' . $words[$createNewUser] . ', subscription ' . $words[$createNewSubscription] );
            }

        }

        // imported timestamp + set count for imported users + subscriptions
        $importObject->setImported();
        \CjwNewsletterLog::writeNotice( 'subscription_list_csvimport', 'import', 'end',
                                        array( 'import_id' => $importId, 'current_user' => \eZUser::currentUserID() ) );
        self::storeResult( $importId, $listSubscriptionArray );
        return $listSubscriptionArray;
    }

    /**
     * @return string the file that holds the result of an import
     */
    public static function resultFilePath( $importId )
    {
        return eZSys::varDirectory() . '/cjw_newsletter/csvimport/' . (int)$importId . '-import_result.serialize';
    }

    public static function storeResult( $importId, $data )
    {
        $fileName = self::resultFilePath( $importId );
        eZDir::mkdir( dirname( $fileName ), false, true );
        return file_put_contents( $fileName, serialize( $data ) ) !== false;
    }

    /**
     * @return array|false the stored result of an import (plain data only: no objects are restored)
     */
    public static function readResult( $importId )
    {
        $fileName = self::resultFilePath( $importId );
        $data = is_file( $fileName ) ? file_get_contents( $fileName ) : false;
        if ( !$data )
        {
            return false;
        }
        // the rows hold the subscription object of a row: restore it as the class it was
        return @unserialize( $data, array( 'allowed_classes' => array( 'CjwNewsletterSubscription' ) ) );
    }

}

?>