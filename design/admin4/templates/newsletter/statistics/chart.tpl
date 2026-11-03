{* Bars of opens and clicks over time, admin4, without JavaScript: the bar heights are .a4-pct-N classes. Same data and actions as design/standard/templates/newsletter/statistics/chart.tpl *}
{def $stat_chart_i18n = 'cjw_newsletter/statistics'}
{if $rows|count}
<figure class="cjwnl-a4-chart" role="img" aria-label="{'Opens and clicks over time'|i18n( $stat_chart_i18n )|wash}">
    <div class="cjwnl-a4-chart-bars">
        {foreach $rows as $row}
        {def $when = cond( eq( $label, 'week' ), $row.start, $row.date )}
        <div class="cjwnl-a4-chart-group" title="{cond( eq( $label, 'week' ), 'Week of %date'|i18n( $stat_chart_i18n,, hash( '%date', $when|l10n( 'shortdate' ) ) ), $when|l10n( 'shortdate' ) )|wash}: {'%opens opens, %clicks clicks'|i18n( $stat_chart_i18n,, hash( '%opens', $row.opens, '%clicks', $row.clicks ) )|wash}">
            <span class="cjwnl-a4-chart-bar is-open a4-pct-{$row.open_height|int}"></span>
            <span class="cjwnl-a4-chart-bar is-click a4-pct-{$row.click_height|int}"></span>
        </div>
        {undef $when}
        {/foreach}
    </div>
    <div class="cjwnl-a4-chart-axis a4-meta">
        <span>{cond( eq( $label, 'week' ), $rows[0].start, $rows[0].date )|l10n( 'shortdate' )}</span>
        <span>{cond( eq( $label, 'week' ), $rows[sub( $rows|count, 1 )].start, $rows[sub( $rows|count, 1 )].date )|l10n( 'shortdate' )}</span>
    </div>
    <ul class="cjwnl-a4-chart-legend a4-meta"><li class="is-open">{'Opens'|i18n( $stat_chart_i18n )}</li><li class="is-click">{'Clicks'|i18n( $stat_chart_i18n )}</li></ul>
</figure>
{else}
<p class="a4-empty">{'Nothing counted yet.'|i18n( $stat_chart_i18n )}</p>
{/if}
{undef $stat_chart_i18n}
