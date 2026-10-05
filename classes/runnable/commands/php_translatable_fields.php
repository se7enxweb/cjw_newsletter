<?php
/**
 * ext:cjw_newsletter:translatable-fields: makes the text fields of the newsletter classes translatable (4.2.0
 * upgrade step, idempotent). Options: --dry-run (only shows what would change).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\Command\Extension\CjwNewsletter
{

class TranslatableFields extends \Exponential\Runnable\Command
{
    public function run()
    {
        $this->script( array( 'description' => "Make the text fields of the newsletter classes translatable (cjw_newsletter 4.2.0):\n"
                                             . "the title, short title, short description and description of an edition, the title and text of\n"
                                             . "an article. Other fields are left as they are. Idempotent.",
                              'use-session' => false, 'use-modules' => false, 'use-extensions' => true ) );
        $options = $this->startup( '[dry-run]', '', array( 'dry-run' => 'Only show what would change' ) );
        $cli = $this->cli();
        $dryRun = (bool)$options['dry-run'];
        $admin = \eZUser::fetchByName( 'admin' );
        if ( $admin )
            \eZUser::setCurrentlyLoggedInUser( $admin, $admin->attribute( 'contentobject_id' ) );
        $failed = false;
        foreach ( \CjwNewsletterTranslatableFields::apply( $dryRun ) as $row )
        {
            $cli->output( sprintf( '%-24s %-20s %s', $row['class'], $row['attribute'], $row['result'] ) );
            $failed = $failed || $row['result'] === 'missing';
        }
        $cli->output( $failed ? 'WARN some classes or fields are missing (are the newsletter classes installed?)' : ( $dryRun ? 'PASS dry run, nothing changed' : 'PASS' ) );
        $this->shutdown( $failed ? 1 : 0 );
    }
}

}
