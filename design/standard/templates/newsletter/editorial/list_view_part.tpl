{*  newsletter/editorial/list_view_part.tpl (cjw_newsletter 4.2.0, area editorial)

    The part of the list attribute view ([ExtensionPointSettings] ListViewParts[]): the approval, the article pool and
    the recurring sends of the list. Variables: attribute, list_object.
*}
{def $pool = fetch( 'newsletter', 'list_article_pool', hash( 'list_contentobject_id', $attribute.contentobject_id ) )
     $schedules = fetch( 'newsletter', 'schedule_list', hash( 'list_contentobject_id', $attribute.contentobject_id ) )}
<div class="block">
    <label>{'Approval'|i18n( 'cjw_newsletter/editorial' )}:</label>
    {if $list_object.approval_required}{'an edition is sent only after its approval'|i18n( 'cjw_newsletter/editorial' )}{else}{'no'|i18n( 'cjw_newsletter/editorial' )}{/if}
</div>
<div class="block">
    <label>{'Article pool'|i18n( 'cjw_newsletter/editorial' )}:</label>
    {$pool.label|wash}{if $pool.is_stored|not} ({'from the settings'|i18n( 'cjw_newsletter/editorial' )}){/if}
</div>
<div class="block">
    <label>{'Recurring sends'|i18n( 'cjw_newsletter/editorial' )}:</label>
    {if $schedules|count}{foreach $schedules as $s}<a href={concat( 'newsletter/schedule_edit/', $s.id )|ezurl}>{$s.recurrence_text|wash}</a>{if $s.is_active|not} ({$s.status_name|wash}){/if}{delimiter}; {/delimiter}{/foreach}{else}{'none'|i18n( 'cjw_newsletter/editorial' )}{/if}
</div>
{undef $pool $schedules}
