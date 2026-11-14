{* The box "Newsletter statistics" of a content object, admin4. Same data and actions as design/standard/templates/newsletter/statistics/article_box.tpl *}
{if ne( ezini( 'StatisticsSettings', 'ArticleStatsBox', 'cjw_newsletter.ini' ), 'disabled' )}
{def $i18n = 'cjw_newsletter/statistics'
     $a = fetch( 'newsletter', 'article_statistics', hash( 'contentobject_id', $object_id ) )}
<section class="a4-card cjwnl-a4-gap" aria-labelledby="nl-stat-box-{$object_id}">
    <h2 class="a4-h2" id="nl-stat-box-{$object_id}">{'Newsletter statistics'|i18n( $i18n )}</h2>
    <div class="a4-card-body">
    {if $a.editions|count}
    <ul class="a4-figures">
        <li class="a4-figure"><strong>{$a.editions|count}</strong><span>{'editions'|i18n( $i18n )}</span></li>
        <li class="a4-figure"><strong>{$a.sends}</strong><span>{'sends'|i18n( $i18n )}</span></li>
        <li class="a4-figure"><strong>{$a.sent}</strong><span>{'mails sent'|i18n( $i18n )}</span></li>
        <li class="a4-figure"><strong>{$a.clicks}</strong><span>{'clicks on its links'|i18n( $i18n )}</span></li>
    </ul>
    <div class="a4-table-wrap"><table class="a4-table">
        <thead><tr><th scope="col">{'Edition'|i18n( $i18n )}</th><th scope="col">{'List'|i18n( $i18n )}</th><th scope="col">{'Created'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Sent'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Clicks'|i18n( $i18n )}</th><th scope="col"></th></tr></thead>
        <tbody>
        {foreach $a.editions as $e}
        {foreach $e.sends as $s}
        <tr>
            <td>{if $e.node_id}<a href={concat( 'content/view/full/', $e.node_id )|ezurl}>{$e.name|wash}</a>{else}{$e.name|wash}{/if}</td>
            <td>{$s.list.name|wash}</td>
            <td>{$s.created|l10n( 'shortdate' )}</td>
            <td class="a4-num">{$s.sent}</td>
            <td class="a4-num">{if $s.tracking_mode}{$s.article_clicks}{else}<span class="a4-meta" title="{'not tracked'|i18n( $i18n )|wash}">&ndash;</span>{/if}</td>
            <td><a href={concat( 'newsletter/report/', $s.id )|ezurl}>{'Report'|i18n( $i18n )}</a></td>
        </tr>
        {/foreach}
        {if $e.sends|count|not}
        <tr><td>{$e.name|wash}</td><td colspan="5" class="a4-meta">{'not sent yet'|i18n( $i18n )}</td></tr>
        {/if}
        {/foreach}
        </tbody>
    </table></div>
    {else}
    <p class="a4-empty">{'This content has not gone out in a newsletter yet.'|i18n( $i18n )}</p>
    {/if}
    </div>
</section>
{undef $i18n $a}
{/if}
