<?php
/**
 * newsletter/mailin_address_list: the mail-in addresses and the last messages they got (cjw_newsletter 4.2.0, area
 * deliverability).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class MailinAddressList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );
        $tpl = templateInit();
        $lists = array();
        foreach ( \CjwNewsletterTestSend::listChoices() as $list )
            $lists[$list['id']] = $list['name'];
        $addresses = array();
        $byId = array();
        foreach ( \CjwNewsletterMailinAddress::fetchList( null, 0, 0, array( 'email' => 'asc', 'plus_tag' => 'asc' ) ) as $address )
        {
            $listId = (int)$address->attribute( 'list_contentobject_id' );
            $row = array( 'id' => (int)$address->attribute( 'id' ), 'address' => \CjwNewsletterMailin::displayAddress( $address ),
                          'action' => (string)$address->attribute( 'action' ), 'list_id' => $listId,
                          'list_name' => isset( $lists[$listId] ) ? $lists[$listId] : '', 'mailbox_id' => (int)$address->attribute( 'mailbox_id' ),
                          'is_active' => (int)$address->attribute( 'is_active' ) === 1 );
            $addresses[] = $row;
            $byId[$row['id']] = $row['address'];
        }
        $messages = array();
        $names = array( 0 => 'new', 1 => 'pending', 2 => 'done', 9 => 'rejected' );
        foreach ( \CjwNewsletterMailinMessage::fetchList( null, 25, 0, array( 'id' => 'desc' ) ) as $message )
        {
            $status = (int)$message->attribute( 'status' );
            $messages[] = array( 'created' => (int)$message->attribute( 'created' ), 'address' => isset( $byId[(int)$message->attribute( 'mailin_address_id' )] ) ? $byId[(int)$message->attribute( 'mailin_address_id' )] : '',
                                 'from' => (string)$message->attribute( 'email_from' ), 'action' => (string)$message->attribute( 'action' ),
                                 'status' => isset( $names[$status] ) ? $names[$status] : (string)$status, 'note' => (string)$message->attribute( 'note' ),
                                 'newsletter_user_id' => (int)$message->attribute( 'newsletter_user_id' ) );
        }
        $tpl->setVariable( 'addresses', $addresses );
        $tpl->setVariable( 'messages', $messages );
        $tpl->setVariable( 'enabled', \CjwNewsletterMailin::enabled() );
        $tpl->setVariable( 'plus_addressing', \CjwNewsletterMailin::plusAddressing() );
        $tpl->setVariable( 'kernel_reader', class_exists( 'expMailBounceReader' ) ? \expMailBounceReader::enabled() : false );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/deliverability/mailin_address_list.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/deliverability', 'Mail-in addresses' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
