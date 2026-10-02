<?php
/**
 * The code of extension/cjw_newsletter/bin/php/iniloader.php, moved into a class (#207 stage 1). The file extension/cjw_newsletter/bin/php/iniloader.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 * @description Print the serialized INI object of a siteaccess (-s siteaccess)
 */
/*
 * The original header of extension/cjw_newsletter/bin/php/iniloader.php:
 *
 *
 * File iniloader.php
 *
 * -script to get a serialized ini object of the siteaccess<br>
 * -iniloader.php -s siteaccess<br>
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @author Felix Woldt 2008
 * @subpackage phpscript
 * @filesource
 *
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

class Iniloader extends \Exponential\Runnable\Command
{
    public function run()
    {
        // the script's variables were globals; functions of the script read them with "global"
        foreach ( array( 'cli', 'ini', 'iniName', 'options', 'script' ) as $__name )
            ${$__name} = &$GLOBALS[$__name];
        unset( $__name );

        $cli = $this->cli();
        $script = $this->script( array( 'description' => ( "eZ Publish INI Reader\n\n" .
                                                                "Read INI Files\n" .
                                                                "\n" .
                                                                "iniloader.php -s siteaccess site.ini" ),
                                             'use-session' => false,
                                             'use-modules' => true,
                                             'use-extensions' => true ) );

        $options = $this->startup( "",
                                        "[ininame]",
                                        array() );
        $iniName = $options['arguments'][0];
        $ini = \eZINI::instance('site.ini');

        //serialize( $ini );
        $cli->output( serialize( $ini ) );

        $script->shutdown();
    }
}

}
