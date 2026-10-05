<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:import-eznewsletter - take the lists, subscribers and subscriptions of an old eznewsletter
 * installation over into cjw_newsletter. The old tables are only read.
 */
class ImportEznewsletter extends \Exponential\Runnable\Command
{
    protected function commandName() { return 'import-eznewsletter'; }

    public function run()
    {
        $this->script( array( 'description' => "Take the lists, subscribers and subscriptions of an eznewsletter installation over into cjw_newsletter.\n"
                                               . "The old tables are read only and never changed. Every row read is logged in the migration log\n"
                                               . "(newsletter/migration_log); a second run skips what an earlier run took over.",
                              'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $options = $this->startup( '[dry-run][source-sqlite:][list-map:][create-lists][system-node:][include-pending][robinson-to-suppression][batch-size:]', '',
            array( 'dry-run' => 'Check and count everything and log it, write nothing else',
                   'source-sqlite' => 'Read the old tables from this SQLite file instead of the installation\'s database',
                   'list-map' => 'Old list id:cjw list object id pairs, joined by commas, e.g. 1:18150,2:18151',
                   'create-lists' => 'Create a cjw list for every old list that is not mapped (needs --system-node)',
                   'system-node' => 'The node id of the newsletter system under which new lists are created',
                   'include-pending' => 'Also take over subscriptions that were never confirmed (as pending; no mail is sent)',
                   'robinson-to-suppression' => 'Put the addresses of the old do-not-contact list on the suppression list (reason legal)',
                   'batch-size' => 'Rows read at a time (default: [EznewsletterImportSettings] BatchSize)' ) );
        $cli = $this->cli();

        $listMap = array();
        foreach ( array_filter( explode( ',', (string)$options['list-map'] ), 'strlen' ) as $pair )
        {
            if ( !preg_match( '/^\s*(\d+)\s*:\s*(\d+)\s*$/', $pair, $m ) )
            {
                $cli->error( 'Not an old:new pair in --list-map: ' . $pair );
                $this->shutdown( 1 );
                return 1;
            }
            $listMap[(int)$m[1]] = (int)$m[2];
        }
        if ( $options['create-lists'] && !(int)$options['system-node'] )
        {
            $cli->error( '--create-lists needs --system-node=<node id of a newsletter system>' );
            $this->shutdown( 1 );
            return 1;
        }
        try
        {
            $source = $options['source-sqlite'] ? \CjwNewsletterEznewsletterSource::sqliteFile( (string)$options['source-sqlite'] )
                                                : \CjwNewsletterEznewsletterSource::sameDatabase();
        }
        catch ( \Throwable $e )
        {
            $cli->error( $e->getMessage() );
            $this->shutdown( 1 );
            return 1;
        }
        $lock = \CjwNewsletterRunner::lock( 'import' );
        if ( !$lock )
        {
            $cli->error( 'Another import is running.' );
            $this->shutdown( 1 );
            return 1;
        }
        $settings = array( 'dry_run' => (bool)$options['dry-run'], 'list_map' => $listMap, 'create_lists' => (bool)$options['create-lists'],
                           'system_node_id' => (int)$options['system-node'], 'include_pending' => (bool)$options['include-pending'],
                           'robinson_to_suppression' => (bool)$options['robinson-to-suppression'], 'out' => $cli );
        if ( (int)$options['batch-size'] > 0 )
            $settings['batch_size'] = (int)$options['batch-size'];
        $migration = new \CjwNewsletterEznewsletterMigration( $source, $settings );
        $totals = $migration->run();
        \CjwNewsletterRunner::unlock( $lock );
        if ( $totals['error'] !== '' )
        {
            $cli->error( $totals['error'] );
            $this->shutdown( 1 );
            return 1;
        }
        if ( $totals['reasons'] )
        {
            $parts = array();
            foreach ( $totals['reasons'] as $reason => $count )
                $parts[] = $reason . ' ' . $count;
            $cli->output( 'Skipped: ' . implode( ', ', $parts ) . '.' );
        }
        $cli->output( 'Log: newsletter/migration_log/' . $totals['run_id'] . ( $totals['dry_run'] ? ' (dry run: nothing was written)' : '' ) );
        $this->shutdown( $totals['subscriptions']['failed'] ? 2 : 0 );
        return $totals['subscriptions']['failed'] ? 2 : 0;
    }
}

}
