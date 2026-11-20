<?php
/**
 * File containing the CjwNewsletterSegmentCondition class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage segments
 */

/**
 * Validates and cleans one segment condition. Pure functions, no kernel class is used.
 *
 * A condition is an array with the key `type` and the parameters of that type as further keys.
 * Field names come from the whitelists below, so no condition can put user input into SQL as an identifier.
 *
 * @package cjw_newsletter
 * @subpackage segments
 */
class CjwNewsletterSegmentCondition
{
    const ERROR_UNKNOWN_TYPE = 'unknown_type';
    const ERROR_MISSING_PARAM = 'missing_param';
    const ERROR_INVALID_PARAM = 'invalid_param';
    const ERROR_FIELD_NOT_ALLOWED = 'field_not_allowed';
    const ERROR_TAGS_UNAVAILABLE = 'tags_unavailable';

    /** @var string[] */
    public static $subscriptionFields = array( 'created', 'approved' );

    /** @var string[] */
    public static $profileFields = array(
        'first_name', 'last_name', 'organisation',
        'custom_data_text_1', 'custom_data_text_2', 'custom_data_text_3', 'custom_data_text_4'
    );

    /** @var string[] */
    public static $profileOperators = array( 'set', 'not_set', 'equals', 'contains' );

    /** @var string[] */
    public static $behaviorTypes = array( 'opened', 'clicked', 'not_opened', 'not_clicked', 'clicked_object', 'clicked_tag' );

    /** @var string[] */
    public static $types = array(
        'subscribed_within', 'language', 'output_format', 'salutation', 'profile', 'interest',
        'opened', 'clicked', 'not_opened', 'not_clicked', 'clicked_object', 'clicked_tag'
    );

    /**
     * @param array $condition
     * @param bool $tagsAvailable false when the table eztags_attribute_link does not exist
     * @return array|string the cleaned condition, or an error code (ERROR_*)
     */
    public static function normalise( array $condition, $tagsAvailable = true )
    {
        $type = isset( $condition['type'] ) && is_string( $condition['type'] ) ? $condition['type'] : '';
        if ( !in_array( $type, self::$types, true ) )
        {
            return self::ERROR_UNKNOWN_TYPE;
        }

        switch ( $type )
        {
            case 'subscribed_within':
                $field = self::param( $condition, 'field' );
                if ( $field === null )
                {
                    return self::ERROR_MISSING_PARAM;
                }
                if ( !is_string( $field ) || !in_array( $field, self::$subscriptionFields, true ) )
                {
                    return self::ERROR_FIELD_NOT_ALLOWED;
                }
                $days = self::positiveInt( $condition, 'days' );
                return is_int( $days ) ? array( 'type' => $type, 'field' => $field, 'days' => $days ) : $days;

            case 'language':
                $values = self::stringList( $condition, 'values' );
                return is_array( $values ) ? array( 'type' => $type, 'values' => $values ) : $values;

            case 'output_format':
                $format = self::param( $condition, 'format' );
                if ( $format === null )
                {
                    return self::ERROR_MISSING_PARAM;
                }
                if ( $format !== 'html' && $format !== 'text' )
                {
                    return self::ERROR_INVALID_PARAM;
                }
                return array( 'type' => $type, 'format' => $format );

            case 'salutation':
                $values = self::param( $condition, 'values' );
                if ( $values === null )
                {
                    return self::ERROR_MISSING_PARAM;
                }
                if ( !is_array( $values ) || !$values )
                {
                    return self::ERROR_INVALID_PARAM;
                }
                $clean = array();
                foreach ( $values as $value )
                {
                    if ( !self::isIntLike( $value ) || !in_array( (int)$value, array( 0, 1, 2 ), true ) )
                    {
                        return self::ERROR_INVALID_PARAM;
                    }
                    $clean[] = (int)$value;
                }
                return array( 'type' => $type, 'values' => array_values( array_unique( $clean ) ) );

            case 'profile':
                $field = self::param( $condition, 'field' );
                $op = self::param( $condition, 'op' );
                if ( $field === null || $op === null )
                {
                    return self::ERROR_MISSING_PARAM;
                }
                if ( !is_string( $field ) || !in_array( $field, self::$profileFields, true ) )
                {
                    return self::ERROR_FIELD_NOT_ALLOWED;
                }
                if ( !is_string( $op ) || !in_array( $op, self::$profileOperators, true ) )
                {
                    return self::ERROR_INVALID_PARAM;
                }
                $clean = array( 'type' => $type, 'field' => $field, 'op' => $op );
                if ( $op === 'equals' || $op === 'contains' )
                {
                    $value = self::param( $condition, 'value' );
                    if ( $value === null )
                    {
                        return self::ERROR_MISSING_PARAM;
                    }
                    if ( !is_string( $value ) || $value === '' )
                    {
                        return self::ERROR_INVALID_PARAM;
                    }
                    $clean['value'] = $value;
                }
                return $clean;

            case 'interest':
                $id = self::positiveInt( $condition, 'interest_id' );
                if ( !is_int( $id ) )
                {
                    return $id;
                }
                $picked = isset( $condition['picked'] ) ? $condition['picked'] : true;
                if ( !is_bool( $picked ) )
                {
                    return self::ERROR_INVALID_PARAM;
                }
                return array( 'type' => $type, 'interest_id' => $id, 'picked' => $picked );

            case 'opened':
            case 'clicked':
                $window = self::param( $condition, 'window' );
                if ( $window === null )
                {
                    return self::ERROR_MISSING_PARAM;
                }
                if ( $window !== 'sends' && $window !== 'days' )
                {
                    return self::ERROR_INVALID_PARAM;
                }
                $n = self::positiveInt( $condition, 'n' );
                return is_int( $n ) ? array( 'type' => $type, 'window' => $window, 'n' => $n ) : $n;

            case 'not_opened':
            case 'not_clicked':
                $n = self::positiveInt( $condition, 'n' );
                return is_int( $n ) ? array( 'type' => $type, 'n' => $n ) : $n;

            case 'clicked_object':
                $id = self::positiveInt( $condition, 'contentobject_id' );
                return is_int( $id ) ? array( 'type' => $type, 'contentobject_id' => $id ) : $id;

            case 'clicked_tag':
                if ( !$tagsAvailable )
                {
                    return self::ERROR_TAGS_UNAVAILABLE;
                }
                $id = self::positiveInt( $condition, 'tag_id' );
                return is_int( $id ) ? array( 'type' => $type, 'tag_id' => $id ) : $id;
        }

        return self::ERROR_UNKNOWN_TYPE;
    }

    /**
     * @param array $condition
     * @return bool true for the types that need the send history
     */
    public static function isBehavior( $condition )
    {
        return is_array( $condition ) && isset( $condition['type'] )
            && in_array( $condition['type'], self::$behaviorTypes, true );
    }

    private static function param( array $condition, $name )
    {
        return array_key_exists( $name, $condition ) && $condition[$name] !== null ? $condition[$name] : null;
    }

    private static function isIntLike( $value )
    {
        return is_int( $value ) || ( is_string( $value ) && preg_match( '/^-?\d+$/', $value ) === 1 );
    }

    /**
     * @return int|string the integer (>= 1) or an error code
     */
    private static function positiveInt( array $condition, $name )
    {
        $value = self::param( $condition, $name );
        if ( $value === null )
        {
            return self::ERROR_MISSING_PARAM;
        }
        if ( !self::isIntLike( $value ) || (int)$value < 1 )
        {
            return self::ERROR_INVALID_PARAM;
        }
        return (int)$value;
    }

    /**
     * @return string[]|string a non-empty list of non-empty strings, or an error code
     */
    private static function stringList( array $condition, $name )
    {
        $values = self::param( $condition, $name );
        if ( $values === null )
        {
            return self::ERROR_MISSING_PARAM;
        }
        if ( !is_array( $values ) || !$values )
        {
            return self::ERROR_INVALID_PARAM;
        }
        $clean = array();
        foreach ( $values as $value )
        {
            if ( !is_string( $value ) || $value === '' )
            {
                return self::ERROR_INVALID_PARAM;
            }
            $clean[] = $value;
        }
        return array_values( array_unique( $clean ) );
    }
}
