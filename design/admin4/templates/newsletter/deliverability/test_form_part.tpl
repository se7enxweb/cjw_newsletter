{* The deliverability part of the test send form ([ExtensionPointSettings] TestFormParts[]), admin4: choose a test group of the edition's list
   (or one for every list). Its addresses are added to the typed ones (CjwNewsletterTestSend::recipients()). Same data and
   actions as design/standard/templates/newsletter/deliverability/test_form_part.tpl. Variables: node (the edition). *}
{def $cjwnl_groups = fetch( 'newsletter', 'test_group_list', hash( 'list_contentobject_id', $node.parent.contentobject_id ) )}
{if $cjwnl_groups|count}
<label class="nl-test-group a4-inline-label" for="nl-test-group-{$node.node_id}">{'Test group'|i18n( 'cjw_newsletter/deliverability' )}
    <select class="a4-input" id="nl-test-group-{$node.node_id}" name="CjwNewsletterTestGroupId">
        <option value="0">{'Only the addresses above'|i18n( 'cjw_newsletter/deliverability' )}</option>
        {foreach $cjwnl_groups as $cjwnl_group}
        <option value="{$cjwnl_group.id|wash}">{$cjwnl_group.name|wash} ({$cjwnl_group.address_count})</option>
        {/foreach}
    </select>
</label>
{/if}
<span class="a4-help nl-hint">{'Test mails are marked as tests and are never counted.'|i18n( 'cjw_newsletter/deliverability' )}</span>
{undef $cjwnl_groups}
