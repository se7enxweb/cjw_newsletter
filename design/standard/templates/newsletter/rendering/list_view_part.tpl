{* The rendering's part of the list attribute view (cjw_newsletter 4.2.0, [ExtensionPointSettings] ListViewParts[]).
   Variables: attribute, list_object *}
{def $i18n = 'cjw_newsletter/rendering'
     $languages = cjwnl_rendering_data( 'list_languages', $list_object.contentobject_id )}
<div class="block float-break cjwnl-rendering-part">
    <div class="element">
        <label>{'Skins allowed for a send'|i18n( $i18n )}:</label>
        {if $list_object.skin_name_array_string|eq( '' )}{'Every skin'|i18n( $i18n )}{else}{foreach $list_object.skin_name_array_string|explode( ';' ) as $skin}{if $skin|ne( '' )}{$skin|wash} {/if}{/foreach}{/if}
    </div>
    <div class="element">
        <label>{'Main language'|i18n( $i18n )}:</label>
        {cjwnl_rendering_data( 'main_language', $list_object.contentobject_id )|wash}
    </div>
    <div class="element">
        <label>{'Languages'|i18n( $i18n )}:</label>
        {if $languages|count|eq( 0 )}{'Only the main language'|i18n( $i18n )}{else}{$languages|implode( ', ' )|wash}{/if}
    </div>
    <div class="element">
        <label>{'Interests'|i18n( $i18n )}:</label>
        {switch match=$list_object.interest_source}
        {case match='topics'}{'Topics of the list'|i18n( $i18n )}{/case}
        {case match='eztags'}{'Tags (eztags)'|i18n( $i18n )}{/case}
        {case}{'No interests'|i18n( $i18n )}{/case}
        {/switch}
        {if $list_object.interest_source|ne( '' )}({cjwnl_rendering_data( 'interests', $list_object.contentobject_id )|count}){/if}
    </div>
</div>
{undef $i18n $languages}
