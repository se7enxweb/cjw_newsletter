{*  newsletter/statistics/list_view_part.tpl (cjw_newsletter 4.2.0, area statistics)

    The tracking mode in the list attribute view ([ExtensionPointSettings] ListViewParts[]). attribute, list_object
*}
{def $i18n = 'cjw_newsletter/statistics'
     $mode = cond( is_object( $list_object ), $list_object.tracking_mode|int, 0 )}
<div class="block">
    <label>{'Statistics: tracking of the sends'|i18n( $i18n )}:</label>
    {cond( eq( $mode, 2 ), 'per person with consent'|i18n( $i18n ), eq( $mode, 1 ), 'anonymous totals'|i18n( $i18n ), 'off'|i18n( $i18n ) )}
</div>
{undef $i18n $mode}
