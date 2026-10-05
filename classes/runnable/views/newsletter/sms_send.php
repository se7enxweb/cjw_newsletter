<?php
/**
 * Sends an edition by SMS: newsletter/sms_send/<edition node id>.
 *
 * The page shows who would get the SMS (the subscribers of the list with a confirmed number), the text with its
 * length and parts, a test SMS to a typed number, and the SMS sends of the edition. "Send by SMS" makes a send with
 * channel sms; the next queue run makes one SMS per subscriber who allows it and sends them within the throttle.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SmsSend extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $nodeId = isset( $Params['NodeId'] ) ? (int)$Params['NodeId'] : 0;
        $node = $nodeId > 0 ? \eZContentObjectTreeNode::fetch( $nodeId ) : null;
        if ( !is_object( $node ) || !$node->canRead() )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
        $object = $node->attribute( 'object' );
        $dataMap = $object->attribute( 'data_map' );
        $edition = null;
        foreach ( $dataMap as $attribute )
            if ( $attribute->attribute( 'data_type_string' ) === 'cjwnewsletteredition' )
                $edition = $attribute->attribute( 'content' );
        if ( !is_object( $edition ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );
        $list = $edition->attribute( 'list_attribute_content' );

        include_once( 'kernel/common/template.php' );
        $http = \eZHTTPTool::instance();
        $tpl = \eZTemplate::factory();
        $i18n = 'cjw_newsletter/sms';
        $text = $http->hasPostVariable( 'SmsText' ) ? str_replace( "\r\n", "\n", (string)$http->postVariable( 'SmsText' ) ) : '';
        $testPhone = $http->hasPostVariable( 'SmsTestPhone' ) ? trim( (string)$http->postVariable( 'SmsTestPhone' ) ) : '';
        $errors = array();

        if ( $http->hasPostVariable( 'SmsBackButton' ) )
            return $this->viewResult( null, $module->redirectTo( '/content/view/full/' . $nodeId ) );

        if ( $http->hasPostVariable( 'SmsTestButton' ) )
        {
            $errors = \CjwNewsletterSms::validateText( $text );
            if ( !$errors )
            {
                $result = \CjwNewsletterSms::sendTest( $testPhone, $text );
                if ( $result['ok'] )
                    \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $i18n, 'The test SMS was sent to %phone.', null, array( '%phone' => $result['phone'] ) ) );
                else
                    $errors[] = $result['error'];
            }
        }
        elseif ( $http->hasPostVariable( 'SmsSendButton' ) )
        {
            if ( !\CjwNewsletterSms::enabled() )
                $errors[] = \ezpI18n::tr( $i18n, 'SMS are switched off in the settings ([SmsSettings] Sms).' );
            if ( !$errors )
            {
                $send = \CjwNewsletterSms::createSend( $edition, $text );
                if ( is_object( $send ) )
                {
                    \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( $i18n, 'The SMS send was created. The next run of the queue sends it.' ) );
                    return $this->viewResult( null, $module->redirectTo( '/newsletter/sms_send/' . $nodeId ) );
                }
                $errors[] = (string)$send;
            }
        }

        $db = \eZDB::instance();
        $listObjectId = is_object( $list ) ? (int)$list->attribute( 'contentobject_id' ) : 0;
        $approved = (int)\CjwNewsletterSubscription::STATUS_APPROVED;
        $count = function ( $sql ) use ( $db ) {
            $r = $db->arrayQuery( $sql );
            return isset( $r[0]['c'] ) ? (int)$r[0]['c'] : 0;
        };
        $audience = array(
            'subscribers' => $count( 'SELECT COUNT(DISTINCT s.newsletter_user_id) AS c FROM cjwnl_subscription s WHERE s.list_contentobject_id = ' . $listObjectId . ' AND s.status = ' . $approved ),
            'confirmed' => $count( 'SELECT COUNT(DISTINCT s.newsletter_user_id) AS c FROM cjwnl_subscription s, cjwnl_user u WHERE u.id = s.newsletter_user_id AND s.list_contentobject_id = ' . $listObjectId
                                   . ' AND s.status = ' . $approved . ' AND u.phone_status = ' . \CjwNewsletterSms::PHONE_CONFIRMED ),
            'pending' => $count( 'SELECT COUNT(DISTINCT s.newsletter_user_id) AS c FROM cjwnl_subscription s, cjwnl_user u WHERE u.id = s.newsletter_user_id AND s.list_contentobject_id = ' . $listObjectId
                                 . ' AND s.status = ' . $approved . ' AND u.phone_status = ' . \CjwNewsletterSms::PHONE_PENDING ) );

        $sends = array();
        foreach ( (array)$db->arrayQuery( "SELECT id, status, created FROM cjwnl_edition_send WHERE channel = 'sms' AND edition_contentobject_id = " . (int)$object->attribute( 'id' ) . ' ORDER BY id DESC' ) as $row )
        {
            $id = (int)$row['id'];
            $stat = array();
            foreach ( array( 'waiting' => \CjwNewsletterSms::STATUS_NEW, 'sent' => \CjwNewsletterSms::STATUS_SENT, 'failed' => \CjwNewsletterSms::STATUS_FAILED, 'aborted' => \CjwNewsletterSms::STATUS_ABORTED ) as $key => $status )
                $stat[$key] = \CjwNewsletterSmsMessage::fetchListCount( array( 'edition_send_id' => $id, 'status' => $status ) );
            $sends[] = array( 'id' => $id, 'status' => (int)$row['status'], 'created' => (int)$row['created'], 'text' => \CjwNewsletterSms::sendText( $id ) ) + $stat;
        }
        $hint = \CjwNewsletterSms::stopHint();
        if ( $text === '' && !$http->hasPostVariable( 'SmsText' ) )
            $text = (string)$object->attribute( 'name' );

        $tpl->setVariable( 'node', $node );
        $tpl->setVariable( 'node_id', $nodeId );
        $tpl->setVariable( 'list_object_id', $listObjectId );
        $tpl->setVariable( 'list_sms_enabled', is_object( $list ) && (int)$list->attribute( 'sms_enabled' ) === 1 );
        $tpl->setVariable( 'list_sms_sender', is_object( $list ) ? (string)$list->attribute( 'sms_sender' ) : '' );
        $tpl->setVariable( 'sms_enabled', \CjwNewsletterSms::enabled() );
        $tpl->setVariable( 'transport', \CjwNewsletterSms::summary() );
        $tpl->setVariable( 'text', $text );
        $tpl->setVariable( 'test_phone', $testPhone );
        $tpl->setVariable( 'segments', \CjwNewsletterSms::segments( trim( $text ) . ( $hint !== '' ? "\n" . $hint : '' ) ) );
        $tpl->setVariable( 'max_segments', \CjwNewsletterSms::maxSegments() );
        $tpl->setVariable( 'stop_hint', $hint );
        $tpl->setVariable( 'placeholders', method_exists( '\CjwNewsletterPlaceholders', 'names' ) ? \CjwNewsletterPlaceholders::names()
                                           : array( '[[name]]', '[[salutation_name]]', '[[first_name]]', '[[last_name]]' ) );
        $tpl->setVariable( 'audience', $audience );
        $tpl->setVariable( 'sends', $sends );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/sms/send.tpl' );
        $Result['node_id'] = $nodeId;
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => 'content/view/full/' . $nodeId, 'text' => (string)$object->attribute( 'name' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( $i18n, 'Send by SMS' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
