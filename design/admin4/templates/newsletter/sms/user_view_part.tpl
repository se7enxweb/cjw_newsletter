{* The mobile number row on the admin view of a newsletter user, admin4 ([ExtensionPointSettings] UserViewParts[]).
   Same data as design/standard/templates/newsletter/sms/user_view_part.tpl *}
{if and( is_set( $newsletter_user.phone_number ), $newsletter_user.phone_number )}
<tr>
    <th scope="row">{'Mobile number'|i18n( 'cjw_newsletter/sms' )}</th>
    <td>{$newsletter_user.phone_number|wash} {include uri='design:newsletter/sms/phone_status.tpl' status=$newsletter_user.phone_status confirmed=$newsletter_user.phone_confirmed}</td>
</tr>
{/if}
