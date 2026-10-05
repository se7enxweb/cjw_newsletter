<?php
/**
 * File containing the CjwNewsletterCsvMapper class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage importexport
 */

/**
 * Reads a CSV file for an import with a column mapping: the header, the rows, a guess of the mapping from the
 * column names, and one row turned into subscriber fields.
 *
 * A mapping is an array column index => field, the field one of [ImportMappingSettings] MappableFields[] or
 * "ignore". Every row is read whole (no line length limit), a UTF-8 byte order mark is dropped, old Mac line
 * endings are read, and a file in ISO-8859-1 or Windows-1252 is converted to UTF-8.
 *
 * @package cjw_newsletter
 * @subpackage importexport
 */
class CjwNewsletterCsvMapper
{
    const IGNORE = 'ignore';

    /** @var string[] the delimiters a form may choose, by name */
    static $delimiters = array( 'semicolon' => ';', 'comma' => ',', 'tab' => "\t", 'pipe' => '|' );

    /** @var string[] the encodings a file may have */
    static $encodings = array( 'UTF-8', 'ISO-8859-1', 'Windows-1252' );

    /** @var array field => column names that mean it (lower case, without spaces, dashes and underscores) */
    static $synonyms = array(
        'email' => array( 'email', 'emailaddress', 'mail', 'eaddress', 'emailadresse', 'mailadresse', 'adresse', 'courriel', 'epost' ),
        'salutation' => array( 'salutation', 'anrede', 'title', 'civilite', 'gender', 'geschlecht' ),
        'first_name' => array( 'firstname', 'givenname', 'forename', 'vorname', 'prenom', 'fornavn' ),
        'last_name' => array( 'lastname', 'surname', 'familyname', 'name', 'nachname', 'nom', 'etternavn' ),
        'organisation' => array( 'organisation', 'organization', 'company', 'firma', 'unternehmen', 'societe' ),
        'language' => array( 'language', 'locale', 'lang', 'sprache', 'langue', 'sprak' ),
        'phone_number' => array( 'phone', 'phonenumber', 'mobile', 'mobilephone', 'cell', 'telefon', 'handy', 'mobil', 'telephone' ),
        'custom_data_text_1' => array( 'custom1', 'customdatatext1' ),
        'custom_data_text_2' => array( 'custom2', 'customdatatext2' ),
        'custom_data_text_3' => array( 'custom3', 'customdatatext3' ),
        'custom_data_text_4' => array( 'custom4', 'customdatatext4' ) );

    protected $file;
    protected $delimiter;
    protected $hasHeader;
    protected $encoding;
    protected $header = null;
    protected $rows = null;

    /**
     * @param string $file
     * @param string $delimiter the character, or its name (semicolon, comma, tab, pipe)
     * @param bool $hasHeader
     * @param string $encoding
     */
    function __construct( $file, $delimiter = ';', $hasHeader = true, $encoding = 'UTF-8' )
    {
        $this->file = (string)$file;
        $this->delimiter = self::delimiterCharacter( $delimiter );
        $this->hasHeader = (bool)$hasHeader;
        $this->encoding = in_array( $encoding, self::$encodings, true ) ? $encoding : 'UTF-8';
    }

    /**
     * @param string $delimiter a character or a name
     * @return string the character (semicolon when it is not one of the four)
     */
    static function delimiterCharacter( $delimiter )
    {
        $delimiter = (string)$delimiter;
        if ( isset( self::$delimiters[$delimiter] ) )
            return self::$delimiters[$delimiter];
        if ( $delimiter === '\t' )
            return "\t";
        return in_array( $delimiter, self::$delimiters, true ) ? $delimiter : ';';
    }

    /** @return string the name of a delimiter character */
    static function delimiterName( $delimiter )
    {
        $name = array_search( self::delimiterCharacter( $delimiter ), self::$delimiters, true );
        return $name === false ? 'semicolon' : $name;
    }

    /** @return string[] the fields a column can be mapped to ([ImportMappingSettings] MappableFields[]) */
    static function mappableFields()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $fields = $ini->hasVariable( 'ImportMappingSettings', 'MappableFields' ) ? (array)$ini->variable( 'ImportMappingSettings', 'MappableFields' ) : array();
        $result = array();
        foreach ( $fields as $field )
        {
            $field = trim( (string)$field );
            if ( $field !== '' && $field !== self::IGNORE && preg_match( '/^[a-z0-9_]+$/', $field ) && !in_array( $field, $result, true ) )
                $result[] = $field;
        }
        if ( !in_array( 'email', $result, true ) )
            array_unshift( $result, 'email' );
        return $result;
    }

    /**
     * @return array field => its name for the forms (every mappable field, "ignore" first)
     */
    static function fieldNames()
    {
        $names = array( self::IGNORE => 'Do not import', 'email' => 'E-mail address', 'salutation' => 'Salutation', 'first_name' => 'First name',
                        'last_name' => 'Last name', 'organisation' => 'Organisation', 'language' => 'Language', 'phone_number' => 'Phone number',
                        'custom_data_text_1' => 'Custom field 1', 'custom_data_text_2' => 'Custom field 2',
                        'custom_data_text_3' => 'Custom field 3', 'custom_data_text_4' => 'Custom field 4' );
        $result = array( self::IGNORE => ezpI18n::tr( 'cjw_newsletter/importexport', $names[self::IGNORE] ) );
        foreach ( self::mappableFields() as $field )
            $result[$field] = isset( $names[$field] ) ? ezpI18n::tr( 'cjw_newsletter/importexport', $names[$field] ) : $field;
        return $result;
    }

    /**
     * The file read once: the header and every row.
     */
    protected function read()
    {
        if ( $this->rows !== null )
            return;
        $this->rows = array();
        $this->header = array();
        $text = is_file( $this->file ) && is_readable( $this->file ) ? file_get_contents( $this->file ) : false;
        if ( !is_string( $text ) || $text === '' )
            return;
        if ( substr( $text, 0, 3 ) === "\xEF\xBB\xBF" )
            $text = substr( $text, 3 );
        if ( $this->encoding !== 'UTF-8' )
            $text = mb_convert_encoding( $text, 'UTF-8', $this->encoding );
        else if ( !mb_check_encoding( $text, 'UTF-8' ) )
            $text = mb_convert_encoding( $text, 'UTF-8', 'Windows-1252' );
        $text = preg_replace( '/\r\n?/', "\n", $text );
        $fp = fopen( 'php://temp', 'r+' );
        fwrite( $fp, $text );
        rewind( $fp );
        $first = true;
        while ( ( $row = fgetcsv( $fp, 0, $this->delimiter, '"', '\\' ) ) !== false )
        {
            // an empty line
            if ( $row === array( null ) || ( count( $row ) === 1 && trim( (string)$row[0] ) === '' ) )
                continue;
            $row = array_map( function ( $v ) { return trim( (string)$v ); }, $row );
            if ( $first && $this->hasHeader )
                $this->header = $row;
            else
                $this->rows[] = $row;
            $first = false;
        }
        fclose( $fp );
    }

    /** @return string[] the column names: the header row, else "Column 1", ... for as many columns as the widest row */
    function header()
    {
        $this->read();
        if ( $this->hasHeader && $this->header )
            return $this->header;
        $width = 0;
        foreach ( array_slice( $this->rows, 0, 50 ) as $row )
            $width = max( $width, count( $row ) );
        $names = array();
        for ( $i = 1; $i <= $width; $i++ )
            $names[] = ezpI18n::tr( 'cjw_newsletter/importexport', 'Column %number', null, array( '%number' => $i ) );
        return $names;
    }

    /**
     * @param int $limit 0 = every row
     * @param int $offset
     * @return array[] the data rows (without the header), each a list of cell strings
     */
    function rows( $limit = 0, $offset = 0 )
    {
        $this->read();
        return (int)$limit > 0 ? array_slice( $this->rows, (int)$offset, (int)$limit ) : array_slice( $this->rows, (int)$offset );
    }

    /** @return int the data rows */
    function rowCount()
    {
        $this->read();
        return count( $this->rows );
    }

    /**
     * A first mapping from the column names; a column whose name says nothing is ignored. Without a header the
     * column whose first cells hold addresses becomes "email".
     *
     * @param string[] $fields the allowed fields
     * @return array column index => field
     */
    function guessMapping( $fields = null )
    {
        $fields = $fields === null ? self::mappableFields() : $fields;
        $mapping = array();
        $taken = array();
        $header = $this->header();
        if ( $this->hasHeader )
        {
            foreach ( $header as $index => $name )
            {
                $key = preg_replace( '/[^a-z0-9]+/', '', mb_strtolower( (string)$name ) );
                $mapping[$index] = self::IGNORE;
                foreach ( self::$synonyms as $field => $words )
                {
                    if ( in_array( $field, $fields, true ) && !isset( $taken[$field] ) && ( $key === preg_replace( '/_/', '', $field ) || in_array( $key, $words, true ) ) )
                    {
                        $mapping[$index] = $field;
                        $taken[$field] = true;
                        break;
                    }
                }
                // a column named exactly as a field that has no synonyms
                if ( $mapping[$index] === self::IGNORE && in_array( (string)$name, $fields, true ) && !isset( $taken[$name] ) )
                {
                    $mapping[$index] = (string)$name;
                    $taken[$name] = true;
                }
            }
        }
        else
        {
            foreach ( $header as $index => $name )
                $mapping[$index] = self::IGNORE;
        }
        if ( !isset( $taken['email'] ) )
        {
            foreach ( $this->rows( 5 ) as $row )
            {
                foreach ( $row as $index => $cell )
                {
                    if ( strpos( $cell, '@' ) !== false && ezcMailTools::validateEmailAddress( $cell ) && isset( $mapping[$index] ) && $mapping[$index] === self::IGNORE )
                    {
                        $mapping[$index] = 'email';
                        break 2;
                    }
                }
            }
        }
        return $mapping;
    }

    /**
     * Cleans a posted or stored mapping: only known fields, each field at most once, every column present.
     *
     * @param array $mapping column index => field
     * @param int $columns
     * @param string[] $fields
     * @return array column index => field
     */
    static function cleanMapping( $mapping, $columns, $fields = null )
    {
        $fields = $fields === null ? self::mappableFields() : $fields;
        $clean = array();
        $taken = array();
        for ( $i = 0; $i < (int)$columns; $i++ )
        {
            $field = is_array( $mapping ) && isset( $mapping[$i] ) ? (string)$mapping[$i] : self::IGNORE;
            if ( !in_array( $field, $fields, true ) || isset( $taken[$field] ) )
                $field = self::IGNORE;
            if ( $field !== self::IGNORE )
                $taken[$field] = true;
            $clean[$i] = $field;
        }
        return $clean;
    }

    /**
     * A mapping by column name (as stored in cjwnl_import_mapping when the file has a header) applied to a header.
     *
     * @param array $stored column name or index => field
     * @param string[] $header
     * @return array column index => field
     */
    static function mappingForHeader( $stored, $header )
    {
        $mapping = array();
        foreach ( $header as $index => $name )
        {
            if ( isset( $stored[$name] ) )
                $mapping[$index] = $stored[$name];
            else if ( isset( $stored[(string)$index] ) )
                $mapping[$index] = $stored[(string)$index];
            else
                $mapping[$index] = self::IGNORE;
        }
        return $mapping;
    }

    /**
     * @param string[] $row
     * @param array $mapping column index => field
     * @return array field => value (only the mapped fields)
     */
    static function mapRow( $row, $mapping )
    {
        $values = array();
        foreach ( $mapping as $index => $field )
        {
            if ( $field === self::IGNORE )
                continue;
            $values[$field] = isset( $row[$index] ) ? trim( (string)$row[$index] ) : '';
        }
        return $values;
    }

    /**
     * The upload folder of the imports.
     * @return string
     */
    static function directory()
    {
        return eZSys::varDirectory() . '/cjw_newsletter/csvimport';
    }

    /**
     * @param string $file
     * @return bool the file is inside the upload folder (a posted or stored path can never name another file)
     */
    static function isImportFile( $file )
    {
        $dir = realpath( self::directory() );
        $real = is_string( $file ) && $file !== '' ? realpath( $file ) : false;
        return $dir && $real && strpos( $real, $dir . DIRECTORY_SEPARATOR ) === 0 && is_file( $real );
    }
}

?>
