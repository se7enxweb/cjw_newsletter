{* The test send form of a newsletter edition (addresses, parts of the feature areas, send button), admin4. Same data and actions as design/standard/templates/cjw_newsletter_edition_send_testmail.tpl. *}
{def $email_receiver_test = $newsletter_list_attribute_content.email_receiver_test}

<form action={'newsletter/send'|ezurl()} method="post" class="cjwnl-inline-form cjwnl-a4-inline-form">

    {* Testemail input*}
    <input class="a4-input" type="text" name="EmailReseiverTestInput" value="{$email_receiver_test|wash}" title="test1@example.com;test2@example.com" aria-label="{"Send Test Newsletter at"|i18n("cjw_newsletter/send")}" />

    <input type="hidden" name="TopLevelNode" value="{$node.object.main_node_id}" />
    <input type="hidden" name="ContentNodeID" value="{$node.node_id}" />
    <input type="hidden" name="ContentObjectID" value="{$node.object.id}" />
    <input type="hidden" name="mail_newsletter" value="true" />

    {* the parts the feature areas add to the test send form ([ExtensionPointSettings] TestFormParts[]) *}
    {foreach ezini( 'ExtensionPointSettings', 'TestFormParts', 'cjw_newsletter.ini' ) as $cjwnl_part}{include uri=$cjwnl_part node=$node}{/foreach}

    {* Newsletter test email button. *}
    <input type="submit" class="a4-btn a4-btn-primary" name="SendNewsletterTestButton" value="{"Send Test Newsletter"|i18n("cjw_newsletter/send")}" />

</form>
