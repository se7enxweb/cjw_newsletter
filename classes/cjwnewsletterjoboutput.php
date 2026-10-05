<?php
//
// Copyright (C) 1998 - 2026 7x & Exponential Foundation. All rights reserved.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 (or any later version).
//

/*!
  \class CjwNewsletterJobOutput cjwnewsletterjoboutput.php
  \brief Output sink with the two methods of eZCLI the runner uses; a line goes to the terminal and to the log of a job.
*/

class CjwNewsletterJobOutput
{
    protected $cli;
    protected $jobID;

    function __construct( $cli, $jobID = false )
    {
        $this->cli = $cli;
        $this->jobID = $jobID;
    }

    function output( $text = false, $addEOL = true )
    {
        if ( $this->cli )
        {
            $this->cli->output( $text, $addEOL );
        }
        if ( $this->jobID )
        {
            CjwNewsletterJob::log( $this->jobID, (string)$text );
        }
    }

    function error( $text = false, $addEOL = true )
    {
        if ( $this->cli )
        {
            $this->cli->error( $text, $addEOL );
        }
        if ( $this->jobID )
        {
            CjwNewsletterJob::log( $this->jobID, 'ERROR: ' . $text );
        }
    }
}

?>
