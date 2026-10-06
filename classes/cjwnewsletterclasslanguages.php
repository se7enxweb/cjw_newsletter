<?php
/**
 * File containing the CjwNewsletterClassLanguages class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * Moves the newsletter content classes from one language to another (cjw_newsletter 4.2.1: eng-GB to eng-US).
 *
 * The class packages up to 4.2.0 named their classes in eng-GB, and the kernel's package handler creates every
 * language a package names, so installing the newsletter classes added eng-GB to a site whose language is eng-US
 * and made eng-GB the initial language of the newsletter classes. The packages of 4.2.1 use eng-US; an existing
 * installation runs ext:cjw_newsletter:class-languages once.
 *
 * apply() changes, for every version of the newsletter classes: the class and attribute name and description
 * lists (an eng-GB text becomes the eng-US text unless the class has an eng-US text already, and eng-US becomes
 * the always-available language), the language mask and initial language of the class and its rows in
 * ezcontentclass_name. Other languages (ger-DE) keep their texts. Content objects and the language itself are not
 * touched: objects() reports the newsletter objects that still carry the old language, and removing a content
 * language is the administrator's decision (Setup > Languages), because it removes the translations in it.
 * Idempotent: a second run finds nothing to change.
 *
 * @package cjw_newsletter
 */
class CjwNewsletterClassLanguages
{
    const FROM = 'eng-GB';
    const TO = 'eng-US';

    /** @return string[] identifiers of the newsletter classes */
    static function classIdentifiers()
    {
        return CjwNewsletterClassInstaller::classIdentifiers();
    }

    /**
     * The list with $from moved to $to: the text of $from becomes the text of $to (unless $to has one), in the
     * place $from had, and $to becomes the always-available language when the list has a text in $to.
     *
     * @param string $serialized a serialized name or description list
     * @return string|false the new serialized list, or false when it stays as it is (or does not unserialize)
     */
    static function remapList( $serialized, $from = self::FROM, $to = self::TO )
    {
        if ( !is_string( $serialized ) || $serialized === '' )
            return false;
        $list = @unserialize( $serialized, array( 'allowed_classes' => false ) );
        if ( !is_array( $list ) )
            return false;
        $result = array();
        foreach ( $list as $key => $value )
        {
            if ( $key === $from )
            {
                if ( !array_key_exists( $to, $list ) )
                    $result[$to] = $value;
                continue;
            }
            $result[$key] = $value;
        }
        if ( array_key_exists( $to, $result ) && isset( $result['always-available'] ) )
            $result['always-available'] = $to;
        elseif ( isset( $result['always-available'] ) && $result['always-available'] === $from )
            $result['always-available'] = $to;
        if ( $result === $list )
            return false;
        return serialize( $result );
    }

    /** A language mask with the bit of $fromID moved to $toID (bit 1, always available, is kept). */
    static function remapMask( $mask, $fromID, $toID )
    {
        $mask = (int)$mask;
        if ( $fromID && ( $mask & $fromID ) )
            $mask = ( $mask & ~$fromID ) | $toID;
        return $mask;
    }

    /**
     * @param bool $dryRun report only
     * @param string|false $backupFile JSON file that receives the rows before they change (not written in a dry run)
     * @return array hash( rows: list of hash( table, key, change ), error: string|false, backup: string|false )
     */
    static function apply( $dryRun = false, $backupFile = false, $from = self::FROM, $to = self::TO )
    {
        $report = array( 'rows' => array(), 'error' => false, 'backup' => false );
        $fromLanguage = eZContentLanguage::fetchByLocale( $from );
        $toLanguage = eZContentLanguage::fetchByLocale( $to );
        if ( !$toLanguage )
        {
            $report['error'] = "$to is not a content language of this site";
            return $report;
        }
        $fromID = $fromLanguage ? (int)$fromLanguage->attribute( 'id' ) : 0;
        $toID = (int)$toLanguage->attribute( 'id' );
        $db = eZDB::instance();
        $identifiers = array();
        foreach ( self::classIdentifiers() as $identifier )
            $identifiers[] = "'" . $db->escapeString( $identifier ) . "'";

        $backup = array( 'ezcontentclass' => array(), 'ezcontentclass_attribute' => array(), 'ezcontentclass_name' => array() );
        $updates = array();

        $classes = $db->arrayQuery( 'SELECT id, version, identifier, language_mask, initial_language_id, serialized_name_list, serialized_description_list'
                                  . ' FROM ezcontentclass WHERE identifier IN (' . implode( ',', $identifiers ) . ') ORDER BY id, version' );
        foreach ( $classes as $class )
        {
            $id = (int)$class['id'];
            $version = (int)$class['version'];
            $where = "id = $id AND version = $version";
            $set = array();
            foreach ( array( 'serialized_name_list', 'serialized_description_list' ) as $column )
            {
                $new = self::remapList( $class[$column], $from, $to );
                if ( $new !== false )
                    $set[$column] = $new;
            }
            $mask = self::remapMask( $class['language_mask'], $fromID, $toID );
            if ( $mask !== (int)$class['language_mask'] )
                $set['language_mask'] = $mask;
            $initial = (int)$class['initial_language_id'];
            if ( $fromID && $initial === $fromID )
                $set['initial_language_id'] = $initial = $toID;
            if ( $set )
            {
                $backup['ezcontentclass'][] = $class;
                $updates[] = array( 'ezcontentclass', $where, $set );
                $report['rows'][] = array( 'table' => 'ezcontentclass', 'key' => "{$class['identifier']} id $id v$version", 'change' => implode( ', ', array_keys( $set ) ) );
            }

            foreach ( $db->arrayQuery( "SELECT id, version, identifier, serialized_name_list, serialized_description_list FROM ezcontentclass_attribute WHERE contentclass_id = $id AND version = $version ORDER BY id" ) as $attribute )
            {
                $set = array();
                foreach ( array( 'serialized_name_list', 'serialized_description_list' ) as $column )
                {
                    $new = self::remapList( $attribute[$column], $from, $to );
                    if ( $new !== false )
                        $set[$column] = $new;
                }
                if ( $set )
                {
                    $backup['ezcontentclass_attribute'][] = $attribute;
                    $updates[] = array( 'ezcontentclass_attribute', 'id = ' . (int)$attribute['id'] . ' AND version = ' . (int)$attribute['version'], $set );
                    $report['rows'][] = array( 'table' => 'ezcontentclass_attribute', 'key' => "{$class['identifier']}/{$attribute['identifier']} v$version", 'change' => implode( ', ', array_keys( $set ) ) );
                }
            }

            // ezcontentclass_name: one row per language; bit 1 marks the class's initial language (eZContentClassNameList::store())
            $names = $db->arrayQuery( "SELECT contentclass_id, contentclass_version, language_locale, language_id, name FROM ezcontentclass_name WHERE contentclass_id = $id AND contentclass_version = $version" );
            $locales = array();
            foreach ( $names as $row )
                $locales[$row['language_locale']] = true;
            foreach ( $names as $row )
            {
                $rowWhere = "contentclass_id = $id AND contentclass_version = $version AND language_locale = '" . $db->escapeString( $row['language_locale'] ) . "'";
                $locale = $row['language_locale'];
                if ( $locale === $from && isset( $locales[$to] ) )
                {
                    $backup['ezcontentclass_name'][] = $row;
                    $updates[] = array( 'ezcontentclass_name', $rowWhere, null );
                    $report['rows'][] = array( 'table' => 'ezcontentclass_name', 'key' => "{$class['identifier']} v$version $locale", 'change' => "removed ($to has a name)" );
                    continue;
                }
                $set = array();
                $languageID = (int)$row['language_id'] & ~1;
                if ( $locale === $from )
                {
                    $set['language_locale'] = $to;
                    $languageID = $toID;
                }
                $newID = $languageID | ( $languageID === $initial ? 1 : 0 );
                if ( $newID !== (int)$row['language_id'] )
                    $set['language_id'] = $newID;
                if ( $set )
                {
                    $backup['ezcontentclass_name'][] = $row;
                    $updates[] = array( 'ezcontentclass_name', $rowWhere, $set );
                    $report['rows'][] = array( 'table' => 'ezcontentclass_name', 'key' => "{$class['identifier']} v$version $locale", 'change' => implode( ', ', array_keys( $set ) ) );
                }
            }
        }

        if ( $dryRun || !$updates )
            return $report;

        if ( $backupFile )
        {
            if ( !is_dir( dirname( $backupFile ) ) )
                eZDir::mkdir( dirname( $backupFile ), false, true );
            if ( file_put_contents( $backupFile, json_encode( $backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) === false )
            {
                $report['error'] = "cannot write the backup $backupFile, nothing changed";
                return $report;
            }
            $report['backup'] = $backupFile;
        }

        $db->begin();
        foreach ( $updates as $update )
        {
            list( $table, $where, $set ) = $update;
            if ( $set === null )
            {
                $db->query( "DELETE FROM $table WHERE $where" );
                continue;
            }
            $parts = array();
            foreach ( $set as $column => $value )
                $parts[] = is_int( $value ) ? "$column = $value" : "$column = '" . $db->escapeString( $value ) . "'";
            $db->query( "UPDATE $table SET " . implode( ', ', $parts ) . " WHERE $where" );
        }
        $db->commit();

        eZContentClassAttribute::expireCache();
        eZContentClass::expireCache();
        eZContentCacheManager::clearAllContentCache();
        return $report;
    }

    /**
     * Newsletter content objects that still carry $from (language mask or initial language). They are left alone:
     * moving a translation is an editorial step (Content > the object > Translations).
     *
     * @return int
     */
    static function objectCount( $from = self::FROM )
    {
        $language = eZContentLanguage::fetchByLocale( $from );
        if ( !$language )
            return 0;
        $bit = (int)$language->attribute( 'id' );
        $db = eZDB::instance();
        $identifiers = array();
        foreach ( self::classIdentifiers() as $identifier )
            $identifiers[] = "'" . $db->escapeString( $identifier ) . "'";
        $count = 0;
        $rows = $db->arrayQuery( 'SELECT o.language_mask, o.initial_language_id FROM ezcontentobject o, ezcontentclass c'
                               . ' WHERE o.contentclass_id = c.id AND c.version = 0 AND c.identifier IN (' . implode( ',', $identifiers ) . ')' );
        foreach ( $rows as $row )
        {
            if ( ( (int)$row['language_mask'] & $bit ) || (int)$row['initial_language_id'] === $bit )
                $count++;
        }
        return $count;
    }
}

?>
