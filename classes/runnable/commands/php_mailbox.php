<?php
/**
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

/**
 * ext:cjw_newsletter:mailbox - collect the mails of the active mail accounts and parse them (the cronjob part cjw_newsletter_mailbox, by hand).
 */
class Mailbox extends \Exponential\Runnable\Command
{
    protected function commandName() { return 'mailbox'; }

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
        $this->script( array( 'description' => 'Collect the mails of the newsletter mail accounts and parse the bounces',
                              'use-session' => false, 'use-modules' => true, 'use-extensions' => true ) );
        $options = $this->startup( '[dry-run][collect-only][parse-only][job:]', '',
            array( 'dry-run' => 'Count what would be done, change nothing',
                   'collect-only' => 'Only fetch the mails from the servers',
                   'parse-only' => 'Only parse the mails already collected',
                   'job' => 'The ID of a background job (set by the admin)' ) );
        list( $jobID, $out ) = $this->setup( $options );
        $mode = $options['collect-only'] ? 'collect' : ( $options['parse-only'] ? 'parse' : 'both' );
        $totals = \CjwNewsletterRunner::mailbox( $out, $jobID ? 'job' : 'console', $mode, (bool)$options['dry-run'] );
        $code = ( $totals['ok'] && !$totals['locked'] ) ? 0 : 1;
        if ( $totals['locked'] ) { $out->error( 'Another mailbox run is active.' ); $totals['errors'] = array( 'Another mailbox run is active.' ); }
        $this->finish( $jobID, 'mailbox', $code ? 'failed' : 'done', $totals + ( $code ? array( 'error' => implode( ' ', (array)$totals['errors'] ) ) : array() ) );
        $this->shutdown( $code );
        return $code;
    }
}

}
