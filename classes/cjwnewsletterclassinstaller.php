<?php
/**
 * File containing the CjwNewsletterClassInstaller class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * Installs the newsletter content classes from the packages shipped in
 * extension/cjw_newsletter/packages into one content class group.
 *
 * The same code serves the installer of a new installation and the repair of an
 * existing one, and it is idempotent: a class that exists is reported as
 * "already present" and left alone, so no content object is ever touched.
 */
class CjwNewsletterClassInstaller
{
    const DEFAULT_GROUP = 'Newsletter';

    /** Packages in install order. */
    static function packageFiles()
    {
        $dir = dirname( __FILE__ ) . '/../packages/';
        return array( $dir . 'cjw_newsletter_classes-1.0-1.ezpkg',
                      $dir . 'cjw_newsletter_list_virtual-1.0-1.ezpkg' );
    }

    /** Class identifiers the extension needs. */
    static function classIdentifiers()
    {
        return array( 'cjw_newsletter_root', 'cjw_newsletter_system', 'cjw_newsletter_list',
                      'cjw_newsletter_edition', 'cjw_newsletter_article', 'cjw_newsletter_list_virtual' );
    }

    /**
     * Reads the class definitions of a .ezpkg (gzip compressed tar) without extracting it.
     *
     * @return array file name => xml text, only ezcontentclass/class-*.xml entries
     */
    static function readClassXml( $packageFile )
    {
        $tar = is_readable( $packageFile ) ? gzdecode( file_get_contents( $packageFile ) ) : false;
        $result = array();
        if ( $tar === false )
            return $result;
        $offset = 0;
        $length = strlen( $tar );
        while ( $offset + 512 <= $length )
        {
            $header = substr( $tar, $offset, 512 );
            if ( trim( $header, "\0" ) === '' )
                break;
            $name = rtrim( substr( $header, 0, 100 ), "\0" );
            $size = intval( octdec( trim( substr( $header, 124, 12 ), "\0 " ) ) );
            $offset += 512;
            if ( preg_match( '#(^|/)ezcontentclass/(class-[a-z0-9_]+\.xml)$#', $name, $m ) )
                $result[$m[2]] = substr( $tar, $offset, $size );
            $offset += (int)( ceil( $size / 512 ) * 512 );
        }
        return $result;
    }

    /**
     * Imports every missing newsletter class into the group.
     *
     * @return array identifier => 'created' | 'already present' | 'failed'
     */
    static function install( $groupName = self::DEFAULT_GROUP )
    {
        $report = array();
        if ( !eZContentClassGroup::fetchByName( $groupName ) )
        {
            $newGroup = eZContentClassGroup::create();
            $newGroup->setAttribute( 'name', $groupName );
            $newGroup->store();
        }
        $handler = new eZContentClassPackageHandler();
        $userID = (int)eZUser::currentUserID();
        if ( $userID <= 0 )
            $userID = 14;

        foreach ( self::packageFiles() as $packageFile )
        {
            foreach ( self::readClassXml( $packageFile ) as $xml )
            {
                $dom = new DOMDocument();
                if ( !$dom->loadXML( $xml ) )
                    continue;
                $content = $dom->documentElement;
                $identifier = $content->getElementsByTagName( 'identifier' )->item( 0 )->textContent;

                if ( eZContentClass::fetchByIdentifier( $identifier, false ) )
                {
                    $report[$identifier] = 'already present';
                    continue;
                }

                // The package names the group of the system it was exported from; the
                // classes go into the requested group instead.
                $groups = $content->getElementsByTagName( 'groups' )->item( 0 );
                while ( $groups->firstChild )
                    $groups->removeChild( $groups->firstChild );
                $group = $dom->createElement( 'group' );
                $group->setAttribute( 'id', '0' );
                $group->setAttribute( 'name', $groupName );
                $groups->appendChild( $group );

                $installParameters = array( 'user_id' => $userID );
                $installData = array();
                $ok = $handler->install( null, 'install', array(), $identifier, false, '', 'ezcontentclass',
                                         $content, $installParameters, $installData );
                $report[$identifier] = ( $ok && eZContentClass::fetchByIdentifier( $identifier, false ) ) ? 'created' : 'failed';
            }
        }

        // A class that already existed outside the group is linked to it as well, so the
        // group always lists the whole set.
        $classGroup = eZContentClassGroup::fetchByName( $groupName );
        if ( $classGroup )
        {
            foreach ( self::classIdentifiers() as $identifier )
            {
                $class = eZContentClass::fetchByIdentifier( $identifier, true, eZContentClass::VERSION_STATUS_DEFINED );
                if ( $class && !eZContentClassClassGroup::fetch( $class->attribute( 'id' ), 0, $classGroup->attribute( 'id' ) ) )
                    $classGroup->appendClass( $class );
            }
        }
        eZContentClass::expireCache();
        return $report;
    }
}
