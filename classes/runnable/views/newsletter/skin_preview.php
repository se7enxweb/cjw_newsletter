<?php
/**
 * The skins: their names, descriptions and preview images, and one skin rendered with an edition (the newest one,
 * or /(edition)/<node id>) in the HTML or the text part. newsletter/skin_preview/<skin>/<format>.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SkinPreview extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $tr = 'cjw_newsletter/rendering';
        $skins = array();
        foreach ( \CjwNewsletterRendering::availableSkins() as $name )
            $skins[$name] = \CjwNewsletterRendering::skinSettings( $name ) + array( 'has_templates' => \CjwNewsletterRenderingHooks::skinTemplateExists( $name ) );
        $skin = (string)$Params['SkinName'];
        if ( !isset( $skins[$skin] ) )
            $skin = '';
        $formatId = $Params['OutputFormat'] === 'text' || $Params['OutputFormat'] === '1' ? 1 : 0;

        // the edition to show the skin with: /(edition)/<node id>, else the newest edition the user may read
        $userParameters = is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();
        $editionNode = isset( $userParameters['edition'] ) ? \eZContentObjectTreeNode::fetch( (int)$userParameters['edition'] ) : null;
        if ( !$editionNode || $editionNode->attribute( 'class_identifier' ) !== 'cjw_newsletter_edition' || !$editionNode->attribute( 'can_read' ) )
        {
            $editionNode = null;
            $found = \eZContentObjectTreeNode::subTreeByNodeID( array( 'ClassFilterType' => 'include', 'ClassFilterArray' => array( 'cjw_newsletter_edition' ),
                'SortBy' => array( 'published', false ), 'Limit' => 1 ), 1 );
            if ( $found )
                $editionNode = $found[0];
        }
        $output = null;
        if ( $skin !== '' && $editionNode && $skins[$skin]['has_templates'] )
        {
            $object = $editionNode->attribute( 'object' );
            $list = \CjwNewsletterRenderingOperators::listOfNode( $editionNode->attribute( 'node_id' ) );
            $output = \CjwNewsletterEdition::getOutput( $object->attribute( 'id' ), $object->attribute( 'current_version' ), $formatId,
                is_object( $list ) ? $list->attribute( 'main_siteaccess' ) : \eZINI::instance()->variable( 'SiteSettings', 'DefaultAccess' ), $skin, 0 );
        }

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'skins', $skins );
        $tpl->setVariable( 'skin', $skin );
        $tpl->setVariable( 'format_id', $formatId );
        $tpl->setVariable( 'edition_node', $editionNode );
        $tpl->setVariable( 'output', $output );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/rendering/skin_preview.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => $skin !== '' ? 'newsletter/skin_preview' : false, 'text' => \ezpI18n::tr( $tr, 'Skins' ) ) );
        if ( $skin !== '' )
            $Result['path'][] = array( 'url' => false, 'text' => $skins[$skin]['description'] );
        return $this->viewResult( $Result, null );
    }
}

}
