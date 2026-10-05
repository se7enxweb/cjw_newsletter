<?php
/**
 * The A/B subject test of a send (cjw_newsletter 4.2.0, area N4 Statistics): newsletter/ab_test/<edition send id>.
 * Shows the variants with their sent mails, opens, clicks and rates, the state and when the winner is chosen; an
 * editor may choose the winner now ("ChooseWinnerButton") or cancel the test ("CancelTestButton", the rest of the
 * list then gets the edition's own subject).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class AbTest extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );
        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $sendId = isset( $Params['EditionSendId'] ) ? (int)$Params['EditionSendId'] : 0;
        $send = $sendId > 0 ? \CjwNewsletterEditionSend::fetch( $sendId ) : null;
        $test = is_object( $send ) ? \CjwNewsletterAbTester::forSend( $send ) : null;
        if ( !$test )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_FOUND, 'kernel' ) );

        $access = \eZUser::currentUser()->hasAccessTo( 'newsletter', 'send' );
        $canSend = $access['accessWord'] !== 'no';
        if ( $http->hasPostVariable( 'ChooseWinnerButton' ) || $http->hasPostVariable( 'CancelTestButton' ) )
        {
            if ( !$canSend )
                \CjwNewsletterUI::notice( 'error', \ezpI18n::tr( 'extension/cjw_newsletter', 'You do not have the permission for this action.' ) );
            else if ( $http->hasPostVariable( 'ChooseWinnerButton' ) )
            {
                $winner = \CjwNewsletterAbTester::decide( $test );
                \CjwNewsletterUI::notice( $winner ? 'feedback' : 'error', $winner
                    ? \ezpI18n::tr( 'cjw_newsletter/statistics', 'Variant %key won. The rest of the list gets its subject with the next run of the mail queue.', null, array( '%key' => $winner->attribute( 'variant_key' ) ) )
                    : \ezpI18n::tr( 'cjw_newsletter/statistics', 'The winner could not be chosen.' ) );
            }
            else
            {
                $done = \CjwNewsletterAbTester::cancel( $test );
                \CjwNewsletterUI::notice( $done ? 'feedback' : 'error', $done
                    ? \ezpI18n::tr( 'cjw_newsletter/statistics', 'The test was cancelled. The rest of the list gets the edition\'s own subject.' )
                    : \ezpI18n::tr( 'cjw_newsletter/statistics', 'The test is already over.' ) );
            }
            return $this->viewResult( null, $module->redirectToView( 'ab_test', array( $sendId ) ) );
        }

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'test', \CjwNewsletterAbTester::summary( $test, $send ) );
        $tpl->setVariable( 'report', \CjwNewsletterStatisticsReport::send( $send ) );
        $tpl->setVariable( 'can_send', $canSend );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );
        $Result = array( 'content' => $tpl->fetch( 'design:newsletter/statistics/ab_test.tpl' ),
            'path' => array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter', 'Newsletter' ) ),
                             array( 'url' => 'newsletter/report/' . $sendId, 'text' => \ezpI18n::tr( 'cjw_newsletter/statistics', 'Statistics' ) ),
                             array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/statistics', 'A/B test' ) ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
