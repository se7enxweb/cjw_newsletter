{* A link from the mail send form of an edition to its SMS send page (area N5, [ExtensionPointSettings] SendFormParts[]). *}
{if ezini( 'SmsSettings', 'Sms', 'cjw_newsletter.ini' )|eq( 'enabled' )}
<p class="nl-hint" id="nl-sms-send-link"><a href={concat( 'newsletter/sms_send/', $node_id )|ezurl}>{'Send this edition by SMS instead'|i18n( 'cjw_newsletter/sms' )}</a></p>
{/if}
