{*  newsletter/rendering/skin_preview.tpl (cjw_newsletter 4.2.0, area rendering)

    The skins and one skin rendered. Variables: skins (name => hash( name, description, preview_image, text_format,
    accent, has_templates )), skin ('' = none chosen), format_id, edition_node (or null), output (or null).
*}
{ezcss_require( array( 'newsletter_ui.css' ) )}
{def $i18n = 'cjw_newsletter/rendering'}
<div class="newsletter newsletter-skin_preview">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Skins'|i18n( $i18n )} [{$skins|count}]</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        <div class="nl-cards">
        {foreach $skins as $name => $s}
            <section class="nl-card{if $name|eq( $skin )} is-active{/if}">
                <h3>{$s.description|wash} <code>{$name|wash}</code></h3>
                {if $s.preview_image}<img src={concat( 'images/', $s.preview_image )|ezdesign} width="160" height="200" alt="" style="display:block;margin:0 0 .5em 0;border:1px solid #d0d7de;" />{/if}
                <p class="nl-muted">{if $s.text_format|eq( 'plain' )}{'Text part written with the plain text views'|i18n( $i18n )}{else}{'Text part converted from the HTML part'|i18n( $i18n )}{/if}</p>
                {if $s.has_templates|not}<p><span class="nl-pill is-warn">{'No templates found'|i18n( $i18n )}</span></p>{/if}
                <div class="nl-links">
                    <a class="button" href={concat( 'newsletter/skin_preview/', $name, '/html' )|ezurl}>{'HTML part'|i18n( $i18n )}</a>
                    <a class="button" href={concat( 'newsletter/skin_preview/', $name, '/text' )|ezurl}>{'Text part'|i18n( $i18n )}</a>
                </div>
            </section>
        {/foreach}
        </div>

        {if $skin}
        <h2>{$skins[$skin].description|wash}: {if $format_id|eq( 1 )}{'Text part'|i18n( $i18n )}{else}{'HTML part'|i18n( $i18n )}{/if}</h2>
        {if $edition_node}<p class="nl-hint">{'Shown with the edition "%name". Every conditional part is shown, the placeholders are not replaced.'|i18n( $i18n,, hash( '%name', $edition_node.name ) )|wash}</p>{/if}
        {if $output|not}
        <p class="nl-hint">{'There is no edition to show the skin with yet.'|i18n( $i18n )}</p>
        {elseif $format_id|eq( 1 )}
        <pre class="nl-preview-text" style="white-space:pre-wrap;max-width:46em;padding:1em;border:1px solid #d0d7de;border-radius:6px;background:#fafafa;overflow:auto;">{$output.body.text|wash}</pre>
        {else}
        <iframe class="nl-preview-frame" title="{$skins[$skin].description|wash}" sandbox="" srcdoc="{$output.body.html|wash}" style="width:100%;min-height:900px;border:1px solid #d0d7de;border-radius:6px;background:#fff;"></iframe>
        {/if}
        {/if}
    </div>
</div>
</div>
{undef $i18n}
