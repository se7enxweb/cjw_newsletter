{* CSV export of a newsletter list's subscriptions in admin4: delimiter, preview, export.
   Same data and actions as design/standard/templates/newsletter/subscription_list_csvexport.tpl *}

{def $limit = 50
     $base_uri = concat( 'newsletter/subscription_list_csvexport/', $list_node.node_id )}

<div class="a4-page newsletter newsletter-subscription_list_csvexport">

<form enctype="multipart/form-data" name="subscription_csvexport" method="post" action={$base_uri|ezurl}>

    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{"Subscription CSV export"|i18n( 'cjw_newsletter/subscription_list_csvexport' )}</h1>
        </div>
    </div>

    {if is_set($warning)}
    <div class="a4-notice is-warn" role="alert">
        <p class="a4-notice-title">{$warning|wash}</p>
    </div>
    {/if}

    {if fetch( 'user', 'has_access_to', hash( 'module', 'newsletter', 'function', 'import_export' ) )}
    <p class="a4-help">{'To choose the columns and filter by status and date:'|i18n( 'cjw_newsletter/importexport' )}
        <a class="a4-btn" href={concat( 'newsletter/subscriber_export/', $list_node.contentobject_id )|ezurl}>{'Subscriber export'|i18n( 'cjw_newsletter/importexport' )}</a></p>
    {/if}

    {* show / set csv delimiter *}
    <div class="a4-form">
        <div class="a4-field">
            <label for="nl-csv-delimiter">{'CSV field delimiter'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}</label>
            <input id="nl-csv-delimiter" class="a4-input is-auto cjwnl-a4-delimiter" type="text" size="1" maxlength="1" name="CsvDelimiter" value="{$csv_delimiter}" />
        </div>
    </div>

    {* preview for csv import, data limited *}
    {if is_set( $str_preview_csv_data )}
    <section class="a4-section" aria-labelledby="nl-csv-preview">
        <h2 class="a4-h2" id="nl-csv-preview">{'CSV preview'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}</h2>
        <pre class="a4-pre">{$str_preview_csv_data}</pre>
    </section>
    {/if}

    <div class="a4-toolbar">
        <button class="a4-btn a4-btn-primary" type="submit" name="ExportButton" value="{'Export'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}" title="{'Export'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}"><span class="a4-icon a4-bi-download" aria-hidden="true"></span> {'Export'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}</button>
        <button class="a4-btn" type="submit" name="PreviewButton" value="{'Preview'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}" title="{'Preview'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}">{'Preview'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}</button>
        <button class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}" title="{'Cancel subscription export.'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}">{'Cancel'|i18n( 'cjw_newsletter/subscription_list_csvexport' )}</button>
    </div>
</form>
</div>
