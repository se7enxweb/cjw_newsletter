{* The subscribers of a list as CSV, with filters, admin4: same data and actions as design/standard/templates/newsletter/importexport/subscriber_export.tpl *}
<div class="a4-page newsletter newsletter-subscriber_export">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'Subscriber export'|i18n( 'cjw_newsletter/importexport' )}: {$list_object.name|wash}</h1>
            <p class="a4-pagehead-sub">{'The file holds personal data. Every download is recorded in the audit trail with the list, the filters and the number of rows. Cells a spreadsheet would read as a formula start with an apostrophe.'|i18n( 'cjw_newsletter/importexport' )}</p>
        </div>
    </div>

    <form method="post" action={concat( 'newsletter/subscriber_export/', $list_object.id )|ezurl}>
        <section class="a4-card a4-section" aria-labelledby="nl-se-filters">
            <div class="a4-card-head"><h2 class="a4-h3 a4-card-title" id="nl-se-filters">{'Filters'|i18n( 'cjw_newsletter/importexport' )}</h2></div>
            <div class="a4-card-body a4-stack">
                <fieldset class="a4-field">
                    <legend>{'Subscription status'|i18n( 'cjw_newsletter/importexport' )} <span class="a4-muted">({'none checked: every status'|i18n( 'cjw_newsletter/importexport' )})</span></legend>
                    <div class="cjwnl-a4-choices">
                        {foreach $status_names as $status_id => $status_name}
                        <label class="a4-check"><input type="checkbox" name="Statuses[]" value="{$status_id}"{if $filters.statuses|contains( $status_id )} checked="checked"{/if} /> {$status_name|wash}</label>
                        {/foreach}
                    </div>
                </fieldset>
                <div class="a4-filterbar">
                    <div class="a4-field">
                        <label for="nl-se-datefield">{'Date'|i18n( 'cjw_newsletter/importexport' )}</label>
                        <select id="nl-se-datefield" name="DateField">
                            {foreach $date_fields as $date_field}<option value="{$date_field|wash}"{if eq( $date_field, $filters.date_field )} selected="selected"{/if}>{$column_names[$date_field]|wash}</option>{/foreach}
                        </select>
                    </div>
                    <div class="a4-field">
                        <label for="nl-se-datefrom">{'from'|i18n( 'cjw_newsletter/importexport' )}</label>
                        <input id="nl-se-datefrom" type="date" name="DateFrom" value="{$filter_date_from|wash}" />
                    </div>
                    <div class="a4-field">
                        <label for="nl-se-dateto">{'to'|i18n( 'cjw_newsletter/importexport' )}</label>
                        <input id="nl-se-dateto" type="date" name="DateTo" value="{$filter_date_to|wash}" />
                    </div>
                    <div class="a4-field">
                        <label for="nl-se-delimiter">{'Field delimiter'|i18n( 'cjw_newsletter/importexport' )}</label>
                        <select id="nl-se-delimiter" name="CsvDelimiter">
                            {foreach $delimiters as $name}<option value="{$name|wash}"{if eq( $name, $delimiter_name )} selected="selected"{/if}>{cond( eq( $name, 'semicolon' ), 'Semicolon ;'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'comma' ), 'Comma ,'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'tab' ), 'Tab'|i18n( 'cjw_newsletter/importexport' ), 'Pipe |'|i18n( 'cjw_newsletter/importexport' ) )}</option>{/foreach}
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <section class="a4-card a4-section" aria-labelledby="nl-se-columns">
            <div class="a4-card-head"><h2 class="a4-h3 a4-card-title" id="nl-se-columns">{'Columns'|i18n( 'cjw_newsletter/importexport' )}</h2></div>
            <div class="a4-card-body">
                <fieldset class="a4-field">
                    <legend class="a4-sr-only">{'Columns'|i18n( 'cjw_newsletter/importexport' )}</legend>
                    <div class="cjwnl-a4-choices">
                        {foreach $column_names as $column => $column_name}
                        <label class="a4-check"><input type="checkbox" name="Columns[]" value="{$column|wash}"{if $filters.columns|contains( $column )} checked="checked"{/if} /> {$column_name|wash}</label>
                        {/foreach}
                    </div>
                </fieldset>
            </div>
        </section>

        <section class="a4-section" aria-labelledby="nl-se-preview">
            <h2 class="a4-h2" id="nl-se-preview">{'Preview'|i18n( 'cjw_newsletter/importexport' )} <span class="a4-muted">({'the newest first'|i18n( 'cjw_newsletter/importexport' )})</span> <span class="a4-badge is-info">{'%count rows'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $count ) )}</span></h2>
            {if $preview|count}
            <div class="a4-table-wrap">
                <table class="a4-table is-compact">
                    <thead><tr>{foreach $filters.columns as $column}<th scope="col">{$column_names[$column]|wash}</th>{/foreach}</tr></thead>
                    <tbody>
                    {foreach $preview as $row}
                    <tr>{foreach $filters.columns as $column}<td>{$row[$column]|wash}</td>{/foreach}</tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            {else}
            <p class="a4-empty">{'No subscriber matches the filters.'|i18n( 'cjw_newsletter/importexport' )}</p>
            {/if}
        </section>

        <div class="a4-toolbar">
            <input class="a4-btn" type="submit" name="PreviewButton" value="{'Update the preview'|i18n( 'cjw_newsletter/importexport' )|wash}" />
            <input class="a4-btn a4-btn-primary" type="submit" name="ExportButton" value="{'Download CSV'|i18n( 'cjw_newsletter/importexport' )|wash}"{if $count|eq( 0 )} disabled="disabled"{/if} />
            <input class="a4-btn" type="submit" name="CancelButton" value="{'Back to the list'|i18n( 'cjw_newsletter/importexport' )|wash}" />
        </div>
    </form>
</div>
