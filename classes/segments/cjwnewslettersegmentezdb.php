<?php
/**
 * File containing the CjwNewsletterSegmentEzDb class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage segments
 */

/**
 * CjwNewsletterSegmentDb on top of eZDB.
 *
 * @package cjw_newsletter
 * @subpackage segments
 */
class CjwNewsletterSegmentEzDb implements CjwNewsletterSegmentDb
{
    public function rows( $sql, $offset = 0, $limit = 0 )
    {
        $params = array();
        if ( $limit > 0 )
        {
            $params = array( 'offset' => (int)$offset, 'limit' => (int)$limit );
        }
        return eZDB::instance()->arrayQuery( $sql, $params );
    }

    public function quote( $string )
    {
        return eZDB::instance()->escapeString( $string );
    }

    /**
     * Whether a table exists, from the database layer's own table list (no query that can fail).
     *
     * @param string $name
     * @return bool
     */
    public function tableExists( $name )
    {
        $tables = eZDB::instance()->relationList( eZDBInterface::RELATION_TABLE );
        if ( !is_array( $tables ) )
        {
            return false;
        }
        return in_array( strtolower( (string)$name ), array_map( 'strtolower', $tables ), true );
    }
}
