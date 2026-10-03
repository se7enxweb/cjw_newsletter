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

    const SECTION_NAME = 'CJW Newsletter';

    /** First node of a class directly below a parent node, or false. */
    static function findChildNode( $parentNodeID, $classIdentifier )
    {
        $nodes = eZContentObjectTreeNode::subTreeByNodeID( array( 'ClassFilterType' => 'include',
                                                                  'ClassFilterArray' => array( $classIdentifier ),
                                                                  'Depth' => 1, 'DepthOperator' => 'eq',
                                                                  'Limitation' => array(), 'LoadDataMap' => false ),
                                                           (int)$parentNodeID );
        return $nodes ? $nodes[0] : false;
    }

    /** The section "CJW Newsletter" with the navigation part of the extension, created when missing. */
    static function installSection( &$report = array() )
    {
        foreach ( eZSection::fetchList() as $section )
        {
            if ( $section->attribute( 'name' ) == self::SECTION_NAME )
            {
                $report['section'] = 'already present';
                return (int)$section->attribute( 'id' );
            }
        }
        $section = new eZSection( array( 'name' => self::SECTION_NAME,
                                         'navigation_part_identifier' => 'eznewsletternavigationpart',
                                         'locale' => '' ) );
        $section->store();
        $report['section'] = 'created';
        return (int)$section->attribute( 'id' );
    }

    /**
     * Creates the newsletter tree when it is missing: a "Newsletter" root (cjw_newsletter_root) below
     * $parentNodeID, a newsletter system and one list with the settings of the site (main siteaccess,
     * HTML and text output, sender from the site's AdminEmail and SiteName), all in the section
     * "CJW Newsletter". Idempotent: a root that is already there (the node RootFolderNodeId names, or the
     * root class directly below the parent) and what it holds is reported as "already present" and left alone.
     * The classes must exist already (see install()).
     *
     * @param int|null $parentNodeID  null = the content root (node 2), and the root RootFolderNodeId names counts
     * @param array $report  receives key => 'created' | 'already present' | 'failed'
     * @return int|false node id of the newsletter root
     */
    static function installTree( $parentNodeID = null, &$report = array() )
    {
        foreach ( array( 'cjw_newsletter_root', 'cjw_newsletter_system', 'cjw_newsletter_list' ) as $identifier )
        {
            if ( !eZContentClass::fetchByIdentifier( $identifier, false ) )
            {
                $report['tree'] = "failed: class $identifier is missing";
                return false;
            }
        }

        $root = false;
        if ( $parentNodeID === null )
        {
            $parentNodeID = 2;
            $configured = (int)eZINI::instance( 'cjw_newsletter.ini' )->variable( 'NewsletterSettings', 'RootFolderNodeId' );
            $node = $configured > 1 ? eZContentObjectTreeNode::fetch( $configured ) : false;
            if ( $node && $node->attribute( 'class_identifier' ) == 'cjw_newsletter_root' )
                $root = $node;
        }
        if ( !$root )
            $root = self::findChildNode( $parentNodeID, 'cjw_newsletter_root' );

        if ( $root )
        {
            $report['root'] = 'already present';
            $rootID = (int)$root->attribute( 'node_id' );
            $system = self::findChildNode( $rootID, 'cjw_newsletter_system' );
            $report['system'] = $system ? 'already present' : 'missing';
            if ( $system && self::findChildNode( $system->attribute( 'node_id' ), 'cjw_newsletter_list' ) )
            {
                $report['list'] = 'already present';
                return $rootID;
            }
        }

        $user = eZUser::currentUser();
        if ( !$user || !$user->isLoggedIn() )
        {
            $admin = eZUser::fetch( 14 );
            if ( $admin )
                eZUser::setCurrentlyLoggedInUser( $admin, 14 );
        }
        $sectionID = self::installSection( $report );

        if ( !$root )
        {
            $root = self::createNode( $parentNodeID, 'cjw_newsletter_root', 'Newsletter', $sectionID );
            if ( !$root )
            {
                $report['root'] = 'failed';
                return false;
            }
            $report['root'] = 'created';
        }
        $rootID = (int)$root->attribute( 'node_id' );
        eZContentObjectTreeNode::assignSectionToSubTree( $rootID, $sectionID );

        $system = self::findChildNode( $rootID, 'cjw_newsletter_system' );
        if ( $system )
            $report['system'] = 'already present';
        else
        {
            $system = self::createNode( $rootID, 'cjw_newsletter_system', 'Newsletter system', $sectionID );
            $report['system'] = $system ? 'created' : 'failed';
        }
        if ( !$system )
            return $rootID;

        $list = self::findChildNode( $system->attribute( 'node_id' ), 'cjw_newsletter_list' );
        if ( $list )
            $report['list'] = 'already present';
        else
        {
            $list = self::createNode( $system->attribute( 'node_id' ), 'cjw_newsletter_list', 'Newsletter list', $sectionID );
            $report['list'] = $list ? 'created' : 'failed';
            if ( $list )
                self::storeListSettings( $list->attribute( 'object' ) );
        }
        eZContentCacheManager::clearAllContentCache();
        return $rootID;
    }

    static function createNode( $parentNodeID, $classIdentifier, $name, $sectionID )
    {
        $object = eZContentFunctions::createAndPublishObject( array( 'parent_node_id' => (int)$parentNodeID,
                                                                     'class_identifier' => $classIdentifier,
                                                                     'section_id' => (int)$sectionID,
                                                                     'attributes' => array( 'title' => $name ) ) );
        return $object ? $object->attribute( 'main_node' ) : false;
    }

    /** Settings of a new list from the site: siteaccess, output formats, sender from AdminEmail and SiteName. */
    static function storeListSettings( $object )
    {
        $attribute = false;
        foreach ( $object->attribute( 'data_map' ) as $candidate )
        {
            if ( $candidate->attribute( 'data_type_string' ) == 'cjwnewsletterlist' )
                $attribute = $candidate;
        }
        if ( !$attribute )
            return false;
        $data = $attribute->attribute( 'content' );
        if ( !( $data instanceof CjwNewsletterList ) )
            return false;

        $siteIni = eZINI::instance( 'site.ini' );
        $nlIni = eZINI::instance( 'cjw_newsletter.ini' );
        $access = $siteIni->variable( 'SiteSettings', 'DefaultAccess' );
        $sender = $siteIni->hasVariable( 'MailSettings', 'AdminEmail' ) ? $siteIni->variable( 'MailSettings', 'AdminEmail' ) : '';
        if ( $sender == '' )
            $sender = $nlIni->variable( 'NewsletterMailSettings', 'EmailSender' );
        $senderName = $siteIni->variable( 'SiteSettings', 'SiteName' );
        if ( $senderName == '' )
            $senderName = $nlIni->variable( 'NewsletterMailSettings', 'EmailSenderName' );

        $data->setAttribute( 'contentclass_id', (int)$object->attribute( 'contentclass_id' ) );
        $data->setAttribute( 'main_siteaccess', $access );
        $data->setAttribute( 'siteaccess_array_string', CjwNewsletterList::arrayToString( array( $access ) ) );
        $data->setAttribute( 'output_format_array_string', CjwNewsletterList::arrayToString( array( 0, 1 ) ) );
        $data->setAttribute( 'email_sender', $sender );
        $data->setAttribute( 'email_sender_name', $senderName );
        $data->setAttribute( 'email_receiver_test', $sender );
        $data->setAttribute( 'skin_name', 'default' );
        $data->setAttribute( 'auto_approve_registered_user', 1 );
        $data->store();
        return true;
    }

    /**
     * Sets RootFolderNodeId in settings/override/cjw_newsletter.ini.append.php of the installation: the file is
     * created when missing, otherwise only that value changes and every other setting stays.
     *
     * @return string 'created' | 'updated' | 'unchanged'
     */
    static function writeRootFolderSetting( $rootNodeID, $file = null )
    {
        $rootNodeID = (int)$rootNodeID;
        if ( $file === null )
            $file = eZSys::rootDir() . '/settings/override/cjw_newsletter.ini.append.php';
        $line = "RootFolderNodeId=$rootNodeID";
        if ( !file_exists( $file ) )
        {
            file_put_contents( $file, "<?php /* #?ini charset=\"utf-8\"?\n\n[NewsletterSettings]\n$line\n\n*/ ?>\n" );
            return 'created';
        }
        $text = file_get_contents( $file );
        if ( preg_match( '/^RootFolderNodeId=(.*)$/m', $text, $m ) )
        {
            if ( trim( $m[1] ) == (string)$rootNodeID )
                return 'unchanged';
            $new = preg_replace( '/^RootFolderNodeId=.*$/m', $line, $text, 1 );
        }
        elseif ( preg_match( '/^\[NewsletterSettings\][ \t]*\r?$/m', $text ) )
            $new = preg_replace( '/^(\[NewsletterSettings\])[ \t]*\r?$/m', "$1\n$line", $text, 1 );
        else
            $new = preg_replace( '/\*\/\s*\?>\s*$/', "[NewsletterSettings]\n$line\n\n*/ ?>\n", $text, 1, $count );
        if ( !isset( $new ) || $new === $text )
            return 'unchanged';
        file_put_contents( $file, $new );
        return 'updated';
    }
}
