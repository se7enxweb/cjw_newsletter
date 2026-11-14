{* The tracking mode of a list (area statistics, [ExtensionPointSettings] ListEditParts[]), admin4. Same data and POST
   name as design/standard/templates/newsletter/statistics/list_edit_part.tpl. attribute, attribute_base, list_object *}
{def $i18n = 'cjw_newsletter/statistics'
     $mode = cond( is_object( $list_object ), $list_object.tracking_mode|int, 0 )
     $name = concat( $attribute_base, '_CjwNewsletterList_TrackingMode_', $attribute.id )}
<fieldset class="a4-field">
    <legend>{'Statistics: tracking of the sends'|i18n( $i18n )}</legend>
    <div class="a4-row">
        <label class="a4-check"><input type="radio" name="{$name}" value="0"{if eq( $mode, 0 )} checked="checked"{/if} /> <span>{'off'|i18n( $i18n )}</span></label>
        <label class="a4-check"><input type="radio" name="{$name}" value="1"{if eq( $mode, 1 )} checked="checked"{/if} /> <span>{'anonymous totals'|i18n( $i18n )}</span></label>
        <label class="a4-check"><input type="radio" name="{$name}" value="2"{if eq( $mode, 2 )} checked="checked"{/if} /> <span>{'per person with consent'|i18n( $i18n )}</span></label>
    </div>
    <p class="a4-help">{'Per person counts only the subscribers who switched on "Newsletter statistics" on their e-mail preference page; the others are counted anonymously. A send may choose another mode in the send form.'|i18n( $i18n )}</p>
</fieldset>
{undef $i18n $mode $name}
