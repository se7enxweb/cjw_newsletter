{*  newsletter/statistics/article_box.tpl (cjw_newsletter 4.2.0, area statistics)

    The box "Newsletter statistics" of a content object: in which editions it went out, to how many, and how often its
    links were clicked. Totals only. object_id
*}
{if ne( ezini( 'StatisticsSettings', 'ArticleStatsBox', 'cjw_newsletter.ini' ), 'disabled' )}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_statistics.css' ) )}
{def $i18n = 'cjw_newsletter/statistics'
     $a = fetch( 'newsletter', 'article_statistics', hash( 'contentobject_id', $object_id ) )}
<section class="newsletter nl-stat-box" aria-labelledby="nl-stat-box-{$object_id}">
    <h2 id="nl-stat-box-{$object_id}">{'Newsletter statistics'|i18n( $i18n )}</h2>
    {if $a.editions|count}
    <ul class="nl-stats">
        <li><strong>{$a.editions|count}</strong><span class="nl-muted">{'editions'|i18n( $i18n )}</span></li>
        <li><strong>{$a.sends}</strong><span class="nl-muted">{'sends'|i18n( $i18n )}</span></li>
        <li><strong>{$a.sent}</strong><span class="nl-muted">{'mails sent'|i18n( $i18n )}</span></li>
        <li><strong>{$a.clicks}</strong><span class="nl-muted">{'clicks on its links'|i18n( $i18n )}</span></li>
    </ul>
    <div class="nl-stat-scroll"><table class="list nl-table">
        <tr><th>{'Edition'|i18n( $i18n )}</th><th>{'List'|i18n( $i18n )}</th><th>{'Created'|i18n( $i18n )}</th><th class="nl-num">{'Sent'|i18n( $i18n )}</th><th class="nl-num">{'Clicks'|i18n( $i18n )}</th><th class="tight"></th></tr>
        {foreach $a.editions as $e}
        {foreach $e.sends as $s sequence array( 'bglight', 'bgdark' ) as $seq}
        <tr class="{$seq}">
            <td class="nl-wrap">{if $e.node_id}<a href={concat( 'content/view/full/', $e.node_id )|ezurl}>{$e.name|wash}</a>{else}{$e.name|wash}{/if}</td>
            <td class="nl-wrap">{$s.list.name|wash}</td>
            <td>{$s.created|l10n( 'shortdate' )}</td>
            <td class="nl-num">{$s.sent}</td>
            <td class="nl-num">{if $s.tracking_mode}{$s.article_clicks}{else}<span class="nl-muted" title="{'not tracked'|i18n( $i18n )|wash}">&ndash;</span>{/if}</td>
            <td><a href={concat( 'newsletter/report/', $s.id )|ezurl}>{'Report'|i18n( $i18n )}</a></td>
        </tr>
        {/foreach}
        {if $e.sends|count|not}
        <tr><td class="nl-wrap">{$e.name|wash}</td><td colspan="5" class="nl-muted">{'not sent yet'|i18n( $i18n )}</td></tr>
        {/if}
        {/foreach}
    </table></div>
    {else}
    <p class="nl-muted">{'This content has not gone out in a newsletter yet.'|i18n( $i18n )}</p>
    {/if}
</section>
{undef $i18n $a}
{/if}
