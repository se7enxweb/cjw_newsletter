{* The preview tab of a newsletter edition (output format of $show_output_format_id), admin4. Same data and actions as design/standard/templates/cjw_newsletter_edition_preview.tpl. *}
{* The frames are shown whenever this tab is: with the tabs switched off the tab is not on the page at all. *}
{def $show_iframes = true()}

<div class="cjwnl-a4-preview">
    {* show iframes or icons*}
    {include uri="design:includes/cjwnewsletteredition_preview.tpl" newsletter_edition_attribute=$newsletter_edition_attribute show_iframes=$show_iframes height="100" show_output_format_id=$show_output_format_id}
</div>
{undef $show_iframes}
