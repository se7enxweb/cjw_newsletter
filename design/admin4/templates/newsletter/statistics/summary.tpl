{* Totals, weekly trend and privacy state, admin4 (dashboard block and statistics page). Same data and actions as design/standard/templates/newsletter/statistics/summary.tpl *}
{def $stat_summary_i18n = 'cjw_newsletter/statistics'}
<ul class="a4-figures">
    <li class="a4-figure"><strong>{$s.opens}</strong><span>{'opens'|i18n( $stat_summary_i18n )}</span></li>
    <li class="a4-figure"><strong>{$s.clicks}</strong><span>{'clicks'|i18n( $stat_summary_i18n )}</span></li>
    <li class="a4-figure"><strong>{$s.unique_opens}</strong><span>{'unique opens'|i18n( $stat_summary_i18n )}</span></li>
</ul>
<p class="a4-meta">{'last %days days'|i18n( $stat_summary_i18n,, hash( '%days', $s.trend_days ) )}</p>
<div class="a4-badges">
    {if $s.enabled}<span class="a4-badge is-ok">{'Tracking on for the site'|i18n( $stat_summary_i18n )}</span>
    {else}<span class="a4-badge">{'Tracking off for the site'|i18n( $stat_summary_i18n )}</span>{/if}
    {if $s.category_registered}<span class="a4-badge is-info">{'%count people agreed'|i18n( $stat_summary_i18n,, hash( '%count', $s.consents ) )}</span>
    {else}<span class="a4-badge is-warn">{'No consent category'|i18n( $stat_summary_i18n )}</span>{/if}
</div>
<div class="a4-grid is-wide cjwnl-a4-gap">
    <section class="a4-card">
        <h3 class="a4-h3">{'Opens and clicks per week'|i18n( $stat_summary_i18n )}</h3>
        <div class="a4-card-body">{include uri='design:newsletter/statistics/chart.tpl' rows=$s.weeks label='week'}</div>
    </section>
    <section class="a4-card">
        <h3 class="a4-h3">{'Lists'|i18n( $stat_summary_i18n )}</h3>
        <div class="a4-card-body">
        <ul class="a4-figures">
            <li class="a4-figure"><strong>{$s.lists[0]}</strong><span>{'not tracked'|i18n( $stat_summary_i18n )}</span></li>
            <li class="a4-figure"><strong>{$s.lists[1]}</strong><span>{'anonymous totals'|i18n( $stat_summary_i18n )}</span></li>
            <li class="a4-figure"><strong>{$s.lists[2]}</strong><span>{'per person with consent'|i18n( $stat_summary_i18n )}</span></li>
        </ul>
        <p class="a4-meta">{'Per-person rows are kept %months months, then only the totals stay. A person who withdraws the consent or is erased is removed at once.'|i18n( $stat_summary_i18n,, hash( '%months', $s.retention_months ) )|wash}
        {if $s.last_cleanup}{'Last cleanup: %time.'|i18n( $stat_summary_i18n,, hash( '%time', $s.last_cleanup.time|l10n( 'shortdatetime' ) ) )|wash}{/if}</p>
        </div>
    </section>
</div>
{undef $stat_summary_i18n}
