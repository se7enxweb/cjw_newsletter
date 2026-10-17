{* The newsletter tree. Its script is cjw_newsletter.js: it reads data-cjwnl-tree of the ul (the open path, the icons to
   preload and the fold icons) and calls the ezcst_* functions of contentstructuremenu.js, as the inline script did. *}
<link rel="stylesheet" type="text/css" href={'stylesheets/cjw_newsletter_tree.css'|ezdesign} />

<script language="JavaScript" type="text/javascript" src={"javascript/lib/ezjslibcookiesupport.js"|ezdesign}></script>
<script language="JavaScript" type="text/javascript" src={"javascript/lib/ezjslibdomsupport.js"|ezdesign}></script>
<script language="JavaScript" type="text/javascript" src={"javascript/lib/ezjslibimagepreloader.js"|ezdesign}></script>
<script language="JavaScript" type="text/javascript" src={"javascript/contentstructuremenu/contentstructuremenu.js"|ezdesign}></script>

{let rootNodeID             = 1
     classFilter            = ezini( 'TreeMenu', 'ShowClasses'      , 'contentstructuremenu.ini' )
     maxDepth               = ezini( 'TreeMenu', 'MaxDepth'         , 'contentstructuremenu.ini' )
     maxNodes               = ezini( 'TreeMenu', 'MaxNodes'         , 'contentstructuremenu.ini' )
     sortBy                 = ezini( 'TreeMenu', 'SortBy'           , 'contentstructuremenu.ini' )
     fetchHidden            = ezini( 'SiteAccessSettings', 'ShowHiddenNodes', 'site.ini'         )
     itemClickAction        = ezini( 'TreeMenu', 'ItemClickAction'  , 'contentstructuremenu.ini' )
     classIconsSize         = ezini( 'TreeMenu', 'ClassIconsSize'   , 'contentstructuremenu.ini' )
     preloadClassIcons      = ezini( 'TreeMenu', 'PreloadClassIcons', 'contentstructuremenu.ini' )
     autoopenCurrentNode    = ezini( 'TreeMenu', 'AutoopenCurrentNode', 'contentstructuremenu.ini' )
     contentStructureTree   = false()
     menuID                 = "content_tree_menu"
     isDepthUnlimited       = eq($:maxDepth, 0)
     rootNode               = false }

    {* check size of icons *}
    {section show=is_set($:class_icons_size)}
        {set classIconsSize=$:class_icons_size}
    {/section}

    {* icons to preload if preloadClassIcons is enabled (cjw_newsletter.js passes them to ezjslib_preloadImageList) *}
    {def $cjwnl_tree_icons = array()}
    {section show=eq( $:preloadClassIcons, "enabled" )}
        {let iconInfo           = icon_info( class )
             iconsThemePath     = $:iconInfo.theme_path
             iconsList          = $:iconInfo.icons
             defaultIcon        = $:iconInfo.default
             iconSizePath       = $:iconInfo.size_path_list[$:classIconsSize] }

                {* oridinary icons. *}
                {section var=icon loop=$:iconsList}
                    {set $cjwnl_tree_icons = $cjwnl_tree_icons|append( concat( ezsys( 'wwwdir' ), '/', $:iconsThemePath, '/', $:iconSizePath, '/', $:icon ) )}
                {/section}

                {* default icon. *}
                {set $cjwnl_tree_icons = $cjwnl_tree_icons|append( concat( ezsys( 'wwwdir' ), '/', $:iconsThemePath, '/', $:iconSizePath, '/', $:defaultIcon ) )}
        {/let}
    {/section}

    {* check custom_root_node *}
    {section show=is_set( $custom_root_node_id )}
        {set rootNodeID=$custom_root_node_id}
    {/section}

    {set rootNode=fetch( 'content', 'node', hash( node_id, $:rootNodeID ) )}

    {* check custom action when clicking on menu item *}
    {section show=and( is_set( $csm_menu_item_click_action ), eq( $itemClickAction, '' ) )}
        {set itemClickAction=$csm_menu_item_click_action}
    {/section}

    {* if menu action is set translate it to url *}
    {section show=eq( $itemClickAction, '' )|not()}
        {set itemClickAction = $:itemClickAction|ezurl(no)}
    {/section}

    {* create menu *}
        {*set contentStructureTree = content_structure_tree( $:rootNodeID,
                                                            $:classFilter,
                                                            $:maxDepth,
                                                            $:maxNodes,
                                                            $:sortBy,
                                                            $:fetchHidden ) *}
        {* Show menu tree. All container nodes are unfolded. *}
            {*<h4>Alle Systeme</h4>*}

    {* get path to current node which consists of nodes ids *}
    {def $cjwnl_tree_nodes = array()}
    {section var=path loop=$module_result.path}
        {section show=and(is_set($:path.node_id), or($:isDepthUnlimited, $:maxDepth|gt(0)))}
            {set $cjwnl_tree_nodes = $cjwnl_tree_nodes|append( concat( 'n', $:path.node_id ) )}
            {set maxDepth = dec($:maxDepth)}
        {/section}
    {/section}

            <ul id="{$:menuID}" data-cjwnl-tree="{json_encode( hash( 'nodes', $cjwnl_tree_nodes,
                                                                    'icons', $cjwnl_tree_icons,
                                                                    'openIcon', 'images/content_tree-open.gif'|ezdesign( 'no' ),
                                                                    'closeIcon', 'images/content_tree-close.gif'|ezdesign( 'no' ),
                                                                    'emptyIcon', 'images/1x1.gif'|ezdesign( 'no' ),
                                                                    'autoopen', $:autoopenCurrentNode ) )|wash}">
                {include uri="design:contentstructuremenu/2_newsletter_system_tree.tpl" contentStructureTree=$contentStructureTree class_icons_size=$:classIconsSize csm_menu_item_click_action=$:itemClickAction ui_context=$ui_context is_root_node=true()}
            </ul>

    {* initialize menu: cjw_newsletter.js *}
    {ezscript( array( 'cjw_newsletter.js' ) )}
    {undef $cjwnl_tree_icons $cjwnl_tree_nodes}
{/let}

