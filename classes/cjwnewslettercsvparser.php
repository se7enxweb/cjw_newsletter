<?php
/**
 * File containing the CjwNewsletterCsvParser class
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @filesource
 */
/**
 * Class description here
 *
 * @version //autogentag//
 * @package cjw_newsletter
 */

class CjwNewsletterCsvParser
{
    /**
     * Constructor
     *
     * @example classes/CjwNewsletterLog.php
     * @param string $csvFileName
     * @param string $delimiter
     * @param boolean $firstRowIsLabel
     * @param array $csvFieldMappingArray
     * @param boolean $utf8Encode
     * @return void
     */
    function __construct( $csvFileName, $delimiter, $firstRowIsLabel, $csvFieldMappingArray, $utf8Encode = false )
    {
        if ( $delimiter == '\t' || $delimiter == 'tab' )
        {
            $delimiter = "\t";
        }
        if ( !is_string( $delimiter ) || strlen( $delimiter ) !== 1 )
        {
            $delimiter = ';';
        }

        $this->CsvDataArray = array();
        $fp = is_readable( $csvFileName ) ? fopen( $csvFileName, 'r' ) : false;
        // a file with old Mac line endings (a carriage return alone): auto_detect_line_endings did that, and it is deprecated since PHP 8.1
        if ( $fp )
        {
            $head = fread( $fp, 1048576 );
            rewind( $fp );
            if ( preg_match( '/\r(?!\n)/', (string)$head ) )
            {
                $normalised = fopen( 'php://temp', 'r+' );
                stream_copy_to_stream( $fp, $normalised );
                fclose( $fp );
                rewind( $normalised );
                $fp = $normalised;
                $text = preg_replace( '/\r\n?/', "\n", stream_get_contents( $fp ) );
                rewind( $fp );
                ftruncate( $fp, 0 );
                fwrite( $fp, $text );
                rewind( $fp );
            }
        }
        if ( !$fp )
        {
            return;
        }
        $rowArray = array();
        $c = 0;
        $row = array();
        // $firstRow = array( 'email', 'first_name', 'last_name', 'salutation' );
        $firstRow = array();

        foreach ( $csvFieldMappingArray as $key => $item )
        {
            $firstRow[] = $key;
        }

        if ( $firstRowIsLabel == true )
        {
            $firstRowTmp = fgetcsv( $fp, 0, $delimiter, '"', '\\' );
            $c++;
        }

        // Loop file
        while ( ( $row = fgetcsv( $fp, 0, $delimiter, '"', '\\' )) !== FALSE )
        {
            for ( $i=0; $i < count( $firstRow ); $i++ )
            {
                if ( array_key_exists( $i, $row ) )
                {
                    if ( $utf8Encode !== FALSE )
                    {
                        $rowArray[ $c ] [ $firstRow[$i] ] = mb_convert_encoding( $row[ $i ], 'UTF-8', 'ISO-8859-1' );
                    }
                    else
                    {
                        $rowArray[ $c ] [ $firstRow[$i] ] = $row[ $i ];
                    }
                }
            }

            $c++;
        }

        fclose ( $fp );

        $this->CsvDataArray = $rowArray;
    }

    /**
     * Returns data array
     *
     * @return array
     */
    function getCsvDataArray()
    {
        return $this->CsvDataArray;
    }


    /**
     *
     * @var array
     */

    var $CsvDataArray;
}

?>
