{*  node/view/admin_preview/cjw_newsletter_article.tpl (cjw_newsletter 4.2.0, area statistics)

    The admin preview of a newsletter article: the box "Newsletter statistics" ([StatisticsSettings] ArticleStatsBox),
    then every attribute as the admin's default preview shows it.
*}
{include uri='design:newsletter/statistics/article_box.tpl' object_id=$node.contentobject_id}

{section var=Attributes loop=$node.object.contentobject_attributes}
    <div class="block">
    {if $Attributes.item.display_info.view.grouped_input}
    <fieldset>
        <legend>{$Attributes.item.contentclass_attribute.name|wash}{if $Attributes.item.is_information_collector} <span class="collector">({'information collector'|i18n( 'design/admin/content/edit_attribute' )})</span>{/if}</legend>
        {attribute_view_gui attribute=$Attributes.item}
    </fieldset>
    {else}
        <label>{$Attributes.item.contentclass_attribute.name|wash}{if $Attributes.item.is_information_collector} <span class="collector">({'information collector'|i18n( 'design/admin/content/edit_attribute' )})</span>{/if}:</label>
        {attribute_view_gui attribute=$Attributes.item}
    {/if}
    </div>
{/section}
