{* The SMS part of the list attribute edit (area N5, [ExtensionPointSettings] ListEditParts[]), admin4. Same data and
   POST names as design/standard/templates/newsletter/sms/list_edit_part.tpl.
   Variables: attribute, attribute_base, list_object (CjwNewsletterList) *}
{def $i18n = 'cjw_newsletter/sms'
     $prefix = concat( $attribute_base, '_CjwNewsletterList_' )}
<input type="hidden" name="{$prefix}SmsPart_{$attribute.id}" value="1" />
<div class="a4-field">
    <label class="a4-check"><input type="checkbox" name="{$prefix}SmsEnabled_{$attribute.id}" value="1"{if and( is_set( $list_object.sms_enabled ), $list_object.sms_enabled|eq( 1 ) )} checked="checked"{/if} /> <span>{'Editions of this list may be sent by SMS'|i18n( $i18n )}</span></label>
    <p class="a4-help">{'Only subscribers who confirmed their mobile number and agreed to newsletters by SMS get them.'|i18n( $i18n )}</p>
</div>
<div class="a4-field">
    <label for="nl-sms-sender-{$attribute.id}">{'SMS sender'|i18n( $i18n )}</label>
    <input id="nl-sms-sender-{$attribute.id}" type="text" name="{$prefix}SmsSender_{$attribute.id}" value="{if is_set( $list_object.sms_sender )}{$list_object.sms_sender|wash}{/if}" maxlength="50" aria-describedby="nl-sms-sender-{$attribute.id}-help" />
    <p class="a4-help" id="nl-sms-sender-{$attribute.id}-help">{'A number like +4915123456789 or a name of 3 to 11 letters and digits; empty: the sender of the SMS transport.'|i18n( $i18n )}</p>
</div>
{undef $i18n $prefix}
