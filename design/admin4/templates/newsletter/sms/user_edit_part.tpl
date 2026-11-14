{* The mobile number row on the admin edit of a newsletter user, admin4 ([ExtensionPointSettings] UserEditParts[]).
   Same data and actions as design/standard/templates/newsletter/sms/user_edit_part.tpl *}
{def $i18n = 'cjw_newsletter/sms'
     $phone_status = cond( is_set( $newsletter_user.phone_status ), $newsletter_user.phone_status, 0 )}
<tr>
    <th scope="row"><label for="nl-sms-user-phone">{'Mobile number'|i18n( $i18n )}</label></th>
    <td>
        <input type="hidden" name="CjwNewsletterSmsPart" value="1" />
        <input id="nl-sms-user-phone" class="a4-input halfbox" type="text" inputmode="tel" name="CjwNewsletterSmsPhone" value="{if is_set( $newsletter_user.phone_number )}{$newsletter_user.phone_number|wash}{/if}" maxlength="40" placeholder="+49 151 23456789" />
        {include uri='design:newsletter/sms/phone_status.tpl' status=$phone_status confirmed=cond( is_set( $newsletter_user.phone_confirmed ), $newsletter_user.phone_confirmed, 0 )}
        {if $phone_status|ne( 2 )}<div><label class="a4-check"><input type="checkbox" name="CjwNewsletterSmsSendCode" value="1" /> <span>{'Send the confirmation code to this number after saving'|i18n( $i18n )}</span></label></div>{/if}
    </td>
</tr>
{undef $i18n $phone_status}
