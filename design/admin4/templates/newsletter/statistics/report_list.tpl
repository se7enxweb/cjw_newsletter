{* Every send with its numbers, newest first, with the trend above, admin4. Same data and actions as design/standard/templates/newsletter/statistics/report_list.tpl
   Variables: sends, send_count, limit, view_parameters, dashboard *}
{def $i18n = 'cjw_newsletter/statistics'}
<div class="a4-page">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Newsletter statistics'|i18n( $i18n )}</h1>
        </div>
    </div>

    {include uri='design:newsletter/statistics/summary.tpl' s=$dashboard}

    <section class="a4-section cjwnl-a4-gap">
        <h2 class="a4-h2">{'Sends'|i18n( $i18n )}</h2>
        {if $sends|count}
        <div class="a4-table-wrap"><table class="a4-table">
            <thead><tr><th scope="col">{'Edition'|i18n( $i18n )}</th><th scope="col">{'Created'|i18n( $i18n )}</th><th scope="col">{'Tracking'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Sent'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Bounced'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Opens'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Clicks'|i18n( $i18n )}</th><th scope="col" class="a4-num">{'Unsubscribes'|i18n( $i18n )}</th></tr></thead>
            <tbody>
            {foreach $sends as $r}
            <tr>
                <td><a href={concat( 'newsletter/report/', $r.id )|ezurl}>{if $r.edition.name}{$r.edition.name|wash}{else}#{$r.id}{/if}</a>{if $r.list.name}<br /><span class="a4-meta">{$r.list.name|wash}</span>{/if}{if $r.ab_test} <span class="a4-badge is-info">A/B</span>{/if}</td>
                <td>{$r.created|l10n( 'shortdatetime' )}</td>
                <td>{cond( eq( $r.tracking_mode, 2 ), 'per person'|i18n( $i18n ), eq( $r.tracking_mode, 1 ), 'anonymous'|i18n( $i18n ), 'off'|i18n( $i18n ) )}</td>
                <td class="a4-num">{$r.sent}</td>
                <td class="a4-num">{$r.bounced}</td>
                <td class="a4-num">{$r.opens}{if $r.unique_opens} <span class="a4-meta">({$r.unique_opens})</span>{/if}</td>
                <td class="a4-num">{$r.clicks}{if $r.unique_clicks} <span class="a4-meta">({$r.unique_clicks})</span>{/if}</td>
                <td class="a4-num">{$r.unsubscribes}</td>
            </tr>
            {/foreach}
            </tbody>
        </table></div>
        <p class="a4-meta">{'In brackets: unique, counted only for the people who agreed to the newsletter statistics.'|i18n( $i18n )}</p>
        <div class="a4-pager">
            {include name='Navigator' uri='design:navigator/google.tpl' page_uri='newsletter/report' item_count=$send_count view_parameters=$view_parameters item_limit=$limit}
        </div>
        {else}
        <p class="a4-empty">{'Nothing was sent yet.'|i18n( $i18n )}</p>
        {/if}
    </section>

    <div class="a4-toolbar is-bottom">
        <a class="a4-btn" href={'newsletter/statistics_export'|ezurl}>{'Export every send (CSV)'|i18n( $i18n )}</a>
        <a class="a4-btn" href={'newsletter/statistics_export/0/(type)/days'|ezurl}>{'Export the days (CSV)'|i18n( $i18n )}</a>
        <a class="a4-btn" href={'newsletter/index'|ezurl}><span class="a4-icon a4-bi-speedometer2" aria-hidden="true"></span>{'Dashboard'|i18n( $i18n )}</a>
    </div>
</div>
{undef $i18n}
