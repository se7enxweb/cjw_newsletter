{*  newsletter/deliverability/test_form_part.tpl (cjw_newsletter 4.2.0, area deliverability)

    Part of the test send form ([ExtensionPointSettings] TestFormParts[]): choose a test group of the edition's list
    (or one for every list). Its addresses are added to the typed ones (CjwNewsletterTestSend::recipients()).
    Variables: node (the edition).
*}
{def $cjwnl_groups = fetch( 'newsletter', 'test_group_list', hash( 'list_contentobject_id', $node.parent.contentobject_id ) )}
{if $cjwnl_groups|count}
<label class="nl-test-group" for="nl-test-group-{$node.node_id}">{'Test group'|i18n( 'cjw_newsletter/deliverability' )}
    <select id="nl-test-group-{$node.node_id}" name="CjwNewsletterTestGroupId">
        <option value="0">{'Only the addresses above'|i18n( 'cjw_newsletter/deliverability' )}</option>
        {foreach $cjwnl_groups as $cjwnl_group}
        <option value="{$cjwnl_group.id|wash}">{$cjwnl_group.name|wash} ({$cjwnl_group.address_count})</option>
        {/foreach}
    </select>
</label>
{/if}
<span class="nl-hint">{'Test mails are marked as tests and are never counted.'|i18n( 'cjw_newsletter/deliverability' )}</span>
{undef $cjwnl_groups}
