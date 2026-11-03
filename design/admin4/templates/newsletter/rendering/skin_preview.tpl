{* The skins and one skin rendered, admin4. Same data and actions as design/standard/templates/newsletter/rendering/skin_preview.tpl.
   Variables: skins (name => hash( name, description, preview_image, text_format, accent, has_templates )), skin ('' = none
   chosen), format_id, edition_node (or null), output (or null). *}
{def $i18n = 'cjw_newsletter/rendering'}
<div class="a4-page newsletter newsletter-skin_preview">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Skins'|i18n( $i18n )} [{$skins|count}]</h1>
        </div>
    </div>

    <div class="a4-grid is-wide">
    {foreach $skins as $name => $s}
        <section class="a4-card{if $name|eq( $skin )} is-selected{/if}">
            <h2 class="a4-h3">{$s.description|wash} <code>{$name|wash}</code></h2>
            {if $s.preview_image}<img class="cjwnl-a4-skin-image" src={concat( 'images/', $s.preview_image )|ezdesign} width="160" height="200" alt="" />{/if}
            <p class="a4-help">{if $s.text_format|eq( 'plain' )}{'Text part written with the plain text views'|i18n( $i18n )}{else}{'Text part converted from the HTML part'|i18n( $i18n )}{/if}</p>
            {if $s.has_templates|not}<p><span class="a4-badge is-warn">{'No templates found'|i18n( $i18n )}</span></p>{/if}
            <div class="a4-actions nl-links">
                <a class="a4-btn" href={concat( 'newsletter/skin_preview/', $name, '/html' )|ezurl}>{'HTML part'|i18n( $i18n )}</a>
                <a class="a4-btn" href={concat( 'newsletter/skin_preview/', $name, '/text' )|ezurl}>{'Text part'|i18n( $i18n )}</a>
            </div>
        </section>
    {/foreach}
    </div>

    {if $skin}
    <section class="a4-section">
    <h2 class="a4-h2">{$skins[$skin].description|wash}: {if $format_id|eq( 1 )}{'Text part'|i18n( $i18n )}{else}{'HTML part'|i18n( $i18n )}{/if}</h2>
    {if $edition_node}<p class="a4-help">{'Shown with the edition "%name". Every conditional part is shown, the placeholders are not replaced.'|i18n( $i18n,, hash( '%name', $edition_node.name ) )|wash}</p>{/if}
    {if $output|not}
    <p class="a4-empty">{'There is no edition to show the skin with yet.'|i18n( $i18n )}</p>
    {elseif $format_id|eq( 1 )}
    <pre class="a4-pre is-tall nl-preview-text">{$output.body.text|wash}</pre>
    {else}
    <iframe class="cjwnl-a4-frame nl-preview-frame" title="{$skins[$skin].description|wash}" sandbox="" srcdoc="{$output.body.html|wash}"></iframe>
    {/if}
    </section>
    {/if}
</div>
{undef $i18n}
