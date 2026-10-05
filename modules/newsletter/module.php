<?php
/**
 * File module.php
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage modules
 * @filesource
 */

$Module = array( "name" => "CJW Newsletter" );

$ViewList = array();

$ViewList['index'] = array(
    'script' => 'index.php',
    'functions' => array( 'index' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

$ViewList['job'] = array(
    'script' => 'job.php',
    'functions' => array( 'index' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'JobID' ) );

$ViewList['settings'] = array(
    'script' => 'settings.php',
    'functions' => array( 'settings' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

$ViewList['mailbox_item_list'] = array(
    'script' => 'mailbox_item_list.php',
    'functions' => array( 'mailbox_item_list' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( )
    );

$ViewList['mailbox_list'] = array(
    'script' => 'mailbox_list.php',
    'functions' => array( 'mailbox_list' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( )
    );

$ViewList['mailbox_edit'] = array(
    'script' => 'mailbox_edit.php',
    'functions' => array( 'mailbox_edit' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'MailboxId' )
    );

$ViewList['mailbox_item_view'] = array(
    'script' => 'mailbox_item_view.php',
    'functions' => array( 'mailbox_item_view' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'MailboxItemId' )
    );

$ViewList['blacklist_item_list'] = array(
    'script' => 'blacklist_item_list.php',
    'functions' => array( 'blacklist_item' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( )
    );

$ViewList['blacklist_item_add'] = array(
    'script' => 'blacklist_item_add.php',
    'functions' => array( 'blacklist_item' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( )
    );

$ViewList['blacklist_item_remove'] = array(
    'script' => 'blacklist_item_remove.php',
    'functions' => array( 'blacklist_item' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( )
);

$ViewList['import_list'] = array(
    'script' => 'import_list.php',
    'functions' => array( 'import_list' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( )
    );

$ViewList['import_view'] = array(
    'script' => 'import_view.php',
    'functions' => array( 'import_view' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'ImportId' )
    );

$ViewList['user_list'] = array(
    'script' => 'user_list.php',
    'functions' => array( 'user_list' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( )
    );

$ViewList['user_view'] = array(
    'script' => 'user_view.php',
    'functions' => array( 'user_view' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'NewsletterUserId' ) );

$ViewList['user_remove'] = array(
    'script' => 'user_remove.php',
    'functions' => array( 'user_remove' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'NewsletterUserId' ),
    'single_post_actions' => array(
            'RemoveButton' => 'Remove',
            'CancelButton' => 'Cancel',
        )
     );

$ViewList['user_edit'] = array(
    'script' => 'user_edit.php',
    'functions' => array( 'user_edit' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'ui_context' => 'edit',
    'params' => array( 'NewsletterUserId' ),
    'single_post_actions' => array(
        'StoreButton' => 'Store',
        'StoreDraftButton' => 'StoreDraft',
        'CancelButton' => 'Cancel',
        )
    );

$ViewList['user_create'] = array(
    'script' => 'user_create.php',
    'functions' => array( 'user_create', 'user_edit' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'ui_context' => 'edit',
    'params' => array( ),
    'single_post_actions' => array(
        'CreateEditButton' => 'CreateEdit',
        'CancelButton' => 'Cancel',
        )
    );


$ViewList['subscription_list'] = array(
    'script' => 'subscription_list.php',
    'functions' => array( 'subscription_list' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'NodeId' ) );

$ViewList['subscription_view'] = array(
    'script' => 'subscription_view.php',
    'functions' => array( 'subscription_view' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'SubscriptionId' ) );

$ViewList['subscription_list_csvimport'] = array(
    'script' => 'subscription_list_csvimport.php',
    'functions' => array( 'subscription_list_csvimport' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'NodeId', 'ImportId' ) );

$ViewList['subscription_list_csvexport'] = array(
    'script' => 'subscription_list_csvexport.php',
    'functions' => array( 'subscription_list_csvexport' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'NodeId' ) );


$ViewList['subscribe'] = array(
    'script' => 'subscribe.php',
    'functions' => array( 'subscribe' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array(),
    'single_post_actions' => array(
        'SubscribeButton' => 'Subscribe'
    ),
    'post_action_parameters' => array(
          'Subscribe' => array( 'BackUrl' => 'BackUrlInput'  ) )
    );

$ViewList['subscribe_infomail'] = array(
    'script' => 'subscribe_infomail.php',
    'functions' => array( 'subscribe' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array(),
    'single_post_actions' => array(
        'SubscribeInfoMailButton' => 'SubscribeInfoMail'
    ),
    'post_action_parameters' => array(
          'SubscribeInfoMail' => array( 'Email' => 'EmailInput',
                                        'BackUrl' => 'BackUrlInput'  ) )
    );


$ViewList['configure'] = array(
    'script' => 'configure.php',
    'functions' => array( 'configure' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'UserHash', 'ConfirmAll' ),
    'single_post_actions' => array(
        'ConfirmButton' => 'Confirm'
    ),
//    'post_action_parameters' => array(
//        'Subscribe' => array( 'Test' => 'TestInput' ) )
    );

$ViewList['unsubscribe'] = array(
    'script' => 'unsubscribe.php',
    'default_navigation_part' => 'eznewsletternavigationpart',
    'functions' => array( 'unsubscribe' ),
    'params' => array( 'Hash' ),
    'single_post_actions' => array(
        'SubscribeButton' => 'Unsubscribe',
        'CancelButton' => 'Cancel'
    ),
    'post_action_parameters' => array(
          'Cancel' => array( 'CancelUri' => 'CancelUriInput'  ) )
    );

$ViewList['preview'] = array(
    'script' => 'preview.php',
    'functions' => array( 'preview' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'EditionContentObjectId', 'VersionId', 'OutputFormat', 'SiteAccess', 'SkinName' ) );

$ViewList['preview_archive'] = array(
    'script' => 'preview_archive.php',
    'functions' => array( 'preview' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'EditionSendId', 'OutputFormat', 'NewsletterUserId' ) );

$ViewList['send'] = array(
    'script' => 'send.php',
    'functions' => array( 'send' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    // 'params' => array( 'ContentObjectId', 'ContentObjectVersion' ),
    'params' => array( 'NodeId' ),
    'unordered_params' => array( ),
    'single_post_actions' => array(
        'SendNewsletterTestButton' => 'SendNewsletterTest',
        'SendNewsletterButton' => 'SendNewsletter'
    ),
    'post_action_parameters' => array(
          'SendNewsletterTest' => array( 'EmailReseiverTest' => 'EmailReseiverTestInput' ),
          'SendNewsletter' => array( 'SendOutConfirmation' => 'SendOutConfirmationInput' )
     ) );

$ViewList['send_abort'] = array(
    'script' => 'send_abort.php',
    'functions' => array( 'send' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'EditionSendId' ) );

$ViewList['archive'] = array(
    'script' => 'archive.php',
    'functions' => array( 'archive' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'EditionSendHash', 'OutputFormatId', 'SubscriptionHash' ) );

// ---- 4.2.0 N1 Deliverability: views (area N1 changes only this block; the scripts answer 404 until the area writes them)

// test groups of the lists
$ViewList['test_group_list'] = array(
    'script' => 'test_group_list.php',
    'functions' => array( 'deliverability' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

// create or edit a test group
$ViewList['test_group_edit'] = array(
    'script' => 'test_group_edit.php',
    'functions' => array( 'deliverability' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'TestGroupId' ) );

// the mail-in addresses of the lists
$ViewList['mailin_address_list'] = array(
    'script' => 'mailin_address_list.php',
    'functions' => array( 'deliverability' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

// create or edit a mail-in address
$ViewList['mailin_address_edit'] = array(
    'script' => 'mailin_address_edit.php',
    'functions' => array( 'deliverability' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'MailinAddressId' ) );

// rate limits, batches and pauses of the transports
$ViewList['throttle'] = array(
    'script' => 'throttle.php',
    'functions' => array( 'deliverability' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

// CSV import into the kernel suppression list
$ViewList['suppression_import'] = array(
    'script' => 'suppression_import.php',
    'functions' => array( 'deliverability' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

// ---- end N1 views

// ---- 4.2.0 N2 Editorial: views (area N2 changes only this block; the scripts answer 404 until the area writes them)

// the recurring sends
$ViewList['schedule_list'] = array(
    'script' => 'schedule_list.php',
    'functions' => array( 'editorial' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

// create or edit a schedule
$ViewList['schedule_edit'] = array(
    'script' => 'schedule_edit.php',
    'functions' => array( 'editorial' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'ScheduleId' ) );

// the article pools
$ViewList['article_pool_list'] = array(
    'script' => 'article_pool_list.php',
    'functions' => array( 'editorial' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

// create or edit an article pool
$ViewList['article_pool_edit'] = array(
    'script' => 'article_pool_edit.php',
    'functions' => array( 'editorial' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'ArticlePoolId' ) );

// pick articles from the pool for an edition
$ViewList['article_pool'] = array(
    'script' => 'article_pool.php',
    'functions' => array( 'editorial' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'NodeId' ) );

// approve or reject an edition
$ViewList['approval'] = array(
    'script' => 'approval.php',
    'functions' => array( 'editorial or approve' ), // editors ask for the approval, approvers decide
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'EditionContentObjectId', 'Version' ) );

// ---- end N2 views

// ---- 4.2.0 N3 Rendering: views (area N3 changes only this block; the scripts answer 404 until the area writes them)

// preview an edition as a chosen subscriber
$ViewList['preview_as'] = array(
    'script' => 'preview_as.php',
    'functions' => array( 'preview' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'NodeId', 'NewsletterUserId', 'OutputFormatId' ) );

// the interests of the lists
$ViewList['interest_list'] = array(
    'script' => 'interest_list.php',
    'functions' => array( 'rendering' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( ) );

// create or edit an interest
$ViewList['interest_edit'] = array(
    'script' => 'interest_edit.php',
    'functions' => array( 'rendering' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'InterestId' ) );

// preview a skin with sample content
$ViewList['skin_preview'] = array(
    'script' => 'skin_preview.php',
    'functions' => array( 'rendering' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'SkinName', 'OutputFormat' ) );

// ---- end N3 views

// ---- 4.2.0 N4 Statistics: views (area N4 changes only this block; the scripts answer 404 until the area writes them)

// click redirect (public, signed, stored URLs only)
$ViewList['r'] = array(
    'script' => 'r.php',
    'functions' => array( 'track' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'ItemHash', 'LinkId', 'Signature' ) );

// open pixel (public, signed)
$ViewList['o'] = array(
    'script' => 'o.php',
    'functions' => array( 'track' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'ItemHash', 'Signature' ) );

// the report of a send
$ViewList['report'] = array(
    'script' => 'report.php',
    'functions' => array( 'statistics' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'EditionSendId' ) );

// statistics as CSV
$ViewList['statistics_export'] = array(
    'script' => 'statistics_export.php',
    'functions' => array( 'statistics' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'EditionSendId' ) );

// the A/B subject test of a send
$ViewList['ab_test'] = array(
    'script' => 'ab_test.php',
    'functions' => array( 'statistics' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'EditionSendId' ) );

// ---- end N4 views

// ---- 4.2.0 N5 SMS: views (area N5 changes only this block; the scripts answer 404 until the area writes them)

// send an edition by SMS
$ViewList['sms_send'] = array(
    'script' => 'sms_send.php',
    'functions' => array( 'sms' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'NodeId' ) );

// enter the SMS confirmation code (public)
$ViewList['sms_confirm'] = array(
    'script' => 'sms_confirm.php',
    'functions' => array( 'sms_public' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'UserHash' ) );

// incoming SMS of a provider (STOP keyword)
$ViewList['sms_inbound'] = array(
    'script' => 'sms_inbound.php',
    'functions' => array( 'sms_public' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'Transport' ) );

// ---- end N5 views

// ---- 4.2.0 N6 Import/export and migration: views (area N6 changes only this block; the scripts answer 404 until the area writes them)

// map the CSV columns of an import
$ViewList['import_mapping'] = array(
    'script' => 'import_mapping.php',
    'functions' => array( 'import_export' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'ImportId' ) );

// export the subscribers of a list
$ViewList['subscriber_export'] = array(
    'script' => 'subscriber_export.php',
    'functions' => array( 'import_export' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'ListContentObjectId' ) );

// the log of an eznewsletter import run
$ViewList['migration_log'] = array(
    'script' => 'migration_log.php',
    'functions' => array( 'import_export' ),
    'default_navigation_part' => 'eznewsletternavigationpart',
    'params' => array( 'RunId' ) );

// ---- end N6 views

$FunctionList['subscribe'] = array();
$FunctionList['configure'] = array();
$FunctionList['unsubscribe'] = array();
$FunctionList['subscription_list_csvimport'] = array();
$FunctionList['subscription_list_csvimport_import'] = array();
$FunctionList['subscription_list_csvexport'] = array();
$FunctionList['subscription_list'] = array();
$FunctionList['subscription_view'] = array();
$FunctionList['user_list'] = array();
$FunctionList['user_view'] = array();
$FunctionList['user_remove'] = array();
$FunctionList['user_edit'] = array();
$FunctionList['user_create'] = array();
$FunctionList['preview'] = array();
$FunctionList['archive'] = array();
$FunctionList['index'] = array();
$FunctionList['settings'] = array();
$FunctionList['send'] = array();
$FunctionList['mailbox_item_list'] = array();
$FunctionList['mailbox_item_view'] = array();
$FunctionList['mailbox_list'] = array();
$FunctionList['mailbox_edit'] = array();
$FunctionList['blacklist_item'] = array();
$FunctionList['import_list'] = array();
$FunctionList['import_view'] = array();

$FunctionList['admin'] = array(); // for display / hide of admin menue

// ---- 4.2.0 N1 Deliverability: functions
$FunctionList['deliverability'] = array();
// ---- end N1 functions
// ---- 4.2.0 N2 Editorial: functions
$FunctionList['editorial'] = array();
$FunctionList['approve'] = array();
// ---- end N2 functions
// ---- 4.2.0 N3 Rendering: functions
$FunctionList['rendering'] = array();
// ---- end N3 functions
// ---- 4.2.0 N4 Statistics: functions
$FunctionList['statistics'] = array();
$FunctionList['track'] = array();
// ---- end N4 functions
// ---- 4.2.0 N5 SMS: functions
$FunctionList['sms'] = array();
$FunctionList['sms_public'] = array();
// ---- end N5 functions
// ---- 4.2.0 N6 Import/export and migration: functions
$FunctionList['import_export'] = array();
// ---- end N6 functions

?>