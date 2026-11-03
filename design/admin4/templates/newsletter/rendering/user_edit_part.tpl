{* The rendering's rows of the admin user edit form, admin4 ([ExtensionPointSettings] UserEditParts[]): the
   language of the newsletters and the interests.
   Same data and actions as design/standard/templates/newsletter/rendering/user_edit_part.tpl. Variables: newsletter_user *}
{def $i18n = 'cjw_newsletter/rendering'
     $data = cjwnl_rendering_data( 'user_choices', $newsletter_user.id )}
<tr>
    <th scope="row"><label for="nl-rendering-language">{'Language of the newsletters'|i18n( $i18n )}</label></th>
    <td>
        <input type="hidden" name="CjwNewsletterRendering_UserPart" value="1" />
        <select id="nl-rendering-language" class="a4-input" name="CjwNewsletterRendering_Language">
            <option value="">{'The main language of each list'|i18n( $i18n )}</option>
            {foreach cjwnl_rendering_data( 'content_languages' ) as $locale => $language_name}
            <option value="{$locale|wash}"{if $newsletter_user.language|eq( $locale )} selected="selected"{/if}>{$language_name|wash} ({$locale|wash})</option>
            {/foreach}
        </select>
    </td>
</tr>
{if $data.interests|count}
<tr>
    <th scope="row">{'Interests'|i18n( $i18n )}</th>
    <td>
        <div class="a4-chips cjwnl-a4-interests">
        {foreach $data.interests as $interest}
        <label class="a4-check"><input type="checkbox" name="CjwNewsletterRendering_Interest[]" value="{$interest.id}"{if $interest.checked} checked="checked"{/if} /> <span>{$interest.name|wash} <span class="a4-meta">({$interest.list_name|wash})</span></span></label>
        {/foreach}
        </div>
    </td>
</tr>
{/if}
{undef $i18n $data}
