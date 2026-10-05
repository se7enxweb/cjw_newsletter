<?php
/**
 * File containing the CjwNewsletterSubscriberExport class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage importexport
 */

/**
 * The subscribers of one list as CSV, with filters (subscription status, a date range on a chosen date) and a
 * choice of columns.
 *
 * Every cell that a spreadsheet would read as a formula (starting with = + - @, a tab or a carriage return) gets a
 * leading apostrophe. Every export is recorded in the audit trail (data.export.csv) with the list, the filters,
 * the columns and the number of rows, never the addresses themselves.
 *
 * @package cjw_newsletter
 * @subpackage importexport
 */
class CjwNewsletterSubscriberExport
{
    /** @var array column => SQL expression; the order is the default order of the file */
    static $columns = array(
        'email' => 'u.email',
        'salutation' => 'u.salutation',
        'first_name' => 'u.first_name',
        'last_name' => 'u.last_name',
        'organisation' => 'u.organisation',
        'language' => 'u.language',
        'phone_number' => 'u.phone_number',
        'custom_data_text_1' => 'u.custom_data_text_1',
        'custom_data_text_2' => 'u.custom_data_text_2',
        'custom_data_text_3' => 'u.custom_data_text_3',
        'custom_data_text_4' => 'u.custom_data_text_4',
        'subscription_status' => 's.status',
        'output_formats' => 's.output_format_array_string',
        'subscribed' => 's.created',
        'confirmed' => 's.confirmed',
        'approved' => 's.approved',
        'removed' => 's.removed',
        'user_status' => 'u.status',
        'subscription_id' => 's.id',
        'newsletter_user_id' => 'u.id',
        'import_id' => 's.import_id' );

    /** @var string[] the columns shown checked when the form opens */
    static $defaultColumns = array( 'email', 'salutation', 'first_name', 'last_name', 'subscription_status', 'subscribed', 'confirmed', 'approved' );

    /** @var string[] the columns holding a time stamp: written as ISO 8601 dates */
    static $dateColumns = array( 'subscribed', 'confirmed', 'approved', 'removed' );

    /** @var array the date a range filter may apply to => column of cjwnl_subscription */
    static $dateFields = array( 'subscribed' => 's.created', 'confirmed' => 's.confirmed', 'approved' => 's.approved', 'removed' => 's.removed' );

    /**
     * @return array status id => name of the subscription statuses
     */
    static function statusNames()
    {
        return array( CjwNewsletterSubscription::STATUS_PENDING => 'pending',
                      CjwNewsletterSubscription::STATUS_CONFIRMED => 'confirmed',
                      CjwNewsletterSubscription::STATUS_APPROVED => 'approved',
                      CjwNewsletterSubscription::STATUS_REMOVED_SELF => 'removed_self',
                      CjwNewsletterSubscription::STATUS_REMOVED_ADMIN => 'removed_admin',
                      CjwNewsletterSubscription::STATUS_BOUNCED_SOFT => 'bounced_soft',
                      CjwNewsletterSubscription::STATUS_BOUNCED_HARD => 'bounced_hard',
                      CjwNewsletterSubscription::STATUS_BLACKLISTED => 'blacklisted' );
    }

    /**
     * Cleans the filters of a form or a command.
     *
     * @param array $input statuses (int[]), date_field, date_from, date_to (Y-m-d), columns (string[]), delimiter
     * @return array the same keys, valid values only (date_from and date_to as time stamps, 0 = open)
     */
    static function cleanFilters( $input )
    {
        $statuses = array();
        foreach ( isset( $input['statuses'] ) ? (array)$input['statuses'] : array() as $status )
            if ( isset( self::statusNames()[(int)$status] ) && ctype_digit( (string)$status ) )
                $statuses[] = (int)$status;
        $columns = array();
        foreach ( isset( $input['columns'] ) ? (array)$input['columns'] : array() as $column )
            if ( isset( self::$columns[$column] ) && !in_array( $column, $columns, true ) )
                $columns[] = $column;
        if ( !$columns )
            $columns = self::$defaultColumns;
        $dateField = isset( $input['date_field'] ) && isset( self::$dateFields[$input['date_field']] ) ? $input['date_field'] : 'subscribed';
        return array( 'statuses' => array_values( array_unique( $statuses ) ),
                      'date_field' => $dateField,
                      'date_from' => self::day( isset( $input['date_from'] ) ? $input['date_from'] : '', false ),
                      'date_to' => self::day( isset( $input['date_to'] ) ? $input['date_to'] : '', true ),
                      'columns' => $columns,
                      'delimiter' => CjwNewsletterCsvMapper::delimiterCharacter( isset( $input['delimiter'] ) ? $input['delimiter'] : ';' ) );
    }

    /**
     * @param string $value Y-m-d
     * @param bool $end the end of that day
     * @return int a time stamp, 0 when the value is not a date
     */
    static function day( $value, $end )
    {
        if ( !is_string( $value ) || !preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) || !checkdate( (int)$m[2], (int)$m[3], (int)$m[1] ) )
            return 0;
        $time = mktime( 0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1] );
        return $end ? $time + 86399 : $time;
    }

    /** @return string the WHERE clause of a list and its filters */
    protected static function where( $listId, $filters )
    {
        $where = 's.list_contentobject_id = ' . (int)$listId . ' AND s.newsletter_user_id = u.id';
        if ( $filters['statuses'] )
            $where .= ' AND s.status IN ( ' . implode( ', ', array_map( 'intval', $filters['statuses'] ) ) . ' )';
        $field = self::$dateFields[$filters['date_field']];
        if ( $filters['date_from'] > 0 )
            $where .= ' AND ' . $field . ' >= ' . (int)$filters['date_from'];
        if ( $filters['date_to'] > 0 )
            $where .= ' AND ' . $field . ' <= ' . (int)$filters['date_to'] . ' AND ' . $field . ' > 0';
        return $where;
    }

    /** @return int the rows the filters select */
    static function count( $listId, $filters )
    {
        $rows = eZDB::instance()->arrayQuery( 'SELECT COUNT(*) AS c FROM cjwnl_subscription s, cjwnl_user u WHERE ' . self::where( $listId, $filters ) );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }

    /**
     * @param int $listId
     * @param array $filters cleanFilters()
     * @param int $limit 0 = all
     * @param int $offset
     * @param bool $newestFirst the newest subscriptions first (the preview); the file keeps the order they were made
     * @return array[] rows, column => value as written to the file (dates and statuses readable, not yet defused)
     */
    static function fetchRows( $listId, $filters, $limit = 0, $offset = 0, $newestFirst = false )
    {
        $select = array();
        foreach ( $filters['columns'] as $column )
            $select[] = self::$columns[$column] . ' AS ' . $column;
        $sql = 'SELECT ' . implode( ', ', $select ) . ' FROM cjwnl_subscription s, cjwnl_user u WHERE ' . self::where( $listId, $filters ) . ' ORDER BY s.id' . ( $newestFirst ? ' DESC' : '' );
        $params = (int)$limit > 0 ? array( 'limit' => (int)$limit, 'offset' => (int)$offset ) : array();
        $rows = eZDB::instance()->arrayQuery( $sql, $params );
        $names = self::statusNames();
        $out = array();
        foreach ( is_array( $rows ) ? $rows : array() as $row )
        {
            $line = array();
            foreach ( $filters['columns'] as $column )
            {
                $value = isset( $row[$column] ) ? $row[$column] : '';
                if ( in_array( $column, self::$dateColumns, true ) )
                    $value = (int)$value > 0 ? gmdate( 'Y-m-d\TH:i:s\Z', (int)$value ) : '';
                else if ( $column === 'subscription_status' )
                    $value = isset( $names[(int)$value] ) ? $names[(int)$value] : (string)$value;
                else if ( $column === 'output_formats' )
                    $value = implode( ',', array_filter( explode( ';', (string)$value ), 'strlen' ) );
                $line[$column] = (string)$value;
            }
            $out[] = $line;
        }
        return $out;
    }

    /**
     * @param string $value
     * @return string the value, with a leading apostrophe when a spreadsheet would run it as a formula
     */
    static function defuse( $value )
    {
        $value = (string)$value;
        if ( $value !== '' && strpos( "=+-@\t\r", $value[0] ) !== false && !is_numeric( $value ) )
            return "'" . $value;
        return $value;
    }

    /**
     * @param resource $fp
     * @param string[] $values
     * @param string $delimiter
     */
    static function writeLine( $fp, $values, $delimiter )
    {
        fputcsv( $fp, array_map( array( __CLASS__, 'defuse' ), array_values( $values ) ), $delimiter, '"', '' );
    }

    /**
     * Writes the whole file to a stream, in batches.
     *
     * @param resource $fp
     * @param int $listId
     * @param array $filters
     * @return int the rows written
     */
    static function write( $fp, $listId, $filters )
    {
        fwrite( $fp, "\xEF\xBB\xBF" );
        self::writeLine( $fp, $filters['columns'], $filters['delimiter'] );
        $written = 0;
        $batch = 500;
        for ( $offset = 0; ; $offset += $batch )
        {
            $rows = self::fetchRows( $listId, $filters, $batch, $offset );
            foreach ( $rows as $row )
                self::writeLine( $fp, $row, $filters['delimiter'] );
            $written += count( $rows );
            if ( count( $rows ) < $batch )
                break;
        }
        return $written;
    }

    /**
     * The file as a string (the preview and the tests).
     *
     * @return string
     */
    static function csv( $listId, $filters, $limit = 0 )
    {
        $fp = fopen( 'php://temp', 'r+' );
        if ( (int)$limit > 0 )
        {
            self::writeLine( $fp, $filters['columns'], $filters['delimiter'] );
            foreach ( self::fetchRows( $listId, $filters, $limit ) as $row )
                self::writeLine( $fp, $row, $filters['delimiter'] );
        }
        else
            self::write( $fp, $listId, $filters );
        rewind( $fp );
        $text = stream_get_contents( $fp );
        fclose( $fp );
        return $text;
    }

    /**
     * Records an export in the audit trail.
     *
     * @param int $listId
     * @param array $filters
     * @param int $rows
     * @param string $by web or console
     */
    static function audit( $listId, $filters, $rows, $by = 'web' )
    {
        if ( !class_exists( 'expAudit' ) )
            return;
        $names = self::statusNames();
        expAudit::event( 'data.export.csv', array(
            'object' => array( 'type' => 'cjw_newsletter_list', 'id' => (int)$listId ),
            'after' => array( 'rows' => (int)$rows ),
            'x' => array( 'extension' => 'cjw_newsletter', 'export' => 'subscribers', 'by' => (string)$by,
                          'statuses' => array_map( function ( $s ) use ( $names ) { return $names[$s]; }, $filters['statuses'] ),
                          'date_field' => $filters['date_field'],
                          'date_from' => $filters['date_from'] ? gmdate( 'Y-m-d', $filters['date_from'] ) : '',
                          'date_to' => $filters['date_to'] ? gmdate( 'Y-m-d', $filters['date_to'] ) : '',
                          'columns' => $filters['columns'] ) ) );
    }

    /** @return string the name of the download */
    static function fileName( $listId )
    {
        return 'newsletter_subscribers_' . (int)$listId . '_' . gmdate( 'Ymd_His' ) . '.csv';
    }
}

?>
