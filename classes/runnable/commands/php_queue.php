<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:queue - create the mail queue and send the mails (the cronjob parts cjw_newsletter_mailqueue_create and
 * cjw_newsletter_mailqueue_process, by hand). With the file transport nothing leaves the server.
 */
class Queue extends \Exponential\Runnable\Command
{
    protected function commandName() { return 'queue'; }

    protected function finish( $jobID, $command, $status, $result )
    {
        \CjwNewsletterJob::markFinished( $jobID, $command, $status, $result );
    }

    protected function setup( $options )
    {
        $jobID = ( !empty( $options['job'] ) && \CjwNewsletterJob::isID( $options['job'] ) ) ? $options['job'] : false;
        $out = new \CjwNewsletterJobOutput( $jobID ? false : $this->cli(), $jobID );
        if ( $jobID )
        {
            \CjwNewsletterJob::markRunning( $jobID, $this->commandName() );
        }
        return array( $jobID, $out );
    }

    public function run()
    {
        $this->script( array( 'description' => 'Create the newsletter mail queue and send the mails of the queue',
                              'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $options = $this->startup( '[dry-run][create-only][send-only][job:]', '',
            array( 'dry-run' => 'Count what would be done, change nothing',
                   'create-only' => 'Only create the queue (the part cjw_newsletter_mailqueue_create)',
                   'send-only' => 'Only send the mails of the queue (the part cjw_newsletter_mailqueue_process)',
                   'job' => 'The ID of a background job (set by the admin)' ) );
        list( $jobID, $out ) = $this->setup( $options );
        $by = $jobID ? 'job' : 'console';
        $dry = (bool)$options['dry-run'];
        $result = array();
        $code = 0;
        if ( !$options['send-only'] )
        {
            $totals = \CjwNewsletterRunner::queueCreate( $out, $by, $dry );
            $result['create'] = $totals;
            if ( $totals['locked'] ) { $out->error( 'Another queue run is active.' ); $code = 1; }
            elseif ( !$dry ) { $out->output( 'Queue: ' . $totals['sends'] . ' sends, ' . $totals['items'] . ' items created.' ); }
            if ( !$totals['ok'] ) { $code = 1; }
        }
        if ( !$options['create-only'] && !$code )
        {
            $totals = \CjwNewsletterRunner::queueProcess( $out, $by, $dry );
            $result['send'] = $totals;
            if ( $totals['locked'] ) { $out->error( 'Another send run is active.' ); $code = 1; }
            elseif ( !$dry ) { $out->output( 'Sent ' . $totals['sent'] . ' mails, ' . $totals['failed'] . ' failed, ' . $totals['finished'] . ' sends finished.' ); }
            if ( !$totals['ok'] ) { $code = 1; }
        }
        $flat = array();
        foreach ( $result as $part )
        {
            foreach ( $part as $key => $value )
            {
                if ( is_int( $value ) ) { $flat[$key] = ( isset( $flat[$key] ) ? $flat[$key] : 0 ) + $value; }
            }
        }
        $this->finish( $jobID, 'queue', $code ? 'failed' : 'done', $flat + ( $code ? array( 'error' => 'The run failed or another run is active.' ) : array() ) );
        $this->shutdown( $code );
        return $code;
    }
}

}
