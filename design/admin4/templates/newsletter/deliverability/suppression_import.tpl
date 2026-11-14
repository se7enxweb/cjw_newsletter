{* CSV into the kernel suppression list, admin4: same data and actions as design/standard/templates/newsletter/deliverability/suppression_import.tpl *}
<div class="a4-page newsletter newsletter-suppression_import">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'Import into the suppression list'|i18n( 'cjw_newsletter/deliverability' )}</h1>
        </div>
    </div>

    {if $available|not}
    <div class="a4-notice is-warn" role="alert"><p>{'The e-mail preferences of Exponential are not installed here: there is no suppression list.'|i18n( 'cjw_newsletter/deliverability' )}</p></div>
    {else}
    <p class="a4-intro">{'No optional mail of the site goes to an address on the suppression list: newsletters, notifications, offers. Only a hash of each address is stored. The addresses also go on the newsletter blacklist. The file itself is not kept.'|i18n( 'cjw_newsletter/deliverability' )}</p>
    <p class="a4-muted">{'%all addresses are suppressed now (%legal for legal requests, %bounce for bounces).'|i18n( 'cjw_newsletter/deliverability',, hash( '%all', $counts.all, '%legal', $counts.legal, '%bounce', $counts.bounce ) )|wash}</p>

    {if $error}<div class="a4-notice is-bad" role="alert"><p class="a4-notice-title">{$error|wash}</p></div>{/if}
    {if and( $result, $result.ok )}
    <div class="a4-notice {if $result.dry_run}is-warn{else}is-ok{/if}" role="status">
        <p class="a4-notice-title">{if $result.dry_run}{'Check: %added addresses would be suppressed.'|i18n( 'cjw_newsletter/deliverability',, hash( '%added', $result.added ) )|wash}{else}{'%added addresses were suppressed.'|i18n( 'cjw_newsletter/deliverability',, hash( '%added', $result.added ) )|wash}{/if}</p>
    </div>
    <div class="a4-card">
        <dl class="a4-facts">
            <div><dt>{'File'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.file|wash}{if $result.column} ({'column'|i18n( 'cjw_newsletter/deliverability' )} {$result.column|wash}){/if}</dd></div>
            <div><dt>{'Reason'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.reason|wash}</dd></div>
            <div><dt>{'Rows'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.rows}</dd></div>
            <div><dt>{'Valid addresses'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.valid}{if $result.duplicates} ({'%count twice in the file'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $result.duplicates ) )|wash}){/if}</dd></div>
            <div><dt>{'Already suppressed'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.already}</dd></div>
            <div><dt>{'Not an address'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.invalid_count}</dd></div>
        </dl>
    </div>
    {if $result.invalid|count}
    <div class="a4-table-wrap">
        <table class="a4-table">
            <thead><tr><th scope="col" class="a4-num">{'Line'|i18n( 'cjw_newsletter/deliverability' )}</th><th scope="col">{'Value'|i18n( 'cjw_newsletter/deliverability' )}</th></tr></thead>
            <tbody>
            {foreach $result.invalid as $bad}<tr><td class="a4-num">{$bad.line}</td><td class="a4-wrap">{$bad.value|wash}</td></tr>{/foreach}
            </tbody>
        </table>
    </div>
    {/if}
    {/if}

    <form action={'newsletter/suppression_import'|ezurl} method="post" enctype="multipart/form-data">
        <div class="a4-card">
            <div class="a4-card-body a4-form">
                <div class="a4-field a4-field-wide">
                    <label for="nl-si-file">{'CSV file'|i18n( 'cjw_newsletter/deliverability' )}</label>
                    <input id="nl-si-file" type="file" name="SuppressionFile" accept=".csv,.txt,text/csv,text/plain" aria-describedby="nl-si-file-help" />
                    <span class="a4-help" id="nl-si-file-help">{'One address per row, in a column "email" or the first column with an address; ";", ",", tab or "|" between the columns; at most %max rows.'|i18n( 'cjw_newsletter/deliverability',, hash( '%max', $max_rows ) )|wash}</span>
                </div>
                <div class="a4-field a4-field-wide">
                    <label for="nl-si-text">{'Or paste the addresses'|i18n( 'cjw_newsletter/deliverability' )}</label>
                    <textarea id="nl-si-text" name="SuppressionText" rows="5" cols="60"></textarea>
                </div>
                <div class="a4-field">
                    <label for="nl-si-reason">{'Reason'|i18n( 'cjw_newsletter/deliverability' )}</label>
                    <select id="nl-si-reason" name="Reason">
                        {foreach $reasons as $r}<option value="{$r|wash}"{if eq( $r, $reason )} selected="selected"{/if}>{cond( eq( $r, 'legal' ), 'legal request, do-not-contact list'|i18n( 'cjw_newsletter/deliverability' ), eq( $r, 'admin' ), 'administrator'|i18n( 'cjw_newsletter/deliverability' ), eq( $r, 'bounce' ), 'bounce'|i18n( 'cjw_newsletter/deliverability' ), eq( $r, 'complaint' ), 'complaint'|i18n( 'cjw_newsletter/deliverability' ), 'stop all optional e-mail'|i18n( 'cjw_newsletter/deliverability' ) )}</option>{/foreach}
                    </select>
                </div>
                <div class="a4-field">
                    <label for="nl-si-note">{'Note'|i18n( 'cjw_newsletter/deliverability' )}</label>
                    <input id="nl-si-note" type="text" name="Note" value="{$note|wash}" maxlength="200" aria-describedby="nl-si-note-help" />
                    <span class="a4-help" id="nl-si-note-help">{'For the administrators, stored with every entry (addresses are taken out of it).'|i18n( 'cjw_newsletter/deliverability' )}</span>
                </div>
            </div>
            <div class="a4-card-foot">
                <input class="a4-btn" type="submit" name="CheckButton" value="{'Check (change nothing)'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
                <input class="a4-btn a4-btn-primary" type="submit" name="ImportButton" value="{'Import'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            </div>
        </div>
    </form>
    <p class="a4-help">{'On the console:'|i18n( 'cjw_newsletter/deliverability' )} <code>./console ext:cjw_newsletter:deliverability suppression-import --file=list.csv --reason=legal --dry-run</code></p>
    {/if}
</div>
