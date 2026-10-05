<?php
//
// Copyright (C) 1998 - 2026 7x & Exponential Foundation. All rights reserved.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 (or any later version).
//

/*!
  \class CjwNewsletterRunner cjwnewsletterrunner.php
  \brief The work of the cronjob parts and of the console commands as methods, so that the cron, the shell and the
         admin's "run now" actions do the same thing: one run at a time (a lock), the time and the result of the last
         run, and an audit event for each run. Everything takes $dryRun, which counts and changes nothing.
*/

class CjwNewsletterRunner
{
    const LAST_QUEUE_CREATE = 'cjw_newsletter_last_queue_create';
    const LAST_QUEUE_PROCESS = 'cjw_newsletter_last_queue_process';
    const LAST_MAILBOX = 'cjw_newsletter_last_mailbox';
    const LAST_IMPORT = 'cjw_newsletter_last_import';
    const LAST_REPAIR = 'cjw_newsletter_last_repair';

    /*!
     \static
     Remember the time and the result of a run.
    */
    static function recordRun( $key, $result, $by )
    {
        $result['time'] = time();
        $result['by'] = (string)$by;
        $json = json_encode( $result );
        $data = eZSiteData::fetchByName( $key );
        if ( !$data )
        {
            $data = eZSiteData::create( $key, $json );
        }
        else
        {
            $data->setAttribute( 'value', $json );
        }
        $data->store();
    }

    /*!
     \static
     \return array of the last run ( 'time', 'by', ... ), false when there is none
    */
    static function lastRun( $key )
    {
        $data = eZSiteData::fetchByName( $key );
        if ( !$data )
        {
            return false;
        }
        $result = json_decode( (string)$data->attribute( 'value' ), true );
        return is_array( $result ) && isset( $result['time'] ) ? $result : false;
    }

    /*!
     \static
     Take the lock of a run: one run of a kind at a time, whoever starts it (cron, console, admin).

     \return the lock (keep it until the run is over), false when another run holds it
    */
    static function lock( $name )
    {
        $dir = eZSys::cacheDirectory() . '/cjw_newsletter';
        if ( !is_dir( $dir ) )
        {
            eZDir::mkdir( $dir, false, true );
        }
        $handle = @fopen( $dir . '/' . preg_replace( '/[^a-z_]/', '', $name ) . '.lock', 'c' );
        if ( !$handle || !flock( $handle, LOCK_EX | LOCK_NB ) )
        {
            return false;
        }
        return $handle;
    }

    /*!
     \static
     Release the lock of a run.
    */
    static function unlock( $handle )
    {
        if ( $handle && $handle !== true )
        {
            flock( $handle, LOCK_UN );
            fclose( $handle );
        }
    }

    /*!
     \static
     Write the audit event of a run (system.cjw_newsletter.<run>), when the audit is on.
    */
    static function audit( $run, $by, $totals, $dryRun )
    {
        if ( $dryRun || !class_exists( 'expAudit' ) )
        {
            return;
        }
        $data = array( 'object' => 'cjw_newsletter:' . $run, 'after' => array( 'by' => (string)$by, 'totals' => $totals ) );
        if ( !empty( $totals['errors'] ) || ( isset( $totals['ok'] ) && !$totals['ok'] && empty( $totals['locked'] ) ) )
        {
            $data['result'] = 'failed';
            $data['reason'] = 'run';
        }
        expAudit::event( 'system.cjw_newsletter.' . $run, $data );
    }

    /*!
     \static
     The orphans: subscriptions of newsletter users that are gone, and send items without a send or without a user.

     \return array( 'subscriptions' => ids, 'send_items' => ids )
    */
    static function orphans()
    {
        $db = eZDB::instance();
        $result = array( 'subscriptions' => array(), 'send_items' => array() );
        foreach ( (array)$db->arrayQuery( 'SELECT id FROM cjwnl_subscription WHERE newsletter_user_id NOT IN ( SELECT id FROM cjwnl_user )' ) as $row )
        {
            $result['subscriptions'][] = (int)$row['id'];
        }
        foreach ( (array)$db->arrayQuery( 'SELECT id FROM cjwnl_edition_send_item WHERE status = 0 AND ( newsletter_user_id NOT IN ( SELECT id FROM cjwnl_user ) OR edition_send_id NOT IN ( SELECT id FROM cjwnl_edition_send ) )' ) as $row )
        {
            $result['send_items'][] = (int)$row['id'];
        }
        return $result;
    }

    /*!
     \static
     Remove the orphans: subscriptions of removed users, and unsent items that can never be sent.
    */
    static function repair( $cli = false, $by = 'cron', $dryRun = false )
    {
        $totals = array( 'ok' => true, 'locked' => false, 'subscriptions' => 0, 'send_items' => 0 );
        $lock = $dryRun ? true : self::lock( 'repair' );
        if ( !$lock )
        {
            $totals['ok'] = false;
            $totals['locked'] = true;
            return $totals;
        }
        $orphans = self::orphans();
        $totals['subscriptions'] = count( $orphans['subscriptions'] );
        $totals['send_items'] = count( $orphans['send_items'] );
        if ( $cli )
        {
            $cli->output( ( $dryRun ? 'Would remove ' : 'Removing ' ) . $totals['subscriptions'] . ' subscriptions of removed users and ' . $totals['send_items'] . ' unsendable items.' );
        }
        if ( !$dryRun )
        {
            $db = eZDB::instance();
            foreach ( array( 'cjwnl_subscription' => $orphans['subscriptions'], 'cjwnl_edition_send_item' => $orphans['send_items'] ) as $table => $ids )
            {
                foreach ( array_chunk( $ids, 200 ) as $chunk )
                {
                    $db->query( 'DELETE FROM ' . $table . ' WHERE id IN ( ' . implode( ',', array_map( 'intval', $chunk ) ) . ' )' );
                }
            }
            self::recordRun( self::LAST_REPAIR, $totals, $by );
            self::audit( 'repair', $by, $totals, false );
            self::unlock( $lock );
        }
        return $totals;
    }

    /*!
     \static
     The first cronjob part: confirm the users that waited for their eZ user, wake the scheduled sends and create the
     mail queue (one send item per subscriber) of the sends that wait for it.

     \return array( 'ok', 'locked', 'users_confirmed', 'scheduled', 'sends', 'items' )
    */
    static function queueCreate( $cli = false, $by = 'cron', $dryRun = false )
    {
        $totals = array( 'ok' => true, 'locked' => false, 'users_confirmed' => 0, 'scheduled' => 0, 'sends' => 0, 'items' => 0 );
        $cli = $cli ? $cli : new CjwNewsletterJobOutput( false );
        $lock = $dryRun ? true : self::lock( 'queue_create' );
        if ( !$lock )
        {
            $totals['ok'] = false;
            $totals['locked'] = true;
            return $totals;
        }
        if ( $dryRun )
        {
            $totals['users_confirmed'] = count( (array)CjwNewsletterUser::fetchUserListByStatus( CjwNewsletterUser::STATUS_PENDING_EZ_USER_REGISTER, 10000, 0, true ) );
            $due = array();
            foreach ( (array)CjwNewsletterEditionSend::fetchEditionSendListByStatus( array( CjwNewsletterEditionSend::STATUS_WAIT_FOR_SCHEDULE ) ) as $send )
            {
                if ( $send->attribute( 'mailqueue_process_scheduled' ) <= time() )
                {
                    ++$totals['scheduled'];
                    $due[] = $send;
                }
            }
            // a send whose time has come gets its queue in the same run
            foreach ( array_merge( $due, (array)CjwNewsletterEditionSend::fetchEditionSendListByStatus( array( CjwNewsletterEditionSend::STATUS_WAIT_FOR_PROCESS ) ) ) as $send )
            {
                ++$totals['sends'];
                $totals['items'] += count( (array)$send->getSubscriptionObjectArray( CjwNewsletterSubscription::STATUS_APPROVED, 0, 0 ) );
            }
            $cli->output( 'Would confirm ' . $totals['users_confirmed'] . ' users, wake ' . $totals['scheduled'] . ' scheduled sends, create the queue of ' . $totals['sends'] . ' sends (up to ' . $totals['items'] . ' items).' );
            return $totals;
        }
        try
        {
            self::doQueueCreate( $cli, $totals );
        }
        catch ( Exception $e )
        {
            $totals['ok'] = false;
            $totals['errors'] = array( $e->getMessage() );
            $cli->error( 'The queue could not be created: ' . $e->getMessage() );
        }
        self::recordRun( self::LAST_QUEUE_CREATE, $totals, $by );
        self::audit( 'queue_create', $by, $totals, false );
        self::unlock( $lock );
        return $totals;
    }

    /*!
     \static
     The second cronjob part: send the mails of the queue (with the transport of [NewsletterMailSettings]
     TransportMethodCronjob) and finish the sends whose queue is empty.

     \return array( 'ok', 'locked', 'sent', 'failed', 'finished', 'waiting', 'blocked' )
    */
    static function queueProcess( $cli = false, $by = 'cron', $dryRun = false )
    {
        $totals = array( 'ok' => true, 'locked' => false, 'sent' => 0, 'failed' => 0, 'finished' => 0, 'waiting' => 0, 'blocked' => 0 );
        $cli = $cli ? $cli : new CjwNewsletterJobOutput( false );
        $lock = $dryRun ? true : self::lock( 'queue_process' );
        if ( !$lock )
        {
            $totals['ok'] = false;
            $totals['locked'] = true;
            return $totals;
        }
        if ( $dryRun )
        {
            foreach ( (array)CjwNewsletterEditionSend::fetchEditionSendListByStatus( array( CjwNewsletterEditionSend::STATUS_MAILQUEUE_CREATED, CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_STARTED ) ) as $send )
            {
                $statistic = $send->attribute( 'send_items_statistic' );
                $totals['waiting'] += (int)$statistic['items_not_send'];
            }
            $cli->output( 'Would send ' . $totals['waiting'] . ' mails with the transport "' . eZINI::instance( 'cjw_newsletter.ini' )->variable( 'NewsletterMailSettings', 'TransportMethodCronjob' ) . '".' );
            return $totals;
        }
        try
        {
            self::doQueueProcess( $cli, $totals );
        }
        catch ( Exception $e )
        {
            $totals['ok'] = false;
            $totals['errors'] = array( $e->getMessage() );
            $cli->error( 'The queue could not be processed: ' . $e->getMessage() );
        }
        self::recordRun( self::LAST_QUEUE_PROCESS, $totals, $by );
        self::audit( 'queue_process', $by, $totals, false );
        self::unlock( $lock );
        return $totals;
    }

    /*!
     \static
     The mailbox part: collect the mails of the active mail accounts and parse them (bounces, replies).

     \param $mode 'both', 'collect' or 'parse'
     \return array( 'ok', 'locked', 'mailboxes', 'collected', 'parsed', 'errors' )
    */
    static function mailbox( $cli = false, $by = 'cron', $mode = 'both', $dryRun = false )
    {
        $totals = array( 'ok' => true, 'locked' => false, 'mailboxes' => 0, 'collected' => 0, 'parsed' => 0, 'errors' => array() );
        $cli = $cli ? $cli : new CjwNewsletterJobOutput( false );
        $lock = $dryRun ? true : self::lock( 'mailbox' );
        if ( !$lock )
        {
            $totals['ok'] = false;
            $totals['locked'] = true;
            return $totals;
        }
        $mailboxes = (array)CjwNewsletterMailbox::fetchAllActiveMailboxes();
        $totals['mailboxes'] = count( $mailboxes );
        if ( $dryRun )
        {
            $unparsed = eZPersistentObject::count( CjwNewsletterMailboxItem::definition(), array( 'processed' => 0 ) );
            $cli->output( 'Would collect the mails of ' . $totals['mailboxes'] . ' active mail accounts and parse ' . $unparsed . ' waiting mails.' );
            $totals['parsed'] = (int)$unparsed;
            return $totals;
        }
        if ( $mode != 'parse' )
        {
            $cli->output( 'Collecting mails of ' . $totals['mailboxes'] . ' active mail accounts' );
            $collected = $totals['mailboxes'] ? CjwNewsletterMailbox::collectMailsFromActiveMailboxes() : array();
            if ( is_array( $collected ) )
            {
                foreach ( $collected as $id => $result )
                {
                    if ( is_array( $result ) )
                    {
                        $totals['collected'] += count( $result );
                        $cli->output( 'Mail account ' . $id . ': ' . count( $result ) . ' mails' );
                    }
                    else
                    {
                        $totals['ok'] = false;
                        $totals['errors'][] = 'Mail account ' . $id . ': ' . ( is_string( $result ) ? $result : 'no answer' );
                        $cli->error( 'Mail account ' . $id . ': ' . ( is_string( $result ) ? $result : 'no answer' ) );
                    }
                }
            }
            elseif ( $collected !== false )
            {
                $totals['ok'] = false;
                $totals['errors'][] = (string)$collected;
                $cli->error( (string)$collected );
            }
        }
        if ( $mode != 'collect' )
        {
            $parsed = (array)CjwNewsletterMailbox::parseActiveMailboxItems();
            $totals['parsed'] = count( $parsed );
            $cli->output( 'Parsed ' . $totals['parsed'] . ' mails' );
        }
        self::recordRun( self::LAST_MAILBOX, $totals, $by );
        self::audit( 'mailbox', $by, $totals, false );
        self::unlock( $lock );
        return $totals;
    }

    protected static function doQueueCreate( $cli, &$totals )
    {
        \CjwNewsletterLog::getInstance( true );

        $message = "START: cjw_newsletter_mailqueue_create";
        $cli->output( $message );

        // extension point: e.g. the recurring sends make their sends here (CjwNewsletterExtensionPoints)
        \CjwNewsletterExtensionPoints::call( 'queueCreateBefore', array( $cli ) );

        // before we create the edition_send_items we will activate all nl_user with statua 20 - CjwNewlsetterUser::STATUS_PENDING_EZ_USER_REGISTER

        $message = "--\n>> START: check nl users with status STATUS_PENDING_EZ_USER_REGISTER";
        $cli->output( $message );

        $pendingNlUserObjectArray = \CjwNewsletterUser::fetchUserListByStatus( \CjwNewsletterUser::STATUS_PENDING_EZ_USER_REGISTER, 10000, 0, true );

        $message = ">>> NlUser Objects with STATUS_PENDING_EZ_USER_REGISTER found: ". count( $pendingNlUserObjectArray );
        $cli->output( $message );

        $nlUserCounter = 1;
        foreach ( $pendingNlUserObjectArray as $nlUser )
        {
            $ezUserObject = $nlUser->attribute( 'ez_user' );
            $nlUserId = $nlUser->attribute( 'id' );
            $nlUserEzUserId = $nlUser->attribute( 'ez_user_id' );

            if ( is_object( $ezUserObject ) )
            {
                $ezUserIsEnabled = $ezUserObject->attribute( 'is_enabled' );
                // if activated then confirm nl user
                if ( $ezUserIsEnabled )
                {
                    $message = "+ [$nlUserCounter][NL_USER][$nlUserId] eZUser $nlUserEzUserId is enabled => confirm nl user";
                    $cli->output( $message );
                    // confirm user and all oben subscriptions
                    $nlUser->confirmAll();
                    $totals['users_confirmed']++;
                }
                // if not activated do nothing
                else
                {
                    $message = "o [$nlUserCounter][NL_USER][$nlUserId] eZUser $nlUserEzUserId is deactive => ignore ";
                    $cli->output( $message );
                }
            }
            else
            {
                // if a ez_user_id is not available anymore
                // we set the Nl user Status from STATUS_PENDING_EZ_USER_REGISTER => STATUS_PENDING
                // so it is not processed again
                //$nlUser->setAttribute( 'ez_user_id', 0 );
                $nlUser->setAttribute( 'status', \CjwNewsletterUser::STATUS_PENDING );

                $message = "! [$nlUserCounter][NL_USER][$nlUserId] eZUserId $nlUserEzUserId not existing anymore set status=STATUS_PENDING";
                $cli->output( $message );

                $nlUser->store();
            }
            $nlUserCounter++;
        }

        $message = ">> END: check nl users\n--";
        $cli->output( $message );

        // START schedule

        $message = "--\n>> START: check NlEditionSend objects with status STATUS_WAIT_FOR_SCHEDULE";
        $cli->output( $message );

        // fetch all scheduled SEND objects
        $waitForScheduleObjectList = \CjwNewsletterEditionSend::fetchEditionSendListByStatus( array( \CjwNewsletterEditionSend::STATUS_WAIT_FOR_SCHEDULE ) );

        $message = ">>> NlEditionSend objects with STATUS_WAIT_FOR_SCHEDULE found: ". count($waitForScheduleObjectList);
        $cli->output( $message );

        foreach ( $waitForScheduleObjectList as $newsletterEdtionSendObject )
        {
            $scheduleTimestamp = $newsletterEdtionSendObject->attribute( 'mailqueue_process_scheduled' );

            $escalateStatus = $scheduleTimestamp <= time();
            if ($escalateStatus){
                $message = ">>> schedule time has come ".date('Y-m-d H:i:s', $scheduleTimestamp)." escalate status to STATUS_WAIT_FOR_PROCESS";
                $cli->output( $message );
                $newsletterEdtionSendObject->setAttribute('status', \CjwNewsletterEditionSend::STATUS_WAIT_FOR_PROCESS);
                $newsletterEdtionSendObject->store();
                $totals['scheduled']++;
            }
        }

        // END

        $message = "--\n>> START: check NlEditionSend objects with status STATUS_WAIT_FOR_PROCESS";
        $cli->output( $message );

        // 1. search all SEND objects which create for the mail list
        $waitForProcessObjectList = \CjwNewsletterEditionSend::fetchEditionSendListByStatus( array( \CjwNewsletterEditionSend::STATUS_WAIT_FOR_PROCESS ) );

        $message = ">>> NlEditionSend objects with STATUS_WAIT_FOR_PROCESS found: ". count( $waitForProcessObjectList );
        $cli->output( $message );

        // 2. every SEND object true
        foreach ( $waitForProcessObjectList as $newsletterEdtionSendObject )
        {
            $sendId = $newsletterEdtionSendObject->attribute('id');
            $listContentObjectId = $newsletterEdtionSendObject->attribute('list_contentobject_id');
            $listContentObjectVersion = $newsletterEdtionSendObject->attribute('list_contentobject_version');

            $message = "## Procsessing: cjw_newsletter_mailqueue_create - sendObjectId: ". $sendId;
            $cli->output( $message );

            // 3. search all user which corresponding with list and has CjwNewslettersSubscription::STATUS_APPROVED
            // create a new send_item-entry
            $limit = 0;
            $offset = 0;


            //$subscriptionObjectList = CjwNewsletterSubscription::fetchSubscriptionListByListIdAndStatus( $listContentObjectId, CjwNewsletterSubscription::STATUS_APPROVED, $limit, $offset  );
            $subscriptionObjectList = $newsletterEdtionSendObject->getSubscriptionObjectArray( \CjwNewsletterSubscription::STATUS_APPROVED, 0, 0 );

            $message = "++ Find SubscriptionObjects with STATUS_APPROVED: ". count( $subscriptionObjectList );
            $cli->output( $message );

            $counter = 0;
            foreach ( $subscriptionObjectList as $subscriptionObject )
            {
                $subscriptionId = $subscriptionObject->attribute('id');
                $editionContentObjectId = $subscriptionObject->attribute('edition_contentobject_id');
                $newsletterUserId = $subscriptionObject->attribute('newsletter_user_id');
                $subscriptionOutputFormatArray = $subscriptionObject->attribute('output_format_array');

                $counter++;
                // status == STATUS_WAIT_FOR_PROCESS || != ABORT ?
                $newsletterEdtionSendObject->sync();
                if ( $newsletterEdtionSendObject->attribute('status') == \CjwNewsletterEditionSend::STATUS_WAIT_FOR_PROCESS )
                {
                    // every subscription can have multiple outputformats
                    // create for every outputformat one send_item
                    foreach ( $subscriptionOutputFormatArray as $outputFormatId => $outputFormatName )
                    {
                        $newSendItemResult = \CjwNewsletterEditionSendItem::create( $sendId,
                                                                             $newsletterUserId,
                                                                             $outputFormatId,
                                                                             $subscriptionId );
                        if ( is_object( $newSendItemResult ) )
                        {
                            $totals['items']++;
                            // create edtion_send_item
                            $message = "++ [SEND_ITEM][$counter] create new sendItem id: " . $newSendItemResult->attribute('id');
                            $cli->output( $message );
                        }
                        else
                        {
                            // create edtion_send_item
                            $message = "++ [Error][SEND_ITEM][$counter] sendItem already exist do nothing with it!";
                            $cli->output( $message );
                        }
                    }
                }
                else
                {
                    $message = "++ [ABBORT][$counter] Abborting EditionSendObject has not Status STATUS_WAIT_FOR_PROCESS or : ". $sendId ;
                    $cli->output( $message );
                }
            } // end foreach subscriptions

            // if are create all send_item entry's, set status == STATUS_MAILQUEUE_CREATED
            $message = "+ [STATUS_MAILQUEUE_CREATED] $counter sendItems has be processed (create / or do nothing)  SendId: ". $sendId ;
            $cli->output( $message );
            $totals['sends']++;
            $newsletterEdtionSendObject->setAttribute('status', \CjwNewsletterEditionSend::STATUS_MAILQUEUE_CREATED );
            $newsletterEdtionSendObject->store();

            // extension point: e.g. tracked links, A/B variants, outputs per language
            \CjwNewsletterExtensionPoints::call( 'sendQueueCreated', array( $newsletterEdtionSendObject, $cli ) );
        }

        $message = ">> END: check NlEditionSend objects\n--";
        $cli->output( $message );

        $message = "END: cjw_newsletter_mailqueue_create";
        $cli->output( $message );
    }

    protected static function doQueueProcess( $cli, &$totals )
    {
        \CjwNewsletterLog::getInstance( true );

        $message = "START: cjw_newsletter_mailqueue_process";
        $cli->output( $message );

        // extension point: e.g. the SMS queue, the choice of an A/B winner
        \CjwNewsletterExtensionPoints::call( 'queueProcessBefore', array( $cli ) );

        // to fetch all send objetc with status == STATUS_MALQUEUE_CREATED || STATUS_MALQUEUE_STARTED
        $sendObjectList = \CjwNewsletterEditionSend::fetchEditionSendListByStatus( array( \CjwNewsletterEditionSend::STATUS_MAILQUEUE_CREATED , \CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_STARTED ) );

        // - count all + how much should send?
        // - if send = 0 at first element status == STATUS_MALQUEUE_STARTED
        // - if send = count all => status == PROCESS_FINISHED
        foreach ( $sendObjectList as $sendObject )
        {
            // extension point: a send a handler holds back (a pause, an A/B wait, another channel) waits for a later run
            if ( !\CjwNewsletterExtensionPoints::allows( 'sendProcessAllowed', array( $sendObject ) ) )
            {
                $cli->output( 'Send ' . $sendObject->attribute( 'id' ) . ' held back for a later run.' );
                continue;
            }

            // set startdate only at the first time
            if ( $sendObject->attribute( 'status' ) == \CjwNewsletterEditionSend::STATUS_MAILQUEUE_CREATED )
            {
                // if ok, set status == STATUS_MAILQUEUE_PROCESS_STARTED
                $sendObject->setAttribute('status', \CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_STARTED );
                $sendObject->store();
                $message = "Status set: editonSend  STATUS_MAILQUEUE_PROCESS_STARTED";
                $cli->output( $message );
            }

            $editionSendId = (int) $sendObject->attribute('id');
            $sendItemsStatistic = $sendObject->attribute('send_items_statistic');

            $itemsCountAll = $sendItemsStatistic['items_count'];
            $itemsSend = $sendItemsStatistic['items_send'];
            $itemsNotSend = $sendItemsStatistic['items_not_send'];

            // ### sendObject Data
            $outputFormatStringArray = $sendObject->getParsedOutputXml( );

            // embed images
            foreach( $outputFormatStringArray as $outputFormatId => $outputFormatNewsletterContentArray )
            {
               if( $outputFormatNewsletterContentArray['html_mail_image_include'] == 1 )
               {
                   $outputFormatStringArray[ $outputFormatId ] = \CjwNewsletterEdition::prepareImageInclude( $outputFormatNewsletterContentArray );
               }
            }
            // embed images ends

            $emailSender = $sendObject->attribute( 'email_sender' );
            $emailSenderName = $sendObject->attribute( 'email_sender_name' );
            $personalizeContent = (int) $sendObject->attribute( 'personalize_content' );

            $emailReplyTo = $sendObject->attribute( 'email_reply_to' );
            $emailReturnPath = $sendObject->attribute( 'email_return_path' );

            $limit = 50;
            $offset = 0;

            $itemCounter = 1;
            $progressMonitor = new CjwNewsletterProgress( $cli );

            $cjwMail = new \CjwNewsletterMail();
            $cjwMail->setTransportMethodCronjobFromIni();
            // the editions go through the mail gate of the e-mail preferences (Exponential 6.0.15 and later)
            $cjwMail->setMailCategory( 'newsletter' );

            // process every send_item of current sendobject
            for( $i = 0; $i < $itemsNotSend; $i += $limit)
            {
              //  $progressBar->advance();
                $sendItemList = \CjwNewsletterEditionSendItem::fetchListSendIdAndStatus( $editionSendId, \CjwNewsletterEditionSendItem::STATUS_NEW, $limit, $offset );
                $count = count( $sendItemList );

                foreach ( $sendItemList as $sendItem )
                {
                    $id = $sendItem->attribute('id');
                    $outputFormatId = $sendItem->attribute('output_format_id');

                    // ### subscription data
                    $newsletterSubscriptionObject = $sendItem->attribute('newsletter_subscription_object');

                    if ( is_object( $newsletterSubscriptionObject ) )
                    {
                        $newsletterUnsubscribeHash = $newsletterSubscriptionObject->attribute('hash');

                        // ### get newsletter user data through send_item_object
                        $newsletterUserObject = $sendItem->attribute( 'newsletter_user_object' );

                        if ( is_object( $newsletterUserObject ) )
                        {
                            $emailReceiver = $newsletterUserObject->attribute( 'email' );
                            $emailReceiverName = $newsletterUserObject->attribute( 'email_name' );

                            // ### configure hash
                            $newsletterConfigureHash = $newsletterUserObject->attribute( 'hash' );

                            // fetch html & text content of parsed outputxml from senmdobject
                            // data of outputformate
                            $outputStringArray = $outputFormatStringArray[$outputFormatId]['body'];
                            $emailSubject = $outputFormatStringArray[$outputFormatId]['subject'];

                            // the placeholders of this recipient: escaped in the HTML part, raw in the text part and the subject
                            $placeholderValues = \CjwNewsletterPlaceholders::valuesForRecipient( $sendItem, $sendObject,
                                $newsletterUnsubscribeHash, $newsletterUserObject, $personalizeContent === 1 );

                            // extension point: a handler may change the subject, the bodies and the placeholder values
                            // of this recipient, defer the item (rate limit) or close it
                            $message = new \ArrayObject( array( 'subject' => $emailSubject, 'bodies' => $outputStringArray,
                                'values' => $placeholderValues, 'defer' => false, 'abort' => '' ) );
                            \CjwNewsletterExtensionPoints::call( 'itemBeforeSend', array( $message, $sendItem, $sendObject, $newsletterUserObject ) );
                            if ( $message['defer'] )
                            {
                                $progressMonitor->addEntry( "[DEFERRED] $itemCounter/$itemsNotSend", "Newsletter send item {$id} left for a later run." );
                                $totals['deferred'] = ( isset( $totals['deferred'] ) ? $totals['deferred'] : 0 ) + 1;
                                break 2;
                            }
                            if ( $message['abort'] !== '' )
                            {
                                $progressMonitor->addEntry( "[ABORT] $itemCounter/$itemsNotSend", "Newsletter send item {$id} closed: " . $message['abort'] );
                                $sendItem->setAttribute( 'status', \CjwNewsletterEditionSendItem::STATUS_ABORT );
                                $sendItem->store();
                                $itemCounter++;
                                continue;
                            }
                            $outputStringArrayNew = \CjwNewsletterPlaceholders::replaceInBodies( $message['bodies'], $message['values'] );
                            $emailSubject = \CjwNewsletterPlaceholders::replaceInSubject( $message['subject'], $message['values'] );

                            // set x-cjwnl header
                            $cjwMail->resetExtraMailHeaders();
                            $cjwMail->setExtraMailHeadersByNewsletterSendItem( $sendItem );

                            $resultArray = $cjwMail->sendEmail( $emailSender,
                                $emailSenderName,
                                $emailReceiver,
                                $emailReceiverName,
                                $emailSubject,
                                $outputStringArrayNew,
                                false,
                                'utf-8',
                                $emailReplyTo,
                                $emailReturnPath );

                            $sendResult = $resultArray['send_result'];

                            if ( !empty( $resultArray['blocked'] ) )
                            {
                                // the person's e-mail preferences refuse it (all optional e-mail off, the
                                // newsletters off, a suppressed address): the item is closed, the user is not bounced
                                $progressMonitor->addEntry( "[BLOCKED] $itemCounter/$itemsNotSend",
                                    "Newsletter send item {$id} not sent: refused by the e-mail preferences." );
                                $totals['blocked'] = ( isset( $totals['blocked'] ) ? $totals['blocked'] : 0 ) + 1;
                                $sendItem->setAttribute( 'status',
                                    \CjwNewsletterEditionSendItem::STATUS_ABORT );
                                $sendItem->store();
                            }
                            else if ( $sendResult === true )
                            {
                                // emal was send
                                $progressMonitor->addEntry( "[SEND] $itemCounter/$itemsNotSend",
                                    "Newsletter send item {$id} processed. " );

                                // wenn ok als versendet markieren
                                $totals['sent']++;
                                $sendItem->setAttribute( 'status',
                                    \CjwNewsletterEditionSendItem::STATUS_SEND );
                                $sendItem->store();
                            }
                            else
                            {
                                // error execption
                                $exception = $resultArray['send_result'];
                                $progressMonitor->addEntry( "[FAILED] $itemCounter/$itemsNotSend",
                                    "Newsletter send item {$id} failed, abort and bounce newsletter user." );
                                // abort item if mail returns directly e.g. mailbox not found
                                $totals['failed']++;
                                $sendItem->setAttribute( 'status',
                                    \CjwNewsletterEditionSendItem::STATUS_ABORT );
                                $sendItem->store();

                                // bounce send Item
                                $sendItem->setBounced();

                                // bounc nl user
                                $newsletterUser = $sendItem->attribute( 'newsletter_user_object' );
                                if ( is_object( $newsletterUser ) )
                                {
                                    $isHardBounce = false;
                                    // bounce nl user
                                    $newsletterUser->setBounced( $isHardBounce );
                                }
                            }

                            // extension point: e.g. the rate counters, the A/B counts
                            \CjwNewsletterExtensionPoints::call( 'itemSent', array( $sendItem, $sendObject, $resultArray ) );
                        }

                        // newsletter user object not available anymore => abort
                        else
                        {
                            // if object is not available anymore because of removal got to next item
                            $progressMonitor->addEntry( "[FAILED] $itemCounter/$itemsNotSend", "Newsletter send item {$id} failed - newsletter_user_object not available aborting. " );
                            // abort item if mail returns directly e.g. mailbox not found
                            $totals['failed']++;
                            $sendItem->setAttribute( 'status', \CjwNewsletterEditionSendItem::STATUS_ABORT );
                            $sendItem->store();
                        }

                    }
                    else
                    {
                        // if object is not available anymore because of removal got to next item
                        $progressMonitor->addEntry( "[FAILED] $itemCounter/$itemsNotSend", "Newsletter send item {$id} failed - newsletter_subscription_object not available. " );
                        // abort item if mail returns directly e.g. mailbox not found
                        $totals['failed']++;
                            $sendItem->setAttribute( 'status', \CjwNewsletterEditionSendItem::STATUS_ABORT );
                        $sendItem->store();
                    }

                    // parse output_xml with user_content, normal or personalizied?
                    // create email
                    // send email

                    $itemCounter++;

                    // wait for 2/10 seconds
                    // no, we do not sleep - we do work
                    // usleep( 200000 );
                }
            }

            // all send_items of sendobject are send? if yes set status == PROCESS_FINISHED
            $sendObject->sync();
            $sendItemsStatistic = $sendObject->attribute('send_items_statistic');

            $itemsCountAll = $sendItemsStatistic['items_count'];
            $itemsSend = $sendItemsStatistic['items_send'];
            $itemsNotSend = $sendItemsStatistic['items_not_send'];
            $itemsAbort = $sendItemsStatistic['items_abort'];

            // if all objects send or abort than finish processing an archive
            if ( $itemsCountAll == ( $itemsSend + $itemsAbort ) )
            {
                // if ok, set status == STATUS_MAILQUEUE_PROCESS_FINISHED
                $totals['finished']++;
                $sendObject->setAttribute('status', \CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_FINISHED  );
                $sendObject->store();

                $message = "Status set: editonSend  STATUS_MAILQUEUE_PROCESS_FINISHED";
                $cli->output( $message );
            }

            // var_dump( $sendItemsStatistic );
        }

        $message = "END: cjw_newsletter_mailqueue_process";
        $cli->output( $message );
    }
}

?>
