{* The report of the last run of a mapped import, admin4: same data and actions as design/standard/templates/newsletter/importexport/import_report.tpl.
   Parameters $result (CjwNewsletterMappedImport::run()), $reasons (reason => text) *}
{def $t = $result.totals
     $shown = 0}
<section class="a4-section" id="nl-ie-report" aria-labelledby="nl-ie-report-title">
    <h2 class="a4-h2" id="nl-ie-report-title">{if $result.dry_run}{'Report of the dry run'|i18n( 'cjw_newsletter/importexport' )} <span class="a4-badge is-info">{'nothing was written'|i18n( 'cjw_newsletter/importexport' )}</span>{else}{'Report of the import'|i18n( 'cjw_newsletter/importexport' )}{/if}</h2>
    <div class="a4-cards">
        <section class="a4-card">
            <div class="a4-card-head"><h3 class="a4-h3 a4-card-title">{'Subscribers'|i18n( 'cjw_newsletter/importexport' )}</h3></div>
            <div class="a4-card-body">
                <ul class="a4-figures">
                    <li class="a4-figure"><strong>{$t.users_created}</strong> <span>{'new'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                    <li class="a4-figure"><strong>{$t.users_updated}</strong> <span>{'updated'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                </ul>
            </div>
        </section>
        <section class="a4-card">
            <div class="a4-card-head"><h3 class="a4-h3 a4-card-title">{'Subscriptions'|i18n( 'cjw_newsletter/importexport' )}</h3></div>
            <div class="a4-card-body">
                <ul class="a4-figures">
                    <li class="a4-figure"><strong>{$t.subscriptions_created}</strong> <span>{'new'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                    <li class="a4-figure"><strong>{$t.subscriptions_updated}</strong> <span>{'approved'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                    <li class="a4-figure"><strong>{$t.unchanged}</strong> <span>{'already there'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                </ul>
            </div>
        </section>
        <section class="a4-card">
            <div class="a4-card-head"><h3 class="a4-h3 a4-card-title">{'Not imported'|i18n( 'cjw_newsletter/importexport' )}</h3></div>
            <div class="a4-card-body">
                <ul class="a4-figures">
                    <li class="a4-figure{if $t.skipped} is-attention{/if}"><strong>{$t.skipped}</strong> <span>{'skipped'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                    <li class="a4-figure{if $t.errors} is-bad{/if}"><strong{if $t.errors} class="nl-danger"{/if}>{$t.errors}</strong> <span>{'failed'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                    <li class="a4-figure"><strong>{$t.rows}</strong> <span>{'rows read'|i18n( 'cjw_newsletter/importexport' )}</span></li>
                </ul>
            </div>
        </section>
    </div>
    {if sum( $t.skipped, $t.errors )|gt( 0 )}
    <dl class="a4-facts is-rows">
        {foreach $t.reasons as $reason => $count}{if $count|gt( 0 )}<dt>{$reasons[$reason]|wash}</dt><dd>{$count}</dd>{/if}{/foreach}
    </dl>
    <h3 class="a4-h3">{'Rows that were not imported'|i18n( 'cjw_newsletter/importexport' )}</h3>
    <div class="a4-table-wrap">
        <table class="a4-table">
            <thead><tr><th scope="col" class="a4-num">{'Line'|i18n( 'cjw_newsletter/importexport' )}</th><th scope="col">{'E-mail address'|i18n( 'cjw_newsletter/importexport' )}</th><th scope="col">{'Reason'|i18n( 'cjw_newsletter/importexport' )}</th></tr></thead>
            <tbody>
            {foreach $result.rows as $row}
            {if and( or( eq( $row.action, 'skipped' ), eq( $row.action, 'failed' ) ), $shown|lt( 500 ) )}
            {set $shown = inc( $shown )}
            <tr>
                <td class="a4-num">{$row.line}</td>
                <td class="a4-wrap">{$row.email|wash}</td>
                <td><span class="a4-badge {if eq( $row.action, 'failed' )}is-bad{else}is-warn{/if}">{if is_set( $reasons[$row.reason] )}{$reasons[$row.reason]|wash}{else}{$row.reason|wash}{/if}</span></td>
            </tr>
            {/if}
            {/foreach}
            </tbody>
        </table>
    </div>
    {if $shown|ge( 500 )}<p class="a4-help">{'Only the first 500 are listed.'|i18n( 'cjw_newsletter/importexport' )}</p>{/if}
    {/if}
    {def $noted = 0}
    {foreach $result.rows as $row}{if and( is_set( $row.notes ), $row.notes|count )}{set $noted = inc( $noted )}{/if}{/foreach}
    {if $noted}
    <p class="a4-help">{'%count rows had values that were left out (a salutation, language or phone number that could not be read); the rest of these rows was imported.'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $noted ) )}</p>
    {/if}
    {undef $noted}
</section>
{undef $t $shown}
