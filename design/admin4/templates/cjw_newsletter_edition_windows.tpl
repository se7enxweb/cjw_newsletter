{* The tab panels of a newsletter edition (previews per output format, then the kernel's panels), admin4. Same data and actions as design/standard/templates/cjw_newsletter_edition_windows.tpl. *}
{* Preview window *}
{if $tabs_disabled|not}

{if $newsletter_edition_status|ne('draft')}

    {* archive view *}
    {foreach $output_format_array as $output_format_id => $output_format_name}
        <div id="node-tab-preview_{$output_format_id}-content" class="tab-content cjwnl-preview-panel{if $node_tab_index|ne( concat( 'preview_', $output_format_id ) )} hide{else} selected{/if}">
            {include uri='design:cjw_newsletter_edition_preview_archive.tpl' show_output_format_id=$output_format_id}
        </div>
    {/foreach}

{else}

    {* preview html, text *}
    {foreach $output_format_array as $output_format_id => $output_format_name}
        <div id="node-tab-preview_{$output_format_id}-content" class="tab-content cjwnl-preview-panel{if $node_tab_index|ne( concat( 'preview_', $output_format_id ) )} hide{else} selected{/if}">
            {include uri='design:cjw_newsletter_edition_preview.tpl' show_output_format_id=$output_format_id}
        </div>
    {/foreach}

{/if}


<div id="node-tab-view-content" class="tab-content{if $node_tab_index|ne('view')} hide{else} selected{/if}">
    {include uri='design:preview.tpl'}
</div>

{/if}

{* Details window *}
<div id="node-tab-details-content" class="tab-content{if $node_tab_index|ne('details')} hide{else} selected{/if}">
    {include uri='design:details.tpl'}
</div>

{* Translations window *}
<div id="node-tab-translations-content" class="tab-content{if $node_tab_index|ne('translations')} hide{else} selected{/if}">
    {include uri='design:translations.tpl'}
</div>

{* Locations window *}
<div id="node-tab-locations-content" class="tab-content{if $node_tab_index|ne('locations')} hide{else} selected{/if}">
    {include uri='design:locations.tpl'}
</div>

{* Relations window *}
<div id="node-tab-relations-content" class="tab-content{if $node_tab_index|ne('relations')} hide{else} selected{/if}">
    {include uri='design:relations.tpl'}
</div>

{* Published ordering window *}
<div id="node-tab-ordering-content" class="tab-content{if $node_tab_index|ne('ordering')} hide{else} selected{/if}">
    {include uri='design:ordering.tpl'}
</div>
