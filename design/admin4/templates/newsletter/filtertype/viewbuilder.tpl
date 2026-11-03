{* The active filters of a virtual newsletter list, read only (admin4). Requires filtertype_object_array_active.
   Same data as design/standard/templates/newsletter/filtertype/viewbuilder.tpl, included by
   newsletter/subscription_list_virtual.tpl. *}

<section class="a4-section cjwnl-a4-filters" id="filter" aria-labelledby="nl-filters-title">
    <h2 class="a4-h2" id="nl-filters-title">{'Active filters'|i18n( 'cjw_newsletter/filtertypes' )}</h2>

    <div class="a4-table-wrap">
        <table class="a4-table">
            <tbody>
            {foreach $filtertype_object_array_active as $index => $filtertype}
                {include uri="design:newsletter/filtertype/view/tableline.tpl"
                         filtertype_object = $filtertype
                         filter_index = $index
                         name="CjwNewsletterFilterType"}
            {/foreach}
            </tbody>
        </table>
    </div>
</section>
