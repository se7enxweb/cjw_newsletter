{* The optional mobile number in the public subscribe form (area N5, [ExtensionPointSettings] SubscribeFormParts[]).
   A number given here gets a confirmation code by SMS; newsletters by SMS start only once the code is entered. *}
{if ezini( 'SmsSettings', 'Sms', 'cjw_newsletter.ini' )|eq( 'enabled' )}
<div class="block" id="nl-sms-subscribe">
    <label for="CjwNewsletterSmsPhone">{'Mobile number (optional, for newsletters by SMS)'|i18n( 'cjw_newsletter/sms' )}:</label>
    <input class="halfbox" id="CjwNewsletterSmsPhone" type="text" inputmode="tel" autocomplete="tel" name="CjwNewsletterSmsPhone" value="{if ezhttp_hasvariable( 'CjwNewsletterSmsPhone', 'post' )}{ezhttp( 'CjwNewsletterSmsPhone', 'post' )|wash}{/if}" maxlength="40" placeholder="+49 151 23456789" />
    <p class="nl-hint">{'We send a code to this number. Newsletters by SMS start only once you enter it; reply STOP to end them.'|i18n( 'cjw_newsletter/sms' )}</p>
</div>
{/if}
