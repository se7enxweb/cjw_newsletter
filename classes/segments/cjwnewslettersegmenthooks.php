<?php
/**
 * File containing the CjwNewsletterSegmentHooks class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage segments
 */

/**
 * The extension point handler of the segments ([ExtensionPointSettings] Handlers[]=CjwNewsletterSegmentHooks).
 *
 * - sendRecipients: a send with a segment goes only to the segment's members; a send without one is unchanged.
 * - sendFormValidate / sendFormStored: the segment chosen in the send form.
 *
 * @package cjw_newsletter
 * @subpackage segments
 */
class CjwNewsletterSegmentHooks
{
    /**
     * @param array|false $subscriptions what getSubscriptionObjectArray() returned
     * @param CjwNewsletterEditionSend $send
     * @return array|false the subscriptions, or only the members of the send's segment
     */
    static function sendRecipients( $subscriptions, $send )
    {
        $segmentId = is_object( $send ) ? (int)$send->attribute( 'segment_id' ) : 0;
        if ( $segmentId < 1 )
            return $subscriptions;

        $ids = self::memberIds( $segmentId, (int)$send->attribute( 'list_contentobject_id' ) );
        return self::keepIds( $subscriptions, $ids );
    }

    /**
     * @param array|false $subscriptions objects with attribute( 'id' )
     * @param int[] $ids
     * @return array the subscriptions whose id is in $ids, in their order; empty when $ids is empty
     */
    static function keepIds( $subscriptions, array $ids )
    {
        $keep = array_flip( array_map( 'intval', $ids ) );
        $result = array();
        foreach ( is_array( $subscriptions ) ? $subscriptions : array() as $subscription )
        {
            if ( isset( $keep[(int)$subscription->attribute( 'id' )] ) )
                $result[] = $subscription;
        }
        return $result;
    }

    /**
     * @return int[] member subscription ids; empty (and logged) when the segment is missing, of another list or invalid
     */
    protected static function memberIds( $segmentId, $listId )
    {
        $segment = CjwNewsletterSegment::fetch( $segmentId );
        $evaluator = self::evaluator();
        $problem = self::problem( $segment, $listId, $evaluator );
        if ( $problem === 'list' )
        {
            CjwNewsletterLog::writeError( "Segment $segmentId is missing or belongs to another list, the send gets no recipients", __METHOD__ );
            return array();
        }
        if ( $problem === 'condition' )
        {
            CjwNewsletterLog::writeError( "Segment $segmentId has an invalid condition, the send gets no recipients", __METHOD__ );
            return array();
        }
        return self::evaluate( $segment, $evaluator );
    }

    /** @return CjwNewsletterSegmentEvaluator */
    protected static function evaluator()
    {
        return new CjwNewsletterSegmentEvaluator( new CjwNewsletterSegmentEzDb(),
            (int)eZINI::instance( 'cjw_newsletter.ini' )->variable( 'SegmentSettings', 'BatchSize' ) );
    }

    /**
     * @param CjwNewsletterSegment|null $segment
     * @param int $listId
     * @param CjwNewsletterSegmentEvaluator $evaluator
     * @return string '' when the segment can be sent to, 'list' when it is missing or of another list, 'condition' when a condition is invalid
     */
    protected static function problem( $segment, $listId, $evaluator )
    {
        if ( !$segment || (int)$segment->attribute( 'list_contentobject_id' ) !== (int)$listId )
            return 'list';
        $tags = $evaluator->tagsAvailable();
        foreach ( $segment->conditionArray() as $condition )
        {
            if ( !is_array( $condition ) || !is_array( CjwNewsletterSegmentCondition::normalise( $condition, $tags ) ) )
                return 'condition';
        }
        return '';
    }

    /** @return int[] member subscription ids of a valid segment, as of now */
    protected static function evaluate( $segment, $evaluator )
    {
        $now = time();
        return $evaluator->members( (int)$segment->attribute( 'list_contentobject_id' ), (string)$segment->attribute( 'match_mode' ),
            $segment->conditionArray(), $now, CjwNewsletterStatisticsRetention::cutoff( $now ), function ( $userId )
            {
                return CjwNewsletterTracking::hasConsent( CjwNewsletterUser::fetch( (int)$userId ), true );
            } );
    }

    // ------------------------------------------------------------------ the send form

    const POST = 'CjwNewsletterSegmentId';

    /** @return int|null the posted segment id (0 = whole list); null when the form part was not posted */
    protected static function postedSegmentId( $http )
    {
        if ( !$http->hasPostVariable( self::POST ) )
            return null;
        return max( 0, (int)$http->postVariable( self::POST ) );
    }

    /**
     * @return string[] errors: the chosen segment must exist, belong to the edition's list and have valid conditions
     */
    static function sendFormValidate( $http, $objectVersion )
    {
        $segmentId = self::postedSegmentId( $http );
        if ( !$segmentId )
            return array();
        $list = CjwNewsletterStatisticsHooks::listOfObjectVersion( $objectVersion );
        if ( !$list )
            return array( ezpI18n::tr( 'cjw_newsletter/segments', 'The segment does not belong to the list of this edition.' ) );
        $listId = (int)$list->attribute( 'contentobject_id' );
        $segment = CjwNewsletterSegment::fetch( $segmentId );
        $problem = self::problem( $segment, $listId, self::evaluator() );
        if ( $problem === 'list' )
            return array( ezpI18n::tr( 'cjw_newsletter/segments', 'The segment does not exist or does not belong to the list of this edition.' ) );
        if ( $problem === 'condition' )
            return array( ezpI18n::tr( 'cjw_newsletter/segments', 'The segment has an invalid condition. Correct it before sending.' ) );
        return array();
    }

    /** Sets the segment of a mail send when the form part was posted; a send without the part keeps its value. */
    static function sendFormStored( $send, $http, $objectVersion )
    {
        if ( !is_object( $send ) || (string)$send->attribute( 'channel' ) === 'sms' )
            return;
        $segmentId = self::postedSegmentId( $http );
        if ( $segmentId === null )
            return;
        $send->setAttribute( 'segment_id', $segmentId );
        $send->store();
    }

    /**
     * The choices of the send form: the valid segments of a list with their current size.
     *
     * @param int $listId content object id of the list
     * @param bool|null $exact whether the size is shown exactly; null = the current user may see subscribers
     * @return array[] id, name, count, few (true: show "fewer than minimum" instead of the count), minimum
     */
    static function sendFormOptions( $listId, $exact = null )
    {
        if ( $exact === null )
        {
            $access = eZUser::currentUser()->hasAccessTo( 'newsletter', 'subscription_list' );
            $exact = $access['accessWord'] !== 'no';
        }
        $minimum = (int)eZINI::instance( 'cjw_newsletter.ini' )->variable( 'SegmentSettings', 'MinimumVisibleCount' );
        $evaluator = self::evaluator();
        $options = array();
        foreach ( CjwNewsletterSegment::fetchByList( (int)$listId ) as $segment )
        {
            if ( self::problem( $segment, (int)$listId, $evaluator ) !== '' )
                continue;
            $count = count( self::evaluate( $segment, $evaluator ) );
            $options[] = array( 'id' => (int)$segment->attribute( 'id' ), 'name' => (string)$segment->attribute( 'name' ),
                'count' => $count, 'few' => self::isFew( $count, $minimum, $exact ), 'minimum' => $minimum );
        }
        return $options;
    }

    /**
     * The size of a selection that is not stored (the segment page's "show size").
     *
     * @param int $listId content object id of the list
     * @param string $matchMode all|any
     * @param array[] $conditions normalised conditions
     * @param bool|null $exact whether subscribers may be shown; null = the current user may see them
     * @return array size, few, minimum, not_consented and not_consented_few (null without a behavior condition),
     *               sample (email, name, created of the first members; empty unless $exact)
     */
    static function preview( $listId, $matchMode, array $conditions, $exact = null )
    {
        if ( $exact === null )
        {
            $access = eZUser::currentUser()->hasAccessTo( 'newsletter', 'subscription_list' );
            $exact = $access['accessWord'] !== 'no';
        }
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $minimum = (int)$ini->variable( 'SegmentSettings', 'MinimumVisibleCount' );
        $evaluator = self::evaluator();
        $now = time();
        $hasConsent = function ( $userId )
        {
            return CjwNewsletterTracking::hasConsent( CjwNewsletterUser::fetch( (int)$userId ), true );
        };
        $ids = $evaluator->members( (int)$listId, $matchMode, $conditions, $now, CjwNewsletterStatisticsRetention::cutoff( $now ), $hasConsent );

        $notConsented = null;
        foreach ( $conditions as $condition )
        {
            if ( CjwNewsletterSegmentCondition::isBehavior( $condition ) )
            {
                $notConsented = $evaluator->nonConsentingCount( (int)$listId, $hasConsent );
                break;
            }
        }

        $sample = array();
        if ( $exact )
        {
            foreach ( array_slice( $ids, 0, max( 0, (int)$ini->variable( 'SegmentSettings', 'SampleSize' ) ) ) as $id )
            {
                $subscription = CjwNewsletterSubscription::fetch( $id );
                $user = $subscription ? CjwNewsletterUser::fetch( (int)$subscription->attribute( 'newsletter_user_id' ) ) : null;
                if ( !$user )
                    continue;
                $sample[] = array( 'email' => (string)$user->attribute( 'email' ),
                    'name' => trim( $user->attribute( 'first_name' ) . ' ' . $user->attribute( 'last_name' ) ),
                    'created' => (int)$subscription->attribute( 'created' ) );
            }
        }
        return array( 'size' => count( $ids ), 'few' => self::isFew( count( $ids ), $minimum, $exact ), 'minimum' => $minimum,
            'not_consented' => $notConsented,
            'not_consented_few' => $notConsented === null ? false : self::isFew( $notConsented, $minimum, $exact ),
            'sample' => $sample );
    }

    /** @return bool a size below the minimum is hidden from those who may not see subscribers */
    static function isFew( $count, $minimum, $exact )
    {
        return !$exact && (int)$count < (int)$minimum;
    }

    /** Fetch function segment_send_options. @return array result */
    static function fetchSendOptions( $objectVersion )
    {
        $list = CjwNewsletterStatisticsHooks::listOfObjectVersion( $objectVersion );
        return array( 'result' => $list ? self::sendFormOptions( (int)$list->attribute( 'contentobject_id' ) ) : array() );
    }
}
