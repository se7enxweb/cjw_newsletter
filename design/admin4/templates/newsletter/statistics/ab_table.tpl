{* The variants of an A/B test, admin4. Same data and actions as design/standard/templates/newsletter/statistics/ab_table.tpl *}
{def $cjwnl_ab_i18n = 'cjw_newsletter/statistics'}
<p>
    <span class="a4-badge {cond( eq( $test.status_code, 1 ), 'is-warn', $test.open, 'is-info', eq( $test.status_code, 9 ), '', 'is-ok' )}">{$test.status|i18n( $cjwnl_ab_i18n )}</span>
    <span class="a4-meta">{'%percent % of the list per variant, winner by %criterion rate after %hours h'|i18n( $cjwnl_ab_i18n,, hash( '%percent', $test.sample_percent, '%criterion', cond( eq( $test.criterion, 'open' ), 'open'|i18n( $cjwnl_ab_i18n ), 'click'|i18n( $cjwnl_ab_i18n ) ), '%hours', $test.wait_hours ) )|wash}
    {if and( eq( $test.status_code, 1 ), $test.decide_at )} &middot; {'the winner is chosen at %time'|i18n( $cjwnl_ab_i18n,, hash( '%time', $test.decide_at|l10n( 'shortdatetime' ) ) )|wash}{/if}
    {if $test.decided} &middot; {'chosen %time'|i18n( $cjwnl_ab_i18n,, hash( '%time', $test.decided|l10n( 'shortdatetime' ) ) )|wash}{/if}</span>
</p>
<div class="a4-table-wrap"><table class="a4-table">
    <thead><tr><th scope="col">{'Variant'|i18n( $cjwnl_ab_i18n )}</th><th scope="col">{'Subject'|i18n( $cjwnl_ab_i18n )}</th><th scope="col" class="a4-num">{'Sent'|i18n( $cjwnl_ab_i18n )}</th><th scope="col" class="a4-num">{'Opens'|i18n( $cjwnl_ab_i18n )}</th><th scope="col" class="a4-num">{'Clicks'|i18n( $cjwnl_ab_i18n )}</th><th scope="col" class="a4-num">{'Rate'|i18n( $cjwnl_ab_i18n )}</th></tr></thead>
    <tbody>
    {foreach $test.variants as $v}
    <tr class="nl-stat-variant{if $v.winner} is-winner cjwnl-a4-winner{/if}">
        <td>{$v.key|wash}{if $v.winner} <span class="a4-badge is-ok">{'winner'|i18n( $cjwnl_ab_i18n )}</span>{/if}</td>
        <td>{$v.subject|wash}{if $v.own_subject} <span class="a4-meta">({"the edition's subject"|i18n( $cjwnl_ab_i18n )})</span>{/if}</td>
        <td class="a4-num">{$v.sent}</td>
        <td class="a4-num">{$v.opens}</td>
        <td class="a4-num">{$v.clicks}</td>
        <td class="a4-num">{$v.rate|l10n( 'number' )} %</td>
    </tr>
    {/foreach}
    </tbody>
</table></div>
{undef $cjwnl_ab_i18n}
