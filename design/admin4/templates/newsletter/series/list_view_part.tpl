{* The welcome series link of the list attribute view ([ExtensionPointSettings] ListViewParts[]), admin4. Variables: attribute, list_object. @copyright Copyright CJW Network and Exponential Foundation. All rights reserved. GNU General Public License v2.0 (or any later version). *}
<dl class="a4-facts is-rows">
    <div><dt>{'Welcome series'|i18n( 'cjw_newsletter/series' )}:</dt>
    <dd><span class="a4-icon a4-bi-collection-play" aria-hidden="true"></span> <a href={concat( 'newsletter/series_edit/', $attribute.contentobject_id )|ezurl}>{'Manage'|i18n( 'cjw_newsletter/series' )}</a></dd></div>
</dl>
