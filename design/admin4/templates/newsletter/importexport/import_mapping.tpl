{* Map the columns of an uploaded CSV file, preview, dry run, import, report, admin4: same data and actions as design/standard/templates/newsletter/importexport/import_mapping.tpl *}
{def $base_uri = concat( 'newsletter/import_mapping/', $import.id )
     $status = $import.status}
<div class="a4-page newsletter newsletter-import_mapping">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'CSV import %id'|i18n( 'cjw_newsletter/importexport',, hash( '%id', $import.id ) )}: {$list_node.name|wash}</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    {if $errors|count}
    <div class="a4-notice is-bad" role="alert">
        <p class="a4-notice-title">{'Please check'|i18n( 'cjw_newsletter/importexport' )}</p>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}

    <ol class="a4-steps" aria-label="{'Steps'|i18n( 'cjw_newsletter/importexport' )|wash}">
        <li class="is-done">{'Upload'|i18n( 'cjw_newsletter/importexport' )}</li>
        <li class="{cond( $is_done, 'is-done', $import.is_dry_run|eq( 1 ), 'is-done', 'is-current' )}">{'Map the columns'|i18n( 'cjw_newsletter/importexport' )}</li>
        <li class="{cond( $is_done, 'is-done', $import.is_dry_run|eq( 1 ), 'is-current', '' )}">{'Dry run'|i18n( 'cjw_newsletter/importexport' )}</li>
        <li class="{cond( $is_done, 'is-done', '' )}">{'Import'|i18n( 'cjw_newsletter/importexport' )}</li>
    </ol>

    {include uri='design:parts/newsletter/job.tpl' job_id=$job_id}
    {if $job_id}<p class="a4-help"><a href={$base_uri|ezurl}>{'Reload this page when the run is finished to see the report.'|i18n( 'cjw_newsletter/importexport' )}</a></p>{/if}

    <div class="a4-card">
        <dl class="a4-facts">
            <div><dt>{'rows'|i18n( 'cjw_newsletter/importexport' )}</dt><dd>{$row_count}</dd></div>
            <div><dt>{'columns'|i18n( 'cjw_newsletter/importexport' )}</dt><dd>{$header|count}</dd></div>
            <div><dt>{'Consent source'|i18n( 'cjw_newsletter/importexport' )}</dt><dd>{$consent_source|wash}</dd></div>
            {if $saved_mapping}<div><dt>{'Mapping'|i18n( 'cjw_newsletter/importexport' )}</dt><dd>{$saved_mapping.name|wash}</dd></div>{/if}
        </dl>
        <p class="a4-badges">
            {if $is_done}<span class="a4-badge is-ok">{'Imported'|i18n( 'cjw_newsletter/importexport' )} {$import.imported|l10n( 'shortdatetime' )}</span>
            {elseif eq( $status, 2 )}<span class="a4-badge is-warn">{'Running'|i18n( 'cjw_newsletter/importexport' )}</span>
            {elseif eq( $status, 9 )}<span class="a4-badge is-bad">{'Failed'|i18n( 'cjw_newsletter/importexport' )}</span>
            {elseif $import.is_dry_run|eq( 1 )}<span class="a4-badge is-info">{'Dry run done'|i18n( 'cjw_newsletter/importexport' )}</span>
            {else}<span class="a4-badge">{'Not imported yet'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}
        </p>
    </div>

    {if $is_done|not}
    <form method="post" action={$base_uri|ezurl}>
        <section class="a4-card a4-section" aria-labelledby="nl-ie-options">
            <div class="a4-card-head"><h2 class="a4-h3 a4-card-title" id="nl-ie-options">{'File and options'|i18n( 'cjw_newsletter/importexport' )}</h2></div>
            <div class="a4-card-body a4-form">
                <div class="a4-field">
                    <label for="nl-ie-delimiter">{'Field delimiter'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <select id="nl-ie-delimiter" name="CsvDelimiter">
                        {foreach $delimiters as $name}<option value="{$name|wash}"{if eq( $name, $delimiter_name )} selected="selected"{/if}>{cond( eq( $name, 'semicolon' ), 'Semicolon ;'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'comma' ), 'Comma ,'|i18n( 'cjw_newsletter/importexport' ), eq( $name, 'tab' ), 'Tab'|i18n( 'cjw_newsletter/importexport' ), 'Pipe |'|i18n( 'cjw_newsletter/importexport' ) )}</option>{/foreach}
                    </select>
                </div>
                <div class="a4-field">
                    <label for="nl-ie-encoding">{'Encoding'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <select id="nl-ie-encoding" name="Encoding">
                        {foreach $encodings as $encoding}<option value="{$encoding|wash}"{if eq( $encoding, $settings.encoding )} selected="selected"{/if}>{$encoding|wash}</option>{/foreach}
                    </select>
                </div>
                <div class="a4-field">
                    <label class="a4-check"><input type="checkbox" name="HasHeader" value="1"{if $settings.has_header} checked="checked"{/if} /> {'First row holds the column names'|i18n( 'cjw_newsletter/importexport' )}</label>
                </div>
                <fieldset class="a4-field">
                    <legend>{'Output formats of new subscriptions'|i18n( 'cjw_newsletter/importexport' )}</legend>
                    <div class="cjwnl-a4-choices">
                        {foreach $output_formats as $format_id => $format_name}
                        <label class="a4-check"><input type="checkbox" name="Formats[]" value="{$format_id}"{if $settings.formats|contains( $format_id )} checked="checked"{/if} /> {$format_name|wash}</label>
                        {/foreach}
                    </div>
                </fieldset>
                <div class="a4-field">
                    <label class="a4-check"><input type="checkbox" name="UpdateExisting" value="1"{if $settings.update_existing} checked="checked"{/if} /> {'Update the data of existing subscribers'|i18n( 'cjw_newsletter/importexport' )}</label>
                </div>
                <div class="a4-field a4-field-wide">
                    <label for="nl-ie-consent">{'Consent source'|i18n( 'cjw_newsletter/importexport' )}</label>
                    <input id="nl-ie-consent" type="text" name="ConsentSource" maxlength="100" value="{$import.consent_source|wash}" placeholder="{'For example: sign-up form at the trade fair 2025'|i18n( 'cjw_newsletter/importexport' )|wash}" />
                </div>
            </div>
        </section>

        <section class="a4-section" aria-labelledby="nl-ie-columns">
            <h2 class="a4-h2" id="nl-ie-columns">{'Columns'|i18n( 'cjw_newsletter/importexport' )}</h2>
            <p class="a4-intro">{'Choose for each column of the file the field it fills. One column must be the e-mail address; each field can be chosen once.'|i18n( 'cjw_newsletter/importexport' )}</p>
            <div class="a4-table-wrap">
                <table class="a4-table">
                    <thead>
                    <tr>
                        <th scope="col" class="a4-num">#</th>
                        <th scope="col">{'Column'|i18n( 'cjw_newsletter/importexport' )}</th>
                        <th scope="col">{'Field'|i18n( 'cjw_newsletter/importexport' )}</th>
                        <th scope="col">{'Values in the file'|i18n( 'cjw_newsletter/importexport' )}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $header as $index => $column_name}
                    <tr{if ne( $mapping[$index], 'ignore' )} class="is-mapped"{/if}>
                        <td class="a4-num">{sum( $index, 1 )}</td>
                        <td class="a4-wrap"><label for="nl-ie-map-{$index}"><strong>{$column_name|wash}</strong></label></td>
                        <td>
                            <select id="nl-ie-map-{$index}" name="Mapping[{$index}]" data-nl-ie-map="">
                                {foreach $fields as $field => $field_name}<option value="{$field|wash}"{if eq( $mapping[$index], $field )} selected="selected"{/if}>{$field_name|wash}</option>{/foreach}
                            </select>
                        </td>
                        <td class="a4-muted a4-wrap">{if is_set( $samples[$index] )}{$samples[$index]|implode( ' · ' )|wash}{/if}</td>
                    </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </section>

        <section class="a4-section" aria-labelledby="nl-ie-preview">
            <h2 class="a4-h2" id="nl-ie-preview">{'Preview'|i18n( 'cjw_newsletter/importexport' )} <span class="a4-muted">({'the first %count of %total rows'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $preview_rows|count, '%total', $row_count ) )})</span></h2>
            {if $preview_rows|count}
            <div class="a4-table-wrap">
                <table class="a4-table is-compact">
                    <thead>
                    <tr>
                        <th scope="col" class="a4-num">{'Line'|i18n( 'cjw_newsletter/importexport' )}</th>
                        <th scope="col">{'Result'|i18n( 'cjw_newsletter/importexport' )}</th>
                        {foreach $header as $index => $column_name}{if ne( $mapping[$index], 'ignore' )}<th scope="col">{$fields[$mapping[$index]]|wash}</th>{/if}{/foreach}
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $preview_rows as $row}
                    <tr>
                        <td class="a4-num">{$row.line}</td>
                        <td>
                            {if eq( $row.check.status, 'new' )}<span class="a4-badge is-ok">{'new'|i18n( 'cjw_newsletter/importexport' )}</span>
                            {elseif eq( $row.check.status, 'update' )}<span class="a4-badge is-info">{'update'|i18n( 'cjw_newsletter/importexport' )}</span>
                            {else}<span class="a4-badge is-warn" title="{$reasons[$row.check.reason]|wash}">{'skip'|i18n( 'cjw_newsletter/importexport' )}</span> <span class="a4-muted">{$reasons[$row.check.reason]|wash}</span>{/if}
                            {if and( is_set( $row.check.notes ), $row.check.notes|count )}{foreach $row.check.notes as $note}<span class="a4-badge" title="{$reasons[$note]|wash}">{$reasons[$note]|wash}</span>{/foreach}{/if}
                        </td>
                        {foreach $header as $index => $column_name}{if ne( $mapping[$index], 'ignore' )}<td>{if is_set( $row.cells[$index] )}{$row.cells[$index]|wash}{/if}</td>{/if}{/foreach}
                    </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            {else}
            <p class="a4-empty">{'The file has no rows.'|i18n( 'cjw_newsletter/importexport' )}</p>
            {/if}
        </section>

        <div class="a4-toolbar">
            <input class="a4-btn" type="submit" name="PreviewButton" value="{'Update the preview'|i18n( 'cjw_newsletter/importexport' )|wash}" />
            <input class="a4-btn" type="submit" name="DryRunButton" value="{'Dry run'|i18n( 'cjw_newsletter/importexport' )|wash}" title="{'Check every row and count, write nothing'|i18n( 'cjw_newsletter/importexport' )|wash}" />
            <input class="a4-btn a4-btn-primary" type="submit" name="ImportButton" value="{'Import %count rows'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $row_count ) )|wash}" data-nl-confirm-button="{'Import the rows into the list now? The consent source is written to the consent log of every person.'|i18n( 'cjw_newsletter/importexport' )|wash}" />
            <input class="a4-btn" type="submit" name="CancelButton" value="{'Back to the list'|i18n( 'cjw_newsletter/importexport' )|wash}" />
        </div>
        <div class="a4-card a4-section">
            <div class="a4-searchrow">
                <label for="nl-ie-mapping-name">{'Save the mapping as'|i18n( 'cjw_newsletter/importexport' )}</label>
                <input id="nl-ie-mapping-name" type="text" name="MappingName" maxlength="255" size="24" value="" />
                <input class="a4-btn" type="submit" name="SaveMappingButton" value="{'Save mapping'|i18n( 'cjw_newsletter/importexport' )|wash}" />
            </div>
        </div>
    </form>
    {else}
    <div class="a4-toolbar">
        <a class="a4-btn" href={concat( 'newsletter/subscription_list/', $list_node.node_id )|ezurl}>{'Subscriptions of the list'|i18n( 'cjw_newsletter/importexport' )}</a>
        <a class="a4-btn" href={concat( 'newsletter/import_view/', $import.id )|ezurl}>{'Import details'|i18n( 'cjw_newsletter/importexport' )}</a>
        <a class="a4-btn" href={concat( 'newsletter/import_mapping/0/(list)/', $list_node.node_id )|ezurl}>{'New import'|i18n( 'cjw_newsletter/importexport' )}</a>
    </div>
    {/if}

    {if $result}
    {include uri='design:newsletter/importexport/import_report.tpl' result=$result reasons=$reasons}
    {/if}
</div>
{include uri='design:parts/newsletter/script.tpl'}
{undef $base_uri $status}
