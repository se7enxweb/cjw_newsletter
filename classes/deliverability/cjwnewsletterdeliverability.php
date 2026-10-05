<?php
/**
 * File containing the CjwNewsletterDeliverability class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * The batches of the mail queue and the items that are due (cjw_newsletter.ini [ThrottleSettings]).
 *
 * With Throttle=enabled the queue runner sends each send in batches (cjwnl_send_batch): a batch takes at most
 * BatchSize items, and no more than the rate limits of the send's transport allow now (CjwNewsletterThrottle). A run
 * that stops (a limit reached, a pause, the end of the run) leaves the rest of the send for the next run, which
 * goes on after the last item handled: the sending is resumable. PauseBetweenBatches holds the next batch of a send
 * until that many seconds after the previous one finished (no run ever sleeps). With Throttle=disabled nothing of
 * this happens and the queue runs as before.
 *
 * Items that wait for a soft-bounce retry (next_retry in the future) are never taken (dueItems()).
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterDeliverability
{
    const BATCH_NEW = 0;
    const BATCH_RUNNING = 1;
    const BATCH_DONE = 2;
    const BATCH_PAUSED = 3;
    const BATCH_FAILED = 9;

    /** @var CjwNewsletterSendBatch[] send id => the batch running in this process */
    protected static $batches = array();
    /** @var array send id => hash( taken, sent, failed ) of the running batch */
    protected static $counts = array();

    /**
     * @param CjwNewsletterEditionSend $sendObject
     * @return string the transport the send is counted under (throttle_transport, else the cronjob transport)
     */
    static function transportOf( $sendObject )
    {
        return CjwNewsletterThrottle::name( is_object( $sendObject ) ? (string)$sendObject->attribute( 'throttle_transport' ) : '' );
    }

    /**
     * The new items of a send that may be sent now: not waiting for a retry, oldest first.
     *
     * @param int $editionSendId
     * @param int $limit
     * @return CjwNewsletterEditionSendItem[]
     */
    static function dueItems( $editionSendId, $limit = 50 )
    {
        $list = eZPersistentObject::fetchObjectList( CjwNewsletterEditionSendItem::definition(), null,
            array( 'edition_send_id' => (int)$editionSendId, 'status' => CjwNewsletterEditionSendItem::STATUS_NEW,
                   'next_retry' => array( '<=', CjwNewsletterThrottle::now() ) ),
            array( 'id' => 'asc' ), array( 'offset' => 0, 'length' => max( 1, (int)$limit ) ), true );
        return is_array( $list ) ? $list : array();
    }

    /**
     * @return int the new items of a send that may be sent now
     */
    static function dueItemCount( $editionSendId )
    {
        $db = eZDB::instance();
        $rows = $db->arrayQuery( 'SELECT COUNT(*) AS c FROM cjwnl_edition_send_item WHERE edition_send_id = ' . (int)$editionSendId
                                 . ' AND status = ' . CjwNewsletterEditionSendItem::STATUS_NEW . ' AND next_retry <= ' . CjwNewsletterThrottle::now() );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }

    /**
     * The queue runner starts the work on a send: with batches, the batch of this run.
     *
     * @param CjwNewsletterEditionSend $sendObject
     * @param object $cli the output of the run
     * @return CjwNewsletterSendBatch|null|false the batch; null: no batches (Throttle disabled, or nothing due);
     *         false: the send waits for a later run (rate limit, pause between batches)
     */
    static function startBatch( $sendObject, $cli = null )
    {
        if ( !CjwNewsletterThrottle::enabled() || !is_object( $sendObject ) )
            return null;
        $sendId = (int)$sendObject->attribute( 'id' );
        unset( self::$batches[$sendId], self::$counts[$sendId] );
        $due = self::dueItemCount( $sendId );
        if ( $due === 0 )
            return null;
        $now = CjwNewsletterThrottle::now();
        $pause = CjwNewsletterThrottle::pauseBetweenBatches();
        $batches = CjwNewsletterSendBatch::fetchList( array( 'edition_send_id' => $sendId, 'channel' => 'email' ), 1, 0, array( 'batch_number' => 'desc' ) );
        $last = $batches ? $batches[0] : null;
        if ( $last && $pause > 0 && (int)$last->attribute( 'status' ) === self::BATCH_DONE && (int)$last->attribute( 'finished' ) + $pause > $now )
        {
            self::output( $cli, 'Send ' . $sendId . ': next batch not before ' . date( 'Y-m-d H:i:s', (int)$last->attribute( 'finished' ) + $pause ) . '.' );
            return false;
        }
        $transport = self::transportOf( $sendObject );
        $allowed = CjwNewsletterThrottle::acquire( $transport, min( CjwNewsletterThrottle::batchSize(), $due ) );
        if ( $allowed === 0 )
        {
            self::output( $cli, 'Send ' . $sendId . ': the rate limit of the transport "' . $transport . '" is reached or it is paused; left for a later run.' );
            if ( $last && (int)$last->attribute( 'status' ) === self::BATCH_RUNNING )
            {
                $last->setAttribute( 'status', self::BATCH_PAUSED );
                $last->store();
            }
            return false;
        }
        // a batch that was stopped goes on; else a new one
        if ( $last && in_array( (int)$last->attribute( 'status' ), array( self::BATCH_PAUSED, self::BATCH_RUNNING, self::BATCH_NEW ), true ) )
        {
            $batch = $last;
            $left = max( 0, (int)$batch->attribute( 'item_count' ) - (int)$batch->attribute( 'sent_count' ) - (int)$batch->attribute( 'failed_count' ) );
            if ( $left === 0 )
            {
                $batch->setAttribute( 'status', self::BATCH_DONE );
                $batch->setAttribute( 'finished', $now );
                $batch->store();
                $batch = null;
            }
            else
            {
                $allowed = min( $allowed, $left );
            }
        }
        else
        {
            $batch = null;
        }
        if ( $batch === null )
        {
            $batch = CjwNewsletterSendBatch::create( array( 'edition_send_id' => $sendId, 'channel' => 'email',
                'batch_number' => $last ? (int)$last->attribute( 'batch_number' ) + 1 : 1, 'item_count' => $allowed,
                'sent_count' => 0, 'failed_count' => 0, 'last_item_id' => 0, 'status' => self::BATCH_NEW, 'created' => $now ) );
        }
        $batch->setAttribute( 'status', self::BATCH_RUNNING );
        if ( !(int)$batch->attribute( 'started' ) )
            $batch->setAttribute( 'started', $now );
        $batch->store();
        self::$batches[$sendId] = $batch;
        self::$counts[$sendId] = array( 'limit' => $allowed, 'taken' => 0, 'sent' => 0, 'failed' => 0 );
        self::output( $cli, 'Send ' . $sendId . ': batch ' . (int)$batch->attribute( 'batch_number' ) . ', up to ' . $allowed . ' mails with "' . $transport . '".' );
        return $batch;
    }

    /**
     * @return bool the batch of the send has taken all the items it may take in this run
     */
    static function batchFull( $sendObject )
    {
        $sendId = is_object( $sendObject ) ? (int)$sendObject->attribute( 'id' ) : 0;
        return isset( self::$counts[$sendId] ) && self::$counts[$sendId]['taken'] >= self::$counts[$sendId]['limit'];
    }

    /**
     * An item of the send was handled (extension point itemSent).
     *
     * @param CjwNewsletterEditionSendItem $sendItem
     * @param CjwNewsletterEditionSend $sendObject
     * @param array $result CjwNewsletterMail::sendEmail()
     */
    static function itemHandled( $sendItem, $sendObject, $result )
    {
        $sendId = is_object( $sendObject ) ? (int)$sendObject->attribute( 'id' ) : 0;
        if ( !isset( self::$batches[$sendId] ) || !is_object( $sendItem ) )
            return;
        $ok = is_array( $result ) && isset( $result['send_result'] ) && $result['send_result'] === true;
        self::$counts[$sendId]['taken']++;
        self::$counts[$sendId][$ok ? 'sent' : 'failed']++;
        $db = eZDB::instance();
        $db->query( 'UPDATE cjwnl_edition_send_item SET batch_id = ' . (int)self::$batches[$sendId]->attribute( 'id' ) . ' WHERE id = ' . (int)$sendItem->attribute( 'id' ) );
        $batch = self::$batches[$sendId];
        $batch->setAttribute( 'last_item_id', (int)$sendItem->attribute( 'id' ) );
    }

    /**
     * The runner is done with the send in this run: the batch is closed (done, or paused to be resumed) and its
     * mails are counted against the rate limits.
     *
     * @param CjwNewsletterEditionSend $sendObject
     * @return array|null the counts of the batch
     */
    static function endBatch( $sendObject )
    {
        $sendId = is_object( $sendObject ) ? (int)$sendObject->attribute( 'id' ) : 0;
        if ( !isset( self::$batches[$sendId] ) )
            return null;
        $batch = self::$batches[$sendId];
        $counts = self::$counts[$sendId];
        unset( self::$batches[$sendId], self::$counts[$sendId] );
        $batch->setAttribute( 'sent_count', (int)$batch->attribute( 'sent_count' ) + $counts['sent'] );
        $batch->setAttribute( 'failed_count', (int)$batch->attribute( 'failed_count' ) + $counts['failed'] );
        $left = (int)$batch->attribute( 'item_count' ) - (int)$batch->attribute( 'sent_count' ) - (int)$batch->attribute( 'failed_count' );
        $finished = $left <= 0 || self::dueItemCount( $sendId ) === 0;
        $batch->setAttribute( 'status', $finished ? self::BATCH_DONE : self::BATCH_PAUSED );
        if ( $finished )
            $batch->setAttribute( 'finished', CjwNewsletterThrottle::now() );
        $batch->store();
        CjwNewsletterThrottle::record( self::transportOf( $sendObject ), $counts['taken'] );
        return $counts;
    }

    /**
     * @param int $limit
     * @return CjwNewsletterSendBatch[] the newest batches (all sends)
     */
    static function recentBatches( $limit = 10 )
    {
        return CjwNewsletterSendBatch::fetchList( null, (int)$limit, 0, array( 'id' => 'desc' ) );
    }

    /**
     * @return array the numbers of the area for the dashboard and the console
     */
    static function summary()
    {
        $db = eZDB::instance();
        $count = function ( $sql ) use ( $db )
        {
            $rows = $db->arrayQuery( $sql );
            return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
        };
        $since = time() - 30 * 86400;
        $summary = array(
            'throttle' => CjwNewsletterThrottle::enabled(),
            'batch_size' => CjwNewsletterThrottle::batchSize(),
            'transports' => array_values( CjwNewsletterThrottle::states() ),
            'batches_running' => $count( 'SELECT COUNT(*) AS c FROM cjwnl_send_batch WHERE status IN ( ' . self::BATCH_RUNNING . ', ' . self::BATCH_PAUSED . ' )' ),
            'batches' => self::recentBatches( 5 ),
            'retries_waiting' => CjwNewsletterBounce::waitingRetryCount(),
            'retries_made' => $count( 'SELECT COUNT(*) AS c FROM cjwnl_edition_send_item WHERE retry_count > 0' ),
            'bounces_30' => $count( "SELECT COUNT(*) AS c FROM cjwnl_mailbox_item WHERE bounce_code <> '' AND bounce_code <> '0' AND created >= " . (int)$since ),
            'soft_bounce_users' => $count( 'SELECT COUNT(*) AS c FROM cjwnl_user WHERE soft_bounce_count > 0' ),
            'suppressed' => array( 'available' => false, 'bounce' => 0, 'complaint' => 0, 'all' => 0 ),
            'suppress_hard_bounces' => CjwNewsletterBounce::suppressHardBounces(),
            'max_retries' => CjwNewsletterBounce::maxRetries(),
            'test_groups' => CjwNewsletterTestGroup::fetchListCount(),
            'mailin' => array( 'enabled' => CjwNewsletterMailin::enabled(), 'addresses' => CjwNewsletterMailinAddress::fetchListCount( array( 'is_active' => 1 ) ),
                               'address_list' => array_map( function ( $a ) { return CjwNewsletterMailin::displayAddress( $a ); }, CjwNewsletterMailinAddress::fetchList( array( 'is_active' => 1 ), 10, 0, array( 'email' => 'asc' ) ) ),
                               'pending' => CjwNewsletterMailinMessage::fetchListCount( array( 'status' => CjwNewsletterMailin::STATUS_PENDING ) ),
                               'done_30' => $count( 'SELECT COUNT(*) AS c FROM cjwnl_mailin_message WHERE status = ' . CjwNewsletterMailin::STATUS_DONE . ' AND created >= ' . (int)$since ),
                               'rejected_30' => $count( 'SELECT COUNT(*) AS c FROM cjwnl_mailin_message WHERE status = ' . CjwNewsletterMailin::STATUS_REJECTED . ' AND created >= ' . (int)$since ) ),
            'kernel_reader' => array( 'available' => false, 'enabled' => false, 'last_run' => 0 ),
            'problems' => array() );
        if ( class_exists( 'expMailSuppression' ) && CjwNewsletterMailPreferences::available() )
            $summary['suppressed'] = array( 'available' => true, 'bounce' => expMailSuppression::countList( 'bounce' ),
                                            'complaint' => expMailSuppression::countList( 'complaint' ), 'all' => expMailSuppression::countList() );
        if ( class_exists( 'expMailBounceReader' ) )
        {
            $status = expMailBounceReader::status();
            $summary['kernel_reader'] = array( 'available' => true, 'enabled' => (bool)$status['enabled'], 'last_run' => (int)$status['last_run'] );
        }
        foreach ( $summary['transports'] as $state )
            if ( $state['paused'] )
                $summary['problems'][] = array( 'level' => 'warning', 'code' => 'transport_paused',
                    'text' => ezpI18n::tr( 'cjw_newsletter/deliverability', 'The transport "%transport" is paused until %time: no newsletter is sent with it.', null,
                                           array( '%transport' => $state['transport'], '%time' => date( 'Y-m-d H:i', $state['paused_until'] ) ) ),
                    'url' => 'newsletter/throttle' );
        if ( $summary['mailin']['enabled'] && $summary['mailin']['addresses'] > 0 && !$summary['kernel_reader']['enabled']
             && !count( (array)CjwNewsletterMailbox::fetchAllActiveMailboxes() ) )
            $summary['problems'][] = array( 'level' => 'info', 'code' => 'mailin_no_reader',
                'text' => ezpI18n::tr( 'cjw_newsletter/deliverability', 'Mail-in addresses exist, but no mailbox reads them: switch on the bounce reader of the e-mail preferences or a newsletter mail account.' ),
                'url' => 'newsletter/mailin_address_list' );
        return $summary;
    }

    protected static function output( $cli, $text )
    {
        if ( is_object( $cli ) && method_exists( $cli, 'output' ) )
            $cli->output( $text );
    }
}

?>
