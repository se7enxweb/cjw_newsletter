<?php
//
// Copyright (C) 1998 - 2026 7x & Exponential Foundation. All rights reserved.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 (or any later version).
//

/*!
  \class CjwNewsletterProgress cjwnewsletterprogress.php
  \brief The one method of ezcConsoleProgressMonitor the mail queue uses, writing a line to the sink of the run (the
         terminal, the log of a background job) instead of straight to the standard output.
*/

class CjwNewsletterProgress
{
    protected $out;

    function __construct( $out )
    {
        $this->out = $out;
    }

    function addEntry( $tag, $text )
    {
        if ( $this->out )
        {
            $this->out->output( $tag . ' ' . $text );
        }
    }
}

?>
