<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/send.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/send.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/send.php:
 *
 *
 * File send.php
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @subpackage modules
 * @filesource
 *
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class Send extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        require_once( 'kernel/common/i18n.php' );
        include_once( 'kernel/common/template.php' );

        $module = $Params["Module"];
        $http = \eZHTTPTool::instance();

        $viewParameters = array();

        $message_warning = '';
        $message_feedback = '';
        $templateFile = 'design:newsletter/send.tpl';
        $pathString = ezi18n( 'cjw_newsletter/send', 'Send' );

        if( isSet( $Params['NodeId'] ) )
        {
            $nodeId = $Params['NodeId'];
        }
        elseif( $http->hasVariable('ContentNodeID') )
        {
            $nodeId = (int) $http->variable('ContentNodeID');
        }
        else
        {
            $nodeId = null;
        }

        $tpl = templateInit();
        $tpl->setVariable( 'view_parameters', $viewParameters );
        $tpl->setVariable( 'node_id', $nodeId );

        $node = $nodeId ? \eZContentObjectTreeNode::fetch( (int)$nodeId ) : null;

        if ( !is_object( $node ) )
        {
            // return $module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        $objectVersion = $node->attribute( 'contentobject_version_object' );

        if( is_object( $objectVersion ) )
        {
            $tpl->setVariable( 'object_version', $objectVersion );
        }
        else
        {
            $tpl->setVariable( 'error', 'No Object found!' );
        }


        // send newsletter test emails
        if ( $module->isCurrentAction( 'SendNewsletterTest' ) )
        {
            $templateFile = 'design:newsletter/send_newsletter_test_result.tpl';
            $pathString = ezi18n( 'cjw_newsletter/send', 'Send test newsletter' );

            if ( $module->hasActionParameter('EmailReseiverTest') )
            {
                $emailReceiverTest = $module->actionParameter('EmailReseiverTest');
                $newsletterMail = new \CjwNewsletterMail();

                $forceSettingImageIncludeTo = -1;
                if( $http->hasVariable( 'ForceSettingImageIncludeTo' ) )
                {
                    $forceSetting = $http->variable( 'ForceSettingImageIncludeTo' );
                    if ( $forceSetting === '0' )
                    {
                        $forceSettingImageIncludeTo = 0;
                    }
                    elseif ( $forceSetting === '1' )
                    {
                        $forceSettingImageIncludeTo = 1;
                    }
                    elseif ( $forceSetting === '-1' )
                    {
                        $forceSettingImageIncludeTo = -1;
                    }
                }

                $testSendResultArray = $newsletterMail->sendNewsletterTestMail( $objectVersion, $emailReceiverTest, $forceSettingImageIncludeTo );
                $tpl->setVariable( 'email_test_send_result_array', $testSendResultArray );
                //$tpl->setVariable( 'message', $message );
            }
        }

        // Newsletter versenden
        else if ( $module->isCurrentAction( 'SendNewsletter' ) )
        {
            $editionDataMap = $objectVersion->attribute( 'data_map' );

            // TODO check if $node is a cjw_newsletter_edition object

            $attributeEdition = $editionDataMap['newsletter_edition'];
            $attributeEditionContent = $attributeEdition->attribute( 'content' );

            if ( $attributeEditionContent->attribute('is_process') )
            {
                $message_warning = ezi18n( 'cjw_newsletter/datatype/cjwnewsletteredition', "The current edition is already in sending process - to create a new version please stop it first", null , array(  ) );
            }
            elseif ( $attributeEditionContent->attribute( 'is_archive' ) )
            {
                $message_warning = ezi18n( 'cjw_newsletter/datatype/cjwnewsletteredition', "The current edition was already send and is in archive!", null , array(  ) );
            }
            // send out newsletter
            else
            {
                $sendNewsletterOutConfirm = false;
                $sendNewsletterOutDatetime = null;

                if ( $module->hasActionParameter( 'SendOutConfirmation' ) )
                {
                    // validate schedule datetime
                    $theFormData = array( 'year'     => isset( $_POST['CJWNL_datetime_year_noid'] ) ? $_POST['CJWNL_datetime_year_noid'] : 0,
                                          'month'    => isset( $_POST['CJWNL_datetime_month_noid'] ) ? $_POST['CJWNL_datetime_month_noid'] : 0,
                                          'day'      => isset( $_POST['CJWNL_datetime_day_noid'] ) ? $_POST['CJWNL_datetime_day_noid'] : 0,
                                          'hour'     => isset( $_POST['CJWNL_datetime_hour_noid'] ) ? $_POST['CJWNL_datetime_hour_noid'] : 0,
                                          'minute'   => isset( $_POST['CJWNL_datetime_minute_noid'] ) ? $_POST['CJWNL_datetime_minute_noid'] : 0 );
                    $theFormData = array_map('intval', $theFormData);

                    $theDateIsValid = ( \eZDateTimeValidator::validateDate( $theFormData['day'],
                                                                           $theFormData['month'],
                                                                           $theFormData['year'] ) != \eZInputValidator::STATE_INVALID );

                    $theTimeIsValid = ( \eZDateTimeValidator::validateTime( $theFormData['hour'],
                                                                           $theFormData['minute'] ) != \eZInputValidator::STATE_INVALID );

                    if ( $theDateIsValid && $theTimeIsValid )
                    {
                        $sendNewsletterOutDatetime = mktime( $theFormData['hour'],
                                                             $theFormData['minute'],
                                                             0,
                                                             $theFormData['month'],
                                                             $theFormData['day'],
                                                             $theFormData['year'] );
                    }
                    else
                    {
                        $message_warning = ezi18n( 'cjw_newsletter/datatype/cjwnewsletteredition', "The schedule date or time is invalid!", null , array(  ) );
                    }
                    $sendNewsletterOutConfirm = true;
                }


                // to we send out the newsletter
                if ( $sendNewsletterOutDatetime && $sendNewsletterOutConfirm === true )
                {
                    $createResult = $attributeEditionContent->createNewsletterSendObject( $sendNewsletterOutDatetime );
                    // redirect to current url   newsletter/send/ nodeId to loose the post variables
                    // return $module->redirectCurrent();

                    $redirectUri = "/content/view/full/$nodeId";
                    return $this->viewResult( isset( $Result ) ? $Result : null,  $module->redirectTo( $redirectUri ) );
                }
            }

        }


        $has_message = false;
        $tpl->setVariable( 'message_warning', $message_warning );
        $tpl->setVariable( 'message_feedback', $message_feedback );
        if( $message_warning !== '' || $message_feedback !== '' )
        {
            $has_message = true;
        }
        $tpl->setVariable( 'has_message', $has_message );

        $listNode = $node->attribute( 'parent' );
        $systemNode = $listNode->attribute( 'parent' );

        $Result = array();

        $Result['content'] = $tpl->fetch( $templateFile );

        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),

                                  array( 'url'  => $systemNode->attribute( 'url_alias' ),
                                         'text' => $systemNode->attribute( 'name' ) ),

                                  array( 'url'  => $listNode->attribute( 'url_alias' ),
                                         'text' => $listNode->attribute( 'name' ) ),

                                  array( 'url'  => $node->attribute( 'url_alias' ),
                                         'text' => $node->attribute( 'name' ) ),

                                  array( 'url'  => false,
                                         'text' => $pathString ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
