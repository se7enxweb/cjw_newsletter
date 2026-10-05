{* The report of the last run of a mapped import: parameters $result (CjwNewsletterMappedImport::run()), $reasons (reason => text) *}
{def $t = $result.totals
     $shown = 0}
<section class="nl-ie-section nl-ie-report" id="nl-ie-report">
    <h2>{if $result.dry_run}{'Report of the dry run'|i18n( 'cjw_newsletter/importexport' )} <span class="nl-pill is-info">{'nothing was written'|i18n( 'cjw_newsletter/importexport' )}</span>{else}{'Report of the import'|i18n( 'cjw_newsletter/importexport' )}{/if}</h2>
    <div class="nl-cards">
        <section class="nl-card">
            <h3>{'Subscribers'|i18n( 'cjw_newsletter/importexport' )}</h3>
            <ul class="nl-stats">
                <li><strong>{$t.users_created}</strong><span class="nl-muted">{'new'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                <li><strong>{$t.users_updated}</strong><span class="nl-muted">{'updated'|i18n( 'cjw_newsletter/importexport' )}</span></li>
            </ul>
        </section>
        <section class="nl-card">
            <h3>{'Subscriptions'|i18n( 'cjw_newsletter/importexport' )}</h3>
            <ul class="nl-stats">
                <li><strong>{$t.subscriptions_created}</strong><span class="nl-muted">{'new'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                <li><strong>{$t.subscriptions_updated}</strong><span class="nl-muted">{'approved'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                <li><strong>{$t.unchanged}</strong><span class="nl-muted">{'already there'|i18n( 'cjw_newsletter/importexport' )}</span></li>
            </ul>
        </section>
        <section class="nl-card">
            <h3>{'Not imported'|i18n( 'cjw_newsletter/importexport' )}</h3>
            <ul class="nl-stats">
                <li><strong{if $t.skipped} class="nl-ie-warn"{/if}>{$t.skipped}</strong><span class="nl-muted">{'skipped'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                <li><strong{if $t.errors} class="nl-danger"{/if}>{$t.errors}</strong><span class="nl-muted">{'failed'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                <li><strong>{$t.rows}</strong><span class="nl-muted">{'rows read'|i18n( 'cjw_newsletter/importexport' )}</span></li>
            </ul>
        </section>
    </div>
    {if sum( $t.skipped, $t.errors )|gt( 0 )}
    <dl class="nl-kv">
        {foreach $t.reasons as $reason => $count}{if $count|gt( 0 )}<dt>{$reasons[$reason]|wash}</dt><dd>{$count}</dd>{/if}{/foreach}
    </dl>
    <h3>{'Rows that were not imported'|i18n( 'cjw_newsletter/importexport' )}</h3>
    <div class="nl-ie-scroll">
    <table class="list nl-table">
        <tr><th class="nl-num">{'Line'|i18n( 'cjw_newsletter/importexport' )}</th><th>{'E-mail address'|i18n( 'cjw_newsletter/importexport' )}</th><th>{'Reason'|i18n( 'cjw_newsletter/importexport' )}</th></tr>
        {foreach $result.rows as $row sequence array( 'bglight', 'bgdark' ) as $seq}
        {if and( or( eq( $row.action, 'skipped' ), eq( $row.action, 'failed' ) ), $shown|lt( 500 ) )}
        {set $shown = inc( $shown )}
        <tr class="{$seq}">
            <td class="nl-num">{$row.line}</td>
            <td class="nl-wrap">{$row.email|wash}</td>
            <td><span class="nl-pill {if eq( $row.action, 'failed' )}is-bad{else}is-warn{/if}">{if is_set( $reasons[$row.reason] )}{$reasons[$row.reason]|wash}{else}{$row.reason|wash}{/if}</span></td>
        </tr>
        {/if}
        {/foreach}
    </table>
    </div>
    {if $shown|ge( 500 )}<p class="nl-hint">{'Only the first 500 are listed.'|i18n( 'cjw_newsletter/importexport' )}</p>{/if}
    {/if}
    {def $noted = 0}
    {foreach $result.rows as $row}{if and( is_set( $row.notes ), $row.notes|count )}{set $noted = inc( $noted )}{/if}{/foreach}
    {if $noted}
    <p class="nl-hint">{'%count rows had values that were left out (a salutation, language or phone number that could not be read); the rest of these rows was imported.'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $noted ) )}</p>
    {/if}
    {undef $noted}
</section>
{undef $t $shown}
