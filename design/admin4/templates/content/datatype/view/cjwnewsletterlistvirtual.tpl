{* The view of a newsletter list attribute, admin4: a list of facts. Same data and actions as design/standard/templates/content/datatype/view/cjwnewsletterlistvirtual.tpl *}

{def $newsletter_list_content = $attribute.content
     $main_siteaccess_info = $newsletter_list_content.available_siteaccess_list[$newsletter_list_content.main_siteaccess]}

<dl class="a4-facts is-rows">
        <div><dt>{'Main siteaccess'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}</dt><dd>{$newsletter_list_content.main_siteaccess|wash}</dd></div>
        <div><dt>{'Main siteaccess site url'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}</dt><dd>{$main_siteaccess_info.site_url|wash}</dd></div>
        <div><dt>{'Main siteaccess locale'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}</dt><dd>{$main_siteaccess_info.locale|wash}</dd></div>
        <div><dt>{'Newsletter output formats'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}:</dt><dd>{$newsletter_list_content.output_format_array|implode( ', ')}</dd></div>
        <div><dt>{'Email sender'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}:</dt><dd>{$newsletter_list_content.email_sender|wash}</dd></div>
        <div><dt>{'Email sender name'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}:</dt><dd>{$newsletter_list_content.email_sender_name|wash}</dd></div>
        <div><dt>{'Email reply-to'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}:</dt><dd>{$newsletter_list_content.email_reply_to|wash}</dd></div>
        <div><dt>{'Email return-path'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}:</dt><dd>{$newsletter_list_content.email_return_path|wash}</dd></div>
        <div><dt>{'Email receiver test'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}:</dt><dd>{$newsletter_list_content.email_receiver_test|wash}</dd></div>
        <div><dt>{'Personalize content'|i18n('cjw_newsletter/datatype/cjwnewsletterlist')}:</dt><dd>{$newsletter_list_content.personalize_content|choose( 'No'|i18n( 'cjw_newsletter/datatype/cjwnewsletterlist' ), 'Yes'|i18n( 'cjw_newsletter/datatype/cjwnewsletterlist' ) )}</dd></div>
</dl>

{undef $newsletter_list_content $main_siteaccess_info}
