<?php
/**
 * The code of extension/cjw_newsletter/modules/newsletter/index.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/modules/newsletter/index.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/cjw_newsletter/modules/newsletter/index.php:
 *
 *
 * File index.php
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

class Index extends \Exponential\Runnable\ModuleView
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

        $module = $Params[ 'Module' ];
        $http = \eZHTTPTool::instance();
        $user = \eZUser::currentUser();
        $canAccess = function ( $function ) use ( $user )
        {
            $access = $user->hasAccessTo( 'newsletter', $function );
            return $access['accessWord'] != 'no';
        };
        $canSend = $canAccess( 'send' );
        $canMailbox = $canAccess( 'mailbox_item_list' );
        $canAdmin = $canAccess( 'admin' );

        // the "run now" buttons start the console command in the background and show its progress on this page
        $actions = array( 'RunQueueButton' => array( 'queue', array(), $canSend ),
                          'RunQueueDryButton' => array( 'queue', array( '--dry-run' ), $canSend ),
                          'RunMailboxButton' => array( 'mailbox', array(), $canMailbox ),
                          'RepairButton' => array( 'repair', array(), $canAdmin ) );
        foreach ( $actions as $button => $action )
        {
            if ( $http->hasPostVariable( $button ) && !\CjwNewsletterDashboard::missingTables() )
            {
                if ( !$action[2] )
                {
                    \CjwNewsletterUI::notice( 'error', ezpI18n::tr( 'extension/cjw_newsletter', 'You do not have the permission for this action.' ) );
                    return $this->viewResult( null, $module->redirectToView( 'index' ) );
                }
                $error = '';
                $jobID = \CjwNewsletterJob::start( $action[0], $action[1], $error );
                if ( !$jobID )
                {
                    \CjwNewsletterUI::notice( 'error', ezpI18n::tr( 'extension/cjw_newsletter', 'The run could not be started: %reason', null, array( '%reason' => $error ) ) );
                    return $this->viewResult( null, $module->redirectToView( 'index' ) );
                }
                return $this->viewResult( null, $module->redirectToView( 'index', array(), array(), array( 'job' => $jobID ) ) );
            }
        }

        $viewParameters = array( 'offset' => 0,
                                 'namefilter' => '' );

        $userParameters = $Params['UserParameters'];
        $viewParameters = array_merge( $viewParameters, $userParameters );

        $tpl = templateInit();
        $tpl->setVariable( 'view_parameters', $viewParameters );
        $tpl->setVariable( 'summary', \CjwNewsletterDashboard::summary() );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );
        $tpl->setVariable( 'job_id', isset( $userParameters['job'] ) && \CjwNewsletterJob::isID( $userParameters['job'] ) ? $userParameters['job'] : '' );
        $tpl->setVariable( 'can_send', $canSend );
        $tpl->setVariable( 'can_mailbox', $canMailbox );
        $tpl->setVariable( 'can_admin', $canAdmin );

        $tpl->setVariable( 'current_siteaccess', $viewParameters );
        $Result = array();
        $Result['content'] = $tpl->fetch( "design:newsletter/index.tpl" );
        $Result['path'] = array( array( 'url'  => false,
                                        'text' => ezi18n( 'cjw_newsletter', 'Newsletter' ) ),
                                 array( 'url'  => false,
                                        'text' => ezi18n( 'cjw_newsletter/index', 'Dashboard' ) ) );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
