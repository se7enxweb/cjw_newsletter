<?php
//
// Copyright (C) 1998 - 2026 7x & Exponential Foundation. All rights reserved.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 (or any later version).
//

/*!
  \class CjwNewsletterLogWriter cjwnewsletterlogwriter.php
  \brief ezcLogUnixFileWriter assigns a property it does not declare, which is a deprecation notice on PHP 8.2 and later
         on every request that writes a newsletter log line. The property is declared here.
*/

class CjwNewsletterLogWriter extends ezcLogUnixFileWriter
{
    public $defaultFile;
}

?>
