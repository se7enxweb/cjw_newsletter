<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:status - what the start page of the newsletter module shows.
 */
class Status extends \Exponential\Runnable\Command
{
    public function run()
    {
        $this->script( array( 'description' => 'Show the state of the newsletter',
                              'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $this->startup();
        $s = \CjwNewsletterDashboard::summary();
        if ( $s['tables_missing'] )
        {
            $this->output( 'Tables missing: ' . implode( ', ', $s['tables_missing'] ) );
            $this->shutdown( 1 );
            return 1;
        }
        $this->output( 'Lists:         ' . count( $s['lists'] ) );
        foreach ( $s['lists'] as $list )
        {
            $this->output( '  ' . $list['name'] . ': ' . $list['approved'] . ' approved, ' . $list['pending'] . ' waiting' );
        }
        $this->output( 'Users:         ' . $s['users']['total'] . ' (' . $s['users']['confirmed'] . ' confirmed, ' . $s['users']['pending'] . ' pending, ' . $s['users']['bounced'] . ' bounced, ' . $s['users']['blacklisted'] . ' blacklisted, ' . $s['users']['removed'] . ' removed)' );
        $this->output( 'Subscriptions: ' . $s['subscriptions']['total'] . ' (' . $s['subscriptions']['approved'] . ' approved)' );
        $this->output( 'Editions:      ' . $s['editions'] . ', sends ' . json_encode( $s['sends'] ) );
        $t = $s['transport'];
        $this->output( 'Transport:     ' . $t['method'] . ( $t['method'] == 'file' ? ', outbox ' . $t['dir'] . ': ' . $t['files'] . ' mails' . ( $t['last'] ? ', last ' . date( 'Y-m-d H:i:s', $t['last'] ) : '' ) : '' ) );
        foreach ( $s['runs'] as $part => $run )
        {
            $this->output( 'Last ' . str_pad( $part, 13 ) . ': ' . ( $run ? date( 'Y-m-d H:i:s', $run['time'] ) . ' by ' . $run['by'] : 'never' ) );
        }
        foreach ( $s['problems'] as $problem )
        {
            $this->output( strtoupper( $problem['level'] ) . ': ' . $problem['text'] );
        }
        $this->shutdown( 0 );
        return 0;
    }
}

}
