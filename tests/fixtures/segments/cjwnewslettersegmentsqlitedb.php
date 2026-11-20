<?php
/**
 * File containing the CjwNewsletterSegmentSqliteDb test helper
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage segments
 */

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/classes/segments/cjwnewslettersegmentdb.php';

/**
 * In-memory SQLite database with the newsletter schema, for tests that run without the kernel.
 *
 * @package cjw_newsletter
 * @subpackage segments
 */
class CjwNewsletterSegmentSqliteDb implements CjwNewsletterSegmentDb
{
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        $this->pdo = new PDO( 'sqlite::memory:' );
        $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $this->pdo->exec( file_get_contents( dirname( dirname( dirname( __DIR__ ) ) ) . '/sql/sqlite/schema.sql' ) );
    }

    public function rows( $sql, $offset = 0, $limit = 0 )
    {
        if ( $limit > 0 )
        {
            $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        }
        return $this->pdo->query( $sql )->fetchAll( PDO::FETCH_ASSOC );
    }

    public function quote( $string )
    {
        return str_replace( "'", "''", $string );
    }

    public function exec( $sql )
    {
        return $this->pdo->exec( $sql );
    }

    /**
     * Inserts one row; $values override the column defaults. Returns the new id.
     */
    public function insert( $table, array $values )
    {
        $columns = array_keys( $values );
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . $table . ' ( ' . implode( ', ', $columns ) . ' ) VALUES ( :' . implode( ', :', $columns ) . ' )'
        );
        $stmt->execute( $values );
        return (int)$this->pdo->lastInsertId();
    }

    public function addUser( array $o = array() )
    {
        return $this->insert( 'cjwnl_user', $o + array( 'email' => 'user@example.org', 'status' => 1, 'created' => 1000, 'modified' => 1000 ) );
    }

    public function addSubscription( array $o = array() )
    {
        return $this->insert( 'cjwnl_subscription', $o + array( 'list_contentobject_id' => 1, 'newsletter_user_id' => 1, 'status' => 3, 'created' => 1000, 'approved' => 1000 ) );
    }

    public function addInterest( array $o = array() )
    {
        return $this->insert( 'cjwnl_interest', $o + array( 'list_contentobject_id' => 1, 'identifier' => 'topic' . uniqid(), 'name' => 'Topic', 'created' => 1000 ) );
    }

    public function addUserInterest( array $o = array() )
    {
        return $this->insert( 'cjwnl_user_interest', $o + array( 'newsletter_user_id' => 1, 'interest_id' => 1, 'created' => 1000 ) );
    }

    public function addSend( array $o = array() )
    {
        return $this->insert( 'cjwnl_edition_send', $o + array( 'list_contentobject_id' => 1, 'created' => 1000, 'output_xml' => '' ) );
    }

    public function addItem( array $o = array() )
    {
        return $this->insert( 'cjwnl_edition_send_item', $o + array( 'edition_send_id' => 1, 'newsletter_user_id' => 1, 'subscription_id' => 1, 'created' => 1000 ) );
    }

    public function addLink( array $o = array() )
    {
        return $this->insert( 'cjwnl_link', $o + array( 'edition_send_id' => 1, 'url_hash' => md5( uniqid() ), 'url' => 'https://example.org/', 'created' => 1000 ) );
    }

    public function addClick( array $o = array() )
    {
        return $this->insert( 'cjwnl_link_click', $o + array( 'link_id' => 1, 'edition_send_item_id' => 1, 'created' => 1000 ) );
    }
}
