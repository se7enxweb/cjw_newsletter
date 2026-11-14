{* The newsletter list attribute in the object edit form, admin4. Same data, POST names and extension points as
   design/standard/templates/content/datatype/edit/cjwnewsletterlist.tpl; the markup is admin4's form components
   (a4-field, a4-check, a4-table, a4-help) instead of hr, table.list and halfbox fields. *}
{if is_unset( $attribute_base )}
  {def $attribute_base='ContentObjectAttribute'}
{/if}

{def $cjwnl_list_i18n = 'cjw_newsletter/datatype/cjwnewsletterlist'
     $cjwnl_list_id = concat( 'cjwnl-list-', $attribute.id )
     $list_object = $attribute.content
     $available_siteaccess_list = $list_object.available_siteaccess_list
     $available_skin_array = $list_object.available_skin_array
     $available_output_format_list = $attribute.class_content['available_output_format_array']
     $selected_sitaccess_array = $list_object['siteaccess_array']
     $main_siteaccess = $list_object['main_siteaccess']
     $output_format_array = $list_object['output_format_array']
     $email_sender = $list_object['email_sender']
     $email_receiver_test = $list_object['email_receiver_test']
     $skin_name = $list_object['skin_name']
     $auto_approve_registered_user = $list_object['auto_approve_registered_user']
     $personalize_content = $list_object['personalize_content']}

{* the defaults of the standard template *}
{if $main_siteaccess|eq( '' )}{set $main_siteaccess = $available_siteaccess_list[0]}{/if}
{if $email_sender|eq( '' )}{set $email_sender = ezini( 'MailSettings', 'AdminEmail' )}{/if}
{if $email_receiver_test|eq( '' )}{set $email_receiver_test = ezini( 'MailSettings', 'AdminEmail' )}{/if}
{if $output_format_array|eq( '' )}{set $output_format_array = array( 0 )}{/if}

<div class="a4-stack">

<fieldset class="a4-field">
    <legend>{'List options'|i18n( $cjwnl_list_i18n )} <span class="a4-required-mark" aria-hidden="true">*</span></legend>
    <div class="a4-table-wrap"><table class="a4-table">
        <thead><tr>
            <th scope="col">{'Render output'|i18n( $cjwnl_list_i18n )}</th>
            <th scope="col">{'Can subscribe'|i18n( $cjwnl_list_i18n )}</th>
            <th scope="col">{'Siteaccess'|i18n( $cjwnl_list_i18n )}</th>
        </tr></thead>
        <tbody>
        {foreach $available_siteaccess_list as $sitaccess_name => $siteaccess_info}
        <tr>
            <td><input type="radio" name="{$attribute_base}_CjwNewsletterList_MainSiteaccess_{$attribute.id}" value="{$sitaccess_name|wash}"{if $main_siteaccess|eq( $sitaccess_name )} checked="checked"{/if} aria-label="{concat( 'Render output'|i18n( $cjwnl_list_i18n ), ': ', $sitaccess_name )|wash}" /></td>
            <td><input type="checkbox" name="{$attribute_base}_CjwNewsletterList_SiteaccessArray_{$attribute.id}[]" value="{$sitaccess_name|wash}"{if $selected_sitaccess_array|contains( $sitaccess_name )} checked="checked"{/if} aria-label="{concat( 'Can subscribe'|i18n( $cjwnl_list_i18n ), ': ', $sitaccess_name )|wash}" /></td>
            <td><strong>{$sitaccess_name|wash}</strong> <span class="a4-muted">{$siteaccess_info.locale|wash} &middot; {$siteaccess_info.site_url|wash}</span></td>
        </tr>
        {/foreach}
        </tbody>
    </table></div>
</fieldset>

<fieldset class="a4-field">
    <legend>{'Available newsletter output formats'|i18n( $cjwnl_list_i18n )}</legend>
    {foreach $available_output_format_list as $output_format_id => $output_format_name}
    <label class="a4-check"><input type="checkbox" name="{$attribute_base}_CjwNewsletterList_OutputFormatArray_{$attribute.id}[]" value="{$output_format_id|wash}"{if is_set( $output_format_array[ $output_format_id ] )} checked="checked"{/if} /> <span>{$output_format_name|wash}</span></label>
    {/foreach}
</fieldset>

<fieldset class="a4-field">
    <legend>{'Automatically approve subscription after user registration?'|i18n( $cjwnl_list_i18n )}</legend>
    <div class="a4-row">
        <label class="a4-check"><input type="radio" name="{$attribute_base}_CjwNewsletterList_AutoApproveRegisterdUser_{$attribute.id}" value="0"{if $auto_approve_registered_user|not} checked="checked"{/if} /> <span>{'no'|i18n( $cjwnl_list_i18n )}</span></label>
        <label class="a4-check"><input type="radio" name="{$attribute_base}_CjwNewsletterList_AutoApproveRegisterdUser_{$attribute.id}" value="1"{if $auto_approve_registered_user} checked="checked"{/if} /> <span>{'yes'|i18n( $cjwnl_list_i18n )}</span></label>
    </div>
</fieldset>

<div class="a4-grid">
    <div class="a4-field">
        <label for="{$cjwnl_list_id}-sender">{'Newsletter sender email'|i18n( $cjwnl_list_i18n )} <span class="a4-required-mark" aria-hidden="true">*</span></label>
        <input id="{$cjwnl_list_id}-sender" type="text" name="{$attribute_base}_CjwNewsletterList_EmailSender_{$attribute.id}" value="{$email_sender|wash}" />
    </div>
    <div class="a4-field">
        <label for="{$cjwnl_list_id}-sender-name">{'Newsletter sender name'|i18n( $cjwnl_list_i18n )}</label>
        <input id="{$cjwnl_list_id}-sender-name" type="text" name="{$attribute_base}_CjwNewsletterList_EmailSenderName_{$attribute.id}" value="{$list_object['email_sender_name']|wash}" />
    </div>
    <div class="a4-field">
        <label for="{$cjwnl_list_id}-reply-to">{'Email reply-to'|i18n( $cjwnl_list_i18n )}</label>
        <input id="{$cjwnl_list_id}-reply-to" type="text" name="{$attribute_base}_CjwNewsletterList_EmailReplyTo_{$attribute.id}" value="{$list_object['email_reply_to']|wash}" />
    </div>
    <div class="a4-field">
        <label for="{$cjwnl_list_id}-return-path">{'Email return-path'|i18n( $cjwnl_list_i18n )}</label>
        <input id="{$cjwnl_list_id}-return-path" type="text" name="{$attribute_base}_CjwNewsletterList_EmailReturnPath_{$attribute.id}" value="{$list_object['email_return_path']|wash}" />
    </div>
    <div class="a4-field">
        <label for="{$cjwnl_list_id}-test">{'Newsletter default test receiver email'|i18n( $cjwnl_list_i18n )}</label>
        <input id="{$cjwnl_list_id}-test" type="text" name="{$attribute_base}_CjwNewsletterList_EmailReceiverTest_{$attribute.id}" value="{$email_receiver_test|wash}" />
    </div>
</div>

<fieldset class="a4-field">
    <legend>{'Newsletter skin name'|i18n( $cjwnl_list_i18n )}</legend>
    <div class="a4-row">
    {foreach $available_skin_array as $skin_name_2}
        <label class="a4-check"><input type="radio" name="{$attribute_base}_CjwNewsletterList_SkinName_{$attribute.id}" value="{$skin_name_2|wash}"{if or( eq( $skin_name, $skin_name_2 ), eq( $available_skin_array|count(), 1 ) )} checked="checked"{/if} /> <span>{$skin_name_2|wash}</span></label>
    {/foreach}
    </div>
</fieldset>

<fieldset class="a4-field">
    <legend>{'Personalize newsletter if data are available?'|i18n( $cjwnl_list_i18n )}</legend>
    <div class="a4-row">
        <label class="a4-check"><input type="radio" name="{$attribute_base}_CjwNewsletterList_PersonalizeContent_{$attribute.id}" value="0"{if $personalize_content|not} checked="checked"{/if} /> <span>{'no'|i18n( $cjwnl_list_i18n )}</span></label>
        <label class="a4-check"><input type="radio" name="{$attribute_base}_CjwNewsletterList_PersonalizeContent_{$attribute.id}" value="1"{if $personalize_content} checked="checked"{/if} /> <span>{'yes'|i18n( $cjwnl_list_i18n )}</span></label>
    </div>
</fieldset>

{* the parts the feature areas add to the list ([ExtensionPointSettings] ListEditParts[]); the POST names are
   {$attribute_base}_CjwNewsletterList_<Name>_{$attribute.id}, read by the area's listAttributeInput(). Each part has
   an admin4 copy at the same path. *}
{foreach ezini( 'ExtensionPointSettings', 'ListEditParts', 'cjw_newsletter.ini' ) as $cjwnl_part}
{include uri=$cjwnl_part attribute=$attribute attribute_base=$attribute_base list_object=$list_object}
{/foreach}

</div>
{undef}
