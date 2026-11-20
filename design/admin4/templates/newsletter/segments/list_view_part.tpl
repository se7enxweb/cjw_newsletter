{* The segments link of the list attribute view ([ExtensionPointSettings] ListViewParts[]), admin4. Variables: attribute, list_object. *}
<dl class="a4-facts is-rows">
    <div><dt>{'Segments'|i18n( 'cjw_newsletter/segments' )}:</dt>
    <dd><span class="a4-icon a4-bi-signpost-split" aria-hidden="true"></span> <a href={concat( 'newsletter/segment_list/', $attribute.contentobject_id )|ezurl}>{'Manage'|i18n( 'cjw_newsletter/segments' )}</a></dd></div>
</dl>
