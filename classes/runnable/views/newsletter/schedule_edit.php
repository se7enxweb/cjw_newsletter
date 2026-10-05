<?php
/**
 * Create or edit a recurring send: the list, the mode (copy a template edition or send the latest unsent edition),
 * when (chosen weekdays, weekly, monthly, the time and the time zone), the auto-fill from an article pool, the
 * condition and the state.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class ScheduleEdit extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $tr = 'cjw_newsletter/editorial';
        $id = isset( $Params['ScheduleId'] ) ? (int)$Params['ScheduleId'] : 0;

        if ( $id > 0 )
        {
            $schedule = \CjwNewsletterSchedule::fetch( $id );
            if ( !$schedule )
                return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        }
        else
        {
            $schedule = \CjwNewsletterSchedule::create( array( 'recurrence_type' => 'w', 'recurrence_value' => '1', 'send_time' => 8 * 3600,
                                                               'mode' => 'latest', 'skip_if_empty' => 1 ) );
        }

        if ( $http->hasPostVariable( 'DiscardButton' ) )
            return $this->viewResult( null, $module->redirectTo( '/newsletter/schedule_list' ) );

        $lists = \CjwNewsletterEditorialUI::lists();
        $editions = \CjwNewsletterEditorialUI::editions();
        $errors = array();

        if ( $http->hasPostVariable( 'StoreButton' ) )
        {
            $data = self::readForm( $http, $lists, $editions, $errors );
            foreach ( $data as $field => $value )
                $schedule->setAttribute( $field, $value );
            if ( !$errors )
            {
                if ( $schedule->nextRunAfter( time() ) === 0 )
                    $errors['recurrence'] = \ezpI18n::tr( $tr, 'Choose at least one day.' );
            }
            if ( !$errors )
            {
                $now = time();
                if ( $id <= 0 )
                {
                    $schedule->setAttribute( 'creator_contentobject_id', (int)\eZUser::currentUserID() );
                    $schedule->setAttribute( 'created', $now );
                }
                $schedule->setAttribute( 'modified', $now );
                $schedule->updateNextRun( $now );
                $schedule->store();
                \CjwNewsletterUI::notice( 'feedback', $schedule->isActive()
                    ? \ezpI18n::tr( $tr, 'The recurring send was saved. Next run: %time.', null, array( '%time' => \CjwNewsletterEditorialUI::formatTime( $schedule->attribute( 'next_run' ), $schedule->timezoneName() ) ) )
                    : \ezpI18n::tr( $tr, 'The recurring send was saved. It is paused.' ) );
                return $this->viewResult( null, $module->redirectTo( '/newsletter/schedule_list' ) );
            }
        }

        $preview = array();
        $at = time();
        if ( $schedule->nextRunAfter( $at ) > 0 )
        {
            for ( $i = 0; $i < 4; $i++ )
            {
                $at = $schedule->nextRunAfter( $at );
                if ( !$at )
                    break;
                $preview[] = \CjwNewsletterEditorialUI::formatTime( $at, $schedule->timezoneName() );
            }
        }

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'schedule', $schedule );
        $tpl->setVariable( 'schedule_id', $id );
        $tpl->setVariable( 'lists', $lists );
        $tpl->setVariable( 'editions', $editions );
        $tpl->setVariable( 'pools', \CjwNewsletterArticlePool::fetchList( null, 0, 0, array( 'name' => 'asc' ) ) );
        $tpl->setVariable( 'condition_handlers', \CjwNewsletterScheduleRunner::conditionHandlers() );
        $tpl->setVariable( 'weekday_names', \CjwNewsletterSchedule::weekdayNames() );
        $tpl->setVariable( 'timezones', \DateTimeZone::listIdentifiers() );
        $tpl->setVariable( 'default_timezone', \CjwNewsletterSchedule::defaultTimezone() !== '' ? \CjwNewsletterSchedule::defaultTimezone() : date_default_timezone_get() );
        $tpl->setVariable( 'preview', $preview );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'enabled', \CjwNewsletterScheduleRunner::enabled() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/editorial/schedule_edit.tpl' );
        $Result['path'] = \CjwNewsletterEditorialUI::path( array( 'newsletter/schedule_list' => \ezpI18n::tr( $tr, 'Recurring sends' ),
            $id > 0 ? \ezpI18n::tr( $tr, 'Edit' ) : \ezpI18n::tr( $tr, 'New recurring send' ) ) );
        return $this->viewResult( $Result, null );
    }

    /**
     * The posted form, every value checked and cast.
     *
     * @return array field => value
     */
    static function readForm( $http, $lists, $editions, &$errors )
    {
        $tr = 'cjw_newsletter/editorial';
        $data = array();
        $listId = \CjwNewsletterEditorialUI::postedInt( $http, 'ListId' );
        if ( !isset( $lists[$listId] ) )
            $errors['list'] = \ezpI18n::tr( $tr, 'Choose the newsletter list.' );
        $data['list_contentobject_id'] = $listId;

        $mode = \CjwNewsletterEditorialUI::postedText( $http, 'Mode', 20 );
        $data['mode'] = $mode === 'copy' ? 'copy' : 'latest';
        $template = \CjwNewsletterEditorialUI::postedInt( $http, 'TemplateEditionId' );
        if ( $data['mode'] === 'copy' )
        {
            if ( !isset( $editions[$template] ) )
                $errors['template'] = \ezpI18n::tr( $tr, 'Choose the edition that is copied.' );
            else if ( isset( $lists[$listId] ) && $editions[$template]['list_id'] !== $listId )
                $errors['template'] = \ezpI18n::tr( $tr, 'The template edition must belong to the chosen list.' );
        }
        $data['template_edition_contentobject_id'] = $data['mode'] === 'copy' ? max( 0, $template ) : 0;

        $type = \CjwNewsletterEditorialUI::postedText( $http, 'RecurrenceType', 1 );
        $data['recurrence_type'] = in_array( $type, array( 'd', 'w', 'm' ), true ) ? $type : 'w';
        if ( $data['recurrence_type'] === 'd' )
        {
            $days = array();
            foreach ( \CjwNewsletterEditorialUI::postedIds( $http, 'Weekdays' ) as $day )
                if ( $day <= 7 )
                    $days[] = $day;
            sort( $days );
            if ( !$days )
                $errors['recurrence'] = \ezpI18n::tr( $tr, 'Choose at least one day.' );
            $data['recurrence_value'] = implode( ',', $days );
        }
        else if ( $data['recurrence_type'] === 'w' )
        {
            $day = \CjwNewsletterEditorialUI::postedInt( $http, 'Weekday', 1 );
            $data['recurrence_value'] = (string)( $day >= 1 && $day <= 7 ? $day : 1 );
        }
        else
        {
            $day = \CjwNewsletterEditorialUI::postedInt( $http, 'MonthDay', 1 );
            $data['recurrence_value'] = (string)( $day >= 1 && $day <= 31 ? $day : 1 );
        }

        $time = \CjwNewsletterEditorialUI::parseTime( \CjwNewsletterEditorialUI::postedText( $http, 'SendTime', 5 ) );
        if ( $time === false )
            $errors['time'] = \ezpI18n::tr( $tr, 'Enter the time as HH:MM, for example 08:30.' );
        $data['send_time'] = $time === false ? 0 : $time;

        $zone = \CjwNewsletterEditorialUI::postedText( $http, 'Timezone', 64 );
        if ( $zone !== '' && !\CjwNewsletterSchedule::isTimezone( $zone ) )
            $errors['timezone'] = \ezpI18n::tr( $tr, 'Choose a time zone of the list.' );
        $data['timezone'] = \CjwNewsletterSchedule::isTimezone( $zone ) ? $zone : '';

        $data['auto_fill'] = $data['mode'] === 'copy' && \CjwNewsletterEditorialUI::postedInt( $http, 'AutoFill' ) ? 1 : 0;
        $data['skip_if_empty'] = \CjwNewsletterEditorialUI::postedInt( $http, 'SkipIfEmpty' ) ? 1 : 0;
        $pool = \CjwNewsletterEditorialUI::postedInt( $http, 'ArticlePoolId' );
        if ( $pool > 0 && !\CjwNewsletterArticlePool::fetch( $pool ) )
            $errors['pool'] = \ezpI18n::tr( $tr, 'The chosen article pool does not exist.' );
        $data['article_pool_id'] = max( 0, $pool );

        $handler = \CjwNewsletterEditorialUI::postedText( $http, 'ConditionHandler', 255 );
        $handlers = \CjwNewsletterScheduleRunner::conditionHandlers();
        if ( $handler !== '' && !isset( $handlers[$handler] ) )
            $errors['condition'] = \ezpI18n::tr( $tr, 'The chosen condition is not available.' );
        $data['condition_handler'] = isset( $handlers[$handler] ) ? $handler : '';

        $data['status'] = \CjwNewsletterEditorialUI::postedInt( $http, 'Status' ) === \CjwNewsletterSchedule::STATUS_PAUSED
            ? \CjwNewsletterSchedule::STATUS_PAUSED : \CjwNewsletterSchedule::STATUS_ACTIVE;
        return $data;
    }
}

}
