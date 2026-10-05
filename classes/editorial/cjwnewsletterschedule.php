<?php
/**
 * File containing the CjwNewsletterSchedule class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage editorial
 */

/**
 * A recurring send of a list: copy a template edition or send the latest edition, when, auto-fill from a pool.
 *
 * Table cjwnl_schedule (cjw_newsletter 4.2.0, area N2 Editorial; every column is described in doc/schema-4.2.md).
 * Data only: the definition and fetch helpers. The behaviour belongs to the classes of the area.
 *
 * @package cjw_newsletter
 * @subpackage editorial
 */
class CjwNewsletterSchedule extends eZPersistentObject
{
    static function definition()
    {
        return array(
            'fields' => array(
                'id' => array( 'name' => 'Id', 'datatype' => 'integer', 'default' => 0, 'required' => true ),
                'list_contentobject_id' => array( 'name' => 'ListContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'mode' => array( 'name' => 'Mode', 'datatype' => 'string', 'default' => 'latest', 'required' => false ),
                'template_edition_contentobject_id' => array( 'name' => 'TemplateEditionContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'recurrence_type' => array( 'name' => 'RecurrenceType', 'datatype' => 'string', 'default' => 'w', 'required' => false ),
                'recurrence_value' => array( 'name' => 'RecurrenceValue', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'send_time' => array( 'name' => 'SendTime', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'timezone' => array( 'name' => 'Timezone', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'auto_fill' => array( 'name' => 'AutoFill', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'article_pool_id' => array( 'name' => 'ArticlePoolId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'skip_if_empty' => array( 'name' => 'SkipIfEmpty', 'datatype' => 'integer', 'default' => 1, 'required' => false ),
                'condition_handler' => array( 'name' => 'ConditionHandler', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'next_run' => array( 'name' => 'NextRun', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_run' => array( 'name' => 'LastRun', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_edition_send_id' => array( 'name' => 'LastEditionSendId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'last_result' => array( 'name' => 'LastResult', 'datatype' => 'string', 'default' => '', 'required' => false ),
                'status' => array( 'name' => 'Status', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'creator_contentobject_id' => array( 'name' => 'CreatorContentobjectId', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'created' => array( 'name' => 'Created', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
                'modified' => array( 'name' => 'Modified', 'datatype' => 'integer', 'default' => 0, 'required' => false ),
            ),
            'keys' => array( 'id' ),
            'increment_key' => 'id',
            'function_attributes' => array( 'list_name' => 'listName',
                                            'list_node_id' => 'listNodeId',
                                            'template_edition_name' => 'templateEditionName',
                                            'recurrence_text' => 'recurrenceText',
                                            'weekday_array' => 'weekdays',
                                            'send_time_text' => 'sendTimeText',
                                            'timezone_name' => 'timezoneName',
                                            'status_name' => 'statusName',
                                            'is_active' => 'isActive',
                                            'is_due' => 'isDue',
                                            'article_pool' => 'articlePool',
                                            'log_list' => 'recentLog' ),
            'sort' => array( 'id' => 'desc' ),
            'class_name' => 'CjwNewsletterSchedule',
            'name' => 'cjwnl_schedule' );
    }

    /**
     * A new row, not stored yet: the defaults of the definition, overridden by $row.
     *
     * @param array $row field => value
     * @return CjwNewsletterSchedule
     */
    static function create( $row = array() )
    {
        $data = array();
        $definition = self::definition();
        foreach ( $definition['fields'] as $name => $field )
            $data[$name] = array_key_exists( $name, (array)$row ) ? $row[$name] : $field['default'];
        unset( $data['id'] );
        return new CjwNewsletterSchedule( $data );
    }

    /**
     * @param int $id
     * @return CjwNewsletterSchedule|null
     */
    static function fetch( $id )
    {
        $object = eZPersistentObject::fetchObject( self::definition(), null, array( 'id' => (int)$id ), true );
        return $object ? $object : null;
    }

    /**
     * @param array|null $conditions field => value (eZPersistentObject conditions)
     * @param int $limit 0 = all
     * @param int $offset
     * @param array|null $sorts field => asc|desc, null = newest id first
     * @return CjwNewsletterSchedule[]
     */
    static function fetchList( $conditions = null, $limit = 0, $offset = 0, $sorts = null )
    {
        $limits = (int)$limit > 0 ? array( 'limit' => (int)$limit, 'offset' => (int)$offset ) : null;
        $list = eZPersistentObject::fetchObjectList( self::definition(), null, $conditions,
            $sorts === null ? array( 'id' => 'desc' ) : $sorts, $limits, true );
        return is_array( $list ) ? $list : array();
    }

    /**
     * @param array|null $conditions
     * @return int
     */
    static function fetchListCount( $conditions = null )
    {
        return (int)eZPersistentObject::count( self::definition(), $conditions );
    }

    /**
     * @return CjwNewsletterSchedule[] the rows with these values (index cjwnl_schedule_due), newest id first
     */
    static function fetchListByStatusAndNextRun( $status, $nextRun, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'status' => (int)$status, 'next_run' => (int)$nextRun ), $limit, $offset );
    }

    /**
     * @return CjwNewsletterSchedule[] the rows with these values (index cjwnl_schedule_list), newest id first
     */
    static function fetchListByListContentobjectId( $listContentobjectId, $limit = 0, $offset = 0 )
    {
        return self::fetchList( array( 'list_contentobject_id' => (int)$listContentobjectId ), $limit, $offset );
    }

    // ------------------------------------------------------------------ behaviour (4.2.0 N2 Editorial)

    const STATUS_ACTIVE = 0;
    const STATUS_PAUSED = 1;
    const STATUS_REMOVED = 9;

    const MODE_COPY = 'copy';
    const MODE_LATEST = 'latest';

    const RECURRENCE_DAYS = 'd';
    const RECURRENCE_WEEKLY = 'w';
    const RECURRENCE_MONTHLY = 'm';

    /**
     * @return CjwNewsletterSchedule[] the active schedules whose next run has come, the oldest due first
     */
    static function fetchDue( $now = null, $limit = 0 )
    {
        $now = $now === null ? time() : (int)$now;
        return self::fetchList( array( 'status' => self::STATUS_ACTIVE, 'next_run' => array( false, array( 1, $now ) ) ),
                                $limit, 0, array( 'next_run' => 'asc', 'id' => 'asc' ) );
    }

    /**
     * @return CjwNewsletterSchedule[] every schedule that is not removed, by list and id
     */
    static function fetchVisible( $listObjectId = 0 )
    {
        $conditions = array( 'status' => array( '<', self::STATUS_REMOVED ) );
        if ( (int)$listObjectId > 0 )
            $conditions['list_contentobject_id'] = (int)$listObjectId;
        return self::fetchList( $conditions, 0, 0, array( 'next_run' => 'asc', 'id' => 'asc' ) );
    }

    /**
     * @return string the time zone of the schedule: its own, else [ScheduleSettings] DefaultTimezone, else PHP's
     */
    function timezoneName()
    {
        foreach ( array( (string)$this->attribute( 'timezone' ), self::defaultTimezone() ) as $name )
            if ( $name !== '' && self::isTimezone( $name ) )
                return $name;
        return date_default_timezone_get();
    }

    /** @return string [ScheduleSettings] DefaultTimezone, '' when not set */
    static function defaultTimezone()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'ScheduleSettings', 'DefaultTimezone' ) ? trim( (string)$ini->variable( 'ScheduleSettings', 'DefaultTimezone' ) ) : '';
    }

    /** @return bool */
    static function isTimezone( $name )
    {
        return is_string( $name ) && $name !== '' && in_array( $name, DateTimeZone::listIdentifiers(), true );
    }

    /** @return int[] the ISO weekdays (1 Monday .. 7 Sunday) of type d, or the one of type w */
    function weekdays()
    {
        $days = array();
        foreach ( explode( ',', (string)$this->attribute( 'recurrence_value' ) ) as $day )
        {
            $day = (int)trim( $day );
            if ( $day >= 1 && $day <= 7 && !in_array( $day, $days, true ) )
                $days[] = $day;
        }
        sort( $days );
        if ( $this->attribute( 'recurrence_type' ) === self::RECURRENCE_WEEKLY )
            return $days ? array( $days[0] ) : array( 1 );
        return $days;
    }

    /** @return int the day of the month of type m (1 .. 31) */
    function monthDay()
    {
        $day = (int)$this->attribute( 'recurrence_value' );
        return $day >= 1 && $day <= 31 ? $day : 1;
    }

    /** @return int seconds after midnight, 0 .. 86399 */
    function sendTime()
    {
        return max( 0, min( 86399, (int)$this->attribute( 'send_time' ) ) );
    }

    /** @return string HH:MM */
    function sendTimeText()
    {
        $t = $this->sendTime();
        return sprintf( '%02d:%02d', intdiv( $t, 3600 ), intdiv( $t % 3600, 60 ) );
    }

    /**
     * The first time after $after at which the schedule runs, in its time zone: the send time on a chosen weekday
     * (d, w) or on the day of the month (m; a day beyond the end of a month means its last day, so the 31st is the
     * 30th in April and the 28th or 29th in February). On the night the clocks change a send time that does not
     * exist moves forward by the change.
     *
     * @param int $after Unix time
     * @return int Unix time, 0 when the schedule has no valid day
     */
    function nextRunAfter( $after )
    {
        $after = (int)$after;
        $zone = new DateTimeZone( $this->timezoneName() );
        $time = $this->sendTime();
        $h = intdiv( $time, 3600 );
        $m = intdiv( $time % 3600, 60 );
        $s = $time % 60;
        $start = new DateTime( '@' . $after );
        $start->setTimezone( $zone );
        $year = (int)$start->format( 'Y' );
        $month = (int)$start->format( 'n' );
        $day = (int)$start->format( 'j' );

        if ( $this->attribute( 'recurrence_type' ) === self::RECURRENCE_MONTHLY )
        {
            $wanted = $this->monthDay();
            for ( $i = 0; $i < 25; $i++ )
            {
                $candidate = new DateTime( 'now', $zone );
                $candidate->setDate( $year, $month + $i, 1 );
                $last = (int)$candidate->format( 't' );
                $candidate->setDate( (int)$candidate->format( 'Y' ), (int)$candidate->format( 'n' ), min( $wanted, $last ) );
                $candidate->setTime( $h, $m, $s );
                if ( $candidate->getTimestamp() > $after )
                    return $candidate->getTimestamp();
            }
            return 0;
        }

        $days = $this->weekdays();
        if ( !$days )
            return 0;
        for ( $i = 0; $i < 15; $i++ )
        {
            $candidate = new DateTime( 'now', $zone );
            $candidate->setDate( $year, $month, $day + $i );
            $candidate->setTime( $h, $m, $s );
            if ( in_array( (int)$candidate->format( 'N' ), $days, true ) && $candidate->getTimestamp() > $after )
                return $candidate->getTimestamp();
        }
        return 0;
    }

    /**
     * Sets next_run from now (or from $after) when the schedule is active; a paused schedule keeps 0.
     */
    function updateNextRun( $after = null )
    {
        $after = $after === null ? time() : (int)$after;
        $this->setAttribute( 'next_run', (int)$this->attribute( 'status' ) === self::STATUS_ACTIVE ? $this->nextRunAfter( $after ) : 0 );
    }

    /** @return bool */
    function isActive()
    {
        return (int)$this->attribute( 'status' ) === self::STATUS_ACTIVE;
    }

    /** @return bool */
    function isDue( $now = null )
    {
        $now = $now === null ? time() : (int)$now;
        return $this->isActive() && (int)$this->attribute( 'next_run' ) > 0 && (int)$this->attribute( 'next_run' ) <= $now;
    }

    /** @return string */
    function statusName()
    {
        switch ( (int)$this->attribute( 'status' ) )
        {
            case self::STATUS_ACTIVE: return ezpI18n::tr( 'cjw_newsletter/editorial', 'active' );
            case self::STATUS_PAUSED: return ezpI18n::tr( 'cjw_newsletter/editorial', 'paused' );
            default: return ezpI18n::tr( 'cjw_newsletter/editorial', 'removed' );
        }
    }

    /** @return string[] ISO weekday => name, Monday first */
    static function weekdayNames()
    {
        $locale = eZLocale::instance();
        $names = array();
        for ( $d = 1; $d <= 7; $d++ )
            $names[$d] = $locale->longDayName( $d % 7 );
        return $names;
    }

    /** @return string "Mondays, Wednesdays at 08:00", "on the 15th of each month at 08:00" ... */
    function recurrenceText()
    {
        $time = $this->sendTimeText();
        if ( $this->attribute( 'recurrence_type' ) === self::RECURRENCE_MONTHLY )
            return ezpI18n::tr( 'cjw_newsletter/editorial', 'Monthly on day %day at %time', null, array( '%day' => $this->monthDay(), '%time' => $time ) );
        $names = self::weekdayNames();
        $days = array();
        foreach ( $this->weekdays() as $d )
            $days[] = $names[$d];
        if ( $this->attribute( 'recurrence_type' ) === self::RECURRENCE_WEEKLY )
            return ezpI18n::tr( 'cjw_newsletter/editorial', 'Weekly on %day at %time', null, array( '%day' => implode( '', $days ), '%time' => $time ) );
        if ( count( $days ) === 7 )
            return ezpI18n::tr( 'cjw_newsletter/editorial', 'Every day at %time', null, array( '%time' => $time ) );
        return ezpI18n::tr( 'cjw_newsletter/editorial', 'On %days at %time', null, array( '%days' => implode( ', ', $days ), '%time' => $time ) );
    }

    /** @return eZContentObject|null the newsletter list */
    function listObject()
    {
        $id = (int)$this->attribute( 'list_contentobject_id' );
        $object = $id > 0 ? eZContentObject::fetch( $id ) : null;
        return $object instanceof eZContentObject ? $object : null;
    }

    /** @return string */
    function listName()
    {
        $object = $this->listObject();
        return $object ? (string)$object->attribute( 'name' ) : '';
    }

    /** @return int the main node of the list, 0 when it is gone */
    function listNodeId()
    {
        $object = $this->listObject();
        return $object ? (int)$object->attribute( 'main_node_id' ) : 0;
    }

    /** @return string the name of the template edition of mode copy */
    function templateEditionName()
    {
        $id = (int)$this->attribute( 'template_edition_contentobject_id' );
        $object = $id > 0 ? eZContentObject::fetch( $id ) : null;
        return $object ? (string)$object->attribute( 'name' ) : '';
    }

    /** @return CjwNewsletterArticlePool the pool of the auto-fill: the schedule's, else the list's */
    function articlePool()
    {
        $id = (int)$this->attribute( 'article_pool_id' );
        $pool = $id > 0 ? CjwNewsletterArticlePool::fetch( $id ) : null;
        return $pool ? $pool : CjwNewsletterArticlePool::forList( $this->attribute( 'list_contentobject_id' ) );
    }

    /** @return CjwNewsletterScheduleLog[] the last runs, newest first */
    function recentLog( $limit = 10 )
    {
        return CjwNewsletterScheduleLog::fetchList( array( 'schedule_id' => (int)$this->attribute( 'id' ) ), $limit, 0,
                                                    array( 'run_at' => 'desc', 'id' => 'desc' ) );
    }

    /**
     * @return int when the schedule last sent (a run with the result sent), 0 = never
     */
    function lastSentTime()
    {
        $rows = CjwNewsletterScheduleLog::fetchList( array( 'schedule_id' => (int)$this->attribute( 'id' ), 'result' => 'sent' ), 1, 0,
                                                     array( 'run_at' => 'desc', 'id' => 'desc' ) );
        return $rows ? (int)$rows[0]->attribute( 'run_at' ) : 0;
    }

    /**
     * @return int[] the editions the schedule made or sent so far
     */
    function editionObjectIds()
    {
        $ids = array();
        foreach ( CjwNewsletterScheduleLog::fetchList( array( 'schedule_id' => (int)$this->attribute( 'id' ) ) ) as $log )
            if ( (int)$log->attribute( 'edition_contentobject_id' ) > 0 )
                $ids[] = (int)$log->attribute( 'edition_contentobject_id' );
        return array_values( array_unique( $ids ) );
    }

    /**
     * Removes the schedule and its log (the editions and sends it made stay).
     */
    function removeSchedule()
    {
        $db = eZDB::instance();
        $db->begin();
        eZPersistentObject::removeObject( CjwNewsletterScheduleLog::definition(), array( 'schedule_id' => (int)$this->attribute( 'id' ) ) );
        $this->remove();
        $db->commit();
    }
}
