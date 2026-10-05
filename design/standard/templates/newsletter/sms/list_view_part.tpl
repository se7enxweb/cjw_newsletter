{* The SMS settings of a newsletter list on its view (area N5, [ExtensionPointSettings] ListViewParts[]). *}
{if is_set( $list_object.sms_enabled )}
<div class="block">
    <label>{'SMS'|i18n( 'cjw_newsletter/sms' )}:</label>
    {if $list_object.sms_enabled|eq( 1 )}{'switched on'|i18n( 'cjw_newsletter/sms' )}{if $list_object.sms_sender}, {'sender'|i18n( 'cjw_newsletter/sms' )} <code>{$list_object.sms_sender|wash}</code>{/if}
    {else}{'switched off'|i18n( 'cjw_newsletter/sms' )}{/if}
</div>
{/if}
