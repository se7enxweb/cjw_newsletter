{* The frames of the output formats of an edition, admin4. Same data and actions as design/standard/templates/includes/cjwnewsletteredition_preview.tpl.
   {include uri="design:includes/cjwnewsletteredition_preview.tpl" newsletter_edition_attribute=$object show_iframes=true()}
   The frame shows the newsletter as it is mailed: its own HTML, sandboxed (no scripts, no forms, no access to
   the admin page), sized by .cjwnl-a4-preview-frame in cjw_newsletter_admin4.css. *}

{foreach $output_format_array as $output_format_id => $output_format_name}

{if or( is_set( $show_output_format_id )|not, and( is_set( $show_output_format_id ), $show_output_format_id|eq( $output_format_id ) ) )}

{def $src_url = concat('/newsletter/preview/' , $contentobject_id, '/', $contentobject_version, '/', $output_format_id, '/', $list_main_siteaccess, '/',$skin_name,'/')}

<p class="a4-toolbar">
    <a class="a4-btn a4-btn-small" href={$src_url|ezurl} target="new_{$output_format_id}">{'Fullscreen'|i18n('cjw_newsletter/cjwnewsletteredition_preview')}</a>
    <span class="a4-muted">{'Skin'|i18n('cjw_newsletter/cjwnewsletteredition_preview')}: {$skin_name}</span>
</p>

{if $show_iframes}
    <iframe class="cjwnl-preview-frame cjwnl-a4-preview-frame" src={$src_url|ezurl} sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="same-origin" title="{'Preview of the output format %name'|i18n( 'cjw_newsletter/cjwnewsletteredition_preview',, hash( '%name', $output_format_name ) )|wash}" name="EDITION_PREVIEW_{$output_format_id}">
        <p>{'your browser does not support iframes!'|i18n( 'cjw_newsletter/cjwnewsletteredition_preview' )}</p>
    </iframe>
{/if}
{undef $src_url}
{/if}
{/foreach}
