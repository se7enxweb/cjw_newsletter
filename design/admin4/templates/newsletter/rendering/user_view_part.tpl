{* The rendering's rows of the admin user view, admin4 ([ExtensionPointSettings] UserViewParts[]): the
   language of the newsletters and the picked interests.
   Same data as design/standard/templates/newsletter/rendering/user_view_part.tpl. Variables: newsletter_user *}
{def $i18n = 'cjw_newsletter/rendering'
     $data = cjwnl_rendering_data( 'user_choices', $newsletter_user.id )}
<tr>
    <th scope="row">{'Language of the newsletters'|i18n( $i18n )}</th>
    <td>{if $newsletter_user.language}{$newsletter_user.language|wash}{else}{'The main language of each list'|i18n( $i18n )}{/if}</td>
</tr>
<tr>
    <th scope="row">{'Interests'|i18n( $i18n )}</th>
    <td>{def $picked = 0}{foreach $data.interests as $interest}{if $interest.checked}{if $picked}, {/if}{$interest.name|wash}{set $picked = $picked|inc}{/if}{/foreach}{if $picked|eq( 0 )}{'none'|i18n( $i18n )}{/if}{undef $picked}</td>
</tr>
{undef $i18n $data}
