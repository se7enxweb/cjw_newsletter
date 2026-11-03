{* The newsletter tree of the left menu, admin4: the newsletter systems, lists and editions as the content tree draws them.
   Same data and actions as design/admin2/templates/parts/newsletter/newsletter_menu.tpl *}
<div id="content-tree">
    <h4 id="leftmenu-title-newsletter-systems">{'Newsletter systems'|i18n( 'cjw_newsletter/menu' )}</h4>

    {* Treemenu. *}
    <div id="contentstructure">
        {include uri='design:contentstructuremenu/1_newsletter_structure_menu.tpl'}
    </div>
</div>
