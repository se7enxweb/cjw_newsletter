{*  newsletter/statistics/ab_table.tpl (cjw_newsletter 4.2.0, area statistics)

    The variants of an A/B test. test: CjwNewsletterAbTester::summary().
*}
{def $i18n = 'cjw_newsletter/statistics'}
<p>
    <span class="nl-pill {cond( eq( $test.status_code, 1 ), 'is-warn', $test.open, 'is-info', eq( $test.status_code, 9 ), 'is-muted', 'is-ok' )}">{$test.status|i18n( $i18n )}</span>
    <span class="nl-muted">{'%percent % of the list per variant, winner by %criterion rate after %hours h'|i18n( $i18n,, hash( '%percent', $test.sample_percent, '%criterion', cond( eq( $test.criterion, 'open' ), 'open'|i18n( $i18n ), 'click'|i18n( $i18n ) ), '%hours', $test.wait_hours ) )|wash}
    {if and( eq( $test.status_code, 1 ), $test.decide_at )} &middot; {'the winner is chosen at %time'|i18n( $i18n,, hash( '%time', $test.decide_at|l10n( 'shortdatetime' ) ) )|wash}{/if}
    {if $test.decided} &middot; {'chosen %time'|i18n( $i18n,, hash( '%time', $test.decided|l10n( 'shortdatetime' ) ) )|wash}{/if}</span>
</p>
<div class="nl-stat-scroll"><table class="list nl-table">
    <tr><th>{'Variant'|i18n( $i18n )}</th><th>{'Subject'|i18n( $i18n )}</th><th class="nl-num">{'Sent'|i18n( $i18n )}</th><th class="nl-num">{'Opens'|i18n( $i18n )}</th><th class="nl-num">{'Clicks'|i18n( $i18n )}</th><th class="nl-num">{'Rate'|i18n( $i18n )}</th></tr>
    {foreach $test.variants as $v sequence array( 'bglight', 'bgdark' ) as $seq}
    <tr class="{$seq} nl-stat-variant{if $v.winner} is-winner{/if}">
        <td>{$v.key|wash}{if $v.winner} <span class="nl-pill is-ok">{'winner'|i18n( $i18n )}</span>{/if}</td>
        <td class="nl-wrap">{$v.subject|wash}{if $v.own_subject} <span class="nl-muted">({"the edition's subject"|i18n( $i18n )})</span>{/if}</td>
        <td class="nl-num">{$v.sent}</td>
        <td class="nl-num">{$v.opens}</td>
        <td class="nl-num">{$v.clicks}</td>
        <td class="nl-num">{$v.rate|l10n( 'number' )} %</td>
    </tr>
    {/foreach}
</table></div>
{undef $i18n}
