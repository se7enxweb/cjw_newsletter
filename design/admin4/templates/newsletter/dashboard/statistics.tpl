{* The statistics block of the newsletter dashboard in admin4: opens and clicks totals and the weekly trend, the last tracked
   sends, the A/B tests running.
   Same data and actions as design/standard/templates/newsletter/dashboard/statistics.tpl
   Data: summary.areas.CjwNewsletterStatisticsHooks (CjwNewsletterStatisticsReport::dashboard()). Totals only. *}
{if and( is_set( $summary.areas.CjwNewsletterStatisticsHooks ), is_set( $summary.areas.CjwNewsletterStatisticsHooks.weeks ) )}
{def $cjwnl_stat = $summary.areas.CjwNewsletterStatisticsHooks
     $i18n = 'cjw_newsletter/statistics'}
<section class="a4-section">
    <h2 class="a4-h2">{'Statistics'|i18n( $i18n )}</h2>
    {include uri='design:newsletter/statistics/summary.tpl' s=$cjwnl_stat}
    <div class="a4-grid is-wide cjwnl-a4-cards">
        <section class="a4-card a4-stack cjwnl-a4-card cjwnl-a4-wide">
            <h3 class="a4-h3">{'Last tracked sends'|i18n( $i18n )}</h3>
            {if $cjwnl_stat.recent|count}
            <div class="a4-table-wrap">
                <table class="a4-table">
                    <thead>
                        <tr><th scope="col">{'Edition'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Sent'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Opens'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Clicks'|i18n( $i18n )}</th></tr>
                    </thead>
                    <tbody>
                    {foreach $cjwnl_stat.recent as $r}
                        <tr>
                            <td><a href={concat( 'newsletter/report/', $r.id )|ezurl}>{if $r.edition.name}{$r.edition.name|wash}{else}#{$r.id}{/if}</a></td>
                            <td class="a4-num">{$r.sent}</td><td class="a4-num">{$r.opens}</td><td class="a4-num">{$r.clicks}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            {else}
            <p class="a4-empty">{'No send is tracked yet. A list chooses its tracking in the list attribute; a send can choose another in the send form.'|i18n( $i18n )}</p>
            {/if}
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/report'|ezurl}>{'All statistics'|i18n( $i18n )}</a>
                <a class="a4-btn" href={'newsletter/statistics_export'|ezurl}>{'Export (CSV)'|i18n( $i18n )}</a>
            </div>
        </section>
        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'A/B subject tests'|i18n( $i18n )}</h3>
            {if $cjwnl_stat.ab_tests|count}
            <ul class="cjwnl-a4-list">
                {foreach $cjwnl_stat.ab_tests as $t}
                <li><span class="a4-badge {cond( eq( $t.status_code, 1 ), 'is-warn', eq( $t.status_code, 2 ), 'is-ok', 'is-info' )}">{$t.status|i18n( $i18n )}</span>
                    <span><a href={concat( 'newsletter/ab_test/', $t.edition_send_id )|ezurl}>{if $t.edition.name}{$t.edition.name|wash}{else}#{$t.edition_send_id}{/if}</a>
                    {if and( eq( $t.status_code, 1 ), $t.decide_at )}<span class="a4-meta">{'winner at %time'|i18n( $i18n,, hash( '%time', $t.decide_at|l10n( 'shortdatetime' ) ) )|wash}</span>{/if}</span></li>
                {/foreach}
            </ul>
            {else}
            <p class="a4-meta">{'No A/B test is running. Start one in the send form of an edition.'|i18n( $i18n )}</p>
            {/if}
        </section>
    </div>
</section>
{undef $cjwnl_stat $i18n}
{/if}
