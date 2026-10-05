{*  newsletter/statistics/summary.tpl (cjw_newsletter 4.2.0, area statistics)

    Totals and the weekly trend, the privacy state. s: CjwNewsletterStatisticsReport::dashboard().
    Used by the dashboard block and the statistics page.
*}
{def $stat_summary_i18n = 'cjw_newsletter/statistics'}
<div class="nl-status">
    <div class="nl-status-facts">
        <span><strong>{$s.opens}</strong> {'opens'|i18n( $stat_summary_i18n )}</span>
        <span><strong>{$s.clicks}</strong> {'clicks'|i18n( $stat_summary_i18n )}</span>
        <span><strong>{$s.unique_opens}</strong> {'unique opens'|i18n( $stat_summary_i18n )}</span>
        <span class="nl-muted">{'last %days days'|i18n( $stat_summary_i18n,, hash( '%days', $s.trend_days ) )}</span>
    </div>
    <div>
        {if $s.enabled}<span class="nl-pill is-ok">{'Tracking on for the site'|i18n( $stat_summary_i18n )}</span>
        {else}<span class="nl-pill is-muted">{'Tracking off for the site'|i18n( $stat_summary_i18n )}</span>{/if}
        {if $s.category_registered}<span class="nl-pill is-info">{'%count people agreed'|i18n( $stat_summary_i18n,, hash( '%count', $s.consents ) )}</span>
        {else}<span class="nl-pill is-warn">{'No consent category'|i18n( $stat_summary_i18n )}</span>{/if}
    </div>
</div>
<div class="nl-cards">
    <section class="nl-card">
        <h3>{'Opens and clicks per week'|i18n( $stat_summary_i18n )}</h3>
        {include uri='design:newsletter/statistics/chart.tpl' rows=$s.weeks label='week'}
    </section>
    <section class="nl-card">
        <h3>{'Lists'|i18n( $stat_summary_i18n )}</h3>
        <ul class="nl-stats">
            <li><strong>{$s.lists[0]}</strong><span class="nl-muted">{'not tracked'|i18n( $stat_summary_i18n )}</span></li>
            <li><strong>{$s.lists[1]}</strong><span class="nl-muted">{'anonymous totals'|i18n( $stat_summary_i18n )}</span></li>
            <li><strong>{$s.lists[2]}</strong><span class="nl-muted">{'per person with consent'|i18n( $stat_summary_i18n )}</span></li>
        </ul>
        <p class="nl-hint">{'Per-person rows are kept %months months, then only the totals stay. A person who withdraws the consent or is erased is removed at once.'|i18n( $stat_summary_i18n,, hash( '%months', $s.retention_months ) )|wash}
        {if $s.last_cleanup}{'Last cleanup: %time.'|i18n( $stat_summary_i18n,, hash( '%time', $s.last_cleanup.time|l10n( 'shortdatetime' ) ) )|wash}{/if}</p>
    </section>
</div>
{undef $stat_summary_i18n}
