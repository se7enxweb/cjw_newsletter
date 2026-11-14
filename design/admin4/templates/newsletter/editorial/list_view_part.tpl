{* The editorial part of the list attribute view ([ExtensionPointSettings] ListViewParts[]), admin4. Same data and actions as design/standard/templates/newsletter/editorial/list_view_part.tpl. Variables: attribute, list_object. *}
{def $pool = fetch( 'newsletter', 'list_article_pool', hash( 'list_contentobject_id', $attribute.contentobject_id ) )
     $schedules = fetch( 'newsletter', 'schedule_list', hash( 'list_contentobject_id', $attribute.contentobject_id ) )}
<dl class="a4-facts is-rows">
    <div><dt>{'Approval'|i18n( 'cjw_newsletter/editorial' )}:</dt>
    <dd>{if $list_object.approval_required}{'an edition is sent only after its approval'|i18n( 'cjw_newsletter/editorial' )}{else}{'no'|i18n( 'cjw_newsletter/editorial' )}{/if}</dd></div>
    <div><dt>{'Article pool'|i18n( 'cjw_newsletter/editorial' )}:</dt>
    <dd>{$pool.label|wash}{if $pool.is_stored|not} ({'from the settings'|i18n( 'cjw_newsletter/editorial' )}){/if}</dd></div>
    <div><dt>{'Recurring sends'|i18n( 'cjw_newsletter/editorial' )}:</dt>
    <dd>{if $schedules|count}{foreach $schedules as $s}<a href={concat( 'newsletter/schedule_edit/', $s.id )|ezurl}>{$s.recurrence_text|wash}</a>{if $s.is_active|not} ({$s.status_name|wash}){/if}{delimiter}; {/delimiter}{/foreach}{else}{'none'|i18n( 'cjw_newsletter/editorial' )}{/if}</dd></div>
</dl>
{undef $pool $schedules}
