<?php
/**
 * File containing the CjwNewsletterTranslatableFields class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The text fields of the newsletter classes are translatable (cjw_newsletter 4.2.0): one edition with translations,
 * each subscriber gets his language. The classes of the packages up to 4.1 have every field fixed for all languages
 * (can_translate 0), and a translation then carries the same texts as the main language.
 *
 * apply() turns the text fields on and leaves every other field (the list and edition settings, images,
 * relations) as it is. The class installer calls it after installing the classes; an existing installation runs
 * ext:cjw_newsletter:translatable-fields once (idempotent, --dry-run shows what would change).
 *
 * @package cjw_newsletter
 */
class CjwNewsletterTranslatableFields
{
    /** @return array class identifier => the identifiers of its text fields */
    static function fields()
    {
        return array( 'cjw_newsletter_edition' => array( 'title', 'short_title', 'short_description', 'description' ),
                      'cjw_newsletter_article' => array( 'title', 'short_description' ) );
    }

    /**
     * @param bool $dryRun report only
     * @return array[] hash( class, attribute, before, after, result: changed|already|would change|missing )
     */
    static function apply( $dryRun = false )
    {
        $report = array();
        $changed = false;
        foreach ( self::fields() as $classIdentifier => $identifiers )
        {
            $class = eZContentClass::fetchByIdentifier( $classIdentifier, true, eZContentClass::VERSION_STATUS_DEFINED );
            if ( !$class )
            {
                $report[] = array( 'class' => $classIdentifier, 'attribute' => '', 'result' => 'missing' );
                continue;
            }
            $attributes = array();
            foreach ( $class->fetchAttributes() as $attribute )
                $attributes[(string)$attribute->attribute( 'identifier' )] = $attribute;
            foreach ( $identifiers as $identifier )
            {
                if ( !isset( $attributes[$identifier] ) )
                {
                    $report[] = array( 'class' => $classIdentifier, 'attribute' => $identifier, 'result' => 'missing' );
                    continue;
                }
                $attribute = $attributes[$identifier];
                if ( (int)$attribute->attribute( 'can_translate' ) === 1 )
                {
                    $report[] = array( 'class' => $classIdentifier, 'attribute' => $identifier, 'result' => 'already' );
                    continue;
                }
                if ( !$dryRun )
                {
                    $attribute->setAttribute( 'can_translate', 1 );
                    $attribute->store();
                    $changed = true;
                }
                $report[] = array( 'class' => $classIdentifier, 'attribute' => $identifier, 'result' => $dryRun ? 'would change' : 'changed' );
            }
        }
        if ( $changed )
        {
            eZContentClassAttribute::expireCache();
            eZContentClass::expireCache();
            eZContentCacheManager::clearAllContentCache();
        }
        return $report;
    }

    /** @return bool every text field is translatable */
    static function isApplied()
    {
        foreach ( self::apply( true ) as $row )
            if ( $row['result'] === 'would change' )
                return false;
        return true;
    }
}

?>
