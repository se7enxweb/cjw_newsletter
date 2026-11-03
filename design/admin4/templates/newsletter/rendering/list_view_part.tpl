{* The rendering part of the list attribute view ([ExtensionPointSettings] ListViewParts[]), admin4. Same data and actions as design/standard/templates/newsletter/rendering/list_view_part.tpl. Variables: attribute, list_object *}
{def $i18n = 'cjw_newsletter/rendering'
     $languages = cjwnl_rendering_data( 'list_languages', $list_object.contentobject_id )}
<dl class="a4-facts is-rows cjwnl-rendering-part">
    <div><dt>{'Skins allowed for a send'|i18n( $i18n )}:</dt>
    <dd>{if $list_object.skin_name_array_string|eq( '' )}{'Every skin'|i18n( $i18n )}{else}{foreach $list_object.skin_name_array_string|explode( ';' ) as $skin}{if $skin|ne( '' )}{$skin|wash} {/if}{/foreach}{/if}</dd></div>
    <div><dt>{'Main language'|i18n( $i18n )}:</dt>
    <dd>{cjwnl_rendering_data( 'main_language', $list_object.contentobject_id )|wash}</dd></div>
    <div><dt>{'Languages'|i18n( $i18n )}:</dt>
    <dd>{if $languages|count|eq( 0 )}{'Only the main language'|i18n( $i18n )}{else}{$languages|implode( ', ' )|wash}{/if}</dd></div>
    <div><dt>{'Interests'|i18n( $i18n )}:</dt>
    <dd>{switch match=$list_object.interest_source}
        {case match='topics'}{'Topics of the list'|i18n( $i18n )}{/case}
        {case match='eztags'}{'Tags (eztags)'|i18n( $i18n )}{/case}
        {case}{'No interests'|i18n( $i18n )}{/case}
        {/switch}
        {if $list_object.interest_source|ne( '' )}({cjwnl_rendering_data( 'interests', $list_object.contentobject_id )|count}){/if}</dd></div>
</dl>
{undef $i18n $languages}
