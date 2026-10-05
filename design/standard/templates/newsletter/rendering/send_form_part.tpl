{* The rendering's part of the send form (cjw_newsletter 4.2.0, [ExtensionPointSettings] SendFormParts[]): the skin of
   this send, among the skins the list allows. Read by CjwNewsletterRenderingHooks::sendFormValidate()/sendFormStored().
   Variables: node_id, object_version *}
{def $i18n = 'cjw_newsletter/rendering'
     $list = cjwnl_rendering_data( 'list_of_node', $node_id )
     $skins = cjwnl_rendering_data( 'allowed_skins', $node_id )
     $own = 'default'}
{if $list}{if $list.skin_name|ne( '' )}{set $own = $list.skin_name}{/if}{/if}
{if $skins|count|gt( 1 )}
<div class="block cjwnl-rendering-part">
    <label for="CjwNewsletterRendering_SkinName">{'Skin of this send'|i18n( $i18n )}:</label>
    <select id="CjwNewsletterRendering_SkinName" name="CjwNewsletterRendering_SkinName">
    {foreach $skins as $skin}
        {def $settings = cjwnl_rendering_data( 'skin_settings', $skin )}
        <option value="{$skin|wash}"{if $skin|eq( $own )} selected="selected"{/if}>{$settings.description|wash}{if $skin|eq( $own )} ({"the list's skin"|i18n( $i18n )}){/if}</option>
        {undef $settings}
    {/foreach}
    </select>
    <a href={concat( 'newsletter/skin_preview/', $own )|ezurl} target="_blank" rel="noopener">{'Preview the skins'|i18n( $i18n )}</a>
</div>
{/if}
<p class="cjwnl-hint"><a href={concat( 'newsletter/preview_as/', $node_id )|ezurl} target="_blank" rel="noopener">{'Preview as a subscriber'|i18n( $i18n )}</a>: {'his language, the conditional parts and the articles for his interests'|i18n( $i18n )}</p>
{undef $i18n $list $skins $own}
