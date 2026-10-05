<?php
/**
 * File containing the CjwNewsletterRenderingHooks class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The extension point handler of the rendering (area N3, cjw_newsletter.ini [ExtensionPointSettings] Handlers[]).
 * It runs before the statistics handler at each point, so the link rewriting sees the final bodies.
 *
 *  - sendQueueCreated: renders the outputs of the list's other languages (cjwnl_edition_send_output)
 *  - itemBeforeSend:   the subscriber's language, the conditional parts and the "articles for your interests"
 *                      block; the item remembers its language
 *  - sendFormValidate / sendFormStored: the skin of a send (allowed by the list), the output made again in it
 *  - listAttributeInput: allowed skins, languages, main language and interest source of a list
 *  - dashboardSummary: skins, lists with languages, interests
 *
 * Sends of another channel (SMS) are left alone.
 *
 * @package cjw_newsletter
 */
class CjwNewsletterRenderingHooks
{
    const CONTEXT = 'cjw_newsletter/rendering';

    /** The outputs in the other languages, before the statistics handler rewrites their links. */
    static function sendQueueCreated( $sendObject, $cli )
    {
        if ( CjwNewsletterRendering::isOtherChannel( $sendObject ) )
            return;
        try
        {
            $done = CjwNewsletterRendering::renderLanguageOutputs( $sendObject );
            if ( $done && is_object( $cli ) )
                $cli->output( '+ outputs rendered in: ' . implode( ', ', $done ) );
        }
        catch ( Throwable $e )
        {
            CjwNewsletterLog::writeError( 'outputs per language: ' . $e->getMessage(), 'CjwNewsletterRenderingHooks', 'sendQueueCreated',
                                          array( 'send_id' => (int)$sendObject->attribute( 'id' ) ) );
        }
    }

    /**
     * The bodies of one subscriber. The runner replaces the placeholders after every handler ran.
     *
     * @param ArrayObject $message subject, bodies, values, defer, abort
     */
    static function itemBeforeSend( $message, $sendItem, $sendObject, $user )
    {
        if ( CjwNewsletterRendering::isOtherChannel( $sendObject ) || !is_object( $user ) )
            return;
        $list = CjwNewsletterRendering::listOfSend( $sendObject );
        $language = CjwNewsletterRendering::pickLanguage( $user, $list, CjwNewsletterRendering::outputLanguages( $sendObject ) );
        // [LanguageSettings] FallbackToListMainLanguage=disabled: a subscriber whose language the list offers but the edition
        // has no translation in gets no mail (instead of the main language)
        $wanted = (string)$user->attribute( 'language' );
        if ( $wanted !== '' && $wanted !== $language && !self::fallbackToMainLanguage() && in_array( $wanted, CjwNewsletterRendering::listLanguages( $list ), true ) )
        {
            $message['abort'] = 'no translation in ' . $wanted;
            return;
        }
        $parsed = CjwNewsletterRendering::parsedOutput( $sendObject, $language );
        $formatId = (int)$sendItem->attribute( 'output_format_id' );
        if ( isset( $parsed[$formatId] ) )
        {
            $message['subject'] = (string)$parsed[$formatId]['subject'];
            $message['bodies'] = $parsed[$formatId]['body'];
        }
        $listId = (int)$sendObject->attribute( 'list_contentobject_id' );
        // the links and the list name (the runner's values: the hashes and, when personalised, the subscriber's fields)
        $values = $message['values'];
        $message['values'] = array_merge( CjwNewsletterPlaceholders::valuesForSubscriber( $user, $listId, false,
            isset( $values['#_hash_unsubscribe_#'] ) ? (string)$values['#_hash_unsubscribe_#'] : null ), (array)$values );
        $context = CjwNewsletterRendering::subscriberContext( $user, $listId, $language );
        $message['bodies'] = CjwNewsletterRendering::resolveBodies( $message['bodies'], $context,
            array( 'edition_object_id' => (int)$sendObject->attribute( 'edition_contentobject_id' ), 'list_id' => $listId, 'language' => $language ) );
        $message['subject'] = CjwNewsletterConditions::resolve( (string)$message['subject'], $context );
        if ( (string)$sendItem->attribute( 'language' ) !== $language )
        {
            $sendItem->setAttribute( 'language', $language );
            $sendItem->store();
        }
    }

    /** @return bool [LanguageSettings] FallbackToListMainLanguage is not disabled */
    static function fallbackToMainLanguage()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return !$ini->hasVariable( 'LanguageSettings', 'FallbackToListMainLanguage' ) || $ini->variable( 'LanguageSettings', 'FallbackToListMainLanguage' ) !== 'disabled';
    }

    /** The skin posted with the send form must be one the list allows. */
    static function sendFormValidate( $http, $objectVersion )
    {
        $skin = self::postedSkin( $http );
        if ( $skin === null )
            return array();
        $list = self::listOfEditionVersion( $objectVersion );
        if ( !in_array( $skin, CjwNewsletterRendering::allowedSkins( $list ), true ) )
            return array( ezpI18n::tr( self::CONTEXT, 'The skin %skin is not allowed for this list.', null, array( '%skin' => $skin ) ) );
        return array();
    }

    /** A send in another skin than the list's: the output is made again in it. */
    static function sendFormStored( $sendObject, $http, $objectVersion )
    {
        $skin = self::postedSkin( $http );
        if ( $skin === null || CjwNewsletterRendering::isOtherChannel( $sendObject ) )
            return;
        $list = CjwNewsletterRendering::listOfSend( $sendObject );
        if ( !in_array( $skin, CjwNewsletterRendering::allowedSkins( $list ), true ) )
            return;
        $listSkin = is_object( $list ) ? (string)$list->attribute( 'skin_name' ) : '';
        if ( $skin === ( $listSkin === '' ? 'default' : $listSkin ) && (string)$sendObject->attribute( 'skin_name' ) === '' )
            return;
        $edition = CjwNewsletterRendering::editionOfSend( $sendObject );
        if ( !$edition )
            return;
        $sendObject->setAttribute( 'skin_name', $skin );
        $sendObject->setAttribute( 'output_xml', $edition->createOutputXml( $skin ) );
        $sendObject->store();
        CjwNewsletterRendering::clearCache();
    }

    /** @return string|null the skin of the send form part, null when the part was not posted */
    static function postedSkin( $http )
    {
        if ( !is_object( $http ) || !$http->hasPostVariable( 'CjwNewsletterRendering_SkinName' ) )
            return null;
        $skin = trim( (string)$http->postVariable( 'CjwNewsletterRendering_SkinName' ) );
        return $skin === '' ? null : $skin;
    }

    /** @return CjwNewsletterList|false the list of an edition version (the parent of its main node) */
    static function listOfEditionVersion( $objectVersion )
    {
        if ( !is_object( $objectVersion ) )
            return false;
        $object = $objectVersion->attribute( 'contentobject' );
        $node = $object ? $object->attribute( 'main_node' ) : null;
        $parent = $node ? $node->attribute( 'parent' ) : null;
        return $parent ? CjwNewsletterList::fetchByListObjectVersion( $parent->attribute( 'contentobject_id' ), 0 ) : false;
    }

    /**
     * The list attribute's part of the rendering (ListEditParts[] design:newsletter/rendering/list_edit_part.tpl):
     * POST names {$attribute_base}_CjwNewsletterList_<Name>_{$attribute.id}.
     *
     * @return string[] errors
     */
    static function listAttributeInput( $list, $http, $prefix, $postfix, $contentObjectAttribute )
    {
        $name = function ( $field ) use ( $prefix, $postfix ) {
            return $prefix . $field . $postfix;
        };
        if ( !$http->hasPostVariable( $name( 'RenderingPart' ) ) )
            return array();
        $errors = array();
        $available = CjwNewsletterRendering::availableSkins();
        $skins = $http->hasPostVariable( $name( 'SkinNameArray' ) ) ? array_values( array_intersect( array_map( 'strval', (array)$http->postVariable( $name( 'SkinNameArray' ) ) ), $available ) ) : array();
        $list->setAttribute( 'skin_name_array_string', $skins ? CjwNewsletterList::arrayToString( $skins ) : '' );
        $own = (string)$list->attribute( 'skin_name' );
        if ( $skins && $own !== '' && !in_array( $own, $skins, true ) )
            $errors[] = ezpI18n::tr( self::CONTEXT, 'The skin of the list (%skin) must be one of the allowed skins.', null, array( '%skin' => $own ) );

        $known = CjwNewsletterRendering::contentLanguages();
        $languages = $http->hasPostVariable( $name( 'LanguageArray' ) ) ? array_values( array_intersect( array_map( 'strval', (array)$http->postVariable( $name( 'LanguageArray' ) ) ), array_keys( $known ) ) ) : array();
        $main = $http->hasPostVariable( $name( 'MainLanguage' ) ) ? trim( (string)$http->postVariable( $name( 'MainLanguage' ) ) ) : '';
        if ( $main !== '' && !isset( $known[$main] ) )
        {
            $errors[] = ezpI18n::tr( self::CONTEXT, 'The main language %language is not a language of the site.', null, array( '%language' => $main ) );
            $main = '';
        }
        if ( $main !== '' && $languages && !in_array( $main, $languages, true ) )
            array_unshift( $languages, $main );
        $list->setAttribute( 'main_language', $main );
        $list->setAttribute( 'language_array_string', $languages ? CjwNewsletterList::arrayToString( $languages ) : '' );

        $source = $http->hasPostVariable( $name( 'InterestSource' ) ) ? (string)$http->postVariable( $name( 'InterestSource' ) ) : '';
        if ( !in_array( $source, CjwNewsletterInterests::sources(), true ) )
            $source = '';
        if ( $source === 'eztags' && !class_exists( 'eZTagsObject' ) )
            $errors[] = ezpI18n::tr( self::CONTEXT, 'Interests from eztags need the eztags extension.' );
        $list->setAttribute( 'interest_source', $source );
        return $errors;
    }

    /** @return array the rendering's summary for the dashboard (rendering.tpl) */
    static function dashboardSummary( $summary )
    {
        $db = eZDB::instance();
        $lists = array();
        foreach ( (array)$db->arrayQuery( "SELECT DISTINCT l.contentobject_id AS contentobject_id FROM cjwnl_list l, ezcontentobject o WHERE o.id = l.contentobject_id" ) as $row )
        {
            $list = CjwNewsletterList::fetchByListObjectVersion( (int)$row['contentobject_id'], 0 );
            if ( !is_object( $list ) )
                continue;
            $object = CjwNewsletterUtils::contentObject( (int)$row['contentobject_id'] );
            $lists[] = array( 'id' => (int)$row['contentobject_id'], 'name' => $object ? (string)$object->attribute( 'name' ) : '#' . (int)$row['contentobject_id'],
                              'skin' => (string)$list->attribute( 'skin_name' ) === '' ? 'default' : (string)$list->attribute( 'skin_name' ),
                              'skins' => CjwNewsletterRendering::allowedSkins( $list ),
                              'main_language' => CjwNewsletterRendering::mainLanguage( $list ),
                              'languages' => CjwNewsletterRendering::listLanguages( $list ),
                              'interest_source' => (string)$list->attribute( 'interest_source' ),
                              'interests' => count( CjwNewsletterInterests::forList( $list ) ) );
        }
        $outputs = $db->arrayQuery( 'SELECT COUNT(*) AS c FROM cjwnl_edition_send_output' );
        $picks = $db->arrayQuery( 'SELECT COUNT(DISTINCT newsletter_user_id) AS c FROM cjwnl_user_interest' );
        $languages = $db->arrayQuery( "SELECT language, COUNT(*) AS c FROM cjwnl_user WHERE language <> '' GROUP BY language" );
        $byLanguage = array();
        foreach ( (array)$languages as $row )
            $byLanguage[(string)$row['language']] = (int)$row['c'];
        $out = array( 'skins' => CjwNewsletterRendering::availableSkins(), 'lists' => $lists,
                      'outputs' => isset( $outputs[0]['c'] ) ? (int)$outputs[0]['c'] : 0,
                      'subscribers_with_interests' => isset( $picks[0]['c'] ) ? (int)$picks[0]['c'] : 0,
                      'subscribers_by_language' => $byLanguage,
                      'problems' => array() );
        foreach ( CjwNewsletterRendering::availableSkins() as $skin )
        {
            if ( !self::skinTemplateExists( $skin ) )
                $out['problems'][] = array( 'level' => 'warning', 'code' => 'skin_missing',
                    'text' => ezpI18n::tr( self::CONTEXT, 'The skin %skin is listed in AvailableSkinArray[] but has no templates.', null, array( '%skin' => $skin ) ),
                    'url' => 'newsletter/skin_preview' );
        }
        return $out;
    }

    /** @return bool the skin has its html.tpl in an active design of the extensions */
    static function skinTemplateExists( $skin )
    {
        foreach ( eZTemplateDesignResource::allDesignBases() as $base )
            if ( is_file( $base . '/templates/newsletter/skin/' . $skin . '/outputformat/html.tpl' ) )
                return true;
        return false;
    }

    /**
     * The admin user edit form (UserEditParts[] design:newsletter/rendering/user_edit_part.tpl): the language.
     *
     * @return string[] errors
     */
    static function userInput( $user, $http )
    {
        if ( !$http->hasPostVariable( 'CjwNewsletterRendering_UserPart' ) )
            return array();
        $language = $http->hasPostVariable( 'CjwNewsletterRendering_Language' ) ? trim( (string)$http->postVariable( 'CjwNewsletterRendering_Language' ) ) : '';
        $known = CjwNewsletterRendering::contentLanguages();
        if ( $language !== '' && !isset( $known[$language] ) )
            return array( ezpI18n::tr( self::CONTEXT, 'This language is not offered.' ) );
        $user->setAttribute( 'language', $language );
        return array();
    }

    /** The interests of the user edit form, once the subscriber is stored (a new one has his id then). */
    static function userStored( $user, $http )
    {
        if ( !$http->hasPostVariable( 'CjwNewsletterRendering_UserPart' ) || !is_object( $user ) || (int)$user->attribute( 'id' ) <= 0 )
            return;
        $choices = CjwNewsletterInterests::choicesForUser( $user->attribute( 'id' ) );
        $wanted = $http->hasPostVariable( 'CjwNewsletterRendering_Interest' ) ? array_map( 'intval', (array)$http->postVariable( 'CjwNewsletterRendering_Interest' ) ) : array();
        CjwNewsletterInterests::setForUser( $user->attribute( 'id' ), $wanted, $choices['offered_ids'] );
    }

    /** For callers that remove a subscriber: his interests go with him (the kernel erasure does it through the category handler). */
    static function userRemoved( $newsletterUserId )
    {
        CjwNewsletterInterests::removeForUser( $newsletterUserId );
    }
}

?>
