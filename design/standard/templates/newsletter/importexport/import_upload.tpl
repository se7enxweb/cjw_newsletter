{* newsletter/import_mapping/0/(list)/<node id>: the upload of a CSV file for an import with a column mapping *}
{ezcss_require( 'newsletter_ui.css' )}
{ezcss_require( 'newsletter_importexport.css' )}
<div class="newsletter newsletter-import_mapping">
<div class="context-block nl nl-ie">
    <div class="box-header">
        <h1 class="context-title">{'CSV import with column mapping'|i18n( 'cjw_newsletter/importexport' )}: {$list_node.name|wash}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
        {if $errors|count}
        <div class="message-error" role="alert">
            <h2>{'The file was not uploaded'|i18n( 'cjw_newsletter/importexport' )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}

        <ol class="nl-ie-steps" aria-label="{'Steps'|i18n( 'cjw_newsletter/importexport' )|wash}">
            <li class="is-current">{'Upload'|i18n( 'cjw_newsletter/importexport' )}</li>
            <li>{'Map the columns'|i18n( 'cjw_newsletter/importexport' )}</li>
            <li>{'Dry run'|i18n( 'cjw_newsletter/importexport' )}</li>
            <li>{'Import'|i18n( 'cjw_newsletter/importexport' )}</li>
        </ol>

        <form enctype="multipart/form-data" method="post" action={'newsletter/import_mapping/0'|ezurl} class="nl-ie-form">
            <input type="hidden" name="ListNodeId" value="{$list_node.node_id}" />
            <div class="nl-ie-grid">
                <label class="nl-ie-wide">{'CSV file'|i18n( 'cjw_newsletter/importexport' )}
                    <input type="file" name="UploadCsvFile" accept=".csv,.txt,text/csv,text/plain" required="required" />
                </label>
                <label>{'Field delimiter'|i18n( 'cjw_newsletter/importexport' )}
                    <select name="CsvDelimiter">
                        {foreach $delimiters as $name}<option value="{$name|wash}">{cond( eq( $name, 'semicolon' ), 'Semicolon ;'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'comma' ), 'Comma ,'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'tab' ), 'Tab'|i18n( 'cjw_newsletter/importexport' ), 'Pipe |'|i18n( 'cjw_newsletter/importexport' ) )}</option>{/foreach}
                    </select>
                </label>
                <label>{'Encoding'|i18n( 'cjw_newsletter/importexport' )}
                    <select name="Encoding">
                        {foreach $encodings as $encoding}<option value="{$encoding|wash}">{$encoding|wash}</option>{/foreach}
                    </select>
                </label>
                <label class="nl-ie-check"><input type="checkbox" name="HasHeader" value="1" checked="checked" /> {'The first row holds the column names'|i18n( 'cjw_newsletter/importexport' )}</label>
                {if $mappings|count}
                <label>{'Saved mapping'|i18n( 'cjw_newsletter/importexport' )}
                    <select name="MappingId">
                        <option value="0">{'None: guess from the column names'|i18n( 'cjw_newsletter/importexport' )}</option>
                        {foreach $mappings as $mapping}<option value="{$mapping.id}">{$mapping.name|wash}{if $mapping.list_contentobject_id|eq( 0 )} ({'every list'|i18n( 'cjw_newsletter/importexport' )}){/if}</option>{/foreach}
                    </select>
                </label>
                {/if}
                <label class="nl-ie-wide">{'Consent source'|i18n( 'cjw_newsletter/importexport' )}
                    <input type="text" name="ConsentSource" maxlength="100" value="" placeholder="{'For example: sign-up form at the trade fair 2025'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                    <span class="nl-hint">{'Where and how these people agreed to get the newsletter. It is written to the consent log of every imported person, with the source "import".'|i18n( 'cjw_newsletter/importexport' )}</span>
                </label>
                <label class="nl-ie-wide">{'Note'|i18n( 'cjw_newsletter/importexport' )}
                    <input type="text" name="Note" maxlength="255" value="" />
                </label>
            </div>
            <p class="nl-hint">{'The next step shows the columns of the file: choose for each the field it fills, check the preview, make a dry run, then import. Existing subscribers are updated, never duplicated; invalid, suppressed and unsubscribed addresses are skipped and listed.'|i18n( 'cjw_newsletter/importexport' )}</p>
            <div class="controlbar nl-ie-buttons">
                <input class="defaultbutton" type="submit" name="UploadButton" value="{'Upload and map the columns'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                <input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/importexport' )|wash}" formnovalidate="formnovalidate" />
            </div>
        </form>
    </div>
</div>
</div>
