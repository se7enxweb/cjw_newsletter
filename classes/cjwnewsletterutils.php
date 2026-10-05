<?php
/**
 * File containing the CjwNewsletterUtils class
 *
 * @copyright Copyright (C) 2007-2012 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @package cjw_newsletter
 * @filesource
 */
/**
 * class with some useful functions
 *
 * @version //autogentag//
 * @package cjw_newsletter
 */
class CjwNewsletterUtils extends eZPersistentObject
{

    function __construct(){ }

    /**
     * generate a unique hash md5
     *
     * @param string $flexibleVar is used as a part of string for md5
     * @return string md5
     */
    /**
     * The content object of an id that a newsletter table holds (a list, an edition, a picked article), or null
     * when it is gone: unlike eZContentObject::fetch() it writes no debug error for a removed object.
     *
     * @param int $id
     * @return eZContentObject|null
     */
    static function contentObject( $id )
    {
        $id = (int)$id;
        return $id > 0 && eZContentObject::exists( $id ) ? eZContentObject::fetch( $id ) : null;
    }

    static function generateUniqueMd5Hash( $flexibleVar = '' )
    {
        // The hash is the only secret in a configure or unsubscribe link, so the random part comes from the
        // system's random source and cannot be guessed from the time or from the other hashes.
        $stringForHash = $flexibleVar. '-'. microtime( true ). '-' . bin2hex( random_bytes( 16 ) );
        return md5( $stringForHash );
    }
    /**
     * A redirect target that came with a request: only a path of this installation is accepted, never an absolute URL,
     * a protocol-relative //host, a backslash form or a value with control characters.
     *
     * @param string $uri the requested target
     * @param string $default what is used when the target is not acceptable
     * @return string
     */
    static function localRedirectPath( $uri, $default )
    {
        $uri = trim( (string)$uri );
        if ( $uri === ''
            || preg_match( '#^[A-Za-z][A-Za-z0-9+.-]*:#', $uri )
            || strpos( $uri, '//' ) === 0
            || strpos( $uri, '\\' ) !== false
            || preg_match( '/[\x00-\x1f\x7f]/', $uri ) )
        {
            return $default;
        }
        return $uri;
    }
}

?>