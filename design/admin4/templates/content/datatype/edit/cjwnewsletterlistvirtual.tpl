{* The virtual newsletter list attribute in the object edit form, admin4. Same data, POST names and buttons as
   design/standard/templates/content/datatype/edit/cjwnewsletterlistvirtual.tpl: the skin, and the filters that pick
   the subscribers (newsletter/filtertype/formbuilder.tpl, which has an admin4 copy). *}
{if is_unset( $attribute_base )}
  {def $attribute_base='ContentObjectAttribute'}
{/if}

{def $i18n = 'cjw_newsletter/datatype/cjwnewsletterlist'
     $list_object = $attribute.content
     $available_skin_array = $list_object.available_skin_array
     $skin_name = $list_object['skin_name']}

<div class="a4-stack">

<fieldset class="a4-field">
    <legend>{'Newsletter skin name'|i18n( $i18n )}</legend>
    <div class="a4-row">
    {foreach $available_skin_array as $skin_name_2}
        <label class="a4-check"><input type="radio" name="{$attribute_base}_CjwNewsletterList_SkinName_{$attribute.id}" value="{$skin_name_2|wash}"{if or( eq( $skin_name, $skin_name_2 ), eq( $available_skin_array|count(), 1 ) )} checked="checked"{/if} /> <span>{$skin_name_2|wash}</span></label>
    {/foreach}
    </div>
</fieldset>

<fieldset class="a4-field">
    <legend>{'Filter'|i18n( 'cjw_newsletter/datatype/cjwnewsletterlistvirtual' )}</legend>
    <div class="a4-row">
        <select id="cjwnl-filter-new-{$attribute.id}" name="{$attribute_base}_new_filtertype_identifier_{$attribute.id}" aria-label="{'Filter'|i18n( 'cjw_newsletter/datatype/cjwnewsletterlistvirtual' )|wash}">
            {foreach $list_object.filtertypes_available as $filtertype}
            <option value="{$filtertype.identifier|wash}">{$filtertype.name|wash}</option>
            {/foreach}
        </select>
        <input class="a4-btn a4-btn-small" type="submit" name="CustomActionButton[{$attribute.id}_new_filter]" value="+" aria-label="{concat( 'Filter'|i18n( 'cjw_newsletter/datatype/cjwnewsletterlistvirtual' ), ' +' )|wash}" />
    </div>

    {include uri='design:newsletter/filtertype/formbuilder.tpl'
             filtertype_object_array_active=$list_object.filtertypes_active
             filtertype_object_array_available=$list_object.filtertypes_available
             post_var_remove=concat( $attribute_base, '_filter_remove_index_', $attribute.id, '[]' )}

    <div class="a4-row">
        <input class="a4-btn a4-btn-small" type="submit" name="CustomActionButton[{$attribute.id}_remove_selected]" value="{'Remove selected'|i18n( 'design/standard/content/datatype' )|wash}" />
    </div>
</fieldset>

</div>
{undef}
