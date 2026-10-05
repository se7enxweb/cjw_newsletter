{*  newsletter/statistics/report.tpl (cjw_newsletter 4.2.0, area statistics)

    The report of one send: sent, delivered, bounced, opens, clicks per link, unsubscribes, opens and clicks over
    time, and the A/B test. report: CjwNewsletterStatisticsReport::send(). Totals only.
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_statistics.css' ) )}
{def $i18n = 'cjw_newsletter/statistics'
     $r = $report
     $modes = hash( 0, 'Off'|i18n( 'cjw_newsletter/statistics' ), 1, 'Anonymous totals'|i18n( 'cjw_newsletter/statistics' ), 2, 'Per person with consent'|i18n( 'cjw_newsletter/statistics' ) )}
<div class="newsletter newsletter-report">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Statistics'|i18n( $i18n )}: {if $r.edition.name}{$r.edition.name|wash}{else}#{$r.id}{/if}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        <div class="nl-status">
            <div class="nl-status-facts">
                {if $r.list.name}<span>{'List'|i18n( $i18n )}: <strong>{$r.list.name|wash}</strong></span>{/if}
                <span>{'Created'|i18n( $i18n )}: <strong>{$r.created|l10n( 'shortdatetime' )}</strong></span>
                {if $r.started}<span>{'Sending started'|i18n( $i18n )}: <strong>{$r.started|l10n( 'shortdatetime' )}</strong></span>{/if}
                {if $r.finished}<span>{'Finished'|i18n( $i18n )}: <strong>{$r.finished|l10n( 'shortdatetime' )}</strong></span>{/if}
            </div>
            <div>
                <span class="nl-pill {cond( eq( $r.tracking_mode, 2 ), 'is-ok', eq( $r.tracking_mode, 1 ), 'is-info', 'is-muted' )}">{'Tracking'|i18n( $i18n )}: {$modes[$r.tracking_mode]}</span>
                {if and( $r.tracking_mode|gt( 0 ), $tracking_enabled|not )}<span class="nl-pill is-warn">{'switched off for the site'|i18n( $i18n )}</span>{/if}
            </div>
        </div>

        <ul class="nl-stat-kpis">
            <li><span class="nl-muted">{'Sent'|i18n( $i18n )}</span><strong>{$r.sent}</strong><span class="nl-stat-rate">{'of %count mails'|i18n( $i18n,, hash( '%count', $r.items ) )}{if $r.waiting}, {'%count waiting'|i18n( $i18n,, hash( '%count', $r.waiting ) )}{/if}</span></li>
            <li><span class="nl-muted">{'Delivered'|i18n( $i18n )}</span><strong>{$r.delivered}</strong><span class="nl-stat-rate">{'%count not sent'|i18n( $i18n,, hash( '%count', $r.not_sent ) )}</span></li>
            <li><span class="nl-muted">{'Bounced'|i18n( $i18n )}</span><strong{if $r.bounced} class="nl-danger"{/if}>{$r.bounced}</strong><span class="nl-stat-rate">{$r.bounce_rate|l10n( 'number' )} %</span></li>
            <li><span class="nl-muted">{'Opens'|i18n( $i18n )}</span><strong>{$r.opens}</strong><span class="nl-stat-rate">{'%count unique'|i18n( $i18n,, hash( '%count', $r.unique_opens ) )} &middot; {$r.open_rate|l10n( 'number' )} %</span></li>
            <li><span class="nl-muted">{'Clicks'|i18n( $i18n )}</span><strong>{$r.clicks}</strong><span class="nl-stat-rate">{'%count unique'|i18n( $i18n,, hash( '%count', $r.unique_clicks ) )} &middot; {$r.click_rate|l10n( 'number' )} %</span></li>
            <li><span class="nl-muted">{'Unsubscribes'|i18n( $i18n )}</span><strong>{$r.unsubscribes}</strong><span class="nl-stat-rate">{$r.unsubscribe_rate|l10n( 'number' )} %</span></li>
        </ul>

        <p class="nl-stat-privacy">
            {if eq( $r.tracking_mode, 2 )}{'Unique opens and clicks and the rates count only the %count people who agreed to the newsletter statistics; everybody else is in the totals of opens and clicks, without a name.'|i18n( $i18n,, hash( '%count', $r.counted_people ) )|wash}
            {elseif eq( $r.tracking_mode, 1 )}{'This send counts anonymous totals only: opens and clicks without a person, so there are no unique numbers.'|i18n( $i18n )}
            {else}{'This send was not tracked. Sent, delivered, bounced and unsubscribes come from the mail queue and the list.'|i18n( $i18n )}{/if}
        </p>

        <section>
            <h2>{'Opens and clicks over time'|i18n( $i18n )}</h2>
            {include uri='design:newsletter/statistics/chart.tpl' rows=$r.days label='day'}
        </section>

        <section>
            <h2>{'Clicks per link'|i18n( $i18n )}</h2>
            {if $r.links|count}
            <div class="nl-stat-scroll"><table class="list nl-table">
                <tr><th>{'Link'|i18n( $i18n )}</th><th class="nl-num">{'Clicks'|i18n( $i18n )}</th><th class="nl-num">{'Unique'|i18n( $i18n )}</th><th>{'Share'|i18n( $i18n )}</th></tr>
                {foreach $r.links as $link sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td class="nl-wrap">{if $link.object}{if $link.object.node_id}<a href={concat( 'content/view/full/', $link.object.node_id )|ezurl}>{$link.object.name|wash}</a>{else}{$link.object.name|wash}{/if}<br />{/if}<span class="nl-stat-url nl-muted">{$link.url|wash}</span></td>
                    <td class="nl-num">{$link.clicks}</td>
                    <td class="nl-num">{$link.unique_clicks}</td>
                    <td><span class="nl-meter" aria-hidden="true"><span style="width: {$link.share|int}%"></span></span> {$link.share|l10n( 'number' )} %</td>
                </tr>
                {/foreach}
            </table></div>
            {else}
            <p class="nl-muted">{'No link of this send is tracked.'|i18n( $i18n )}</p>
            {/if}
        </section>

        {if $r.ab_test}
        <section>
            <h2>{'A/B subject test'|i18n( $i18n )}</h2>
            {include uri='design:newsletter/statistics/ab_table.tpl' test=$r.ab_test}
            <div class="nl-links"><a class="button" href={concat( 'newsletter/ab_test/', $r.id )|ezurl}>{'Open the A/B test'|i18n( $i18n )}</a></div>
        </section>
        {/if}

        <div class="nl-links">
            <a class="button" href={concat( 'newsletter/statistics_export/', $r.id )|ezurl}>{'Export the send (CSV)'|i18n( $i18n )}</a>
            <a class="button" href={concat( 'newsletter/statistics_export/', $r.id, '/(type)/links' )|ezurl}>{'Export the links (CSV)'|i18n( $i18n )}</a>
            <a class="button" href={concat( 'newsletter/statistics_export/', $r.id, '/(type)/days' )|ezurl}>{'Export the days (CSV)'|i18n( $i18n )}</a>
            <a class="button" href={'newsletter/report'|ezurl}>{'All sends'|i18n( $i18n )}</a>
            {if $r.edition.node_id}<a class="button" href={concat( 'content/view/full/', $r.edition.node_id )|ezurl}>{'The edition'|i18n( $i18n )}</a>{/if}
        </div>
    </div>
</div>
</div>
{undef $i18n $r $modes}
