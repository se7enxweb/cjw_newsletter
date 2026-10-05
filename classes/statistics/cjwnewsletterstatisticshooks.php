<?php
/**
 * File containing the CjwNewsletterStatisticsHooks class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * The extension point handler of area N4 Statistics ([ExtensionPointSettings] Handlers[]=CjwNewsletterStatisticsHooks).
 *
 * - sendQueueCreated: the send takes the list's tracking mode when it was made by a schedule; its links (output_xml and
 *   every language output of N3) are rewritten to the click redirect and the open pixel is added; the A/B samples are
 *   drawn.
 * - itemBeforeSend: the recipient's tracking key (per person only with consent) and the A/B subject.
 * - sendProcessAllowed: false while an A/B test waits for its winner.
 * - queueProcessBefore: the A/B tests whose wait is over choose their winner; the daily retention cleanup.
 * - itemSent: the A/B counts.
 * - sendFormValidate / sendFormStored: the tracking mode and the A/B test of a send.
 * - listAttributeInput: the tracking mode of a list.
 * - dashboardSummary: the numbers of the dashboard block.
 *
 * SMS sends (channel sms) and test sends are never tracked.
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterStatisticsHooks
{
    const POST = 'CjwNewsletterStatistics';

    /** @return bool the send is an e-mail send that may be tracked */
    protected static function isMailSend( $send )
    {
        return is_object( $send ) && (string)$send->attribute( 'channel' ) !== 'sms';
    }

    /** @return CjwNewsletterList|null the list of a send */
    static function listOfSend( $send )
    {
        $list = CjwNewsletterList::fetchByListObjectVersion( (int)$send->attribute( 'list_contentobject_id' ), (int)$send->attribute( 'list_contentobject_version' ) );
        return is_object( $list ) ? $list : null;
    }

    /** @return CjwNewsletterList|null the list of the edition being sent (send form) */
    static function listOfObjectVersion( $objectVersion )
    {
        if ( !is_object( $objectVersion ) )
            return null;
        foreach ( (array)$objectVersion->dataMap() as $attribute )
        {
            if ( $attribute->attribute( 'data_type_string' ) !== 'cjwnewsletteredition' )
                continue;
            $edition = $attribute->attribute( 'content' );
            $list = is_object( $edition ) ? $edition->attribute( 'list_attribute_content' ) : null;
            return is_object( $list ) ? $list : null;
        }
        return null;
    }

    // ------------------------------------------------------------------ queue creation

    static function sendQueueCreated( $send, $cli )
    {
        if ( !self::isMailSend( $send ) )
            return;
        // a send made by a schedule (never through the send form) takes the list's tracking mode
        if ( (int)$send->attribute( 'tracking_mode' ) === 0 && (int)$send->attribute( 'schedule_id' ) > 0 )
        {
            $list = self::listOfSend( $send );
            if ( $list && CjwNewsletterTracking::cleanMode( $list->attribute( 'tracking_mode' ) ) > 0 )
            {
                $send->setAttribute( 'tracking_mode', CjwNewsletterTracking::cleanMode( $list->attribute( 'tracking_mode' ) ) );
                $send->store();
            }
        }
        if ( CjwNewsletterTracking::sendMode( $send ) !== CjwNewsletterTracking::MODE_OFF )
        {
            $result = self::rewriteSend( $send );
            if ( is_object( $cli ) )
                $cli->output( "Statistics: send {$send->attribute( 'id' )}: {$result['links']} links tracked in {$result['outputs']} outputs" );
        }
        $test = CjwNewsletterAbTester::forSend( $send );
        if ( $test && (int)$test->attribute( 'status' ) === CjwNewsletterAbTester::STATUS_SAMPLING )
        {
            $r = CjwNewsletterAbTester::assignSamples( $send, $test );
            if ( is_object( $cli ) )
                $cli->output( "Statistics: send {$send->attribute( 'id' )}: A/B test with {$r['samples']} samples, {$r['rest']} wait for the winner" );
        }
    }

    /**
     * Rewrites the links of a send: its output_xml and every output of N3 (cjwnl_edition_send_output).
     *
     * @return array outputs, links
     */
    static function rewriteSend( $send )
    {
        $sendId = (int)$send->attribute( 'id' );
        $state = array();
        $outputs = 0;
        $xml = CjwNewsletterTracking::rewriteOutputXml( $send->attribute( 'output_xml' ), $sendId, $state );
        if ( $xml !== false )
        {
            $send->setAttribute( 'output_xml', $xml );
            $send->store();
            $outputs++;
        }
        if ( class_exists( 'CjwNewsletterEditionSendOutput' ) )
        {
            foreach ( CjwNewsletterEditionSendOutput::fetchList( array( 'edition_send_id' => $sendId ) ) as $output )
            {
                $xml = CjwNewsletterTracking::rewriteOutputXml( $output->attribute( 'output_xml' ), $sendId, $state );
                if ( $xml !== false )
                {
                    $output->setAttribute( 'output_xml', $xml );
                    $output->store();
                    $outputs++;
                }
            }
        }
        return array( 'outputs' => $outputs, 'links' => isset( $state['links'] ) ? count( $state['links'] ) : 0 );
    }

    // ------------------------------------------------------------------ queue processing

    static function queueProcessBefore( $cli )
    {
        CjwNewsletterTracking::resetCache();
        CjwNewsletterAbTester::resetCache();
        $now = time();
        foreach ( CjwNewsletterAbTest::fetchList( array( 'status' => array( array( CjwNewsletterAbTester::STATUS_SAMPLING, CjwNewsletterAbTester::STATUS_WAITING, CjwNewsletterAbTester::STATUS_DECIDED ) ) ) ) as $test )
            self::advanceTest( $test, $now, $cli );
        try
        {
            $r = CjwNewsletterStatisticsRetention::cleanupIfDue( $now );
            if ( $r && is_object( $cli ) )
                $cli->output( "Statistics: retention cleanup: {$r['expired_opens']} opens, {$r['expired_clicks']} clicks expired, {$r['withdrawn_users']} people without consent" );
        }
        catch ( Exception $e )
        {
            eZDebug::writeError( 'Retention cleanup: ' . $e->getMessage(), __METHOD__ );
        }
    }

    /**
     * Moves an A/B test on: samples out -> waiting; wait over -> winner; send finished -> done.
     *
     * @return int the new status
     */
    static function advanceTest( $test, $now = null, $cli = null )
    {
        $now = $now === null ? time() : (int)$now;
        $send = CjwNewsletterEditionSend::fetch( $test->attribute( 'edition_send_id' ) );
        if ( !is_object( $send ) )
            return (int)$test->attribute( 'status' );
        $status = (int)$test->attribute( 'status' );
        $sendStatus = (int)$send->attribute( 'status' );
        // the samples are out (the send has started and no sample waits)
        if ( $status === CjwNewsletterAbTester::STATUS_SAMPLING
             && in_array( $sendStatus, array( CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_STARTED, CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_FINISHED ) )
             && CjwNewsletterAbTester::samplesLeft( $test ) === 0 )
        {
            CjwNewsletterAbTester::markWaiting( $test, $now );
            $status = CjwNewsletterAbTester::STATUS_WAITING;
        }
        if ( $status === CjwNewsletterAbTester::STATUS_WAITING && CjwNewsletterAbTester::due( $test, $now ) )
        {
            $winner = CjwNewsletterAbTester::decide( $test, $now );
            $status = CjwNewsletterAbTester::STATUS_DECIDED;
            if ( $winner && is_object( $cli ) )
                $cli->output( "Statistics: send {$send->attribute( 'id' )}: A/B winner {$winner->attribute( 'variant_key' )} by {$test->attribute( 'criterion' )}" );
        }
        if ( $status === CjwNewsletterAbTester::STATUS_DECIDED && in_array( $sendStatus, array( CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_FINISHED, CjwNewsletterEditionSend::STATUS_ABORT ) ) )
        {
            $test->setAttribute( 'status', CjwNewsletterAbTester::STATUS_DONE );
            $test->setAttribute( 'modified', $now );
            $test->store();
            $status = CjwNewsletterAbTester::STATUS_DONE;
        }
        return $status;
    }

    static function sendProcessAllowed( $send )
    {
        if ( !self::isMailSend( $send ) )
            return true;
        $test = CjwNewsletterAbTester::forSend( $send );
        return !( $test && (int)$test->attribute( 'status' ) === CjwNewsletterAbTester::STATUS_WAITING );
    }

    static function itemBeforeSend( $message, $sendItem, $send, $user )
    {
        if ( !self::isMailSend( $send ) )
            return;
        // the tracking token, wherever the rewritten bodies carry the placeholder
        $bodies = (array)$message['bodies'];
        $has = false;
        foreach ( $bodies as $body )
            if ( strpos( (string)$body, CjwNewsletterTracking::PLACEHOLDER ) !== false )
                $has = true;
        if ( $has )
        {
            $values = (array)$message['values'];
            $values[CjwNewsletterTracking::PLACEHOLDER] = CjwNewsletterTracking::sendMode( $send ) !== CjwNewsletterTracking::MODE_OFF
                ? CjwNewsletterTracking::token( CjwNewsletterTracking::keyForItem( $sendItem, $send, $user ) ) : '';
            $message['values'] = $values;
        }
        // the A/B subject
        $test = CjwNewsletterAbTester::forSend( $send, true );
        if ( !$test )
            return;
        $status = (int)$test->attribute( 'status' );
        if ( ( $status === CjwNewsletterAbTester::STATUS_SAMPLING || $status === CjwNewsletterAbTester::STATUS_WAITING ) && (int)$sendItem->attribute( 'ab_variant_id' ) === 0 )
        {
            // the samples are out: the rest waits for the winner
            CjwNewsletterAbTester::markWaiting( $test );
            CjwNewsletterAbTester::resetCache();
            $message['defer'] = true;
            return;
        }
        $subject = CjwNewsletterAbTester::subjectFor( $test, $sendItem );
        if ( $subject !== null )
        {
            // the subject prefix of the newsletters ([NewsletterMailSettings] EmailSubjectPrefix), as the edition has it
            $ini = eZINI::instance( 'cjw_newsletter.ini' );
            $prefix = $ini->hasVariable( 'NewsletterMailSettings', 'EmailSubjectPrefix' ) ? trim( (string)$ini->variable( 'NewsletterMailSettings', 'EmailSubjectPrefix' ) ) : '';
            if ( $prefix !== '' && strpos( (string)$message['subject'], $prefix ) === 0 && strpos( $subject, $prefix ) !== 0 )
                $subject = $prefix . ' ' . $subject;
            $message['subject'] = $subject;
        }
    }

    static function itemSent( $sendItem, $send, $result )
    {
        $variantId = (int)$sendItem->attribute( 'ab_variant_id' );
        if ( $variantId <= 0 || !self::isMailSend( $send ) )
            return;
        if ( isset( $result['send_result'] ) && $result['send_result'] === true && empty( $result['blocked'] ) )
            eZDB::instance()->query( 'UPDATE cjwnl_ab_variant SET item_count = item_count + 1 WHERE id = ' . $variantId );
    }

    // ------------------------------------------------------------------ the send form

    /** @return array the posted values of the send form part */
    static function posted( $http )
    {
        $p = $http->hasPostVariable( self::POST ) ? (array)$http->postVariable( self::POST ) : array();
        $defaults = CjwNewsletterAbTester::defaults();
        return array(
            'present' => $http->hasPostVariable( self::POST ),
            'tracking' => isset( $p['TrackingMode'] ) ? (string)$p['TrackingMode'] : 'list',
            'ab' => !empty( $p['AbTest'] ),
            'subjects' => isset( $p['AbSubject'] ) ? array_map( 'strval', (array)$p['AbSubject'] ) : array(),
            'sample_percent' => isset( $p['AbSamplePercent'] ) ? trim( (string)$p['AbSamplePercent'] ) : (string)$defaults['sample_percent'],
            'wait_hours' => isset( $p['AbWaitHours'] ) ? str_replace( ',', '.', trim( (string)$p['AbWaitHours'] ) ) : (string)$defaults['wait_hours'],
            'criterion' => isset( $p['AbCriterion'] ) && $p['AbCriterion'] === 'click' ? 'click' : 'open' );
    }

    /** @return int the tracking mode the send gets */
    static function chosenMode( $posted, $list )
    {
        if ( $posted['tracking'] === 'list' || $posted['tracking'] === '' )
            return $list ? CjwNewsletterTracking::cleanMode( $list->attribute( 'tracking_mode' ) ) : CjwNewsletterTracking::MODE_OFF;
        return CjwNewsletterTracking::cleanMode( $posted['tracking'] );
    }

    static function sendFormValidate( $http, $objectVersion )
    {
        $posted = self::posted( $http );
        if ( !$posted['present'] )
            return array();
        $errors = array();
        if ( !in_array( $posted['tracking'], array( 'list', '0', '1', '2' ), true ) )
            $errors[] = ezpI18n::tr( 'cjw_newsletter/statistics', 'Choose a tracking mode.' );
        if ( $posted['ab'] )
            $errors = array_merge( $errors, CjwNewsletterAbTester::validate( $posted['subjects'], $posted['sample_percent'], $posted['wait_hours'],
                self::chosenMode( $posted, self::listOfObjectVersion( $objectVersion ) ) ) );
        return $errors;
    }

    static function sendFormStored( $send, $http, $objectVersion )
    {
        if ( !self::isMailSend( $send ) )
            return;
        $posted = self::posted( $http );
        $list = self::listOfSend( $send );
        $mode = $posted['present'] ? self::chosenMode( $posted, $list ) : ( $list ? CjwNewsletterTracking::cleanMode( $list->attribute( 'tracking_mode' ) ) : 0 );
        $send->setAttribute( 'tracking_mode', $mode );
        $send->store();
        if ( $posted['ab'] && !CjwNewsletterAbTester::validate( $posted['subjects'], $posted['sample_percent'], $posted['wait_hours'], $mode ) )
            CjwNewsletterAbTester::create( $send, $posted['subjects'], (int)$posted['sample_percent'], $posted['criterion'], (float)$posted['wait_hours'] );
    }

    // ------------------------------------------------------------------ the list

    static function listAttributeInput( $list, $http, $prefix, $postfix, $contentObjectAttribute )
    {
        $name = $prefix . 'TrackingMode' . $postfix;
        if ( !$http->hasPostVariable( $name ) )
            return array();
        $value = (string)$http->postVariable( $name );
        if ( !in_array( $value, array( '0', '1', '2' ), true ) )
            return array( ezpI18n::tr( 'cjw_newsletter/statistics', 'Choose a tracking mode.' ) );
        $list->setAttribute( 'tracking_mode', (int)$value );
        return array();
    }

    // ------------------------------------------------------------------ the dashboard

    static function dashboardSummary( $summary )
    {
        try
        {
            return CjwNewsletterStatisticsReport::dashboard();
        }
        catch ( Exception $e )
        {
            eZDebug::writeError( $e->getMessage(), __METHOD__ );
            return array( 'error' => true, 'problems' => array() );
        }
    }
}

?>
