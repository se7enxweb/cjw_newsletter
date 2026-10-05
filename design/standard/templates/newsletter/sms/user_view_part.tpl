{* The mobile number on the admin view of a newsletter user (area N5, [ExtensionPointSettings] UserViewParts[]). *}
{if and( is_set( $newsletter_user.phone_number ), $newsletter_user.phone_number )}
<tr>
    <th>{'Mobile number'|i18n( 'cjw_newsletter/sms' )}</th>
    <td>{$newsletter_user.phone_number|wash} {include uri='design:newsletter/sms/phone_status.tpl' status=$newsletter_user.phone_status confirmed=$newsletter_user.phone_confirmed}</td>
</tr>
{/if}
