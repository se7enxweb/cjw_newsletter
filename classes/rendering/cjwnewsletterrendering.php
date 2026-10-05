<?php
/**
 * File containing the CjwNewsletterRendering class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * What the rendering of cjw_newsletter 4.2.0 shares: the skins (allowed per list, chosen per send), the languages of
 * a list and of a subscriber, the outputs of a send per language (cjwnl_edition_send_output), and the bodies of one
 * subscriber's mail (the language, the conditional parts, the "articles for your interests" block).
 *
 * @package cjw_newsletter
 */
class CjwNewsletterRendering
{
    /** @var array send id|language => parsed output (getParsedOutputXml form), for one process */
    protected static $outputCache = array();
    /** @var array list object id => CjwNewsletterList|false */
    protected static $listCache = array();

    // ------------------------------------------------------------------ skins

    /** @return string[] the skins of [NewsletterSettings] AvailableSkinArray[] that have templates */
    static function availableSkins()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $out = array();
        foreach ( array_unique( (array)$ini->variable( 'NewsletterSettings', 'AvailableSkinArray' ) ) as $skin )
        {
            $skin = trim( (string)$skin );
            if ( preg_match( '/^[a-z0-9_]{1,40}$/i', $skin ) && !in_array( $skin, $out, true ) )
                $out[] = $skin;
        }
        return $out;
    }

    /**
     * @param string $skin
     * @return array hash( name, description, preview_image, text_format, accent )
     */
    static function skinSettings( $skin )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $group = 'Skin_' . $skin;
        $get = function ( $name, $default ) use ( $ini, $group ) {
            return $ini->hasVariable( $group, $name ) ? (string)$ini->variable( $group, $name ) : $default;
        };
        return array( 'name' => (string)$skin,
                      'description' => $get( 'Description', ucfirst( (string)$skin ) ),
                      'preview_image' => $get( 'PreviewImage', '' ),
                      'text_format' => $get( 'TextFormat', 'html' ) === 'plain' ? 'plain' : 'html',
                      'accent' => preg_match( '/^#[0-9a-f]{6}$/i', $get( 'AccentColor', '' ) ) ? $get( 'AccentColor', '' ) : '#1f5f8b' );
    }

    /** @return string plain (the skin writes its text part with the plaintext views) or html (the old conversion) */
    static function skinTextFormat( $skin )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        if ( $ini->hasVariable( 'TextViewSettings', 'PlainTextViews' ) && $ini->variable( 'TextViewSettings', 'PlainTextViews' ) === 'disabled' )
            return 'html';
        $settings = self::skinSettings( $skin );
        return $settings['text_format'];
    }

    /**
     * The skins a list allows (skin_name_array_string; empty = every skin), the list's own skin first.
     *
     * @param CjwNewsletterList $list
     * @return string[]
     */
    static function allowedSkins( $list )
    {
        $available = self::availableSkins();
        if ( !is_object( $list ) )
            return $available;
        $allowed = array_values( array_intersect( CjwNewsletterList::stringToArray( (string)$list->attribute( 'skin_name_array_string' ) ), $available ) );
        if ( !$allowed )
            $allowed = $available;
        $own = (string)$list->attribute( 'skin_name' );
        if ( $own !== '' && in_array( $own, $available, true ) && !in_array( $own, $allowed, true ) )
            array_unshift( $allowed, $own );
        return array_values( array_unique( $allowed ) );
    }

    // ------------------------------------------------------------------ languages

    /** @return string[] the locales a list offers (language_array_string) */
    static function listLanguages( $list )
    {
        if ( !is_object( $list ) )
            return array();
        $out = array();
        foreach ( CjwNewsletterList::stringToArray( (string)$list->attribute( 'language_array_string' ) ) as $locale )
            if ( self::isLocale( $locale ) && !in_array( $locale, $out, true ) )
                $out[] = $locale;
        $main = self::mainLanguage( $list );
        if ( $out && !in_array( $main, $out, true ) )
            array_unshift( $out, $main );
        return $out;
    }

    /** @return string the list's main language: main_language, else the locale of its main siteaccess */
    static function mainLanguage( $list )
    {
        if ( is_object( $list ) && self::isLocale( (string)$list->attribute( 'main_language' ) ) )
            return (string)$list->attribute( 'main_language' );
        $siteAccess = is_object( $list ) ? (string)$list->attribute( 'main_siteaccess' ) : '';
        if ( $siteAccess !== '' && class_exists( 'eZSiteAccess' ) )
        {
            try
            {
                $ini = eZSiteAccess::getIni( $siteAccess, 'site.ini' );
                if ( $ini && $ini->hasVariable( 'RegionalSettings', 'Locale' ) && self::isLocale( (string)$ini->variable( 'RegionalSettings', 'Locale' ) ) )
                    return (string)$ini->variable( 'RegionalSettings', 'Locale' );
            }
            catch ( Throwable $e )
            {
            }
        }
        return (string)eZINI::instance()->variable( 'RegionalSettings', 'Locale' );
    }

    /** @return bool a locale like ger-DE */
    static function isLocale( $locale )
    {
        return (bool)preg_match( '/^[a-z]{3}-[A-Z]{2}(@[a-z0-9]+)?$/', (string)$locale );
    }

    /** @return string[] locale => name, every language of the content */
    static function contentLanguages()
    {
        $out = array();
        foreach ( eZContentLanguage::fetchList() as $language )
            $out[(string)$language->attribute( 'locale' )] = (string)$language->attribute( 'name' );
        return $out;
    }

    /**
     * The language a subscriber gets: theirs when the list offers it and the send has an output in it, else the
     * list's main language ([LanguageSettings] FallbackToListMainLanguage).
     *
     * @param CjwNewsletterUser $user
     * @param CjwNewsletterList $list
     * @param string[] $outputLanguages the languages the send has outputs in (the main one included)
     * @return string
     */
    static function pickLanguage( $user, $list, $outputLanguages )
    {
        $main = self::mainLanguage( $list );
        $wanted = is_object( $user ) ? (string)$user->attribute( 'language' ) : '';
        if ( $wanted !== '' && in_array( $wanted, $outputLanguages, true ) && ( !self::listLanguages( $list ) || in_array( $wanted, self::listLanguages( $list ), true ) ) )
            return $wanted;
        return $main;
    }

    /**
     * Switches the current process to another language: the content (prioritized languages), the texts of the
     * templates and the dates. For the output command, which renders one language per process.
     *
     * @param string $locale
     */
    static function switchLanguage( $locale )
    {
        if ( !self::isLocale( $locale ) )
            return;
        $siteIni = eZINI::instance();
        $siteIni->setVariable( 'RegionalSettings', 'Locale', $locale );
        $siteIni->setVariable( 'RegionalSettings', 'ContentObjectLocale', $locale );
        $siteIni->setVariable( 'RegionalSettings', 'TextTranslation', 'enabled' );
        $languages = array( $locale );
        foreach ( (array)$siteIni->variable( 'RegionalSettings', 'SiteLanguageList' ) as $other )
            if ( $other !== $locale )
                $languages[] = $other;
        $siteIni->setVariable( 'RegionalSettings', 'SiteLanguageList', $languages );
        eZContentLanguage::setPrioritizedLanguages( $languages );
        if ( class_exists( 'eZTranslatorManager' ) )
            eZTranslatorManager::resetTranslations();
        if ( method_exists( 'ezpI18n', 'reset' ) )
            ezpI18n::reset();
    }

    /**
     * The variables the skins get besides contentobject (the output command calls it).
     *
     * @param eZTemplate $tpl
     * @param eZContentObjectVersion|null $version the edition's version
     * @param string $skin
     * @param string $language
     * @param array $urlArray hash( ez_url, ez_root ) of the output command
     */
    static function prepareTemplate( $tpl, $version, $skin, $language, $urlArray )
    {
        CjwNewsletterConditions::$active = true;
        CjwNewsletterRenderingOperators::$baseUrl = isset( $urlArray['ez_url'] ) ? (string)$urlArray['ez_url'] : '';
        CjwNewsletterRenderingOperators::$rootUrl = isset( $urlArray['ez_root'] ) ? (string)$urlArray['ez_root'] : '';
        $list = false;
        if ( is_object( $version ) )
        {
            $object = $version->attribute( 'contentobject' );
            $mainNode = $object ? $object->attribute( 'main_node' ) : null;
            $parent = $mainNode ? $mainNode->attribute( 'parent' ) : null;
            if ( $parent )
                $list = CjwNewsletterList::fetchByListObjectVersion( $parent->attribute( 'contentobject_id' ), 0 );
        }
        $tpl->setVariable( 'newsletter_list', $list );
        $tpl->setVariable( 'newsletter_language', (string)$language );
        $tpl->setVariable( 'newsletter_skin', self::skinSettings( $skin ) );
        $tpl->setVariable( 'newsletter_site_url', CjwNewsletterRenderingOperators::$baseUrl );
        $tpl->setVariable( 'newsletter_root_url', CjwNewsletterRenderingOperators::$rootUrl );
    }

    // ------------------------------------------------------------------ the sends

    /** @return CjwNewsletterList|false the list of a send */
    static function listOfSend( $send )
    {
        $id = (int)$send->attribute( 'list_contentobject_id' );
        $version = (int)$send->attribute( 'list_contentobject_version' );
        $key = $id . '|' . $version;
        if ( !array_key_exists( $key, self::$listCache ) )
            self::$listCache[$key] = CjwNewsletterList::fetchByListObjectVersion( $id, $version );
        return self::$listCache[$key];
    }

    /** @return CjwNewsletterEdition|null the edition (datatype content) of a send's edition version */
    static function editionOfSend( $send )
    {
        $version = eZContentObjectVersion::fetchVersion( (int)$send->attribute( 'edition_contentobject_version' ), (int)$send->attribute( 'edition_contentobject_id' ) );
        if ( !is_object( $version ) )
            return null;
        foreach ( $version->dataMap() as $attribute )
            if ( $attribute->attribute( 'data_type_string' ) === 'cjwnewsletteredition' )
            {
                $content = $attribute->attribute( 'content' );
                return $content instanceof CjwNewsletterEdition ? $content : null;
            }
        return null;
    }

    /** @return bool a send of another channel than e-mail (N5's SMS sends): the rendering leaves it alone */
    static function isOtherChannel( $send )
    {
        if ( !is_object( $send ) || !$send->hasAttribute( 'channel' ) )
            return false;
        $channel = (string)$send->attribute( 'channel' );
        return $channel !== '' && $channel !== 'email';
    }

    /** @return string[] the languages of the edition object (its translations) */
    static function editionLanguages( $send )
    {
        $object = eZContentObject::fetch( (int)$send->attribute( 'edition_contentobject_id' ) );
        return $object ? array_values( (array)$object->availableLanguages() ) : array();
    }

    /**
     * Renders the outputs of a send in the other languages of its list (cjwnl_edition_send_output). The main
     * language stays in output_xml.
     *
     * @param CjwNewsletterEditionSend $send
     * @return string[] the languages rendered now
     */
    static function renderLanguageOutputs( $send )
    {
        $list = self::listOfSend( $send );
        $languages = self::listLanguages( $list );
        if ( count( $languages ) < 2 )
            return array();
        $main = self::mainLanguage( $list );
        $have = self::editionLanguages( $send );
        $edition = null;
        $done = array();
        foreach ( $languages as $language )
        {
            if ( $language === $main || !in_array( $language, $have, true ) )
                continue;
            if ( CjwNewsletterEditionSendOutput::fetchByEditionSendIdAndLanguage( $send->attribute( 'id' ), $language ) )
                continue;
            if ( $edition === null )
                $edition = self::editionOfSend( $send );
            if ( !$edition )
                break;
            $xml = $edition->createOutputXml( (string)$send->attribute( 'skin_name' ), $language );
            $row = CjwNewsletterEditionSendOutput::create( array( 'edition_send_id' => (int)$send->attribute( 'id' ),
                'language' => $language, 'output_xml' => $xml, 'created' => time() ) );
            $row->store();
            $done[] = $language;
        }
        return $done;
    }

    /** @return string[] the languages a send has outputs in (the main language first) */
    static function outputLanguages( $send )
    {
        $list = self::listOfSend( $send );
        $out = array( self::mainLanguage( $list ) );
        foreach ( CjwNewsletterEditionSendOutput::fetchList( array( 'edition_send_id' => (int)$send->attribute( 'id' ) ) ) as $row )
            if ( !in_array( (string)$row->attribute( 'language' ), $out, true ) )
                $out[] = (string)$row->attribute( 'language' );
        return $out;
    }

    /**
     * The parsed output of a send in a language, with the condition markers (getParsedOutputXml( 'raw' ) form).
     *
     * @param CjwNewsletterEditionSend $send
     * @param string $language '' or the main language: output_xml
     * @return array output format id => hash( subject, body, ... )
     */
    static function parsedOutput( $send, $language = '' )
    {
        $list = self::listOfSend( $send );
        $main = self::mainLanguage( $list );
        $language = $language === '' ? $main : $language;
        $key = (int)$send->attribute( 'id' ) . '|' . $language . '|' . md5( (string)$send->attribute( 'output_xml' ) );
        if ( isset( self::$outputCache[$key] ) )
            return self::$outputCache[$key];
        $result = array();
        if ( $language !== $main )
        {
            $row = CjwNewsletterEditionSendOutput::fetchByEditionSendIdAndLanguage( $send->attribute( 'id' ), $language );
            if ( $row )
                $result = CjwNewsletterEditionSend::parseOutputXmlString( (string)$row->attribute( 'output_xml' ) );
        }
        if ( !$result )
            $result = CjwNewsletterEditionSend::parseOutputXmlString( (string)$send->attribute( 'output_xml' ) );
        foreach ( $result as $formatId => $format )
            if ( !empty( $format['html_mail_image_include'] ) && (int)$format['html_mail_image_include'] === 1 )
                $result[$formatId] = CjwNewsletterEdition::prepareImageInclude( $format );
        if ( count( self::$outputCache ) > 50 )
            self::$outputCache = array();
        return self::$outputCache[$key] = $result;
    }

    /** Forgets the parsed outputs (a test, a re-render). */
    static function clearCache()
    {
        self::$outputCache = array();
        self::$listCache = array();
        CjwNewsletterInterestBlock::clearCache();
    }

    /**
     * The condition context of a subscriber.
     *
     * @return array hash( mode, user, list_id, language, interests )
     */
    static function subscriberContext( $user, $listObjectId, $language )
    {
        return array( 'mode' => is_object( $user ) ? 'subscriber' : 'anonymous', 'user' => is_object( $user ) ? $user : null,
                      'list_id' => (int)$listObjectId, 'language' => (string)$language,
                      'interests' => is_object( $user ) ? CjwNewsletterInterests::keysForUser( $user->attribute( 'id' ) ) : array() );
    }

    /**
     * The bodies of one subscriber: the conditions resolved and the interests block filled.
     *
     * @param array $bodies hash( html, text )
     * @param array $context subscriberContext()
     * @param array $blockOptions hash( edition_object_id, list_id, language ) for the interests block
     * @return array
     */
    static function resolveBodies( $bodies, $context, $blockOptions = array() )
    {
        $out = array();
        foreach ( (array)$bodies as $type => $body )
        {
            $body = (string)$body;
            $isHtml = $type === 'html';
            if ( CjwNewsletterInterestBlock::hasMarker( $body ) )
                $body = CjwNewsletterInterestBlock::resolve( $body, $isHtml, isset( $context['user'] ) ? $context['user'] : null, $blockOptions );
            $body = CjwNewsletterConditions::resolve( $body, $context );
            if ( !$isHtml && $body !== '' )
                $body = CjwNewsletterPlainText::tidy( $body ) . "\n";
            $out[$type] = $body;
        }
        return $out;
    }

    /**
     * An output array of CjwNewsletterEdition::getOutputRaw() (one format) resolved for a context.
     *
     * @param array $result hash( subject, body: hash( html, text ), ... )
     * @param array $context
     * @return array
     */
    static function resolveOutputArray( $result, $context )
    {
        if ( !is_array( $result ) || !isset( $result['body'] ) || !is_array( $result['body'] ) )
            return $result;
        $options = array( 'edition_object_id' => isset( $result['contentobject_id'] ) ? (int)$result['contentobject_id'] : 0,
                          'list_id' => isset( $context['list_id'] ) ? (int)$context['list_id'] : 0,
                          'language' => isset( $context['language'] ) ? (string)$context['language'] : '',
                          'preview' => isset( $context['mode'] ) && $context['mode'] === 'all' );
        $result['body'] = self::resolveBodies( $result['body'], $context, $options );
        if ( isset( $result['subject'] ) )
            $result['subject'] = CjwNewsletterConditions::resolve( (string)$result['subject'], $context );
        return $result;
    }

    /**
     * The complete mail of one subscriber as the runner sends it, for the preview as a subscriber and the tests:
     * language, conditions, interests block and placeholders.
     *
     * @param CjwNewsletterEditionSend|null $send a send; null = rendered now from the edition
     * @param CjwNewsletterUser $user
     * @param array $options hash( edition: CjwNewsletterEdition (without a send), skin, output_format_id )
     * @return array hash( subject, html, text, language, skin, output_format_id )
     */
    static function mailFor( $send, $user, $options = array() )
    {
        $formatId = isset( $options['output_format_id'] ) ? (int)$options['output_format_id'] : 0;
        if ( is_object( $send ) )
        {
            $list = self::listOfSend( $send );
            $listId = (int)$send->attribute( 'list_contentobject_id' );
            $language = self::pickLanguage( $user, $list, self::outputLanguages( $send ) );
            $parsed = self::parsedOutput( $send, $language );
            $editionObjectId = (int)$send->attribute( 'edition_contentobject_id' );
            $skin = (string)$send->attribute( 'skin_name' );
            $personalize = (int)$send->attribute( 'personalize_content' ) === 1;
            if ( !isset( $parsed[$formatId] ) && $parsed )
                $formatId = (int)key( $parsed );
            $subject = isset( $parsed[$formatId] ) ? $parsed[$formatId]['subject'] : '';
            $bodies = isset( $parsed[$formatId] ) ? $parsed[$formatId]['body'] : array( 'html' => '', 'text' => '' );
        }
        else
        {
            $edition = $options['edition'];
            $list = $edition->attribute( 'list_attribute_content' );
            $listId = (int)$list->attribute( 'contentobject_id' );
            $have = array();
            $object = eZContentObject::fetch( (int)$edition->attribute( 'contentobject_id' ) );
            if ( $object )
                $have = array_values( (array)$object->availableLanguages() );
            $main = self::mainLanguage( $list );
            $language = self::pickLanguage( $user, $list, array_merge( array( $main ), array_intersect( self::listLanguages( $list ), $have ) ) );
            $skin = isset( $options['skin'] ) && $options['skin'] !== '' ? (string)$options['skin'] : (string)$list->attribute( 'skin_name' );
            $raw = CjwNewsletterEdition::getOutputRaw( $edition->attribute( 'contentobject_id' ), $edition->attribute( 'contentobject_attribute_version' ),
                $formatId, $list->attribute( 'main_siteaccess' ), $skin, 0, $language === $main ? '' : $language );
            $editionObjectId = (int)$edition->attribute( 'contentobject_id' );
            $personalize = (int)$list->attribute( 'personalize_content' ) === 1;
            $subject = isset( $raw['subject'] ) ? $raw['subject'] : '';
            $bodies = isset( $raw['body'] ) ? $raw['body'] : array( 'html' => '', 'text' => '' );
        }
        $context = self::subscriberContext( $user, $listId, $language );
        $bodies = self::resolveBodies( $bodies, $context, array( 'edition_object_id' => $editionObjectId, 'list_id' => $listId, 'language' => $language ) );
        $subject = CjwNewsletterConditions::resolve( (string)$subject, $context );
        $values = CjwNewsletterPlaceholders::valuesForSubscriber( $user, $listId, $personalize );
        // the hashes of the older skins, as the runner gives them
        $subscription = is_object( $user ) && $listId > 0 ? CjwNewsletterSubscription::fetchByListIdAndNewsletterUserId( $listId, $user->attribute( 'id' ) ) : null;
        $values = array_merge( array( '#_hash_unsubscribe_#' => is_object( $subscription ) ? (string)$subscription->attribute( 'hash' ) : '',
                                      '#_hash_configure_#' => is_object( $user ) ? (string)$user->attribute( 'hash' ) : '',
                                      '#_hash_edition_#' => is_object( $send ) ? (string)$send->attribute( 'hash' ) : '' ), $values );
        $bodies = CjwNewsletterPlaceholders::replaceInBodies( $bodies, $values );
        return array( 'subject' => CjwNewsletterPlaceholders::replaceInSubject( $subject, $values ),
                      'html' => $bodies['html'], 'text' => $bodies['text'], 'language' => $language, 'skin' => $skin,
                      'output_format_id' => $formatId );
    }
}

?>
