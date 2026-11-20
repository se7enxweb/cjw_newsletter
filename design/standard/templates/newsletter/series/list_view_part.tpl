{* newsletter/series/list_view_part.tpl: the welcome series link of the list attribute view ([ExtensionPointSettings] ListViewParts[]). Variables: attribute, list_object. @copyright Copyright CJW Network and Exponential Foundation. All rights reserved. GNU General Public License v2.0 (or any later version). *}
<div class="block">
    <label>{'Welcome series'|i18n( 'cjw_newsletter/series' )}:</label>
    <a href={concat( 'newsletter/series_edit/', $attribute.contentobject_id )|ezurl}>{'Manage'|i18n( 'cjw_newsletter/series' )}</a>
</div>
