{* The rendering's rows of the admin user edit form (cjw_newsletter 4.2.0, [ExtensionPointSettings] UserEditParts[]): the
   language of the newsletters and the interests. Read by CjwNewsletterRenderingHooks::userInput() / userStored().
   Variables: newsletter_user *}
{def $i18n = 'cjw_newsletter/rendering'
     $data = cjwnl_rendering_data( 'user_choices', $newsletter_user.id )}
<tr>
    <th>{'Language of the newsletters'|i18n( $i18n )}</th>
    <td>
        <input type="hidden" name="CjwNewsletterRendering_UserPart" value="1" />
        <select name="CjwNewsletterRendering_Language">
            <option value="">{'The main language of each list'|i18n( $i18n )}</option>
            {foreach cjwnl_rendering_data( 'content_languages' ) as $locale => $language_name}
            <option value="{$locale|wash}"{if $newsletter_user.language|eq( $locale )} selected="selected"{/if}>{$language_name|wash} ({$locale|wash})</option>
            {/foreach}
        </select>
    </td>
</tr>
{if $data.interests|count}
<tr>
    <th>{'Interests'|i18n( $i18n )}</th>
    <td>
        {foreach $data.interests as $interest}
        <label style="display:inline-block;margin-right:1em;"><input type="checkbox" name="CjwNewsletterRendering_Interest[]" value="{$interest.id}"{if $interest.checked} checked="checked"{/if} /> {$interest.name|wash} <span style="color:#666;">({$interest.list_name|wash})</span></label>
        {/foreach}
    </td>
</tr>
{/if}
{undef $i18n $data}
