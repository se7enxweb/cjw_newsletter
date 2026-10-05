<?php
/**
 * File containing the CjwNewsletterRenderingOperators class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The template operators of the newsletter rendering (cjw_newsletter 4.2.0):
 *
 *   {cjwnl_condition_open( hash( 'field', 'first_name', 'operator', 'not_empty' ) )} ... {cjwnl_condition_else()}
 *   ... {cjwnl_condition_close()}                                      a conditional part (CjwNewsletterConditions)
 *   {$attribute|cjwnl_plaintext()}                                     ezxmltext as plain text (the plaintext tag views)
 *   {$attribute.data_text|cjwnl_richtext( 'text' | 'html' )}           ezrichtext (DocBook) as text or HTML
 *   {cjwnl_interests_block( 5, $heading, $accent )}                                    "articles for your interests", filled per person
 *   {$url|cjwnl_abs_url()}                                             an absolute address of the newsletter's site
 *   {$title|cjwnl_text_underline( '=' )}                               a title with a line under it (text part)
 *   {$text|cjwnl_text_wrap( 72 )}                                      text wrapped at a width
 *
 * @package cjw_newsletter
 */
class CjwNewsletterRenderingOperators
{
    /** @var string the site address of the edition being rendered (createoutput sets it): https://host/siteaccess */
    static $baseUrl = '';
    /** @var string the root address (images, design files) */
    static $rootUrl = '';

    function operatorList()
    {
        return array( 'cjwnl_condition_open', 'cjwnl_condition_else', 'cjwnl_condition_close', 'cjwnl_plaintext',
                      'cjwnl_richtext', 'cjwnl_interests_block', 'cjwnl_abs_url', 'cjwnl_text_underline', 'cjwnl_text_wrap', 'cjwnl_rendering_data' );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'cjwnl_condition_open' => array( 'settings' => array( 'type' => 'array', 'required' => false, 'default' => array() ) ),
                      'cjwnl_condition_else' => array(),
                      'cjwnl_condition_close' => array(),
                      'cjwnl_plaintext' => array(),
                      'cjwnl_richtext' => array( 'format' => array( 'type' => 'string', 'required' => false, 'default' => 'html' ) ),
                      'cjwnl_interests_block' => array( 'limit' => array( 'type' => 'integer', 'required' => false, 'default' => 0 ),
                                                         'heading' => array( 'type' => 'string', 'required' => false, 'default' => '' ),
                                                         'accent' => array( 'type' => 'string', 'required' => false, 'default' => '' ) ),
                      'cjwnl_abs_url' => array(),
                      'cjwnl_text_underline' => array( 'char' => array( 'type' => 'string', 'required' => false, 'default' => '=' ) ),
                      'cjwnl_text_wrap' => array( 'width' => array( 'type' => 'integer', 'required' => false, 'default' => 72 ) ),
                      'cjwnl_rendering_data' => array( 'name' => array( 'type' => 'string', 'required' => true, 'default' => '' ),
                                                       'param' => array( 'type' => 'mixed', 'required' => false, 'default' => false ) ) );
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
        switch ( $operatorName )
        {
            case 'cjwnl_condition_open':
                $operatorValue = CjwNewsletterConditions::open( (array)$namedParameters['settings'] );
                break;
            case 'cjwnl_condition_else':
                $operatorValue = CjwNewsletterConditions::elseMarker();
                break;
            case 'cjwnl_condition_close':
                $operatorValue = CjwNewsletterConditions::close();
                break;
            case 'cjwnl_plaintext':
                $operatorValue = CjwNewsletterPlainText::xmlText( $operatorValue );
                break;
            case 'cjwnl_richtext':
                $operatorValue = $namedParameters['format'] === 'text'
                    ? CjwNewsletterRichText::toText( (string)$operatorValue )
                    : CjwNewsletterRichText::toHtml( (string)$operatorValue );
                break;
            case 'cjwnl_interests_block':
                $operatorValue = CjwNewsletterInterestBlock::marker( (int)$namedParameters['limit'], (string)$namedParameters['heading'], (string)$namedParameters['accent'] );
                break;
            case 'cjwnl_abs_url':
                $operatorValue = self::absoluteUrl( (string)$operatorValue );
                break;
            case 'cjwnl_text_underline':
                $text = trim( (string)$operatorValue );
                $char = $namedParameters['char'] === '' ? '=' : mb_substr( $namedParameters['char'], 0, 1, 'UTF-8' );
                $operatorValue = $text === '' ? '' : $text . "\n" . str_repeat( $char, max( 3, min( 72, mb_strlen( $text, 'UTF-8' ) ) ) );
                break;
            case 'cjwnl_text_wrap':
                $operatorValue = CjwNewsletterPlainText::wrap( (string)$operatorValue, max( 20, (int)$namedParameters['width'] ) );
                break;
            case 'cjwnl_rendering_data':
                $operatorValue = self::data( (string)$namedParameters['name'], $namedParameters['param'] );
                break;
        }
    }

    /**
     * The data the rendering's templates need ({cjwnl_rendering_data( 'allowed_skins', $node_id )}).
     *
     *   available_skins                  string[]
     *   skin_settings( skin )            hash( name, description, preview_image, text_format, accent )
     *   allowed_skins( edition node id ) string[] of the list of the edition
     *   list_of_node( node id )          CjwNewsletterList of a list node or an edition node, or false
     *   list_languages( list object id ) string[]
     *   main_language( list object id )  string
     *   content_languages                locale => name
     *   interests( list object id )      CjwNewsletterInterest[] the list offers
     *   all_interests                    CjwNewsletterInterest[] every interest
     *   interest_user_count( id )        int
     *   placeholder_names                string[]
     *   condition_fields                 string[]
     *
     * @param string $name
     * @param mixed $param
     * @return mixed
     */
    static function data( $name, $param )
    {
        switch ( $name )
        {
            case 'available_skins':
                return CjwNewsletterRendering::availableSkins();
            case 'skin_settings':
                return CjwNewsletterRendering::skinSettings( (string)$param );
            case 'allowed_skins':
                return CjwNewsletterRendering::allowedSkins( self::listOfNode( (int)$param ) );
            case 'list_of_node':
                return self::listOfNode( (int)$param );
            case 'list_languages':
                return CjwNewsletterRendering::listLanguages( CjwNewsletterList::fetchByListObjectVersion( (int)$param, 0 ) );
            case 'main_language':
                return CjwNewsletterRendering::mainLanguage( CjwNewsletterList::fetchByListObjectVersion( (int)$param, 0 ) );
            case 'content_languages':
                return CjwNewsletterRendering::contentLanguages();
            case 'interests':
                return CjwNewsletterInterests::forList( (int)$param );
            case 'all_interests':
                return CjwNewsletterInterest::fetchList( null, 0, 0, array( 'list_contentobject_id' => 'asc', 'priority' => 'asc', 'name' => 'asc' ) );
            case 'interest_user_count':
                return CjwNewsletterInterests::userCount( (int)$param );
            case 'placeholder_names':
                return CjwNewsletterPlaceholders::names();
            case 'condition_fields':
                return CjwNewsletterConditions::fields();
            case 'user_choices':
                return CjwNewsletterInterests::choicesForUser( (int)$param );
            case 'plaintext_template':
                return self::plainTextTemplate( (string)$param );
        }
        return false;
    }

    /**
     * The plain text view of a datatype, for design:newsletter/rendering/plaintext_attribute.tpl (the skins include it
     * for every field; attribute_view_gui with view=plaintext is not used, its compiled form does not find views
     * that are added to an extension).
     *
     * @param string $dataTypeString
     * @return string|false design:content/datatype/view/plaintext/<datatype>.tpl, false when there is none
     */
    static function plainTextTemplate( $dataTypeString )
    {
        if ( !preg_match( '/^[a-z0-9_]{1,60}$/i', $dataTypeString ) )
            return false;
        $path = '/content/datatype/view/plaintext/' . $dataTypeString . '.tpl';
        $overrides = eZTemplateDesignResource::overrideArray();
        return isset( $overrides[$path] ) ? 'design:content/datatype/view/plaintext/' . $dataTypeString . '.tpl' : false;
    }

    /** @return CjwNewsletterList|false the list of a list node, or of the list above an edition node */
    static function listOfNode( $nodeId )
    {
        $node = eZContentObjectTreeNode::fetch( (int)$nodeId );
        if ( !$node )
            return false;
        $list = CjwNewsletterList::fetchByListObjectVersion( $node->attribute( 'contentobject_id' ), 0 );
        if ( is_object( $list ) )
            return $list;
        $parent = $node->attribute( 'parent' );
        return $parent ? CjwNewsletterList::fetchByListObjectVersion( $parent->attribute( 'contentobject_id' ), 0 ) : false;
    }

    /**
     * An absolute address: a full address stays as it is, a path gets the site address of the edition in front.
     *
     * @param string $url
     * @return string
     */
    static function absoluteUrl( $url )
    {
        $url = trim( $url );
        if ( $url === '' || preg_match( '#^([a-z][a-z0-9+.-]*:|//|\#)#i', $url ) )
            return $url;
        $base = self::$baseUrl;
        if ( $base === '' )
        {
            $path = $url;
            eZURI::transformURI( $path, false, 'full' );
            return $path;
        }
        return rtrim( $base, '/' ) . '/' . ltrim( $url, '/' );
    }
}

?>
