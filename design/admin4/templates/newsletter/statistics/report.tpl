{* The report of one send, admin4: sent, delivered, bounced, opens, clicks per link, unsubscribes, opens and clicks over time, A/B test.
   Same data and actions as design/standard/templates/newsletter/statistics/report.tpl. report: CjwNewsletterStatisticsReport::send(). *}
{def $i18n = 'cjw_newsletter/statistics'
     $r = $report
     $modes = hash( 0, 'Off'|i18n( 'cjw_newsletter/statistics' ), 1, 'Anonymous totals'|i18n( 'cjw_newsletter/statistics' ), 2, 'Per person with consent'|i18n( 'cjw_newsletter/statistics' ) )}
<div class="a4-page">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Statistics'|i18n( $i18n )}: {if $r.edition.name}{$r.edition.name|wash}{else}#{$r.id}{/if}</h1>
        </div>
    </div>

    <div class="a4-card">
        <dl class="a4-facts">
            {if $r.list.name}<div><dt>{'List'|i18n( $i18n )}</dt><dd>{$r.list.name|wash}</dd></div>{/if}
            <div><dt>{'Created'|i18n( $i18n )}</dt><dd>{$r.created|l10n( 'shortdatetime' )}</dd></div>
            {if $r.started}<div><dt>{'Sending started'|i18n( $i18n )}</dt><dd>{$r.started|l10n( 'shortdatetime' )}</dd></div>{/if}
            {if $r.finished}<div><dt>{'Finished'|i18n( $i18n )}</dt><dd>{$r.finished|l10n( 'shortdatetime' )}</dd></div>{/if}
        </dl>
        <div class="a4-badges cjwnl-a4-gap">
            <span class="a4-badge {cond( eq( $r.tracking_mode, 2 ), 'is-ok', eq( $r.tracking_mode, 1 ), 'is-info', '' )}">{'Tracking'|i18n( $i18n )}: {$modes[$r.tracking_mode]}</span>
            {if and( $r.tracking_mode|gt( 0 ), $tracking_enabled|not )}<span class="a4-badge is-warn">{'switched off for the site'|i18n( $i18n )}</span>{/if}
        </div>
    </div>

    <ul class="a4-figures cjwnl-a4-gap">
        <li class="a4-figure"><span>{'Sent'|i18n( $i18n )}</span><strong>{$r.sent}</strong><span>{'of %count mails'|i18n( $i18n,, hash( '%count', $r.items ) )}{if $r.waiting}, {'%count waiting'|i18n( $i18n,, hash( '%count', $r.waiting ) )}{/if}</span></li>
        <li class="a4-figure"><span>{'Delivered'|i18n( $i18n )}</span><strong>{$r.delivered}</strong><span>{'%count not sent'|i18n( $i18n,, hash( '%count', $r.not_sent ) )}</span></li>
        <li class="a4-figure{if $r.bounced} is-bad{/if}"><span>{'Bounced'|i18n( $i18n )}</span><strong{if $r.bounced} class="nl-danger"{/if}>{$r.bounced}</strong><span>{$r.bounce_rate|l10n( 'number' )} %</span></li>
        <li class="a4-figure"><span>{'Opens'|i18n( $i18n )}</span><strong>{$r.opens}</strong><span>{'%count unique'|i18n( $i18n,, hash( '%count', $r.unique_opens ) )} &middot; {$r.open_rate|l10n( 'number' )} %</span></li>
        <li class="a4-figure"><span>{'Clicks'|i18n( $i18n )}</span><strong>{$r.clicks}</strong><span>{'%count unique'|i18n( $i18n,, hash( '%count', $r.unique_clicks ) )} &middot; {$r.click_rate|l10n( 'number' )} %</span></li>
        <li class="a4-figure"><span>{'Unsubscribes'|i18n( $i18n )}</span><strong>{$r.unsubscribes}</strong><span>{$r.unsubscribe_rate|l10n( 'number' )} %</span></li>
    </ul>

    <p class="a4-notice is-info" role="status">
        {if eq( $r.tracking_mode, 2 )}{'Unique opens and clicks and the rates count only the %count people who agreed to the newsletter statistics; everybody else is in the totals of opens and clicks, without a name.'|i18n( $i18n,, hash( '%count', $r.counted_people ) )|wash}
        {elseif eq( $r.tracking_mode, 1 )}{'This send counts anonymous totals only: opens and clicks without a person, so there are no unique numbers.'|i18n( $i18n )}
        {else}{'This send was not tracked. Sent, delivered, bounced and unsubscribes come from the mail queue and the list.'|i18n( $i18n )}{/if}
    </p>

    <section class="a4-section">
        <h2 class="a4-h2">{'Opens and clicks over time'|i18n( $i18n )}</h2>
        {include uri='design:newsletter/statistics/chart.tpl' rows=$r.days label='day'}
    </section>

    <section class="a4-section">
        <h2 class="a4-h2">{'Clicks per link'|i18n( $i18n )}</h2>
        {if $r.links|count}
        <div class="a4-table-wrap"><table class="a4-table">
            <thead><tr><th scope="col">{'Link'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Clicks'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Unique'|i18n( $i18n )}</th><th scope="col">{'Share'|i18n( $i18n )}</th></tr></thead>
            <tbody>
            {foreach $r.links as $link}
            <tr>
                <td>{if $link.object}{if $link.object.node_id}<a href={concat( 'content/view/full/', $link.object.node_id )|ezurl}>{$link.object.name|wash}</a>{else}{$link.object.name|wash}{/if}<br />{/if}<span class="a4-meta cjwnl-a4-url">{$link.url|wash}</span></td>
                <td class="a4-num">{$link.clicks}</td>
                <td class="a4-num">{$link.unique_clicks}</td>
                <td><span class="cjwnl-a4-share"><span class="a4-meter a4-pct-{$link.share|int}" aria-hidden="true"><span></span></span> {$link.share|l10n( 'number' )} %</span></td>
            </tr>
            {/foreach}
            </tbody>
        </table></div>
        {else}
        <p class="a4-empty">{'No link of this send is tracked.'|i18n( $i18n )}</p>
        {/if}
    </section>

    {if $r.ab_test}
    <section class="a4-section">
        <h2 class="a4-h2">{'A/B subject test'|i18n( $i18n )}</h2>
        {include uri='design:newsletter/statistics/ab_table.tpl' test=$r.ab_test}
        <div class="a4-actions"><a class="a4-btn" href={concat( 'newsletter/ab_test/', $r.id )|ezurl}>{'Open the A/B test'|i18n( $i18n )}</a></div>
    </section>
    {/if}

    <div class="a4-toolbar is-bottom">
        <a class="a4-btn" href={concat( 'newsletter/statistics_export/', $r.id )|ezurl}>{'Export the send (CSV)'|i18n( $i18n )}</a>
        <a class="a4-btn" href={concat( 'newsletter/statistics_export/', $r.id, '/(type)/links' )|ezurl}>{'Export the links (CSV)'|i18n( $i18n )}</a>
        <a class="a4-btn" href={concat( 'newsletter/statistics_export/', $r.id, '/(type)/days' )|ezurl}>{'Export the days (CSV)'|i18n( $i18n )}</a>
        <a class="a4-btn" href={'newsletter/report'|ezurl}>{'All sends'|i18n( $i18n )}</a>
        {if $r.edition.node_id}<a class="a4-btn" href={concat( 'content/view/full/', $r.edition.node_id )|ezurl}>{'The edition'|i18n( $i18n )}</a>{/if}
    </div>
</div>
{undef $i18n $r $modes}
