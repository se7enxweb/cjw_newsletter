<?php
/**
 * newsletter/mailin_address_edit/<id>: create, edit or remove a mail-in address (cjw_newsletter 4.2.0, area
 * deliverability).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class MailinAddressEdit extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $id = isset( $Params['MailinAddressId'] ) ? (int)$Params['MailinAddressId'] : 0;
        $address = $id > 0 ? \CjwNewsletterMailinAddress::fetch( $id ) : \CjwNewsletterMailinAddress::create( array( 'action' => 'both', 'is_active' => 1 ) );
        if ( !$address )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $back = '/newsletter/mailin_address_list';
        $errors = array();
        $confirmRemove = false;

        if ( $http->hasPostVariable( 'CancelButton' ) )
            return $this->viewResult( null, $module->redirectTo( $back ) );
        if ( $http->hasPostVariable( 'RemoveButton' ) && $id > 0 )
            $confirmRemove = true;
        else if ( $http->hasPostVariable( 'ConfirmRemoveButton' ) && $id > 0 )
        {
            $address->remove();
            \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/deliverability', 'The mail-in address was removed.' ) );
            return $this->viewResult( null, $module->redirectTo( $back ) );
        }
        else if ( $http->hasPostVariable( 'StoreButton' ) )
        {
            $input = array();
            foreach ( array( 'email', 'plus_tag', 'list_contentobject_id', 'action', 'mailbox_id', 'is_active' ) as $field )
                $input[$field] = $http->hasPostVariable( $field ) ? (string)$http->postVariable( $field ) : '';
            $errors = \CjwNewsletterMailin::storeAddress( $address, $input );
            if ( !$errors )
            {
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/deliverability', 'The mail-in address %address was stored.', null,
                                                                   array( '%address' => \CjwNewsletterMailin::displayAddress( $address ) ) ) );
                return $this->viewResult( null, $module->redirectTo( $back ) );
            }
        }

        $mailboxes = array();
        foreach ( (array)\CjwNewsletterMailbox::fetchAllMailboxes() as $mailbox )
            if ( is_object( $mailbox ) )
                $mailboxes[] = array( 'id' => (int)$mailbox->attribute( 'id' ), 'name' => (string)$mailbox->attribute( 'email' ) . ' (' . (string)$mailbox->attribute( 'server' ) . ')' );

        include_once( 'kernel/common/template.php' );
        $tpl = \eZTemplate::factory();
        $values = array();
        foreach ( array( 'id', 'email', 'plus_tag', 'list_contentobject_id', 'action', 'mailbox_id', 'is_active' ) as $field )
            $values[$field] = $address->attribute( $field );
        $values['id'] = (int)$values['id'];
        $lists = \CjwNewsletterTestSend::listChoices();
        // a new address belongs to a list (an address for every list only takes unsubscribe mails)
        if ( !$values['id'] && !$http->hasPostVariable( 'StoreButton' ) && $lists )
            $values['list_contentobject_id'] = $lists[0]['id'];
        $tpl->setVariable( 'address', $values );
        $tpl->setVariable( 'display', \CjwNewsletterMailin::displayAddress( $address ) );
        $tpl->setVariable( 'lists', $lists );
        $tpl->setVariable( 'mailboxes', $mailboxes );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'confirm_remove', $confirmRemove );
        $tpl->setVariable( 'plus_addressing', \CjwNewsletterMailin::plusAddressing() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/deliverability/mailin_address_edit.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => 'newsletter/mailin_address_list', 'text' => \ezpI18n::tr( 'cjw_newsletter/deliverability', 'Mail-in addresses' ) ),
                                 array( 'url' => false, 'text' => $id ? \CjwNewsletterMailin::displayAddress( $address ) : \ezpI18n::tr( 'cjw_newsletter/deliverability', 'New mail-in address' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
