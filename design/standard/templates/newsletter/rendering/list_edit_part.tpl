{* The rendering's part of the list attribute edit form (cjw_newsletter 4.2.0, [ExtensionPointSettings] ListEditParts[]):
   allowed skins, languages, main language and interest source. Read by CjwNewsletterRenderingHooks::listAttributeInput().
   Variables: attribute, attribute_base, list_object *}
{def $i18n = 'cjw_newsletter/rendering'
     $name = concat( $attribute_base, '_CjwNewsletterList_' )
     $postfix = concat( '_', $attribute.id )
     $skins = cjwnl_rendering_data( 'available_skins' )
     $allowed = $list_object.skin_name_array_string|explode( ';' )
     $languages = cjwnl_rendering_data( 'content_languages' )
     $chosen = $list_object.language_array_string|explode( ';' )}
<div class="cjwnl-rendering-part">
<input type="hidden" name="{$name}RenderingPart{$postfix}" value="1" />

<fieldset>
<legend>{'Skins allowed for a send'|i18n( $i18n )}</legend>
<p class="cjwnl-hint">{'The editor chooses one of these when an edition is sent. None ticked: every skin.'|i18n( $i18n )}</p>
{foreach $skins as $skin}
{def $settings = cjwnl_rendering_data( 'skin_settings', $skin )}
<label class="cjwnl-inline"><input type="checkbox" name="{$name}SkinNameArray{$postfix}[]" value="{$skin|wash}"{if $allowed|contains( $skin )} checked="checked"{/if} /> {$settings.description|wash} <span class="cjwnl-hint">({$skin|wash})</span></label>
{undef $settings}
{/foreach}
</fieldset>

<fieldset>
<legend>{'Languages'|i18n( $i18n )}</legend>
<p class="cjwnl-hint">{'One edition with translations: each subscriber gets his language when the edition has it, else the main language. None ticked: every subscriber gets the main language.'|i18n( $i18n )}</p>
{foreach $languages as $locale => $language_name}
<label class="cjwnl-inline"><input type="checkbox" name="{$name}LanguageArray{$postfix}[]" value="{$locale|wash}"{if $chosen|contains( $locale )} checked="checked"{/if} /> {$language_name|wash} <span class="cjwnl-hint">({$locale|wash})</span></label>
{/foreach}
<div class="block">
<label for="{$name}MainLanguage{$postfix}">{'Main language'|i18n( $i18n )}</label>
<select id="{$name}MainLanguage{$postfix}" name="{$name}MainLanguage{$postfix}">
<option value="">{'The language of the main siteaccess'|i18n( $i18n )}</option>
{foreach $languages as $locale => $language_name}
<option value="{$locale|wash}"{if $list_object.main_language|eq( $locale )} selected="selected"{/if}>{$language_name|wash} ({$locale|wash})</option>
{/foreach}
</select>
</div>
</fieldset>

<fieldset>
<legend>{'Interests'|i18n( $i18n )}</legend>
<p class="cjwnl-hint">{'Subscribers pick interests on their preference page; the block "articles for your interests" of a skin is filled from them.'|i18n( $i18n )}</p>
<label class="cjwnl-inline"><input type="radio" name="{$name}InterestSource{$postfix}" value=""{if $list_object.interest_source|eq( '' )} checked="checked"{/if} /> {'No interests'|i18n( $i18n )}</label>
<label class="cjwnl-inline"><input type="radio" name="{$name}InterestSource{$postfix}" value="topics"{if $list_object.interest_source|eq( 'topics' )} checked="checked"{/if} /> {'Topics of the list'|i18n( $i18n )}</label>
<label class="cjwnl-inline"><input type="radio" name="{$name}InterestSource{$postfix}" value="eztags"{if $list_object.interest_source|eq( 'eztags' )} checked="checked"{/if} /> {'Tags (eztags)'|i18n( $i18n )}</label>
<p class="cjwnl-hint"><a href={'newsletter/interest_list'|ezurl}>{'Manage the interests'|i18n( $i18n )}</a></p>
</fieldset>
</div>
{undef $i18n $name $postfix $skins $allowed $languages $chosen}
