{* newsletter/import_mapping/<id>: map the columns of an uploaded CSV file, preview, dry run, import, report *}
{ezcss_require( 'newsletter_ui.css' )}
{ezcss_require( 'newsletter_importexport.css' )}
{def $base_uri = concat( 'newsletter/import_mapping/', $import.id )
     $status = $import.status}
<div class="newsletter newsletter-import_mapping">
<div class="context-block nl nl-ie">
    <div class="box-header">
        <h1 class="context-title">{'CSV import %id'|i18n( 'cjw_newsletter/importexport',, hash( '%id', $import.id ) )}: {$list_node.name|wash}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
        {if $errors|count}
        <div class="message-error" role="alert">
            <h2>{'Please check'|i18n( 'cjw_newsletter/importexport' )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}

        <ol class="nl-ie-steps" aria-label="{'Steps'|i18n( 'cjw_newsletter/importexport' )|wash}">
            <li class="is-done">{'Upload'|i18n( 'cjw_newsletter/importexport' )}</li>
            <li class="{cond( $is_done, 'is-done', $import.is_dry_run|eq( 1 ), 'is-done', 'is-current' )}">{'Map the columns'|i18n( 'cjw_newsletter/importexport' )}</li>
            <li class="{cond( $is_done, 'is-done', $import.is_dry_run|eq( 1 ), 'is-current', '' )}">{'Dry run'|i18n( 'cjw_newsletter/importexport' )}</li>
            <li class="{cond( $is_done, 'is-done', '' )}">{'Import'|i18n( 'cjw_newsletter/importexport' )}</li>
        </ol>

        {include uri='design:parts/newsletter/job.tpl' job_id=$job_id}
        {if $job_id}<p class="nl-hint"><a href={$base_uri|ezurl}>{'Reload this page when the run is finished to see the report.'|i18n( 'cjw_newsletter/importexport' )}</a></p>{/if}

        <div class="nl-status">
            <div class="nl-status-facts">
                <span><strong>{$row_count}</strong> {'rows'|i18n( 'cjw_newsletter/importexport' )}</span>
                <span><strong>{$header|count}</strong> {'columns'|i18n( 'cjw_newsletter/importexport' )}</span>
                <span>{'Consent source'|i18n( 'cjw_newsletter/importexport' )}: <strong>{$consent_source|wash}</strong></span>
                {if $saved_mapping}<span>{'Mapping'|i18n( 'cjw_newsletter/importexport' )}: <strong>{$saved_mapping.name|wash}</strong></span>{/if}
            </div>
            <div>
                {if $is_done}<span class="nl-pill is-ok">{'Imported'|i18n( 'cjw_newsletter/importexport' )} {$import.imported|l10n( 'shortdatetime' )}</span>
                {elseif eq( $status, 2 )}<span class="nl-pill is-warn">{'Running'|i18n( 'cjw_newsletter/importexport' )}</span>
                {elseif eq( $status, 9 )}<span class="nl-pill is-bad">{'Failed'|i18n( 'cjw_newsletter/importexport' )}</span>
                {elseif $import.is_dry_run|eq( 1 )}<span class="nl-pill is-info">{'Dry run done'|i18n( 'cjw_newsletter/importexport' )}</span>
                {else}<span class="nl-pill is-muted">{'Not imported yet'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}
            </div>
        </div>

        {if $is_done|not}
        <form method="post" action={$base_uri|ezurl} class="nl-ie-form">
            <section class="nl-ie-section">
                <h2>{'File and options'|i18n( 'cjw_newsletter/importexport' )}</h2>
                <div class="nl-filter">
                    <label>{'Field delimiter'|i18n( 'cjw_newsletter/importexport' )}
                        <select name="CsvDelimiter">
                            {foreach $delimiters as $name}<option value="{$name|wash}"{if eq( $name, $delimiter_name )} selected="selected"{/if}>{cond( eq( $name, 'semicolon' ), 'Semicolon ;'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'comma' ), 'Comma ,'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'tab' ), 'Tab'|i18n( 'cjw_newsletter/importexport' ), 'Pipe |'|i18n( 'cjw_newsletter/importexport' ) )}</option>{/foreach}
                        </select>
                    </label>
                    <label>{'Encoding'|i18n( 'cjw_newsletter/importexport' )}
                        <select name="Encoding">
                            {foreach $encodings as $encoding}<option value="{$encoding|wash}"{if eq( $encoding, $settings.encoding )} selected="selected"{/if}>{$encoding|wash}</option>{/foreach}
                        </select>
                    </label>
                    <label class="nl-ie-check"><input type="checkbox" name="HasHeader" value="1"{if $settings.has_header} checked="checked"{/if} /> {'First row holds the column names'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <fieldset class="nl-ie-formats">
                        <legend>{'Output formats of new subscriptions'|i18n( 'cjw_newsletter/importexport' )}</legend>
                        {foreach $output_formats as $format_id => $format_name}
                        <label class="nl-ie-check"><input type="checkbox" name="Formats[]" value="{$format_id}"{if $settings.formats|contains( $format_id )} checked="checked"{/if} /> {$format_name|wash}</label>
                        {/foreach}
                    </fieldset>
                    <label class="nl-ie-check"><input type="checkbox" name="UpdateExisting" value="1"{if $settings.update_existing} checked="checked"{/if} /> {'Update the data of existing subscribers'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <label class="nl-ie-wide">{'Consent source'|i18n( 'cjw_newsletter/importexport' )}
                        <input type="text" name="ConsentSource" maxlength="100" value="{$import.consent_source|wash}" placeholder="{'For example: sign-up form at the trade fair 2025'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                    </label>
                </div>
            </section>

            <section class="nl-ie-section">
                <h2>{'Columns'|i18n( 'cjw_newsletter/importexport' )}</h2>
                <p class="nl-muted">{'Choose for each column of the file the field it fills. One column must be the e-mail address; each field can be chosen once.'|i18n( 'cjw_newsletter/importexport' )}</p>
                <div class="nl-ie-scroll">
                <table class="list nl-table nl-ie-mapping">
                    <tr>
                        <th class="nl-num">#</th>
                        <th>{'Column'|i18n( 'cjw_newsletter/importexport' )}</th>
                        <th>{'Field'|i18n( 'cjw_newsletter/importexport' )}</th>
                        <th>{'Values in the file'|i18n( 'cjw_newsletter/importexport' )}</th>
                    </tr>
                    {foreach $header as $index => $column_name sequence array( 'bglight', 'bgdark' ) as $seq}
                    <tr class="{$seq}{if ne( $mapping[$index], 'ignore' )} is-mapped{/if}">
                        <td class="nl-num">{sum( $index, 1 )}</td>
                        <td class="nl-wrap"><label for="nl-ie-map-{$index}"><strong>{$column_name|wash}</strong></label></td>
                        <td>
                            <select id="nl-ie-map-{$index}" name="Mapping[{$index}]" data-nl-ie-map="">
                                {foreach $fields as $field => $field_name}<option value="{$field|wash}"{if eq( $mapping[$index], $field )} selected="selected"{/if}>{$field_name|wash}</option>{/foreach}
                            </select>
                        </td>
                        <td class="nl-muted nl-ie-samples">{if is_set( $samples[$index] )}{$samples[$index]|implode( ' · ' )|wash}{/if}</td>
                    </tr>
                    {/foreach}
                </table>
                </div>
            </section>

            <section class="nl-ie-section">
                <h2>{'Preview'|i18n( 'cjw_newsletter/importexport' )} <span class="nl-muted">({'the first %count of %total rows'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $preview_rows|count, '%total', $row_count ) )})</span></h2>
                {if $preview_rows|count}
                <div class="nl-ie-scroll">
                <table class="list nl-table nl-ie-preview">
                    <tr>
                        <th class="nl-num">{'Line'|i18n( 'cjw_newsletter/importexport' )}</th>
                        <th>{'Result'|i18n( 'cjw_newsletter/importexport' )}</th>
                        {foreach $header as $index => $column_name}{if ne( $mapping[$index], 'ignore' )}<th>{$fields[$mapping[$index]]|wash}</th>{/if}{/foreach}
                    </tr>
                    {foreach $preview_rows as $row sequence array( 'bglight', 'bgdark' ) as $seq}
                    <tr class="{$seq}">
                        <td class="nl-num">{$row.line}</td>
                        <td>
                            {if eq( $row.check.status, 'new' )}<span class="nl-pill is-ok">{'new'|i18n( 'cjw_newsletter/importexport' )}</span>
                            {elseif eq( $row.check.status, 'update' )}<span class="nl-pill is-info">{'update'|i18n( 'cjw_newsletter/importexport' )}</span>
                            {else}<span class="nl-pill is-warn" title="{$reasons[$row.check.reason]|wash}">{'skip'|i18n( 'cjw_newsletter/importexport' )}</span> <span class="nl-muted">{$reasons[$row.check.reason]|wash}</span>{/if}
                            {if and( is_set( $row.check.notes ), $row.check.notes|count )}{foreach $row.check.notes as $note}<span class="nl-pill is-muted" title="{$reasons[$note]|wash}">{$reasons[$note]|wash}</span>{/foreach}{/if}
                        </td>
                        {foreach $header as $index => $column_name}{if ne( $mapping[$index], 'ignore' )}<td>{if is_set( $row.cells[$index] )}{$row.cells[$index]|wash}{/if}</td>{/if}{/foreach}
                    </tr>
                    {/foreach}
                </table>
                </div>
                {else}
                <div class="nl-empty"><p>{'The file has no rows.'|i18n( 'cjw_newsletter/importexport' )}</p></div>
                {/if}
            </section>

            <div class="controlbar nl-ie-buttons">
                <input class="button" type="submit" name="PreviewButton" value="{'Update the preview'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                <input class="button" type="submit" name="DryRunButton" value="{'Dry run'|i18n( 'cjw_newsletter/importexport' )|wash}" title="{'Check every row and count, write nothing'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                <input class="defaultbutton" type="submit" name="ImportButton" value="{'Import %count rows'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $row_count ) )|wash}" data-nl-confirm-button="{'Import the rows into the list now? The consent source is written to the consent log of every person.'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                <span class="nl-spacer"></span>
                <input class="button" type="submit" name="CancelButton" value="{'Back to the list'|i18n( 'cjw_newsletter/importexport' )|wash}" />
            </div>
            <div class="nl-ie-save">
                <label for="nl-ie-mapping-name">{'Save the mapping as'|i18n( 'cjw_newsletter/importexport' )}</label>
                <input id="nl-ie-mapping-name" type="text" name="MappingName" maxlength="255" size="24" value="" />
                <input class="button" type="submit" name="SaveMappingButton" value="{'Save mapping'|i18n( 'cjw_newsletter/importexport' )|wash}" />
            </div>
        </form>
        {else}
        <div class="nl-links nl-ie-buttons">
            <a class="button" href={concat( 'newsletter/subscription_list/', $list_node.node_id )|ezurl}>{'Subscriptions of the list'|i18n( 'cjw_newsletter/importexport' )}</a>
            <a class="button" href={concat( 'newsletter/import_view/', $import.id )|ezurl}>{'Import details'|i18n( 'cjw_newsletter/importexport' )}</a>
            <a class="button" href={concat( 'newsletter/import_mapping/0/(list)/', $list_node.node_id )|ezurl}>{'New import'|i18n( 'cjw_newsletter/importexport' )}</a>
        </div>
        {/if}

        {if $result}
        {include uri='design:newsletter/importexport/import_report.tpl' result=$result reasons=$reasons}
        {/if}
    </div>
</div>
</div>
{include uri='design:parts/newsletter/script.tpl'}
{undef $base_uri $status}
