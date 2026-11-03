{* One filter of a virtual newsletter list as form fields (admin4): remove box, name, operation, values.
   Same data and actions as design/standard/templates/newsletter/filtertype/form/tableline.tpl *}
{def $filter_identifier = $filtertype_object.identifier
     $filter_name = $filtertype_object.name
     $values_available = $filtertype_object.values_available
     $values_selected = $filtertype_object.values
     $operations_available = $filtertype_object.operations_available
     $operation_selected = $filtertype_object.operation
      }

<tr class="filter" id="tr_filter_{$filter_index}">
    <td>
        <input type="checkbox" name="{$post_var_remove}" value="{$filter_index}" aria-label="{$filter_name|wash}" />
    </td>
    <td>
        <input type="hidden" name="F[{$filter_index}][i]" value="{$filter_identifier}" id="name_{$filter_index}" />
        <label for="operation_{$filter_index}">{$filter_name}</label>
    </td>
    <td>
        <select class="a4-input is-auto select-small" name="F[{$filter_index}][o]" id="operation_{$filter_index}">
        {foreach $operations_available as $key => $name}
            <option value="{$key}"{if $key|eq( $operation_selected )} selected="selected"{/if}>{$name}</option>
        {/foreach}
        </select>
    </td>
    <td>
        {if is_array( $values_available )}
            <select class="a4-input is-auto" name="F[{$filter_index}][v][]" id="values_{$filter_index}"{if $values_selected|count|gt( 1 )} multiple="multiple"{/if} aria-label="{$filter_name|wash}">
            {foreach $values_available as $key => $name}
                <option value="{$key}"{if $values_selected|contains( $key )} selected="selected"{/if}>{$name}</option>
            {/foreach}
            </select>
            <a data-cjwnl-toggle-multiple="values_{$filter_index}" href="#">[+]</a>
        {else}

            {if $values_selected|count|eq( 0 )}
                {set $values_selected = array( '' )}
            {/if}

            {foreach $values_selected as $value}
                <input class="a4-input is-auto" type="text" name="F[{$filter_index}][v][]" id="values_{$filter_index}" value="{$value}" aria-label="{$filter_name|wash}" />
            {/foreach}
        {/if}
    </td>
</tr>

{undef $filter_identifier $filter_name $values_available $values_selected $operations_available $operation_selected}
