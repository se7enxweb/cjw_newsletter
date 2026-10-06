<?php
/**
 * ext:cjw_newsletter:class-languages: moves the newsletter content classes from eng-GB to eng-US (4.2.1 upgrade
 * step, idempotent). Options: --dry-run (only shows what would change), --backup-dir (where the rows are saved
 * before they change, default var/<site>/cjw_newsletter).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

class ClassLanguages extends \Exponential\Runnable\Command
{
    public function run()
    {
        $this->script( array( 'description' => "Move the newsletter content classes from eng-GB to eng-US (cjw_newsletter 4.2.1):\n"
                                             . "class and attribute names and descriptions, language mask, initial language and the class name rows.\n"
                                             . "German texts stay. Content objects and the eng-GB language itself are left alone. Idempotent.",
                              'use-session' => false, 'use-modules' => false, 'use-extensions' => true ) );
        $options = $this->startup( '[dry-run][backup-dir:]', '', array( 'dry-run' => 'Only show what would change',
                                                                           'backup-dir' => 'Directory for the JSON backup of the changed rows' ) );
        $cli = $this->cli();
        $dryRun = (bool)$options['dry-run'];
        $admin = \eZUser::fetchByName( 'admin' );
        if ( $admin )
            \eZUser::setCurrentlyLoggedInUser( $admin, $admin->attribute( 'contentobject_id' ) );
        $dir = $options['backup-dir'] ? rtrim( $options['backup-dir'], '/' ) : \eZSys::varDirectory() . '/cjw_newsletter';
        $backupFile = $dir . '/class-languages-backup-' . date( 'Ymd-His' ) . '.json';

        $report = \CjwNewsletterClassLanguages::apply( $dryRun, $backupFile );
        foreach ( $report['rows'] as $row )
            $cli->output( sprintf( '%-26s %-46s %s', $row['table'], $row['key'], $row['change'] ) );
        if ( $report['backup'] )
            $cli->output( 'backup: ' . $report['backup'] );
        $objects = \CjwNewsletterClassLanguages::objectCount();
        if ( $objects )
            $cli->output( "note: $objects newsletter objects still carry eng-GB translations; they are left alone" );
        if ( $report['error'] )
        {
            $cli->output( 'FAIL ' . $report['error'] );
            $this->shutdown( 1 );
        }
        $count = count( $report['rows'] );
        $cli->output( $count == 0 ? 'PASS nothing to change, the newsletter classes use eng-US'
                                  : ( $dryRun ? "PASS dry run, $count rows would change, nothing changed" : "PASS $count rows changed" ) );
        $this->shutdown( 0 );
    }
}

}
