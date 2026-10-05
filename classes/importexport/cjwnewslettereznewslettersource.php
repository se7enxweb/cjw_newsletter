<?php
/**
 * File containing the CjwNewsletterEznewsletterSource class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage importexport
 */

/**
 * Read-only access to the tables of an old eznewsletter installation, for ext:cjw_newsletter:import-eznewsletter.
 *
 * The tables are read from the installation's own database (the usual case: the old extension ran on the same
 * database), or from an SQLite file (a copy of the old data). Only SELECT statements are run; anything else is
 * refused before it reaches the database, and an SQLite file is opened read-only.
 *
 * @package cjw_newsletter
 * @subpackage importexport
 */
class CjwNewsletterEznewsletterSource
{
    /** @var string[] the old tables the migration reads; the first three are needed */
    static $tables = array( 'ezsubscription_list', 'ezsubscription', 'ezsubscriptionuserdata', 'ezrobinsonlist',
                            'eznewsletter', 'ezsendnewsletteritem', 'eznewslettertype', 'ez_newsletter_subscription' );

    /** @var string[] */
    static $requiredTables = array( 'ezsubscription_list', 'ezsubscription', 'ezsubscriptionuserdata' );

    /** @var PDO|null */
    protected $pdo = null;

    /** @var eZDBInterface|null */
    protected $db = null;

    /** @var string */
    protected $label = '';

    /** @var array|null name => true */
    protected $tableList = null;

    /**
     * The installation's own database.
     * @return CjwNewsletterEznewsletterSource
     */
    static function sameDatabase()
    {
        $source = new self();
        $source->db = eZDB::instance();
        $source->label = 'database';
        return $source;
    }

    /**
     * An SQLite file, opened read-only.
     *
     * @param string $file
     * @return CjwNewsletterEznewsletterSource
     * @throws RuntimeException when it cannot be opened
     */
    static function sqliteFile( $file )
    {
        if ( !class_exists( 'PDO' ) || !in_array( 'sqlite', PDO::getAvailableDrivers(), true ) )
            throw new RuntimeException( 'PDO SQLite is not available' );
        if ( !is_file( $file ) || !is_readable( $file ) )
            throw new RuntimeException( 'Not a readable file: ' . $file );
        $options = array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC );
        if ( defined( 'PDO::SQLITE_ATTR_OPEN_FLAGS' ) && defined( 'PDO::SQLITE_OPEN_READONLY' ) )
            $options[PDO::SQLITE_ATTR_OPEN_FLAGS] = PDO::SQLITE_OPEN_READONLY;
        $source = new self();
        $source->pdo = new PDO( 'sqlite:' . $file, null, null, $options );
        $source->pdo->exec( 'PRAGMA query_only = ON' );
        $source->label = 'sqlite:' . basename( $file );
        return $source;
    }

    /** @return string where the data is read from */
    function label()
    {
        return $this->label;
    }

    /** @return bool the table exists in the source */
    function hasTable( $table )
    {
        if ( $this->tableList === null )
        {
            $this->tableList = array();
            if ( $this->pdo )
            {
                foreach ( $this->pdo->query( "SELECT name FROM sqlite_master WHERE type = 'table'" ) as $row )
                    $this->tableList[strtolower( $row['name'] )] = true;
            }
            else
            {
                foreach ( array_keys( (array)$this->db->eZTableList() ) as $name )
                    $this->tableList[strtolower( $name )] = true;
            }
        }
        return isset( $this->tableList[strtolower( $table )] );
    }

    /** @return string[] the required tables that are missing */
    function missingTables()
    {
        $missing = array();
        foreach ( self::$requiredTables as $table )
            if ( !$this->hasTable( $table ) )
                $missing[] = $table;
        return $missing;
    }

    /**
     * Runs one SELECT.
     *
     * @param string $sql a SELECT statement, without a trailing semicolon
     * @param int $limit 0 = no limit
     * @param int $offset
     * @return array[] the rows
     * @throws InvalidArgumentException for anything that is not one SELECT
     */
    function select( $sql, $limit = 0, $offset = 0 )
    {
        $sql = trim( (string)$sql );
        if ( !preg_match( '/^SELECT\s/i', $sql ) || strpos( $sql, ';' ) !== false
             || preg_match( '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|REPLACE|TRUNCATE|ATTACH|PRAGMA)\b/i', $sql ) )
            throw new InvalidArgumentException( 'The eznewsletter source only runs SELECT statements' );
        if ( $this->pdo )
        {
            if ( (int)$limit > 0 )
                $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
            $statement = $this->pdo->query( $sql );
            return $statement->fetchAll();
        }
        $params = (int)$limit > 0 ? array( 'limit' => (int)$limit, 'offset' => (int)$offset ) : array();
        $rows = $this->db->arrayQuery( $sql, $params );
        return is_array( $rows ) ? $rows : array();
    }

    /** @return int the rows of a table */
    function count( $table, $where = '' )
    {
        if ( !preg_match( '/^[a-z_]+$/', $table ) || !$this->hasTable( $table ) )
            return 0;
        $rows = $this->select( 'SELECT COUNT(*) AS c FROM ' . $table . ( $where !== '' ? ' WHERE ' . $where : '' ) );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }
}

?>
