<?php
/**
 * File containing the CjwNewsletterSegmentDb interface
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage segments
 */

/**
 * The database access of the segment classes, so that they run on eZDB and in tests on SQLite.
 *
 * @package cjw_newsletter
 * @subpackage segments
 */
interface CjwNewsletterSegmentDb
{
    /**
     * @param string $sql
     * @param int $offset
     * @param int $limit 0 means no limit
     * @return array[] associative rows
     */
    public function rows( $sql, $offset = 0, $limit = 0 );

    /**
     * Escapes a string for use inside a quoted SQL literal. Returns no surrounding quotes.
     *
     * @param string $string
     * @return string
     */
    public function quote( $string );
}
