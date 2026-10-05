{*  newsletter/dashboard/statistics.tpl (cjw_newsletter 4.2.0, area statistics)

    The dashboard block: opens and clicks totals and the weekly trend, the last tracked sends, the A/B tests running.
    Data: summary.areas.CjwNewsletterStatisticsHooks (CjwNewsletterStatisticsReport::dashboard()). Totals only.
*}
{if and( is_set( $summary.areas.CjwNewsletterStatisticsHooks ), is_set( $summary.areas.CjwNewsletterStatisticsHooks.weeks ) )}
{ezcss_require( 'newsletter_statistics.css' )}
{def $cjwnl_stat = $summary.areas.CjwNewsletterStatisticsHooks
     $i18n = 'cjw_newsletter/statistics'}
<section class="nl-area nl-area-statistics">
    <h2>{'Statistics'|i18n( $i18n )}</h2>
    {include uri='design:newsletter/statistics/summary.tpl' s=$cjwnl_stat}
    <div class="nl-cards">
        <section class="nl-card nl-card-wide">
            <h3>{'Last tracked sends'|i18n( $i18n )}</h3>
            {if $cjwnl_stat.recent|count}
            <div class="nl-stat-scroll"><table class="list nl-table">
                <tr><th>{'Edition'|i18n( $i18n )}</th><th class="nl-num">{'Sent'|i18n( $i18n )}</th><th class="nl-num">{'Opens'|i18n( $i18n )}</th><th class="nl-num">{'Clicks'|i18n( $i18n )}</th></tr>
                {foreach $cjwnl_stat.recent as $r sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td class="nl-wrap"><a href={concat( 'newsletter/report/', $r.id )|ezurl}>{if $r.edition.name}{$r.edition.name|wash}{else}#{$r.id}{/if}</a></td>
                    <td class="nl-num">{$r.sent}</td><td class="nl-num">{$r.opens}</td><td class="nl-num">{$r.clicks}</td>
                </tr>
                {/foreach}
            </table></div>
            {else}
            <p class="nl-muted">{'No send is tracked yet. A list chooses its tracking in the list attribute; a send can choose another in the send form.'|i18n( $i18n )}</p>
            {/if}
            <div class="nl-links">
                <a class="button" href={'newsletter/report'|ezurl}>{'All statistics'|i18n( $i18n )}</a>
                <a class="button" href={'newsletter/statistics_export'|ezurl}>{'Export (CSV)'|i18n( $i18n )}</a>
            </div>
        </section>
        <section class="nl-card">
            <h3>{'A/B subject tests'|i18n( $i18n )}</h3>
            {if $cjwnl_stat.ab_tests|count}
            <ul class="nl-problems">
                {foreach $cjwnl_stat.ab_tests as $t}
                <li><span class="nl-pill {cond( eq( $t.status_code, 1 ), 'is-warn', eq( $t.status_code, 2 ), 'is-ok', 'is-info' )}">{$t.status|i18n( $i18n )}</span>
                    <span><a href={concat( 'newsletter/ab_test/', $t.edition_send_id )|ezurl}>{if $t.edition.name}{$t.edition.name|wash}{else}#{$t.edition_send_id}{/if}</a>
                    {if and( eq( $t.status_code, 1 ), $t.decide_at )}<span class="nl-muted">{'winner at %time'|i18n( $i18n,, hash( '%time', $t.decide_at|l10n( 'shortdatetime' ) ) )|wash}</span>{/if}</span></li>
                {/foreach}
            </ul>
            {else}
            <p class="nl-muted">{'No A/B test is running. Start one in the send form of an edition.'|i18n( $i18n )}</p>
            {/if}
        </section>
    </div>
</section>
{undef $cjwnl_stat $i18n}
{/if}
