{*  newsletter/statistics/list_edit_part.tpl (cjw_newsletter 4.2.0, area statistics)

    The tracking mode of a list ([ExtensionPointSettings] ListEditParts[]), read by
    CjwNewsletterStatisticsHooks::listAttributeInput(). attribute, attribute_base, list_object
*}
{def $i18n = 'cjw_newsletter/statistics'
     $mode = cond( is_object( $list_object ), $list_object.tracking_mode|int, 0 )
     $name = concat( $attribute_base, '_CjwNewsletterList_TrackingMode_', $attribute.id )}
<label>{'Statistics: tracking of the sends'|i18n( $i18n )}</label>
<input type="radio" name="{$name}" value="0"{if eq( $mode, 0 )} checked="checked"{/if} /> {'off'|i18n( $i18n )}
<input type="radio" name="{$name}" value="1"{if eq( $mode, 1 )} checked="checked"{/if} /> {'anonymous totals'|i18n( $i18n )}
<input type="radio" name="{$name}" value="2"{if eq( $mode, 2 )} checked="checked"{/if} /> {'per person with consent'|i18n( $i18n )}
<p class="nl-hint">{'Per person counts only the subscribers who switched on "Newsletter statistics" on their e-mail preference page; the others are counted anonymously. A send may choose another mode in the send form.'|i18n( $i18n )}</p>
{undef $i18n $mode $name}
