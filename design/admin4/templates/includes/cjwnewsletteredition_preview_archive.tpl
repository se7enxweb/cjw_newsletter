{* The frames of the archived output formats of a sent edition, admin4. Same data and actions as design/standard/templates/includes/cjwnewsletteredition_preview_archive.tpl.
   {include uri="design:includes/cjwnewsletteredition_preview_archive.tpl" newsletter_edition_attribute=$object show_iframes=true()}
   The frame shows the newsletter as it is mailed: its own HTML, sandboxed (no scripts, no forms, no access to
   the admin page), sized by .cjwnl-a4-preview-frame in cjw_newsletter_admin4.css.
   @see in calling tpl def $list_attribute_content = $newsletter_edition_attribute_content.list_attribute_content *}

{def
     $edition_send_current = $newsletter_edition_attribute_content.edition_send_current
     $edition_send_id = $edition_send_current.id
     $archive_url = concat('/newsletter/archive/' , $edition_send_current.hash )}
{set $output_format_array = $edition_send_current.output_format_array}

{foreach $output_format_array as $output_format_id => $output_format_name}

{if or( is_set( $show_output_format_id )|not, and( is_set( $show_output_format_id ), $show_output_format_id|eq( $output_format_id ) ) )}

{def $src_url = concat('/newsletter/preview_archive/' , $edition_send_id, '/', $output_format_id)}
<p class="a4-toolbar"><a class="a4-btn a4-btn-small" href={$src_url|ezurl} target="new_{$output_format_id}">{'Fullscreen'|i18n('cjw_newsletter/cjwnewsletteredition_preview')}</a></p>

{if $show_iframes}
    <iframe class="cjwnl-preview-frame cjwnl-a4-preview-frame" src={$src_url|ezurl} sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="same-origin" title="{'Preview of the output format %name'|i18n( 'cjw_newsletter/cjwnewsletteredition_preview',, hash( '%name', $output_format_name ) )|wash}" name="EDITION_PREVIEW_{$output_format_id}">
        <p>{'your browser does not support iframes!'|i18n( 'cjw_newsletter/cjwnewsletteredition_preview' )}</p>
    </iframe>
{/if}
{undef $src_url}
{/if}
{/foreach}

<p><a href={$archive_url|ezurl} target="_blank">{'Archive view'|i18n('cjw_newsletter/cjwnewsletteredition_preview_archive')}</a></p>

{undef $show_iframes}
