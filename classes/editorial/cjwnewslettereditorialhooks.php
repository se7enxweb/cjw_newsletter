<?php
/**
 * File containing the CjwNewsletterEditorialHooks class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * The extension point handler of the area Editorial (registered in cjw_newsletter.ini [ExtensionPointSettings]
 * Handlers[]): the recurring sends run at the start of the queue-create part, an edition of a list with approval is
 * sent only when it is approved, the list attribute keeps its approval and pool settings, and the dashboard shows the
 * schedules and the waiting approvals.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterEditorialHooks
{
    /**
     * The due recurring sends make their sends, which the same queue-create run then queues.
     */
    static function queueCreateBefore( $cli )
    {
        if ( !CjwNewsletterScheduleRunner::enabled() )
            return;
        $totals = CjwNewsletterScheduleRunner::runDue( $cli, 'cron', false );
        if ( $cli && $totals['locked'] )
            $cli->output( 'Recurring sends: another run is active.' );
        else if ( $cli && $totals['due'] )
            $cli->output( 'Recurring sends: ' . $totals['sent'] . ' sent, ' . $totals['skipped'] . ' skipped, ' . $totals['failed'] . ' failed.' );
    }

    /**
     * A send of a list with approval waits until its edition version is approved.
     */
    static function sendProcessAllowed( $sendObject )
    {
        return CjwNewsletterApprovalFlow::maySend( (int)$sendObject->attribute( 'edition_contentobject_id' ),
                                                   (int)$sendObject->attribute( 'edition_contentobject_version' ),
                                                   (int)$sendObject->attribute( 'list_contentobject_id' ) );
    }

    /**
     * The send form refuses an edition of a list with approval that is not approved.
     *
     * @return string[]
     */
    static function sendFormValidate( $http, $objectVersion )
    {
        if ( !$objectVersion instanceof eZContentObjectVersion )
            return array();
        $editionId = (int)$objectVersion->attribute( 'contentobject_id' );
        if ( CjwNewsletterApprovalFlow::maySend( $editionId, (int)$objectVersion->attribute( 'version' ) ) )
            return array();
        $state = CjwNewsletterApprovalFlow::state( $editionId, (int)$objectVersion->attribute( 'version' ) );
        if ( $state === 'pending' )
            return array( ezpI18n::tr( 'cjw_newsletter/editorial', 'This edition waits for its approval. It can be sent when it is approved.' ) );
        if ( $state === 'rejected' )
            return array( ezpI18n::tr( 'cjw_newsletter/editorial', 'This edition was rejected. Change it and ask for the approval again.' ) );
        return array( ezpI18n::tr( 'cjw_newsletter/editorial', 'The list needs an approval before an edition is sent. Ask for the approval first.' ) );
    }

    /**
     * The approval and the pool of the list (the form part newsletter/editorial/list_edit_part.tpl).
     *
     * @return string[]
     */
    static function listAttributeInput( $list, $http, $prefix, $postfix, $contentObjectAttribute )
    {
        $base = $prefix . '_CjwNewsletterList_';
        $id = '_' . $contentObjectAttribute->attribute( 'id' );
        if ( !$http->hasPostVariable( $base . 'EditorialPart' . $id ) )
            return array();
        $list->setAttribute( 'approval_required', $http->hasPostVariable( $base . 'ApprovalRequired' . $id ) && (int)$http->postVariable( $base . 'ApprovalRequired' . $id ) ? 1 : 0 );
        $poolId = $http->hasPostVariable( $base . 'ArticlePoolId' . $id ) ? (int)$http->postVariable( $base . 'ArticlePoolId' . $id ) : 0;
        if ( $poolId > 0 && !CjwNewsletterArticlePool::fetch( $poolId ) )
            return array( ezpI18n::tr( 'cjw_newsletter/editorial', 'The chosen article pool does not exist.' ) );
        $list->setAttribute( 'article_pool_id', max( 0, $poolId ) );
        return array();
    }

    /**
     * The data of the dashboard block newsletter/dashboard/editorial.tpl.
     */
    static function dashboardSummary( $summary )
    {
        $schedules = CjwNewsletterSchedule::fetchVisible();
        $active = 0;
        $next = array();
        foreach ( $schedules as $schedule )
        {
            if ( $schedule->isActive() )
            {
                ++$active;
                if ( (int)$schedule->attribute( 'next_run' ) > 0 )
                    $next[] = $schedule;
            }
        }
        usort( $next, function ( $a, $b ) { return (int)$a->attribute( 'next_run' ) - (int)$b->attribute( 'next_run' ); } );
        $pending = CjwNewsletterApprovalFlow::pending( 20 );
        $failed = CjwNewsletterScheduleLog::fetchList( array( 'result' => 'failed', 'run_at' => array( '>', time() - 7 * 86400 ) ), 5, 0, array( 'run_at' => 'desc' ) );
        $problems = array();
        if ( $active && !CjwNewsletterScheduleRunner::enabled() )
            $problems[] = array( 'level' => 'warning', 'code' => 'schedules_disabled', 'url' => 'newsletter/schedule_list',
                                 'text' => ezpI18n::tr( 'cjw_newsletter/editorial', 'There are active recurring sends, but [ScheduleSettings] Schedules is disabled: the cronjob does not run them.' ) );
        if ( $failed )
            $problems[] = array( 'level' => 'error', 'code' => 'schedule_failed', 'url' => 'newsletter/schedule_list',
                                 'text' => ezpI18n::tr( 'cjw_newsletter/editorial', '%count runs of recurring sends failed in the last 7 days.', null, array( '%count' => count( $failed ) ) ) );
        foreach ( $pending as $approval )
        {
            if ( (int)$approval->attribute( 'requested' ) < time() - 2 * 86400 )
            {
                $problems[] = array( 'level' => 'hint', 'code' => 'approval_waiting', 'url' => 'newsletter/approval/' . (int)$approval->attribute( 'edition_contentobject_id' ) . '/' . (int)$approval->attribute( 'edition_contentobject_version' ),
                                     'text' => ezpI18n::tr( 'cjw_newsletter/editorial', '"%name" has waited more than two days for its approval.', null, array( '%name' => $approval->editionName() ) ) );
            }
        }
        $last = CjwNewsletterRunner::lastRun( CjwNewsletterScheduleRunner::LAST_RUN );
        return array( 'enabled' => CjwNewsletterScheduleRunner::enabled(),
                      'schedule_count' => count( $schedules ),
                      'active_count' => $active,
                      'next' => array_slice( $next, 0, 5 ),
                      'pending' => $pending,
                      'pending_count' => count( $pending ),
                      'pool_count' => CjwNewsletterArticlePool::fetchListCount(),
                      'last_run' => $last ? $last : array(),
                      'problems' => $problems );
    }
}
