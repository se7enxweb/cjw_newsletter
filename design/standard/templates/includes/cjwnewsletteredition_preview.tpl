

{*
    {include uri="design:includes/cjwnewsletteredition_preview.tpl" $newsletter_edition_attribute=$object show_iframes=true()}

newsletter edition vorschau in iframes oder nur links zu den full views
*}

{if is_set( $show_iframes )|not() }
    {def $show_iframes = true()}
{/if}

{* The frame shows the newsletter as it is mailed: its own HTML, sandboxed (no scripts, no forms, no access to
   the admin page), sized by the class cjwnl-preview-frame in cjw_newsletter.css. *}

{* @see in calling tpl def $newsletter_edition_attribute_content = $newsletter_edition_attribute.content *}

{def $list_attribute_content = $newsletter_edition_attribute_content.list_attribute_content
     $output_format_array = $list_attribute_content.output_format_array
     $skin_name = $list_attribute_content.skin_name
     $list_main_siteaccess =  $list_attribute_content.main_siteaccess
     $contentobject_id = $newsletter_edition_attribute_content.contentobject_id
     $contentobject_version = $newsletter_edition_attribute_content.contentobject_attribute_version
     }

{foreach $output_format_array as $output_format_id => $output_format_name}
{def $src_url = concat('/newsletter/preview/' , $contentobject_id, '/', $contentobject_version, '/', $output_format_id, '/', $list_main_siteaccess, '/',$skin_name,'/')}
<img src={concat('newsletter/icons/crystal-newsletter/16x16/preview_', $output_format_id, '.png')|ezimage} title="{$output_format_name}" /> {'Preview'|i18n('cjw_newsletter/cjwnewsletteredition_preview')} "{$output_format_name}"
[{'Skin'|i18n('cjw_newsletter/cjwnewsletteredition_preview')}: {$skin_name}] <a href={$src_url|ezurl} target="new_{$output_format_id}">[{'Fullscreen'|i18n('cjw_newsletter/cjwnewsletteredition_preview')}]</a>
{if $show_iframes}
    <iframe class="cjwnl-preview-frame" src={$src_url|ezurl} sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="same-origin" title="{'Preview of the output format %name'|i18n( 'cjw_newsletter/cjwnewsletteredition_preview',, hash( '%name', $output_format_name ) )|wash}" name="EDITION_PREVIEW_{$output_format_id}">
        <p>{'your browser does not support iframes!'|i18n( 'cjw_newsletter/cjwnewsletteredition_preview' )}</p>
    </iframe>
{/if}
{undef $src_url}
{/foreach}

{*$newsletter_edition_attribute.content|attribute(show,1)*}