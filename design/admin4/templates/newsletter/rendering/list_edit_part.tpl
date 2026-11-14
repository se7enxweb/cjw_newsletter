{* The rendering part of the list attribute edit ([ExtensionPointSettings] ListEditParts[]), admin4. Same data and POST
   names as design/standard/templates/newsletter/rendering/list_edit_part.tpl: allowed skins, languages, main language
   and interest source. Variables: attribute, attribute_base, list_object *}
{def $i18n = 'cjw_newsletter/rendering'
     $name = concat( $attribute_base, '_CjwNewsletterList_' )
     $postfix = concat( '_', $attribute.id )
     $skins = cjwnl_rendering_data( 'available_skins' )
     $allowed = $list_object.skin_name_array_string|explode( ';' )
     $languages = cjwnl_rendering_data( 'content_languages' )
     $chosen = $list_object.language_array_string|explode( ';' )}
<input type="hidden" name="{$name}RenderingPart{$postfix}" value="1" />

<fieldset class="a4-field">
    <legend>{'Skins allowed for a send'|i18n( $i18n )}</legend>
    <p class="a4-help">{'The editor chooses one of these when an edition is sent. None ticked: every skin.'|i18n( $i18n )}</p>
    {foreach $skins as $skin}
    {def $settings = cjwnl_rendering_data( 'skin_settings', $skin )}
    <label class="a4-check"><input type="checkbox" name="{$name}SkinNameArray{$postfix}[]" value="{$skin|wash}"{if $allowed|contains( $skin )} checked="checked"{/if} /> <span>{$settings.description|wash} <span class="a4-muted">({$skin|wash})</span></span></label>
    {undef $settings}
    {/foreach}
</fieldset>

<fieldset class="a4-field">
    <legend>{'Languages'|i18n( $i18n )}</legend>
    <p class="a4-help">{'One edition with translations: each subscriber gets his language when the edition has it, else the main language. None ticked: every subscriber gets the main language.'|i18n( $i18n )}</p>
    {foreach $languages as $locale => $language_name}
    <label class="a4-check"><input type="checkbox" name="{$name}LanguageArray{$postfix}[]" value="{$locale|wash}"{if $chosen|contains( $locale )} checked="checked"{/if} /> <span>{$language_name|wash} <span class="a4-muted">({$locale|wash})</span></span></label>
    {/foreach}
</fieldset>

<div class="a4-field">
    <label for="{$name}MainLanguage{$postfix}">{'Main language'|i18n( $i18n )}</label>
    <select id="{$name}MainLanguage{$postfix}" name="{$name}MainLanguage{$postfix}">
        <option value="">{'The language of the main siteaccess'|i18n( $i18n )}</option>
        {foreach $languages as $locale => $language_name}
        <option value="{$locale|wash}"{if $list_object.main_language|eq( $locale )} selected="selected"{/if}>{$language_name|wash} ({$locale|wash})</option>
        {/foreach}
    </select>
</div>

<fieldset class="a4-field">
    <legend>{'Interests'|i18n( $i18n )}</legend>
    <p class="a4-help">{'Subscribers pick interests on their preference page; the block "articles for your interests" of a skin is filled from them.'|i18n( $i18n )}</p>
    <div class="a4-row">
        <label class="a4-check"><input type="radio" name="{$name}InterestSource{$postfix}" value=""{if $list_object.interest_source|eq( '' )} checked="checked"{/if} /> <span>{'No interests'|i18n( $i18n )}</span></label>
        <label class="a4-check"><input type="radio" name="{$name}InterestSource{$postfix}" value="topics"{if $list_object.interest_source|eq( 'topics' )} checked="checked"{/if} /> <span>{'Topics of the list'|i18n( $i18n )}</span></label>
        <label class="a4-check"><input type="radio" name="{$name}InterestSource{$postfix}" value="eztags"{if $list_object.interest_source|eq( 'eztags' )} checked="checked"{/if} /> <span>{'Tags (eztags)'|i18n( $i18n )}</span></label>
    </div>
    <p class="a4-help"><a href={'newsletter/interest_list'|ezurl}>{'Manage the interests'|i18n( $i18n )}</a></p>
</fieldset>
{undef $i18n $name $postfix $skins $allowed $languages $chosen}
