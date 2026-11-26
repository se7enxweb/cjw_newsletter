<?php
/**
 * File containing the CjwNewsletterMailbox class
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @filesource
 */
/**
 * Connect with mailaccount and fetch/parse mailitems
 *
 * @version //autogentag//
 * @package cjw_newsletter
 */
class CjwNewsletterMailbox extends eZPersistentObject
{

    /**
     * Store ezc...Transport object global
     */
    var $TransportObject = null;

    /**
     * constructor
     *
     * @param mixed $row
     * @return void
     */
    function __construct( $row = array() )
    {
        parent::__construct( $row );
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
                                         'email' => array( 'name'  => 'Email',
                                                        'datatype' => 'string',
                                                        'default'  => '',
                                                        'required' => true ),
                                         'server' => array( 'name' => 'Server',
                                                        'datatype' => 'string',
                                                        'default'  => '',
                                                        'required' => true ),
                                         'port' => array( 'name'   => 'Port',
                                                        'datatype' => 'integer',
                                                        'default'  => 0,
                                                        'required' => true ),
                                         'user_name' => array( 'name'   => 'UserName',
                                                        'datatype' => 'string',
                                                        'default'  => '',
                                                        'required' => true ),
                                         'password' => array( 'name' => 'Password',
                                                        'datatype'   => 'string',
                                                        'default'    => '',
                                                        'required'   => true ),
                                         'type' => array( 'name'     => 'Type',
                                                        'datatype' => 'string',
                                                        'default'  => '',
                                                        'required' => true ),
                                         'is_activated' => array( 'name'     => 'IsActivated',
                                                                  'datatype' => 'integer',
                                                                  'default'  => 0,
                                                                  'required' => true ),
                                         'is_ssl' => array( 'name'     => 'IsSsl',
                                                            'datatype' => 'integer',
                                                            'default'  => 0,
                                                            'required' => true ),
                                         'delete_mails_from_server' => array( 'name'     => 'DeleteMailsFromServer',
                                                                              'datatype' => 'integer',
                                                                              'default'  => 0,
                                                                              'required' => true ),
                                         'last_server_connect' => array( 'name'     => 'LastServerConnect',
                                                                         'datatype' => 'integer',
                                                                         'default'  => 0,
                                                                         'required' => true )),
                      'keys' => array( 'id' ),
                      'increment_key' => 'id',
                      'class_name' => 'CjwNewsletterMailbox',
                      'name' => 'cjwnl_mailbox' );
    }

    /**
     * fetch all active mailboxes
     *
     * @return object
     */
    public static function fetchAllActiveMailboxes()
    {
        $objectList = eZPersistentObject::fetchObjectList(
                                                            self::definition(),
                                                            null,
                                                            array( 'is_activated' => 1 ),
                                                            null,
                                                            null,
                                                            true
                                                         );


        return $objectList;
    }

    /**
     * fetch all exists mailboxes
     *
     * @return array
     */
    public static function fetchAllMailboxes()
    {
        $objectList = eZPersistentObject::fetchObjectList(
                                                            self::definition(),
                                                            null,
                                                            null,
                                                            null,
                                                            null,
                                                            true
                                                         );

        return $objectList;
    }

    /**
     * fetch data of mailbox to edit they in frontend
     *
     * @param integer $id
     * @param boolean $asObject
     * @return object
     */
    static public function fetchMailboxDataForEdit( $id, $asObject = true )
    {
        $mailboxObject = eZPersistentObject::fetchObject(
                                                            self::definition(),
                                                            null,
                                                            array( 'id' => $id ),
                                                            $asObject
                                                         );
        return $mailboxObject;
    }


    /** The mailboxes to collect from; a seam for tests. */
    protected static function activeMailboxes()
    {
        return self::fetchAllActiveMailboxes();
    }

    /**
     * connect to all mailboxes which are activated and store all mails in our db
     *
     * @return array
     */
    public static function collectMailsFromActiveMailboxes()
    {
        $mailboxesProcessArray = array();

        // fetch all acitive mailboxes
        $mailboxes = static::activeMailboxes();

        if ( is_array( $mailboxes ) )
        {
            // foreach mailbox connect and get mails
            foreach ( $mailboxes as $mailbox )
            {
                if ( is_object( $mailbox ) )
                {
                    try
                    {
                        // connect to mailbox
                        $connectResult = $mailbox->connect();

                        if( is_object( $connectResult ) )
                        {
                            CjwNewsletterLog::writeDebug(
                                                    'CjwNewsletterMailbox::collectMailsFromActiveMailboxes',
                                                    'mailbox',
                                                    'connect-ok',
                                                     array( 'mailbox_id' => $mailbox->attribute('id') ) );

                            // One failing mailbox must not stop the others.
                            try
                            {
                                $mailboxesProcessArray[ $mailbox->attribute( 'id' ) ] = $mailbox->fetchMails();
                                $mailbox->disconnect();
                            }
                            catch( Exception $e )
                            {
                                $mailboxesProcessArray[ $mailbox->attribute( 'id' ) ] = 'fetch failed: ' . $e->getMessage();
                                CjwNewsletterLog::writeError(
                                            'CjwNewsletterMailbox::collectMailsFromActiveMailboxes',
                                            'mailbox',
                                            'fetch-failed',
                                             array( 'mailbox_id' => $mailbox->attribute( 'id' ),
                                                    'error-code' => $e->getMessage() ) );
                                try
                                {
                                    $mailbox->disconnect();
                                }
                                catch( Exception $e2 )
                                {
                                    // the connect error is already recorded; a failed disconnect adds nothing
                                }
                                continue;
                            }

                        }
                        else
                        {
                            $mailboxesProcessArray[ $mailbox->attribute( 'id' ) ] = 'connect failed: ' . $connectResult;
                            CjwNewsletterLog::writeError(
                                                    'CjwNewsletterMailbox::collectMailsFromActiveMailboxes',
                                                    'mailbox',
                                                    'connect-failed',
                                                     array( 'error_code' => $connectResult,
                                                            'mailbox_id' => $mailbox->attribute('id') )
                                                     );
                        }

                    }
                    catch( Exception $e )
                    {
                        $mailboxesProcessArray[ $mailbox->attribute( 'id' ) ] = 'connect failed: ' . $e->getMessage();
                        CjwNewsletterLog::writeError(
                                                        'CjwNewsletterMailbox::collectMailsFromActiveMailboxes',
                                                        'mailbox',
                                                        'connect-failed',
                                                         array( 'mailbox_id' => $mailbox->attribute( 'id' ),
                                                                'error-code' => $e->getMessage() ) );
                        continue;
                    }

                }
            }

            return $mailboxesProcessArray;
        }
        else
        {
            return false;
        }
    }


    /**
     * parse all mailitems of all active mailboxes and get status code ...
     *
     * @return array
     */
    public static function parseActiveMailboxItems()
    {
        // fetch mails which has a empty progressed_field ( unparsed mails )
        $objectList = eZPersistentObject::fetchObjectList(
                                                            CjwNewsletterMailboxItem::definition(),
                                                            null,
                                                            array( 'processed' => 0 ),
                                                            null,
                                                            null,
                                                            $asObject = true,
                                                            null,
                                                            null
                                                          );

       $parseResultArray = array();

       foreach ( $objectList as $mailboxItem )
       {
            $parseResultArray[ $mailboxItem->attribute( 'id' ) ] = $mailboxItem->parseMail();
       }

       return $parseResultArray;
    }


    /**
     * store changed or new mailbox in database
     *
     * @param array $mailboxDataArray
     * @return boolean
     */
    public function storeMailboxData( $id, $mailboxDataArray )
    {
        // edit
        if ( $id != 0 )
        {
            $mailboxObject = $this->fetchMailboxDataForEdit( $id );
        }
        // add
        else
        {
            $mailboxObject = $this;
        }

        // set data
        if ( is_object( $mailboxObject ) )
        {
            foreach ( $mailboxDataArray as $key => $value )
            {
                $mailboxObject->setAttribute( $key, $value );
            }
        }
        // save
        $mailboxObject->store();

        return true;
    }

    /**
     * connect with mailaccount
     *
     * @return object / boolean
     */
    /**
     * The connection settings as they may be written to the newsletter log: the password is replaced by a mask.
     * An empty password stays empty, so a log entry still shows that none was set.
     *
     * @param array $settingArray
     * @return array
     */
    public static function loggableSettings( array $settingArray )
    {
        if ( isset( $settingArray['password'] ) && $settingArray['password'] !== '' )
        {
            $settingArray['password'] = '***';
        }
        return $settingArray;
    }

    public function connect()
    {
        // current mailbox id
        $mailboxId = $this->attribute( 'id' );

        // current mailbox type, for pop3-/imap switch
        $mailboxType = $this->attribute( 'type' );

        // login data
        $server                = $this->attribute( 'server' );
        $userName              = $this->attribute( 'user_name' );
        $password              = $this->attribute( 'password' );
        $port                  = $this->attribute( 'port' );
        $ssl                   = (bool) $this->attribute( 'is_ssl' );
        $deleteMailsFromServer = (bool) $this->attribute( 'delete_mails_from_server' );

        if( $port > 0 )
        {
            $serverPort = $port;
        }
        else
        {
            // let choose ezc the right port if not set
            $serverPort = null;
        }

        $ezcTransportObject = null;

        $settingArray = array( 'mailbox_id'               => $mailboxId,
                               'type'                     => $mailboxType,
                               'server'                   => $server,
                               'user_name'                => $userName,
                               'password'                 => $password,
                               'port'                     => $port,
                               'is_ssl'                   => $ssl,
                               'delete_mails_from_server' => $deleteMailsFromServer );
        try
        {
            // create transport object
            switch ( $mailboxType )
            {
                case 'imap':
                    $options = new ezcMailImapTransportOptions();
                    $options->ssl = $ssl;
                    $options->timeout = 3;
                    $ezcTransportObject = new ezcMailImapTransport( $server, $serverPort, $options );
                    break;
                case 'pop3':
                    $options = new ezcMailPop3TransportOptions();
                    $options->ssl = $ssl;
                    $options->timeout = 3;
                    // $options->authenticationMethod = ezcMailPop3Transport::AUTH_APOP;
                    $ezcTransportObject = new ezcMailPop3Transport( $server, $serverPort, $options );
                    break;
                default:
                    $message = 'Unknown mailbox type ' . $mailboxType;
                    CjwNewsletterLog::writeError(
                                                'CjwNewsletterMailbox::connect',
                                                'mailbox',
                                                'connect-failed',
                                                 array( 'error-code' => $message ) );
                    return $message;

            }
        }
        catch( Exception $e )
        {
            CjwNewsletterLog::writeError(
                                            'authenticate ezcMailTransport CjwNewsletterMailbox::connect',
                                            'mailbox',
                                            'connect-failed',
                                             array( 'error-code' => $e->getMessage() ) );
            return $e->getMessage();
        }

        try
        {
            // authenticate twise is not allowed
            $ezcTransportObject->authenticate( $userName, $password );
        }
        catch( Exception $e )
        {
            CjwNewsletterLog::writeError(
                                            'CjwNewsletterMailbox::connect',
                                            'mailbox',
                                            'authenticate-failed',
                                             array_merge( array( 'error-code' => $e->getMessage() ),
                                                          self::loggableSettings( $settingArray ) )  );
            return $e->getMessage();
        }

        try
        {

            switch ( $mailboxType )
            {
                case 'imap':
                    $ezcTransportObject->selectMailbox( 'Inbox' );
                    break;
            }

            $this->TransportObject = $ezcTransportObject;

            // set connect time
            $this->setAttribute( 'last_server_connect', time() );
            $this->store();

            CjwNewsletterLog::writeDebug(
                                        'CjwNewsletterMailbox::connect',
                                        'mailbox',
                                        'connect-ok',
                                         self::loggableSettings( $settingArray ) );

            return $ezcTransportObject;

        }
        catch( Exception $e )
        {
            CjwNewsletterLog::writeError(
                                            'CjwNewsletterMailbox::connect',
                                            'mailbox',
                                            'selectBox-failed',
                                             array( 'error-code' => $e->getMessage() ) );

            return $e->getMessage();
        }

    }


    /**
     * disconnect connection
     * @return unknown_type
     */
    public function disconnect()
    {
        if( is_object( $this->TransportObject ) )
        {
            $this->TransportObject->disconnect();
        }
    }


    /**
     * fetch mails to parse and/or store
     *
     * @return void
     */
    public function fetchMails( )
    {
        $statusArray = array( 'added'   => array(),
                              'exists' => array(),
                              'failed'  => array() );
        $mailboxId = $this->attribute( 'id' );
        $mailboxDeleteMailsFromServer = (bool) $this->attribute( 'delete_mails_from_server' );
        // numbers of messages that are stored or already known; only these are deleted.
        $deleteNumbers = array();

        if ( is_object( $this->TransportObject ) )
        {
            $transport = $this->TransportObject;
            $uniqueIdentifierArray = array();
            $messageIdArray = array();

            try
            {
                // it is possible that not all pop3 server understand this
                // array( message_num => unique_id );
                // array( 1 => '000001fc4420e93a', 2 => '000001fd4420e93a' );
                $uniqueIdentifierArray = $transport->listUniqueIdentifiers();
            }
            catch( Exception $e )
            {
                $uniqueIdentifierArray = array();
                CjwNewsletterLog::writeError(
                                            'CjwNewsletterMailbox::fetchMails',
                                            'mailbox',
                                            'listUniqueIdentifiers-failed',
                                             array( 'error-code' => $e->getMessage() ) );
            }

            try
            {
                // array( message_id => message_size );
                // array( 2 => 1700, 5 => 1450 );
                $messageIdArray = $transport->listMessages( );
            }
            catch( Exception $e )
            {
                $messageIdArray = array();
                CjwNewsletterLog::writeError(
                                            'CjwNewsletterMailbox::fetchMails',
                                            'mailbox',
                                            'listMessages-failed',
                                             array( 'error-code' => $e->getMessage() ) );
            }

            // array( message_id => message_identifier )
            $messageNumberArray = array();

            // only fetch messages from server which are not in the db
            // use message_identifier for check
            $existingMessageIdentifierArray = $this->extractAllExistingIdentifiers( $uniqueIdentifierArray );

            $maxMessageSize = CjwNewsletterMailParser::maxMessageSize();
            foreach ( $messageIdArray as $messageId => $messageSize )
            {
                if( isset( $uniqueIdentifierArray[ $messageId ] ) )
                {
                    $uniqueIdentifier = $uniqueIdentifierArray[ $messageId ];
                }
                else
                {
                    $uniqueIdentifier = false;
                }
                if( $uniqueIdentifier !== false && array_key_exists( $uniqueIdentifier, $existingMessageIdentifierArray ) )
                {
                    $statusArray['exists'][ $messageId ] = $uniqueIdentifier;
                    // an oversized stub is not stored content: that message stays on the server
                    if ( strpos( (string)$existingMessageIdentifierArray[ $uniqueIdentifier ], 'stub_' ) !== 0 )
                    {
                        $deleteNumbers[] = $messageId;
                    }
                }
                else if ( (int)$messageSize > $maxMessageSize )
                {
                    // the whole message is read into memory: one that is too large is left on the server. With a
                    // unique identifier it is remembered as a processed item without content, so that it is
                    // reported once and not again in every run; without one (a server without UIDL) it is reported
                    // each time
                    $statusArray['failed'][ $messageId ] = $uniqueIdentifier;
                    CjwNewsletterLog::writeError( 'CjwNewsletterMailbox::fetchMails',
                                                  'mailbox',
                                                  'message-too-large',
                                                  array( 'message' => $messageId, 'identifier' => (string)$uniqueIdentifier, 'size' => (int)$messageSize, 'max' => $maxMessageSize ) );
                    if ( $uniqueIdentifier !== false )
                    {
                        CjwNewsletterMailboxItem::addOversizedMailboxItem( $mailboxId, $uniqueIdentifier, $messageId, (int)$messageSize );
                    }
                }
                else
                {
                    $messageNumberArray[ $messageId ] = $uniqueIdentifier;
                }
            }

            if ( count( $messageNumberArray ) > 0 )
            {
                // only fetch x item at once to avoid script timeout ... if call from admin frontend
                // the cronjob may be has other settings
                $fetchLimit = 50;
                $counter = 0;

                foreach ( $messageNumberArray as $messageId => $messageIdentifier )
                {
                    if( $counter >= $fetchLimit )
                    {
                        break;
                    }
                    else
                    {
                        // create mailobject from message id
                        // never flag on fetch: deleting happens after the item is stored
                        $mailObject = $transport->fetchByMessageNr( $messageId, false );

                        // convert mailobject to string with own function
                        $messageString = $this->convertMailToString( $mailObject );

                        if( $messageIdentifier === false )
                        {
                            $messageIdentifier = 'cjwnl_'.md5( $messageString );
                        }

                        // if messageString has content
                        if ( $messageString != null )
                        {
                            // add item to DB / Filesystem
                            try
                            {
                                $addResult = $this->storeItem( $mailboxId, $messageIdentifier, $messageId, $messageString );
                            }
                            catch( Exception $e )
                            {
                                $addResult = null;
                                CjwNewsletterLog::writeError( 'CjwNewsletterMailbox::fetchMails',
                                                              'mailbox',
                                                              'store-failed',
                                                              array( 'message' => $messageId, 'error-code' => $e->getMessage() ) );
                            }
                            if ( is_object( $addResult ) )
                            {
                                $statusArray[ 'added' ] [ $messageId ] =  $messageIdentifier;
                                $deleteNumbers[] = $messageId;
                            }
                            else if ( $addResult === false )
                            {
                                $statusArray[ 'exists' ] [ $messageId ] = $messageIdentifier;
                                $deleteNumbers[] = $messageId;
                            }
                            else
                            {
                                $statusArray[ 'failed' ] [ $messageId ] = $messageIdentifier;
                            }
                            unset( $addResult );
                        }
                        else
                        {
                            $statusArray[ 'failed' ] [ $messageId ] = $messageIdentifier;
                        }
                        unset( $messageString );
                        unset( $mailObject );
                    }
                    $counter++;
                }

            }

            if ( $mailboxDeleteMailsFromServer )
            {
                foreach ( $deleteNumbers as $number )
                {
                    $transport->delete( $number );
                }
            }
            // IMAP removes deleted messages on expunge, POP3 at QUIT
            if ( $mailboxDeleteMailsFromServer && count( $deleteNumbers ) > 0 && $this->attribute( 'type' ) == 'imap' )
            {
                $transport->expunge();
            }
        }
        return $statusArray;
    }

    /** Stores one fetched message; an object means stored, false means already known. A seam for tests. */
    protected function storeItem( $mailboxId, $identifier, $messageNumber, $raw )
    {
        return CjwNewsletterMailboxItem::addMailboxItem( $mailboxId, $identifier, $messageNumber, $raw );
    }

    /**
     * check if  message_idendifier are in db if not return it otherwise ignore
     * it is used to avoid connections
     * array( message_id => message_identifer )
     * @param $array
     * @return unknown_type
     */
    protected function extractAllExistingIdentifiers( $messageIdentifierArray )
    {
        $existingMessageIdentifierArray = array();
        $mailboxId = (int) $this->attribute('id');
        $identifierImplodeString = '';

        $db = eZDB::instance();
        foreach( (array)$messageIdentifierArray as $identifier )
        {
            $identifierImplodeString .= "'" . $db->escapeString( (string)$identifier ) . "',";
        }

        $sql = "SELECT id, message_identifier, email_subject, message_size FROM cjwnl_mailbox_item
                WHERE mailbox_id=$mailboxId
                AND message_identifier IN ( $identifierImplodeString -1 )";
        $rows = $db->arrayQuery( $sql );
        $maxMessageSize = CjwNewsletterMailParser::maxMessageSize();
        foreach( $rows as $row )
        {
            // the size as well: a stored mail could carry the stub's subject itself
            $isStub = strpos( (string)$row[ 'email_subject' ], '(not fetched: larger than' ) === 0
                      && (int)$row[ 'message_size' ] > $maxMessageSize;
            $existingMessageIdentifierArray[ $row[ 'message_identifier' ] ] = ( $isStub ? 'stub_' : 'item_' ).$row['id'];
        }
        return $existingMessageIdentifierArray;
    }

    /**
     * set transport object
     *
     * @param object $object
     * @return boolean
     */
    public function setTransportObject( $object )
    {
        if ( is_object( $object ) )
        {
            $this->TransportObject = $object;
        }
        else
        {
            return false;
        }
    }

    /**
     * convert mailobject to string
     *
     * @param object $mailObjectSet
     * @return string / boolean
     */
    public function convertMailToString( $mailObjectSet )
    {
        if ( is_object( $mailObjectSet ) )
        {
            $rawMail = '';
            while ( ( $line = $mailObjectSet->getNextLine() ) !== null )
            {
                // if memory_usage is to high return false 10 MB files should be ok normal usage is 1672448
                // on some imap connections i had such a problem
                // after new fetch it was ok
                //if( memory_get_usage( true ) > 3000000 )
                //    return false;
                $rawMail .= $line;
            }
            return $rawMail;
        }
        else
        {
            return false;
        }
    }

}

?>
