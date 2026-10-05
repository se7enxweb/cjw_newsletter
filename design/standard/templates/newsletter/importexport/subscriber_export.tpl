{* newsletter/subscriber_export/<list object id>: the subscribers of a list as CSV, with filters *}
{ezcss_require( 'newsletter_ui.css' )}
{ezcss_require( 'newsletter_importexport.css' )}
<div class="newsletter newsletter-subscriber_export">
<div class="context-block nl nl-ie">
    <div class="box-header">
        <h1 class="context-title">{'Subscriber export'|i18n( 'cjw_newsletter/importexport' )}: {$list_object.name|wash}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        <p class="nl-muted">{'The file holds personal data. Every download is recorded in the audit trail with the list, the filters and the number of rows. Cells a spreadsheet would read as a formula start with an apostrophe.'|i18n( 'cjw_newsletter/importexport' )}</p>

        <form method="post" action={concat( 'newsletter/subscriber_export/', $list_object.id )|ezurl} class="nl-ie-form">
            <section class="nl-ie-section">
                <h2>{'Filters'|i18n( 'cjw_newsletter/importexport' )}</h2>
                <fieldset class="nl-ie-choices">
                    <legend>{'Subscription status'|i18n( 'cjw_newsletter/importexport' )} <span class="nl-muted">({'none checked: every status'|i18n( 'cjw_newsletter/importexport' )})</span></legend>
                    {foreach $status_names as $status_id => $status_name}
                    <label class="nl-ie-check"><input type="checkbox" name="Statuses[]" value="{$status_id}"{if $filters.statuses|contains( $status_id )} checked="checked"{/if} /> {$status_name|wash}</label>
                    {/foreach}
                </fieldset>
                <div class="nl-filter">
                    <label>{'Date'|i18n( 'cjw_newsletter/importexport' )}
                        <select name="DateField">
                            {foreach $date_fields as $date_field}<option value="{$date_field|wash}"{if eq( $date_field, $filters.date_field )} selected="selected"{/if}>{$column_names[$date_field]|wash}</option>{/foreach}
                        </select>
                    </label>
                    <label>{'from'|i18n( 'cjw_newsletter/importexport' )} <input type="date" name="DateFrom" value="{$filter_date_from|wash}" /></label>
                    <label>{'to'|i18n( 'cjw_newsletter/importexport' )} <input type="date" name="DateTo" value="{$filter_date_to|wash}" /></label>
                    <label>{'Field delimiter'|i18n( 'cjw_newsletter/importexport' )}
                        <select name="CsvDelimiter">
                            {foreach $delimiters as $name}<option value="{$name|wash}"{if eq( $name, $delimiter_name )} selected="selected"{/if}>{cond( eq( $name, 'semicolon' ), 'Semicolon ;'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'comma' ), 'Comma ,'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'tab' ), 'Tab'|i18n( 'cjw_newsletter/importexport' ), 'Pipe |'|i18n( 'cjw_newsletter/importexport' ) )}</option>{/foreach}
                        </select>
                    </label>
                </div>
            </section>
            <section class="nl-ie-section">
                <h2>{'Columns'|i18n( 'cjw_newsletter/importexport' )}</h2>
                <fieldset class="nl-ie-choices nl-ie-columns">
                    <legend class="nl-ie-hidden">{'Columns'|i18n( 'cjw_newsletter/importexport' )}</legend>
                    {foreach $column_names as $column => $column_name}
                    <label class="nl-ie-check"><input type="checkbox" name="Columns[]" value="{$column|wash}"{if $filters.columns|contains( $column )} checked="checked"{/if} /> {$column_name|wash}</label>
                    {/foreach}
                </fieldset>
            </section>

            <section class="nl-ie-section">
                <h2>{'Preview'|i18n( 'cjw_newsletter/importexport' )} <span class="nl-muted">({'the newest first'|i18n( 'cjw_newsletter/importexport' )})</span> <span class="nl-pill is-info">{'%count rows'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $count ) )}</span></h2>
                {if $preview|count}
                <div class="nl-ie-scroll">
                <table class="list nl-table nl-ie-preview">
                    <tr>{foreach $filters.columns as $column}<th>{$column_names[$column]|wash}</th>{/foreach}</tr>
                    {foreach $preview as $row sequence array( 'bglight', 'bgdark' ) as $seq}
                    <tr class="{$seq}">{foreach $filters.columns as $column}<td>{$row[$column]|wash}</td>{/foreach}</tr>
                    {/foreach}
                </table>
                </div>
                {else}
                <div class="nl-empty"><p>{'No subscriber matches the filters.'|i18n( 'cjw_newsletter/importexport' )}</p></div>
                {/if}
            </section>

            <div class="controlbar nl-ie-buttons">
                <input class="button" type="submit" name="PreviewButton" value="{'Update the preview'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                <input class="defaultbutton" type="submit" name="ExportButton" value="{'Download CSV'|i18n( 'cjw_newsletter/importexport' )|wash}"{if $count|eq( 0 )} disabled="disabled"{/if} />
                <input class="button" type="submit" name="CancelButton" value="{'Back to the list'|i18n( 'cjw_newsletter/importexport' )|wash}" />
            </div>
        </form>
    </div>
</div>
</div>
