{* The SMS fields of the newsletter list attribute (area N5, [ExtensionPointSettings] ListEditParts[]).
   attribute, attribute_base, list_object (CjwNewsletterList) *}
{def $i18n = 'cjw_newsletter/sms'
     $prefix = concat( $attribute_base, '_CjwNewsletterList_' )}
<input type="hidden" name="{$prefix}SmsPart_{$attribute.id}" value="1" />
<div class="block">
    <label><input type="checkbox" name="{$prefix}SmsEnabled_{$attribute.id}" value="1"{if and( is_set( $list_object.sms_enabled ), $list_object.sms_enabled|eq( 1 ) )} checked="checked"{/if} />
    {'Editions of this list may be sent by SMS'|i18n( $i18n )}</label>
    <p class="nl-hint">{'Only subscribers who confirmed their mobile number and agreed to newsletters by SMS get them.'|i18n( $i18n )}</p>
</div>
<div class="block">
    <label for="nl-sms-sender-{$attribute.id}">{'SMS sender'|i18n( $i18n )}</label>
    <input id="nl-sms-sender-{$attribute.id}" class="halfbox" type="text" name="{$prefix}SmsSender_{$attribute.id}" value="{if is_set( $list_object.sms_sender )}{$list_object.sms_sender|wash}{/if}" maxlength="50" />
    <p class="nl-hint">{'A number like +4915123456789 or a name of 3 to 11 letters and digits; empty: the sender of the SMS transport.'|i18n( $i18n )}</p>
</div>
{undef $i18n $prefix}
