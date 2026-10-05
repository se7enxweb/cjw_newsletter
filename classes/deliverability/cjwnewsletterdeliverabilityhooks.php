<?php
/**
 * File containing the CjwNewsletterDeliverabilityHooks class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * The extension point handler of the area "deliverability" (cjw_newsletter.ini [ExtensionPointSettings]
 * Handlers[]=CjwNewsletterDeliverabilityHooks, see CjwNewsletterExtensionPoints):
 *
 *  - sendProcessAllowed: a send whose transport is paused waits;
 *  - itemBeforeSend: a batch that has taken what the rate limits allow stops the send for this run (defer);
 *  - itemSent: the counts of the batch, and the soft-bounce count of a user who got a mail again is reset;
 *  - testSendRecipients: the addresses of the chosen test group;
 *  - sendFormStored: the transport whose limits apply is kept on the send;
 *  - dashboardSummary: throttle, batches, bounces, suppressions, test groups and mail-in for the dashboard.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterDeliverabilityHooks
{
    static function sendProcessAllowed( $sendObject )
    {
        $transport = CjwNewsletterDeliverability::transportOf( $sendObject );
        return CjwNewsletterThrottle::pausedUntil( $transport ) <= CjwNewsletterThrottle::now();
    }

    static function itemBeforeSend( $message, $sendItem, $sendObject, $user )
    {
        if ( CjwNewsletterDeliverability::batchFull( $sendObject ) )
            $message['defer'] = true;
    }

    static function itemSent( $sendItem, $sendObject, $result )
    {
        CjwNewsletterDeliverability::itemHandled( $sendItem, $sendObject, $result );
        if ( is_object( $sendItem ) && is_array( $result ) && isset( $result['send_result'] ) && $result['send_result'] === true )
        {
            $db = eZDB::instance();
            $db->query( 'UPDATE cjwnl_user SET soft_bounce_count = 0 WHERE id = ' . (int)$sendItem->attribute( 'newsletter_user_id' ) . ' AND soft_bounce_count > 0' );
        }
    }

    static function testSendRecipients( $emails, $http, $objectVersion )
    {
        return CjwNewsletterTestSend::recipients( $emails, $http, $objectVersion );
    }

    static function sendFormStored( $sendObject, $http, $objectVersion )
    {
        if ( !is_object( $sendObject ) || (string)$sendObject->attribute( 'throttle_transport' ) !== '' )
            return;
        $sendObject->setAttribute( 'throttle_transport', CjwNewsletterThrottle::name( '' ) );
        $sendObject->store();
    }

    static function dashboardSummary( $summary )
    {
        return CjwNewsletterDeliverability::summary();
    }
}

?>
