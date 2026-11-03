{* One filter of a virtual newsletter list, read only (admin4): number, name, operation, values.
   Same data as design/standard/templates/newsletter/filtertype/view/tableline.tpl *}
{def $filter_identifier = $filtertype_object.identifier
     $filter_name = $filtertype_object.name
     $values_available = $filtertype_object.values_available
     $values_selected = $filtertype_object.values
     $operations_available = $filtertype_object.operations_available
     $operation_selected = $filtertype_object.operation
      }

<tr class="filter" id="tr_filter_{$filter_index}">
    <td class="a4-num">
        {$filter_index|sum('1')|wash()}
    </td>
    <td>
        {$filter_name|wash()}
    </td>
    <td>
        {$operations_available[ $operation_selected ]|wash()}
    </td>
    <td>
        {def $counter = 0}
        {foreach $values_selected as $value}
            {if $counter|ne( 0 )} {'or'|i18n( 'cjw_newsletter/filtertypes' )} {/if} {$value|wash()}
            {set $counter = $count|inc()}
        {/foreach}
        {undef $counter}
    </td>
</tr>

{undef $filter_identifier $filter_name $values_available $values_selected $operations_available $operation_selected}
