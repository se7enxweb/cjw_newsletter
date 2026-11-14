{* The active filters of a virtual newsletter list, as form fields (admin4). Requires filtertype_object_array_active.
   Same data and actions as design/standard/templates/newsletter/filtertype/formbuilder.tpl. The closing </form> is
   the includer's, as in the standard template. *}

{* The [+] link of a filter switches its select between single and multiple choice (cjw_newsletter.js). *}
{ezscript( array( 'cjw_newsletter.js' ) )}

<fieldset class="a4-field cjwnl-a4-filters" title="{'Filter'|i18n( 'cjw_newsletter/filtertypes' )}" id="filter">
    <legend>{'Active filters'|i18n( 'cjw_newsletter/filtertypes' )}</legend>

    <div class="a4-table-wrap">
        <table class="a4-table">
            <tbody>
            {foreach $filtertype_object_array_active as $index => $filtertype}
                {*
                    - identifier
                    - name
                    - operation
                    - values
                *}
                {include uri="design:newsletter/filtertype/form/tableline.tpl"
                         filtertype_object = $filtertype
                         filter_index = $index
                         name="CjwNewsletterFilterType"
                         post_var_remove=$post_var_remove}
            {/foreach}
            </tbody>
        </table>
    </div>

</fieldset>


</form>
