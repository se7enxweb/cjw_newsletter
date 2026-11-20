{* newsletter/segments/list_view_part.tpl: the segments link of the list attribute view ([ExtensionPointSettings] ListViewParts[]). Variables: attribute, list_object. *}
<div class="block">
    <label>{'Segments'|i18n( 'cjw_newsletter/segments' )}:</label>
    <a href={concat( 'newsletter/segment_list/', $attribute.contentobject_id )|ezurl}>{'Manage'|i18n( 'cjw_newsletter/segments' )}</a>
</div>
