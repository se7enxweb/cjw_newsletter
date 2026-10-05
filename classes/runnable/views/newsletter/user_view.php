<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/user_view.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/user_view.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/user_view.php:
 *
 *
 * File user_view.php
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

class UserView extends \Exponential\Runnable\ModuleView
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

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $tpl = templateInit();

        $templateFile = 'design:newsletter/user_view.tpl';

        $newsLetterUserId = (int) $Params['NewsletterUserId'];
        $newsletterUserObject = \CjwNewsletterUser::fetch( $newsLetterUserId );

        if( !is_object( $newsletterUserObject ) )
        {
            return $this->viewResult( isset( $Result ) ? $Result : null,  $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }

        $viewParameters = array();
        if( is_array( $Params['UserParameters'] ) )
        {
            $viewParameters = array_merge( $viewParameters, $Params['UserParameters'] );
        }

        // the public names of the custom fields (cjw_newsletter.ini [NewsletterUserSettings] CustomFieldMappingArray and [CustomFieldMapping_<field>] Name)
        $customFieldNames = array();
        $ini = \eZINI::instance( 'cjw_newsletter.ini' );
        $mapped = $ini->hasVariable( 'NewsletterUserSettings', 'CustomFieldMappingArray' ) ? (array)$ini->variable( 'NewsletterUserSettings', 'CustomFieldMappingArray' ) : array();
        foreach ( array( 'custom_data_text_1', 'custom_data_text_2', 'custom_data_text_3', 'custom_data_text_4' ) as $field )
        {
            if ( in_array( $field, $mapped ) && $ini->hasVariable( 'CustomFieldMapping_' . $field, 'Name' ) )
            {
                $customFieldNames[$field] = $ini->variable( 'CustomFieldMapping_' . $field, 'Name' );
            }
        }
        $tpl->setVariable( 'custom_field_names', $customFieldNames );
        $tpl->setVariable( 'view_parameters', $viewParameters );

        $tpl->setVariable( 'newsletter_user', $newsletterUserObject );

        $Result = array();

        $Result['content'] = $tpl->fetch( $templateFile );
        $Result['path'] =  array( array( 'url'  => 'newsletter/index',
                                         'text' => ezi18n( 'cjw_newsletter/path', 'Newsletter' ) ),
                                  array( 'url'  => 'newsletter/user_list',
                                         'text' => ezi18n( 'cjw_newsletter/user_list', 'Users' ) ),
                                  array( 'url'  => false,
                                         'text' => $newsletterUserObject->attribute('name') ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
