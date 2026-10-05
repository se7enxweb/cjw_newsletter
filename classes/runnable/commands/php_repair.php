<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:repair - remove the orphans the start page reports.
 */
class Repair extends \Exponential\Runnable\Command
{
    protected function commandName() { return 'repair'; }

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
        $this->script( array( 'description' => 'Remove newsletter subscriptions of removed users and unsendable mails',
                              'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $options = $this->startup( '[dry-run][job:]', '',
            array( 'dry-run' => 'Count what would be removed, remove nothing', 'job' => 'The ID of a background job (set by the admin)' ) );
        list( $jobID, $out ) = $this->setup( $options );
        $totals = \CjwNewsletterRunner::repair( $out, $jobID ? 'job' : 'console', (bool)$options['dry-run'] );
        $code = $totals['locked'] ? 1 : 0;
        if ( $code ) { $out->error( 'Another repair is active.' ); }
        $this->finish( $jobID, 'repair', $code ? 'failed' : 'done', $totals + ( $code ? array( 'error' => 'Another repair is active.' ) : array() ) );
        $this->shutdown( $code );
        return $code;
    }
}

}
