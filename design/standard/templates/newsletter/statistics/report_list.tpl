{*  newsletter/statistics/report_list.tpl (cjw_newsletter 4.2.0, area statistics)

    Every send with its numbers, newest first; the trend of the last weeks above. Totals only.
    sends (CjwNewsletterStatisticsReport::sends()), send_count, limit, view_parameters, dashboard
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_statistics.css' ) )}
{def $i18n = 'cjw_newsletter/statistics'}
<div class="newsletter newsletter-report_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Newsletter statistics'|i18n( $i18n )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {include uri='design:newsletter/statistics/summary.tpl' s=$dashboard}

        <section>
            <h2>{'Sends'|i18n( $i18n )}</h2>
            {if $sends|count}
            <div class="nl-stat-scroll"><table class="list nl-table">
                <tr><th>{'Edition'|i18n( $i18n )}</th><th>{'Created'|i18n( $i18n )}</th><th>{'Tracking'|i18n( $i18n )}</th><th class="nl-num">{'Sent'|i18n( $i18n )}</th><th class="nl-num">{'Bounced'|i18n( $i18n )}</th><th class="nl-num">{'Opens'|i18n( $i18n )}</th><th class="nl-num">{'Clicks'|i18n( $i18n )}</th><th class="nl-num">{'Unsubscribes'|i18n( $i18n )}</th></tr>
                {foreach $sends as $r sequence array( 'bglight', 'bgdark' ) as $seq}
                <tr class="{$seq}">
                    <td class="nl-wrap"><a href={concat( 'newsletter/report/', $r.id )|ezurl}>{if $r.edition.name}{$r.edition.name|wash}{else}#{$r.id}{/if}</a>{if $r.list.name}<br /><span class="nl-muted">{$r.list.name|wash}</span>{/if}{if $r.ab_test} <span class="nl-pill is-info">A/B</span>{/if}</td>
                    <td>{$r.created|l10n( 'shortdatetime' )}</td>
                    <td>{cond( eq( $r.tracking_mode, 2 ), 'per person'|i18n( $i18n ), eq( $r.tracking_mode, 1 ), 'anonymous'|i18n( $i18n ), 'off'|i18n( $i18n ) )}</td>
                    <td class="nl-num">{$r.sent}</td>
                    <td class="nl-num">{$r.bounced}</td>
                    <td class="nl-num">{$r.opens}{if $r.unique_opens} <span class="nl-muted">({$r.unique_opens})</span>{/if}</td>
                    <td class="nl-num">{$r.clicks}{if $r.unique_clicks} <span class="nl-muted">({$r.unique_clicks})</span>{/if}</td>
                    <td class="nl-num">{$r.unsubscribes}</td>
                </tr>
                {/foreach}
            </table></div>
            <p class="nl-hint">{'In brackets: unique, counted only for the people who agreed to the newsletter statistics.'|i18n( $i18n )}</p>
            <div class="nl-pager">
                {include name='Navigator' uri='design:navigator/google.tpl' page_uri='newsletter/report' item_count=$send_count view_parameters=$view_parameters item_limit=$limit}
            </div>
            {else}
            <div class="nl-empty"><p><strong>{'Nothing was sent yet.'|i18n( $i18n )}</strong></p></div>
            {/if}
        </section>

        <div class="nl-links">
            <a class="button" href={'newsletter/statistics_export'|ezurl}>{'Export every send (CSV)'|i18n( $i18n )}</a>
            <a class="button" href={'newsletter/statistics_export/0/(type)/days'|ezurl}>{'Export the days (CSV)'|i18n( $i18n )}</a>
            <a class="button" href={'newsletter/index'|ezurl}>{'Dashboard'|i18n( $i18n )}</a>
        </div>
    </div>
</div>
</div>
{undef $i18n}
