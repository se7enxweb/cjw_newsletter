{* The upload of a CSV file for an import with a column mapping, admin4: same data and actions as design/standard/templates/newsletter/importexport/import_upload.tpl *}
<div class="a4-page newsletter newsletter-import_mapping">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'CSV import with column mapping'|i18n( 'cjw_newsletter/importexport' )}: {$list_node.name|wash}</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    {if $errors|count}
    <div class="a4-notice is-bad" role="alert">
        <p class="a4-notice-title">{'The file was not uploaded'|i18n( 'cjw_newsletter/importexport' )}</p>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}

    <ol class="a4-steps" aria-label="{'Steps'|i18n( 'cjw_newsletter/importexport' )|wash}">
        <li class="is-current" aria-current="step">{'Upload'|i18n( 'cjw_newsletter/importexport' )}</li>
        <li>{'Map the columns'|i18n( 'cjw_newsletter/importexport' )}</li>
        <li>{'Dry run'|i18n( 'cjw_newsletter/importexport' )}</li>
        <li>{'Import'|i18n( 'cjw_newsletter/importexport' )}</li>
    </ol>

    <form enctype="multipart/form-data" method="post" action={'newsletter/import_mapping/0'|ezurl}>
        <input type="hidden" name="ListNodeId" value="{$list_node.node_id}" />
        <div class="a4-card">
            <div class="a4-card-body a4-form">
                <div class="a4-field a4-field-wide">
                    <label for="nl-ie-file">{'CSV file'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <input id="nl-ie-file" type="file" name="UploadCsvFile" accept=".csv,.txt,text/csv,text/plain" required="required" />
                </div>
                <div class="a4-field">
                    <label for="nl-ie-delimiter">{'Field delimiter'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <select id="nl-ie-delimiter" name="CsvDelimiter">
                        {foreach $delimiters as $name}<option value="{$name|wash}">{cond( eq( $name, 'semicolon' ), 'Semicolon ;'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'comma' ), 'Comma ,'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'tab' ), 'Tab'|i18n( 'cjw_newsletter/importexport' ), 'Pipe |'|i18n( 'cjw_newsletter/importexport' ) )}</option>{/foreach}
                    </select>
                </div>
                <div class="a4-field">
                    <label for="nl-ie-encoding">{'Encoding'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <select id="nl-ie-encoding" name="Encoding">
                        {foreach $encodings as $encoding}<option value="{$encoding|wash}">{$encoding|wash}</option>{/foreach}
                    </select>
                </div>
                <div class="a4-field">
                    <label class="a4-check"><input type="checkbox" name="HasHeader" value="1" checked="checked" /> {'The first row holds the column names'|i18n( 'cjw_newsletter/importexport' )}</label>
                </div>
                {if $mappings|count}
                <div class="a4-field">
                    <label for="nl-ie-saved-mapping">{'Saved mapping'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <select id="nl-ie-saved-mapping" name="MappingId">
                        <option value="0">{'None: guess from the column names'|i18n( 'cjw_newsletter/importexport' )}</option>
                        {foreach $mappings as $mapping}<option value="{$mapping.id}">{$mapping.name|wash}{if $mapping.list_contentobject_id|eq( 0 )} ({'every list'|i18n( 'cjw_newsletter/importexport' )}){/if}</option>{/foreach}
                    </select>
                </div>
                {/if}
                <div class="a4-field a4-field-wide">
                    <label for="nl-ie-consent">{'Consent source'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <input id="nl-ie-consent" type="text" name="ConsentSource" maxlength="100" value="" placeholder="{'For example: sign-up form at the trade fair 2025'|i18n( 'cjw_newsletter/importexport' )|wash}" aria-describedby="nl-ie-consent-help" />
                    <span class="a4-help" id="nl-ie-consent-help">{'Where and how these people agreed to get the newsletter. It is written to the consent log of every imported person, with the source "import".'|i18n( 'cjw_newsletter/importexport' )}</span>
                </div>
                <div class="a4-field a4-field-wide">
                    <label for="nl-ie-note">{'Note'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <input id="nl-ie-note" type="text" name="Note" maxlength="255" value="" />
                </div>
            </div>
            <div class="a4-card-foot">
                <input class="a4-btn a4-btn-primary" type="submit" name="UploadButton" value="{'Upload and map the columns'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                <input class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/importexport' )|wash}" formnovalidate="formnovalidate" />
            </div>
        </div>
        <p class="a4-help">{'The next step shows the columns of the file: choose for each the field it fills, check the preview, make a dry run, then import. Existing subscribers are updated, never duplicated; invalid, suppressed and unsubscribed addresses are skipped and listed.'|i18n( 'cjw_newsletter/importexport' )}</p>
    </form>
</div>
