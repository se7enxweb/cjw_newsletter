<?php
/**
 * File containing the CjwNewsletterThrottle class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * The rate limits of the transports (cjw_newsletter.ini [ThrottleSettings]): how many mails (or SMS) a transport may
 * send in the current minute and the current hour, and a pause an administrator sets.
 *
 * Every sender asks before it sends and says afterwards what it sent:
 *
 * \code
 * $n = CjwNewsletterThrottle::acquire( 'smtp', 200 );   // how many of 200 may go now (0 = wait for a later run)
 * ... send $n ...
 * CjwNewsletterThrottle::record( 'smtp', $sent );
 * \endcode
 *
 * The windows are fixed (the minute and the hour of the clock) and kept in cjwnl_throttle_state, one row per transport
 * and window, so that every process (cron, console, the admin's "send now") counts against the same limit. A pause
 * (paused_until) is respected whether [ThrottleSettings] Throttle is enabled or not; the limits only when it is.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterThrottle
{
    /** window type => seconds */
    static $windows = array( 'minute' => 60, 'hour' => 3600 );

    /** @var int|null test override of the clock */
    static $now = null;

    /**
     * @return int the current time (a test may set self::$now)
     */
    static function now()
    {
        return self::$now !== null ? (int)self::$now : time();
    }

    /**
     * @return bool [ThrottleSettings] Throttle=enabled: batches and rate limits
     */
    static function enabled()
    {
        return self::setting( 'Throttle', 'disabled' ) === 'enabled';
    }

    /**
     * The name a transport is counted under: lower case letters, digits, "_" and "-"; empty = the transport of the
     * newsletter cronjob ([NewsletterMailSettings] TransportMethodCronjob).
     *
     * @param string $transport
     * @return string
     */
    static function name( $transport )
    {
        $transport = strtolower( trim( (string)$transport ) );
        if ( $transport === '' )
        {
            $ini = eZINI::instance( 'cjw_newsletter.ini' );
            $transport = $ini->hasVariable( 'NewsletterMailSettings', 'TransportMethodCronjob' )
                ? strtolower( trim( (string)$ini->variable( 'NewsletterMailSettings', 'TransportMethodCronjob' ) ) ) : 'file';
        }
        $transport = preg_replace( '/[^a-z0-9_-]/', '', $transport );
        return $transport === '' ? 'file' : substr( $transport, 0, 50 );
    }

    /**
     * @param string $transport
     * @param string $windowType minute or hour
     * @return int the limit of the window, 0 = none ([ThrottleSettings] MaxPerMinute[<transport>] / MaxPerHour[<transport>])
     */
    static function limit( $transport, $windowType )
    {
        $name = $windowType === 'hour' ? 'MaxPerHour' : 'MaxPerMinute';
        $values = self::setting( $name, array() );
        $transport = self::name( $transport );
        if ( is_array( $values ) && isset( $values[$transport] ) )
            return max( 0, (int)$values[$transport] );
        return 0;
    }

    /**
     * @return int [ThrottleSettings] BatchSize, at least 1
     */
    static function batchSize()
    {
        return max( 1, (int)self::setting( 'BatchSize', 200 ) );
    }

    /**
     * @return int [ThrottleSettings] PauseBetweenBatches in seconds, at least 0
     */
    static function pauseBetweenBatches()
    {
        return max( 0, (int)self::setting( 'PauseBetweenBatches', 0 ) );
    }

    /**
     * How many of $wanted may be sent now with $transport. Nothing is counted yet: call record() with what was sent.
     *
     * @param string $transport
     * @param int $wanted
     * @return int 0 .. $wanted (0 = paused or a limit reached: wait for a later run)
     */
    static function acquire( $transport, $wanted )
    {
        $wanted = max( 0, (int)$wanted );
        if ( $wanted === 0 )
            return 0;
        $transport = self::name( $transport );
        $now = self::now();
        if ( self::pausedUntil( $transport ) > $now )
            return 0;
        if ( !self::enabled() )
            return $wanted;
        $allowed = $wanted;
        foreach ( self::$windows as $type => $seconds )
        {
            $limit = self::limit( $transport, $type );
            if ( $limit <= 0 )
                continue;
            $row = self::row( $transport, $type, false );
            $sent = ( $row && (int)$row->attribute( 'window_start' ) === self::windowStart( $type, $now ) ) ? (int)$row->attribute( 'sent_count' ) : 0;
            $allowed = min( $allowed, max( 0, $limit - $sent ) );
        }
        return $allowed;
    }

    /**
     * Counts what a transport sent in the current windows.
     *
     * @param string $transport
     * @param int $sent
     */
    static function record( $transport, $sent )
    {
        $sent = max( 0, (int)$sent );
        if ( $sent === 0 )
            return;
        $transport = self::name( $transport );
        $now = self::now();
        $db = eZDB::instance();
        foreach ( array_keys( self::$windows ) as $type )
        {
            $row = self::row( $transport, $type, true );
            $start = self::windowStart( $type, $now );
            if ( (int)$row->attribute( 'window_start' ) !== $start )
            {
                $row->setAttribute( 'window_start', $start );
                $row->setAttribute( 'sent_count', $sent );
                $row->setAttribute( 'modified', $now );
                $row->store();
            }
            else
            {
                // one statement, so that two processes never lose a count
                $db->query( 'UPDATE cjwnl_throttle_state SET sent_count = sent_count + ' . $sent . ', modified = ' . (int)$now
                            . ' WHERE id = ' . (int)$row->attribute( 'id' ) );
            }
        }
    }

    /**
     * @param string $transport
     * @return int the end of a pause, 0 = not paused
     */
    static function pausedUntil( $transport )
    {
        $row = self::row( self::name( $transport ), 'minute', false );
        return $row ? (int)$row->attribute( 'paused_until' ) : 0;
    }

    /**
     * Pauses a transport: nothing is sent with it before $until.
     *
     * @param string $transport
     * @param int $until a time; 0 lifts the pause
     */
    static function pause( $transport, $until )
    {
        $row = self::row( self::name( $transport ), 'minute', true );
        $row->setAttribute( 'paused_until', max( 0, (int)$until ) );
        $row->setAttribute( 'modified', self::now() );
        $row->store();
    }

    /**
     * Lifts the pause of a transport.
     *
     * @param string $transport
     */
    static function resume( $transport )
    {
        if ( self::pausedUntil( $transport ) > 0 )
            self::pause( $transport, 0 );
    }

    /**
     * The transports to show: those with a limit, a row or a send that names them, and the cronjob transport.
     *
     * @return string[]
     */
    static function transports()
    {
        $names = array( self::name( '' ) );
        foreach ( array( 'MaxPerMinute', 'MaxPerHour' ) as $setting )
        {
            $values = self::setting( $setting, array() );
            if ( is_array( $values ) )
                foreach ( array_keys( $values ) as $name )
                    $names[] = self::name( $name );
        }
        foreach ( CjwNewsletterThrottleState::fetchList() as $row )
            $names[] = (string)$row->attribute( 'transport' );
        $names = array_values( array_unique( array_filter( $names, 'strlen' ) ) );
        sort( $names );
        return $names;
    }

    /**
     * The state of every transport, for the dashboard, the throttle view and the console.
     *
     * @return array[] transport => hash( transport, limit_minute, limit_hour, sent_minute, sent_hour, paused_until,
     *                 paused, available (what acquire() would give of a batch), is_cronjob )
     */
    static function states()
    {
        $now = self::now();
        $cron = self::name( '' );
        $out = array();
        foreach ( self::transports() as $transport )
        {
            $state = array( 'transport' => $transport, 'is_cronjob' => $transport === $cron, 'paused_until' => self::pausedUntil( $transport ) );
            foreach ( self::$windows as $type => $seconds )
            {
                $row = self::row( $transport, $type, false );
                $state['limit_' . $type] = self::limit( $transport, $type );
                $state['sent_' . $type] = ( $row && (int)$row->attribute( 'window_start' ) === self::windowStart( $type, $now ) ) ? (int)$row->attribute( 'sent_count' ) : 0;
            }
            $state['paused'] = $state['paused_until'] > $now;
            $state['available'] = self::acquire( $transport, self::batchSize() );
            $out[$transport] = $state;
        }
        return $out;
    }

    // ------------------------------------------------------------------ internals

    /**
     * @return int the start of the window of $type that $time is in
     */
    static function windowStart( $type, $time )
    {
        $seconds = isset( self::$windows[$type] ) ? self::$windows[$type] : 60;
        return (int)( floor( (int)$time / $seconds ) * $seconds );
    }

    /**
     * @param string $transport already cleaned by name()
     * @param string $type
     * @param bool $create make the row when it is missing
     * @return CjwNewsletterThrottleState|null
     */
    protected static function row( $transport, $type, $create )
    {
        $row = CjwNewsletterThrottleState::fetchByTransportAndWindowType( $transport, $type );
        if ( !$row && $create )
        {
            $row = CjwNewsletterThrottleState::create( array( 'transport' => $transport, 'window_type' => $type,
                                                              'window_start' => self::windowStart( $type, self::now() ),
                                                              'sent_count' => 0, 'paused_until' => 0, 'modified' => self::now() ) );
            $row->store();
        }
        return $row;
    }

    protected static function setting( $name, $default )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'ThrottleSettings', $name ) ? $ini->variable( 'ThrottleSettings', $name ) : $default;
    }
}

?>
