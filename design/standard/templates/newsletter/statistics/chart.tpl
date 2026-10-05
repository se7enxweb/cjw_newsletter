{*  newsletter/statistics/chart.tpl (cjw_newsletter 4.2.0, area statistics)

    Bars of opens and clicks over time, without JavaScript.
    rows   array of hash( opens, clicks, open_height, click_height, and date or start )
    label  'day' (rows have date) or 'week' (rows have start)
*}
{def $stat_chart_i18n = 'cjw_newsletter/statistics'}
{if $rows|count}
<figure class="nl-chart" role="img" aria-label="{'Opens and clicks over time'|i18n( $stat_chart_i18n )|wash}">
    <div class="nl-chart-bars">
        {foreach $rows as $row}
        {def $when = cond( eq( $label, 'week' ), $row.start, $row.date )}
        <div class="nl-chart-group" title="{cond( eq( $label, 'week' ), 'Week of %date'|i18n( $stat_chart_i18n,, hash( '%date', $when|l10n( 'shortdate' ) ) ), $when|l10n( 'shortdate' ) )|wash}: {'%opens opens, %clicks clicks'|i18n( $stat_chart_i18n,, hash( '%opens', $row.opens, '%clicks', $row.clicks ) )|wash}">
            <span class="nl-chart-bar is-open" style="height: {$row.open_height}%"></span>
            <span class="nl-chart-bar is-click" style="height: {$row.click_height}%"></span>
        </div>
        {undef $when}
        {/foreach}
    </div>
    <div class="nl-chart-axis">
        <span>{cond( eq( $label, 'week' ), $rows[0].start, $rows[0].date )|l10n( 'shortdate' )}</span>
        <span>{cond( eq( $label, 'week' ), $rows[sub( $rows|count, 1 )].start, $rows[sub( $rows|count, 1 )].date )|l10n( 'shortdate' )}</span>
    </div>
    <ul class="nl-chart-legend"><li class="is-open">{'Opens'|i18n( $stat_chart_i18n )}</li><li class="is-click">{'Clicks'|i18n( $stat_chart_i18n )}</li></ul>
</figure>
{else}
<p class="nl-muted">{'Nothing counted yet.'|i18n( $stat_chart_i18n )}</p>
{/if}
{undef $stat_chart_i18n}
