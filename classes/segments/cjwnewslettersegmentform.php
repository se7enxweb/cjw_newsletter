<?php
/**
 * File containing the CjwNewsletterSegmentForm class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage segments
 */

/**
 * Turns the posted rows of the segment form into conditions and back. Pure functions, no kernel class is used.
 *
 * Rows are posted as Condition[<i>][type] and Condition[<i>][<param>]; a row is a flat array of strings
 * (salutation values are a list of strings).
 *
 * @package cjw_newsletter
 * @subpackage segments
 */
class CjwNewsletterSegmentForm
{
    /**
     * @param array $post the POST variables
     * @return array[] the posted rows, reindexed in the order they were posted
     */
    public static function rows( array $post )
    {
        $rows = array();
        if ( isset( $post['Condition'] ) && is_array( $post['Condition'] ) )
        {
            foreach ( $post['Condition'] as $row )
            {
                if ( is_array( $row ) )
                    $rows[] = $row;
            }
        }
        return $rows;
    }

    /** @return string 'any' or 'all' (the fallback) */
    public static function matchMode( array $post )
    {
        return isset( $post['MatchMode'] ) && $post['MatchMode'] === 'any' ? 'any' : 'all';
    }

    /** @return array[] the rows with an empty one appended */
    public static function addRow( array $rows )
    {
        $rows[] = array( 'type' => '' );
        return $rows;
    }

    /** @return array[] the rows without row $index (unchanged when there is none) */
    public static function removeRow( array $rows, $index )
    {
        unset( $rows[(int)$index] );
        return array_values( $rows );
    }

    /**
     * @param array[] $rows posted rows
     * @param bool $tagsAvailable see CjwNewsletterSegmentCondition::normalise()
     * @return array conditions (the normalised conditions, empty when there is any error) and errors (row index => error code)
     */
    public static function validate( array $rows, $tagsAvailable = true )
    {
        $conditions = array();
        $errors = array();
        foreach ( array_values( $rows ) as $index => $row )
        {
            $result = CjwNewsletterSegmentCondition::normalise( self::toCondition( $row ), $tagsAvailable );
            if ( is_array( $result ) )
                $conditions[] = $result;
            else
                $errors[$index] = $result;
        }
        return array( 'conditions' => $errors ? array() : $conditions, 'errors' => $errors );
    }

    /** @return array the row as a condition for normalise(): text lists split, empty parameters left out */
    public static function toCondition( array $row )
    {
        $condition = array();
        foreach ( $row as $name => $value )
        {
            if ( is_array( $value ) )
            {
                if ( $value )
                    $condition[$name] = array_values( $value );
                continue;
            }
            $value = trim( (string)$value );
            if ( $value === '' )
                continue;
            if ( $name === 'values' && isset( $row['type'] ) && $row['type'] === 'language' )
                $condition[$name] = array_values( array_unique( array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) ) );
            else if ( $name === 'picked' )
                $condition[$name] = $value !== '0';
            else
                $condition[$name] = $value;
        }
        if ( isset( $condition['type'] ) && $condition['type'] === 'profile'
            && isset( $condition['op'] ) && ( $condition['op'] === 'set' || $condition['op'] === 'not_set' ) )
            unset( $condition['value'] );
        if ( isset( $condition['values'] ) && is_array( $condition['values'] ) && !$condition['values'] )
            unset( $condition['values'] );
        return $condition;
    }

    /**
     * @param array[] $conditions stored conditions
     * @return array[] form rows: lists of languages as comma separated text, picked as '1'/'0', the other values as strings
     */
    public static function fromConditions( array $conditions )
    {
        $rows = array();
        foreach ( $conditions as $condition )
        {
            if ( !is_array( $condition ) )
                continue;
            $row = array();
            foreach ( $condition as $name => $value )
            {
                if ( is_bool( $value ) )
                    $row[$name] = $value ? '1' : '0';
                else if ( is_array( $value ) )
                    $row[$name] = isset( $condition['type'] ) && $condition['type'] === 'language'
                        ? implode( ', ', $value ) : array_map( 'strval', $value );
                else
                    $row[$name] = (string)$value;
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
