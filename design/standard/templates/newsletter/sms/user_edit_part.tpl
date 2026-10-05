{* The mobile number on the admin page of a newsletter user (area N5, [ExtensionPointSettings] UserEditParts[]).
   newsletter_user. An administrator can enter or remove a number; it is confirmed only with the code the person gets. *}
{def $i18n = 'cjw_newsletter/sms'
     $phone_status = cond( is_set( $newsletter_user.phone_status ), $newsletter_user.phone_status, 0 )}
<tr>
    <th><label for="nl-sms-user-phone">{'Mobile number'|i18n( $i18n )}</label></th>
    <td>
        <input type="hidden" name="CjwNewsletterSmsPart" value="1" />
        <input id="nl-sms-user-phone" class="halfbox" type="text" inputmode="tel" name="CjwNewsletterSmsPhone" value="{if is_set( $newsletter_user.phone_number )}{$newsletter_user.phone_number|wash}{/if}" maxlength="40" placeholder="+49 151 23456789" />
        {include uri='design:newsletter/sms/phone_status.tpl' status=$phone_status confirmed=cond( is_set( $newsletter_user.phone_confirmed ), $newsletter_user.phone_confirmed, 0 )}
        {if $phone_status|ne( 2 )}<br /><label><input type="checkbox" name="CjwNewsletterSmsSendCode" value="1" /> {'Send the confirmation code to this number after saving'|i18n( $i18n )}</label>{/if}
    </td>
</tr>
{undef $i18n $phone_status}
