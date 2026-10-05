<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:statistics - the newsletter statistics (cjw_newsletter 4.2.0, area N4 Statistics).
 *
 *   (no option)   a summary: the site switch, the consent category, the lists per tracking mode, the totals
 *   --cleanup     remove the per-person rows older than [TrackingSettings] PersonRetentionMonths and those of people
 *                 whose consent is not on any more (the totals stay); the mail queue does this once a day by itself
 *   --decide      choose the winners of the A/B tests whose wait is over
 *   --at=<time>   act as if it were then (a date strtotime() reads, or a Unix time)
 *   --dry-run     with --cleanup: count only
 */
class Statistics extends \Exponential\Runnable\Command
{
    protected function commandName() { return 'statistics'; }

    /** @return int|false the time of --at, false when it cannot be read */
    public static function parseAt( $value )
    {
        $value = trim( (string)$value );
        if ( $value === '' )
            return time();
        if ( preg_match( '/^[0-9]{9,11}$/', $value ) )
            return (int)$value;
        $t = strtotime( $value );
        return $t === false ? false : (int)$t;
    }

    public function run()
    {
        $this->script( array( 'description' => 'Newsletter statistics: retention cleanup, A/B winners, summary',
                              'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $options = $this->startup( '[cleanup][decide][dry-run][at:]', '',
            array( 'cleanup' => 'Remove expired per-person rows and those of people without consent (the totals stay)',
                   'decide' => 'Choose the winners of the A/B tests whose wait is over',
                   'dry-run' => 'With --cleanup: count, remove nothing',
                   'at' => 'Act as if it were this time (a date or a Unix time)' ) );
        $cli = $this->cli();
        $now = self::parseAt( isset( $options['at'] ) ? $options['at'] : '' );
        if ( $now === false )
        {
            $cli->error( 'FAIL  --at: not a date or a time' );
            $this->shutdown( 2 );
            return 2;
        }
        $code = 0;
        if ( $options['cleanup'] )
        {
            $r = \CjwNewsletterStatisticsRetention::cleanup( $now, (bool)$options['dry-run'] );
            $cli->output( ( $r['dry_run'] ? 'DRY RUN  ' : 'PASS  ' ) . 'retention: per-person rows before ' . date( 'Y-m-d H:i', $r['cutoff'] )
                . ": {$r['expired_opens']} opens, {$r['expired_clicks']} clicks, {$r['expired_items']} mails reset; "
                . "{$r['withdrawn_users']} people without consent ({$r['withdrawn_rows']} rows)" );
        }
        if ( $options['decide'] )
        {
            $n = 0;
            foreach ( \CjwNewsletterAbTest::fetchList( array( 'status' => array( array( \CjwNewsletterAbTester::STATUS_SAMPLING, \CjwNewsletterAbTester::STATUS_WAITING, \CjwNewsletterAbTester::STATUS_DECIDED ) ) ) ) as $test )
            {
                $before = (int)$test->attribute( 'status' );
                $after = \CjwNewsletterStatisticsHooks::advanceTest( $test, $now, $cli );
                if ( $after !== $before )
                    $n++;
            }
            $cli->output( "PASS  A/B tests moved on: $n" );
        }
        if ( !$options['cleanup'] && !$options['decide'] )
        {
            $d = \CjwNewsletterStatisticsReport::dashboard( $now );
            $cli->output( 'Tracking (site switch): ' . ( $d['enabled'] ? 'enabled' : 'disabled' ) );
            $cli->output( "Consent category '{$d['category']}': " . ( $d['category_registered'] ? "set up, {$d['consents']} people agreed" : 'not set up' ) );
            $cli->output( "Lists: {$d['lists'][0]} off, {$d['lists'][1]} anonymous totals, {$d['lists'][2]} per person with consent" );
            $cli->output( "Last {$d['trend_days']} days: {$d['opens']} opens ({$d['unique_opens']} unique), {$d['clicks']} clicks ({$d['unique_clicks']} unique)" );
            $cli->output( 'A/B tests running: ' . count( $d['ab_tests'] ) );
            $cli->output( "Per-person rows kept {$d['retention_months']} months; last cleanup: " . ( $d['last_cleanup'] ? date( 'Y-m-d H:i', $d['last_cleanup']['time'] ) : 'never' ) );
            foreach ( $d['problems'] as $p )
                $cli->output( strtoupper( $p['level'] ) . '  ' . $p['text'] );
        }
        $this->shutdown( $code );
        return $code;
    }
}

}
