<?php
//
// Copyright (C) 1998 - 2026 7x & Exponential Foundation. All rights reserved.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 (or any later version).
//

/*!
  \class CjwNewsletterJob cjwnewsletterjob.php
  \brief A run of the export or the import in the background, started from the admin: the same console
         command the cronjob part and the shell use, started with expProcessTools like the preloader.
         The command writes its state and output to var/cjw_newsletter/jobs/<id>.json and <id>.log.
*/

class CjwNewsletterJob
{
    /*!
     \static
     \return the folder of the job files
    */
    static function directory()
    {
        return eZSys::varDirectory() . '/cjw_newsletter/jobs';
    }

    /*!
     \static
     \return true when $id looks like a job id of this class
    */
    static function isID( $id )
    {
        return is_string( $id ) && preg_match( '/^[0-9]{14}-[a-f0-9]{8}$/', $id ) === 1;
    }

    /*!
     \static
     Start a command in the background.

     \param $command 'queue', 'mailbox', 'import' or 'repair'
     \param $arguments array of command line arguments, for example array( '--feed=3' )
     \param $error receives the reason when it could not start
     \return the job id, false on failure
    */
    static function start( $command, $arguments, &$error )
    {
        if ( !in_array( $command, array( 'queue', 'mailbox', 'import', 'repair' ), true ) )
        {
            $error = 'Unknown command.';
            return false;
        }
        $php = class_exists( 'expProcessTools' ) ? expProcessTools::phpCli() : false;
        $setsid = class_exists( 'expProcessTools' ) ? expProcessTools::setsid() : false;
        if ( !$php || !$setsid || !function_exists( 'proc_open' ) )
        {
            $error = class_exists( 'expProcessTools' ) && expProcessTools::error() !== ''
                ? expProcessTools::error() : 'A background run needs proc_open, setsid and the PHP command line.';
            return false;
        }
        $dir = self::directory();
        if ( !is_dir( $dir ) && !eZDir::mkdir( $dir, false, true ) )
        {
            $error = 'The folder ' . $dir . ' cannot be created.';
            return false;
        }
        $id = date( 'YmdHis' ) . '-' . bin2hex( random_bytes( 4 ) );
        $user = eZUser::currentUser();
        self::write( $id, array( 'id' => $id, 'command' => $command, 'status' => 'starting', 'started' => time(),
                                 'by' => $user->attribute( 'login' ), 'result' => null ) );
        touch( $dir . '/' . $id . '.log' );

        $line = array( $setsid, '-f', $php, 'extension/cjw_newsletter/bin/php/' . $command . '.php', '--job=' . $id, '-q' );
        foreach ( $arguments as $argument )
        {
            if ( preg_match( '/^--[a-z\-]+(=[A-Za-z0-9_.\-]+)?$/', $argument ) )
            {
                $line[] = $argument;
            }
        }
        $siteaccess = eZSiteAccess::current();
        if ( isset( $siteaccess['name'] ) && $siteaccess['name'] !== '' )
        {
            $line[] = '--siteaccess=' . $siteaccess['name'];
        }
        if ( function_exists( 'posix_geteuid' ) && posix_geteuid() === 0 )
        {
            $line[] = '--allow-root-user';
        }
        $pipes = array();
        $env = getenv();
        $env = is_array( $env ) ? $env : array();
        if ( empty( $env['PATH'] ) )
        {
            $env['PATH'] = '/usr/local/bin:/usr/bin:/bin';
        }
        $process = @proc_open( $line, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, eZSys::rootDir(), $env );
        if ( !is_resource( $process ) )
        {
            $error = 'The command could not be started.';
            self::write( $id, array( 'id' => $id, 'command' => $command, 'status' => 'failed', 'started' => time(), 'result' => array( 'error' => $error ) ) );
            return false;
        }
        foreach ( $pipes as $pipe )
        {
            fclose( $pipe );
        }
        proc_close( $process );
        return $id;
    }

    /*!
     \static
     Store the state of a job.
    */
    static function write( $id, $state )
    {
        if ( !self::isID( $id ) )
        {
            return false;
        }
        $file = self::directory() . '/' . $id . '.json';
        return file_put_contents( $file . '.tmp', json_encode( $state ) ) !== false && rename( $file . '.tmp', $file );
    }

    /*!
     \static
     \return array( state keys..., 'log' => last lines ), false for an unknown job
    */
    static function status( $id )
    {
        if ( !self::isID( $id ) )
        {
            return false;
        }
        $file = self::directory() . '/' . $id . '.json';
        if ( !is_file( $file ) )
        {
            return false;
        }
        $state = json_decode( (string)file_get_contents( $file ), true );
        if ( !is_array( $state ) )
        {
            return false;
        }
        $log = self::directory() . '/' . $id . '.log';
        $lines = is_file( $log ) ? array_slice( file( $log, FILE_IGNORE_NEW_LINES ), -40 ) : array();
        $state['log'] = $lines;
        // a job that says "starting" or "running" for ten minutes without a sign of life is dead
        if ( in_array( $state['status'], array( 'starting', 'running' ), true ) && is_file( $log ) && filemtime( $log ) < time() - 600 && filemtime( $file ) < time() - 600 )
        {
            $state['status'] = 'failed';
            $state['result'] = array( 'error' => 'The job stopped without a result.' );
        }
        return $state;
    }

    /*!
     \static
     Append a line to the log of a job.
    */
    static function log( $id, $text )
    {
        if ( self::isID( $id ) )
        {
            file_put_contents( self::directory() . '/' . $id . '.log', $text . "\n", FILE_APPEND );
        }
    }

    /*!
     \static
     Keep the newest 20 jobs.
    */
    static function prune()
    {
        $files = glob( self::directory() . '/*.json' );
        if ( !$files || count( $files ) <= 20 )
        {
            return;
        }
        sort( $files );
        foreach ( array_slice( $files, 0, count( $files ) - 20 ) as $file )
        {
            @unlink( $file );
            @unlink( substr( $file, 0, -5 ) . '.log' );
        }
    }

    /*!
     \static
     The command says it runs.
    */
    static function markRunning( $id, $command )
    {
        if ( !self::isID( $id ) )
        {
            return;
        }
        $state = self::status( $id );
        self::write( $id, array( 'id' => $id, 'command' => $command, 'status' => 'running', 'started' => $state ? $state['started'] : time(),
                                 'by' => $state && isset( $state['by'] ) ? $state['by'] : 'job', 'result' => null ) );
    }

    /*!
     \static
     The command says how it ended: $status is 'done' or 'failed'.
    */
    static function markFinished( $id, $command, $status, $result )
    {
        if ( !self::isID( $id ) )
        {
            return;
        }
        $state = self::status( $id );
        self::write( $id, array( 'id' => $id, 'command' => $command, 'status' => $status,
                                 'started' => $state ? $state['started'] : time(), 'finished' => time(),
                                 'by' => $state && isset( $state['by'] ) ? $state['by'] : 'job', 'result' => $result ) );
        self::prune();
    }
}

?>
