{* CSV import into a newsletter list in admin4: upload, format and delimiter, the imported rows, import.
   Same data and actions as design/standard/templates/newsletter/subscription_list_csvimport.tpl *}
{def $limit                    = 50
     $base_uri                 = concat( 'newsletter/subscription_list_csvimport/', $list_node.node_id, '/', $import_id )
     $items_count              = $csv_data_array|count()
     $list_output_format_array = $list_node.data_map.newsletter_list.content.output_format_array
}
{if ezpreference( 'admin_import_list_limit' )}
    {switch match=ezpreference( 'admin_import_list_limit' )}
        {case match=1}
            {set $limit=10}
        {/case}
        {case match=2}
            {set $limit=25}
        {/case}
        {case match=3}
            {set $limit=50}
        {/case}
    {/switch}
{/if}
<div class="a4-page newsletter newsletter-subscription_list_csvimport">
<form enctype="multipart/form-data" name="subscription_csvimport" method="post" action={$base_uri|ezurl}>

    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Subscription CSV import'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</h1>
        </div>
    </div>

    {if is_set($warning)}
    <div class="a4-notice is-warn" role="alert">
        <p class="a4-notice-title">{$warning|wash}</p>
    </div>
    {elseif is_set( $view_parameters.error )}
    <div class="a4-notice is-warn" role="alert">
        <p class="a4-notice-title">
        {switch match=$view_parameters.error}
            {case match='CSV_DELIM_ERROR'}
                {'Unsupported CSV delimiter. Please use one of the following:'|i18n( 'cjw_newsletter/subscription_list_csvimport' )} {$csv_supported_delims|implode( ' &nbsp; ' )}
            {/case}
        {/switch}
        </p>
    </div>
    {/if}

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    {include uri='design:parts/newsletter/job.tpl' job_id=$job_id}

    {if fetch( 'user', 'has_access_to', hash( 'module', 'newsletter', 'function', 'import_export' ) )}
    <p class="a4-help">{'This import reads the columns in a fixed order. To choose which column fills which field, with a preview, a dry run and a consent source:'|i18n( 'cjw_newsletter/importexport' )}
        <a class="a4-btn" href={concat( 'newsletter/import_mapping/0/(list)/', $list_node.node_id )|ezurl}>{'CSV import with column mapping'|i18n( 'cjw_newsletter/importexport' )}</a></p>
    {/if}

    <div class="a4-form is-wide">
        <div class="a4-field">
            {if $import_id|eq( 0 )}
            <label for="nl-csv-file">{"Upload file"|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</label>
            <div class="a4-searchrow">
                <input type="hidden" name="MAX_FILE_SIZE" value="0" /><input id="nl-csv-file" name="UploadCsvFile" type="file" />
                <button class="a4-btn" type="submit" value="{'Upload file'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}"><span class="a4-icon a4-bi-upload" aria-hidden="true"></span> {'Upload file'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</button>
            </div>
            {else}
            {if is_object( $import_object )}
            <p><a href={concat( 'newsletter/import_view/', $import_object.id )|ezurl()}>{'Import   Id'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}: {$import_object.id|wash}</a></p>
            {if $import_object.is_imported}
            <p>{'Import done'|i18n( 'cjw_newsletter/subscription_list_csvimport' )} {$import_object.imported|l10n( shortdatetime )|wash}</p>
            {/if}
            {/if}
            <p><strong>{"Csv File Uploaded"|i18n( 'cjw_newsletter/subscription_list_csvimport' )}:</strong>
            {*<input name="CsvFilePath" type="hidden" value="{$csv_file_path}" />*}{$csv_file_path|wash}</p>
            {/if}
        </div>

        <div class="a4-field">
            <label class="a4-check"><input type="checkbox" name="FirstRowIsLabel"{if $first_row_is_label|eq( true() )} checked="checked"{/if} /> <span>{'First row is label'|i18n( 'cjw_newsletter/subscription_list_csvimport')}</span></label>
        </div>
    </div>

    {def $csv_header_row = ''
         $tr_db_keys     = ''
         $tr_csv_fields  = ''}
    {foreach $csv_field_mapping_array as $db_key => $csv_field_name}
        {set $csv_header_row = concat( $csv_header_row, $csv_field_name, $csv_delimiter )
             $tr_db_keys     = concat( $tr_db_keys, '<td>', $db_key, '</td>' )
             $tr_csv_fields  = concat( $tr_csv_fields, '<td>', $csv_field_name, '</td>' )}
    {/foreach}
    <div class="a4-table-wrap">
        <table class="a4-table">
            <tbody>
            <tr>
                <th scope="row">{"Database key"|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>{$tr_db_keys}
            </tr>
            <tr>
                <th scope="row">{"Csv field"|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>{$tr_csv_fields}
            </tr>
            </tbody>
        </table>
    </div>

    {set $csv_header_row = $csv_header_row|trim( $csv_delimiter )}
<p class="a4-help">{'The expected format of the file, with example rows:'|i18n( 'extension/cjw_newsletter' )}</p>
<pre class="a4-pre">{$csv_header_row}
{for 1 to 3 as $counter}
{def $count = 0}
{foreach $csv_field_mapping_array as $key => $item}
{set $count = $count|inc()}
{if gt( $count, 1 )}{$csv_delimiter}{/if}
{if eq( $key, 'email' )}user_{$counter}@example.com
{elseif eq( $key, 'first_name' )}first_name_{$counter}
{elseif eq( $key, 'last_name' )}last_name_{$counter}
{elseif eq( $key, 'salutation' )}(1|2)
{else}{$item}_{$counter}{/if}
{/foreach}
{undef $count}
{/for}
</pre>
    {undef $csv_header_row $tr_db_keys $tr_csv_fields}

    <div class="a4-form is-wide">
        {* Output format. *}
        <div class="a4-field">
            <span class="a4-label" id="nl-csv-format">{"Output format"|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</span>
            <div role="radiogroup" aria-labelledby="nl-csv-format">
            {foreach $list_output_format_array as $outputformat_id => $outputformat_name}
            <label class="a4-inline-label"><input type="radio" name="SelectedOutputFormatArray[]" value="{$outputformat_id|wash()}" {cond( $selected_output_format_array|contains( $outputformat_id ),  'checked',  '')} /> {$outputformat_name|wash()}</label>
            {/foreach}
            </div>
        </div>

        <div class="a4-field">
            <label for="nl-csv-delimiter">{'CSV field delimiter'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</label>
            <div class="a4-searchrow">
                <input id="nl-csv-delimiter" class="a4-input is-auto cjwnl-a4-delimiter" type="text" size="1" maxlength="1" name="CsvDelimiter" value="{$csv_delimiter}" />
                <span class="a4-help">( {'supported CSV delimiters: '|i18n( 'cjw_newsletter/subscription_list_csvimport' )} {$csv_supported_delims|implode( ' &nbsp; ' )} )</span>
            </div>
        </div>

        {if $import_object.is_imported|not()}
        <div class="a4-field">
            <label for="nl-csv-note">{"Import note"|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</label>
            <textarea id="nl-csv-note" class="a4-input box" name="Note">{$note|wash()}</textarea>
        </div>
        {else}
        <div class="a4-field">
            <span class="a4-label">{"Import note"|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</span>
            <p>{$note|wash()}</p>
        </div>
        {/if}
    </div>

    <div class="a4-toolbar">
        {if $import_object.is_imported|not()}
        <button class="a4-btn a4-btn-primary" type="submit" name="Update" value="{'Update'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}" title="{'Update'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}"><span class="a4-icon a4-bi-arrow-clockwise" aria-hidden="true"></span> {'Update'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</button>
        <button class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}" title="{'Cancel subscription import.'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}">{'Cancel'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</button>
        {/if}
    </div>

    <section class="a4-section" aria-labelledby="nl-csv-items">
        <h2 class="a4-h2" id="nl-csv-items">{'Imported items'|i18n( 'cjw_newsletter/subscription_list_csvimport' )} [{$items_count}]</h2>

        <div class="a4-filterbar">
            <div class="a4-field">
                <ul class="a4-tabs">
                {switch match=$limit}
                    {case match=25}
                        <li><a href={'/user/preferences/set/admin_import_list_limit/1'|ezurl()} title="{'Show 10 items per page.'|i18n( 'design/admin/node/view/full' )}">10</a></li>
                        <li><span class="current" aria-current="true">25</span></li>
                        <li><a href={'/user/preferences/set/admin_import_list_limit/3'|ezurl()} title="{'Show 50 items per page.'|i18n( 'design/admin/node/view/full' )}">50</a></li>
                    {/case}
                    {case match=50}
                        <li><a href={'/user/preferences/set/admin_import_list_limit/1'|ezurl()} title="{'Show 10 items per page.'|i18n( 'design/admin/node/view/full' )}">10</a></li>
                        <li><a href={'/user/preferences/set/admin_import_list_limit/2'|ezurl()} title="{'Show 25 items per page.'|i18n( 'design/admin/node/view/full' )}">25</a></li>
                        <li><span class="current" aria-current="true">50</span></li>
                    {/case}
                    {case}
                        <li><span class="current" aria-current="true">10</span></li>
                        <li><a href={'/user/preferences/set/admin_import_list_limit/2'|ezurl()} title="{'Show 25 items per page.'|i18n( 'design/admin/node/view/full' )}">25</a></li>
                        <li><a href={'/user/preferences/set/admin_import_list_limit/3'|ezurl()} title="{'Show 50 items per page.'|i18n( 'design/admin/node/view/full' )}">50</a></li>
                    {/case}
                {/switch}
                </ul>
            </div>
        </div>

        {* Subscription import table. *}
        <div class="a4-table-wrap">
            <table class="a4-table">
                <thead>
                <tr>
                    <th scope="col">{'Row nr'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                    <th scope="col">{'Email'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                    <th scope="col">{'First name'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                    <th scope="col">{'Last name'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                    <th scope="col">{'Salutation'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                    <th scope="col">{'Email ok'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                    <th scope="col">{'Nl user created'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                    <th scope="col">{'Subscription created'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                    <th scope="col">{'Created / modified'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $csv_data_array as $row => $data_set max $limit offset $view_parameters.offset}
                {def $email_ok                = '-'
                     $user_created            = '-'
                     $subscription_created    = '-'
                     $subscription_object     = false()
                     $user_object             = false()
                     $user_status_old         = '-'
                     $user_status_new         = '-'
                     $subscription_status_old = '-'
                     $subscription_status_new = '-'
                     $newsletter_user_id      = 0}
                {if is_set($list_subscription_array[$row])}
                    {set $email_ok                = $list_subscription_array[$row].email_ok
                         $user_created            = $list_subscription_array[$row].user_created
                         $subscription_created    = $list_subscription_array[$row].subscription_created
                         $user_status_old         = $list_subscription_array[$row].user_status_old
                         $user_status_new         = $list_subscription_array[$row].user_status_new
                         $subscription_status_old = $list_subscription_array[$row].subscription_status_old
                         $subscription_status_new = $list_subscription_array[$row].subscription_status_new
                         $newsletter_user_id      = $list_subscription_array[$row].newsletter_user_id
                    }
                    {if is_set( $list_subscription_array[$row]['subscription_object'] )}
                        {set $subscription_object = $list_subscription_array[$row].subscription_object}
                    {/if}
                    {if is_object( $subscription_object )}
                        {set $user_object = $subscription_object.newsletter_user}
                    {/if}
                {/if}
                <tr>
                    <td>{$row|wash()}</td>
                    <td class="a4-wrap">
                        {if $newsletter_user_id|gt( 0 )}
                            <a href={concat( 'newsletter/user_view/', $newsletter_user_id )|ezurl()} title="{$newsletter_user_id|wash()}{if is_object( $user_object )} - {$user_object.name|wash()}{/if}" >{$data_set.email|wash()}</a>
                        {else}
                            {$data_set.email|wash()}
                        {/if}
                    </td>
                    <td>{$data_set.first_name|wash()}</td>
                    <td>{$data_set.last_name|wash()}</td>
                    <td title="{if is_object($user_object)}{$user_object.salutation|wash()}{$user_object.salutation_name|wash()}{/if}">
                        {$data_set.salutation|wash()}
                        {if is_object($user_object)}
                            -&gt; {$user_object.salutation|wash()}
                        {/if}
                    </td>
                    <td title="{$email_ok|wash()}">
                        {if $email_ok|eq( '1' )}
                            {'yes'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}
                        {elseif $email_ok|eq( '0' )}
                            {'no'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}
                        {else}
                            {$email_ok|wash()}
                        {/if}
                    </td>
                    <td title="{'status old: %old -new: %new'|i18n( 'cjw_newsletter/subscription_list_csvimport',, hash( '%old', $user_status_old|wash(), '%new', $user_status_new|wash() ) )}">
                        {if $user_created|eq( '1' )}
                            <strong>{'created'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</strong>
                        {elseif $user_created|eq( '2' )}
                            <strong>{'updated'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</strong>
                        {elseif $user_created|eq( '0' )}
                            <strong>{'no'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</strong>
                            {if $subscription_status_old|eq( 3 )}
                                {'Removed by user'|i18n( 'cjw_newsletter/subscription/status' )}
                            {elseif $subscription_status_old|eq( 8 )}
                                {'Blacklisted'|i18n( 'cjw_newsletter/subscription/status' )}
                            {/if}
                        {else}
                            {$user_created|wash()}
                        {/if}
                    </td>
                    <td title="{'status old: %old - new: %new'|i18n( 'cjw_newsletter/subscription_list_csvimport',, hash( '%old', $subscription_status_old|wash(), '%new', $subscription_status_new|wash() ) )}">
                        {if $subscription_created|eq( '1' )}
                            {'created'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}
                        {elseif $subscription_created|eq( '2' )}
                            <strong>{'updated'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</strong>
                        {elseif $subscription_created|eq( '0' )}
                            <strong>{'no'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</strong>
                            {if $subscription_status_old|eq( 3 )}
                                {'Removed by user'|i18n( 'cjw_newsletter/subscription/status' )}
                            {elseif $subscription_status_old|eq( 8 )}
                                {'Blacklisted'|i18n( 'cjw_newsletter/subscription/status' )}
                            {/if}
                        {else}
                            {$subscription_created|wash()}
                        {/if}
                    </td>
                    <td>
                        {if is_object($subscription_object)}{$subscription_object.modified|datetime( 'custom', "%Y%m%d-%H:%i:%s" )}{/if}
                    </td>
                </tr>
                {undef $email_ok
                       $user_created
                       $subscription_created
                       $subscription_object
                       $user_object
                       $subscription_status_old
                       $subscription_status_new
                       $user_status_old
                       $user_status_new
                       $newsletter_user_id}
                {/foreach}
                </tbody>
            </table>
        </div>

        {* Navigator. *}
        <div class="a4-listfoot">
            {include name            = 'Navigator'
                     uri             = 'design:navigator/google.tpl'
                     page_uri        = $base_uri
                     item_count      = $items_count
                     view_parameters = $view_parameters
                     item_limit      = $limit}
        </div>
    </section>

    <div class="a4-toolbar">
        {if and( is_object( $import_object ), $import_object.is_imported|not() )}
        <button class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}" title="{'Cancel subscription import.'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}">{'Cancel'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</button>
        {def $access = fetch( 'user', 'has_access_to', hash( 'module',   'newsletter',
                                                              'function', 'subscription_list_csvimport_import' ) )}
        {if $access}
            {* Access is allowed. *}<button class="a4-btn a4-btn-primary" type="submit" name="ImportButton" value="{'Import all'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}" title="{'Import all'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}">{'Import all'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</button>
        {else}
            {* Access is denied. *}<button class="a4-btn" type="button" disabled="disabled" title="{'Import all - disabled'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}">{'Import all'|i18n( 'cjw_newsletter/subscription_list_csvimport' )}</button>
        {/if}
        {/if}
    </div>
</form>
</div>
{include uri='design:parts/newsletter/script.tpl'}
