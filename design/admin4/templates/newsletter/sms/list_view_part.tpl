{* The SMS settings of a newsletter list on its view ([ExtensionPointSettings] ListViewParts[]), admin4. Same data and actions as design/standard/templates/newsletter/sms/list_view_part.tpl. *}
{if is_set( $list_object.sms_enabled )}
<dl class="a4-facts is-rows">
    <div><dt>{'SMS'|i18n( 'cjw_newsletter/sms' )}:</dt>
    <dd>{if $list_object.sms_enabled|eq( 1 )}{'switched on'|i18n( 'cjw_newsletter/sms' )}{if $list_object.sms_sender}, {'sender'|i18n( 'cjw_newsletter/sms' )} <code>{$list_object.sms_sender|wash}</code>{/if}
    {else}{'switched off'|i18n( 'cjw_newsletter/sms' )}{/if}</dd></div>
</dl>
{/if}
