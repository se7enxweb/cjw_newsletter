{*  newsletter/deliverability/suppression_import.tpl (cjw_newsletter 4.2.0, area deliverability): CSV into the kernel suppression list *}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-suppression_import">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Import into the suppression list'|i18n( 'cjw_newsletter/deliverability' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {if $available|not}
        <div class="nl-confirm"><p>{'The e-mail preferences of Exponential are not installed here: there is no suppression list.'|i18n( 'cjw_newsletter/deliverability' )}</p></div>
        {else}
        <p class="nl-muted">{'No optional mail of the site goes to an address on the suppression list: newsletters, notifications, offers. Only a hash of each address is stored. The addresses also go on the newsletter blacklist. The file itself is not kept.'|i18n( 'cjw_newsletter/deliverability' )}</p>
        <p class="nl-muted">{'%all addresses are suppressed now (%legal for legal requests, %bounce for bounces).'|i18n( 'cjw_newsletter/deliverability',, hash( '%all', $counts.all, '%legal', $counts.legal, '%bounce', $counts.bounce ) )|wash}</p>

        {if $error}<div class="message-error"><h2>{$error|wash}</h2></div>{/if}
        {if and( $result, $result.ok )}
        <div class="{if $result.dry_run}message-warning{else}message-feedback{/if}">
            <h2>{if $result.dry_run}{'Check: %added addresses would be suppressed.'|i18n( 'cjw_newsletter/deliverability',, hash( '%added', $result.added ) )|wash}{else}{'%added addresses were suppressed.'|i18n( 'cjw_newsletter/deliverability',, hash( '%added', $result.added ) )|wash}{/if}</h2>
        </div>
        <dl class="nl-kv">
            <dt>{'File'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.file|wash}{if $result.column} ({'column'|i18n( 'cjw_newsletter/deliverability' )} {$result.column|wash}){/if}</dd>
            <dt>{'Reason'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.reason|wash}</dd>
            <dt>{'Rows'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.rows}</dd>
            <dt>{'Valid addresses'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.valid}{if $result.duplicates} ({'%count twice in the file'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $result.duplicates ) )|wash}){/if}</dd>
            <dt>{'Already suppressed'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.already}</dd>
            <dt>{'Not an address'|i18n( 'cjw_newsletter/deliverability' )}</dt><dd>{$result.invalid_count}</dd>
        </dl>
        {if $result.invalid|count}
        <table class="list nl-table">
            <tr><th class="nl-num">{'Line'|i18n( 'cjw_newsletter/deliverability' )}</th><th>{'Value'|i18n( 'cjw_newsletter/deliverability' )}</th></tr>
            {foreach $result.invalid as $bad sequence array( 'bglight', 'bgdark' ) as $seq}<tr class="{$seq}"><td class="nl-num">{$bad.line}</td><td class="nl-wrap">{$bad.value|wash}</td></tr>{/foreach}
        </table>
        {/if}
        {/if}

        <form action={'newsletter/suppression_import'|ezurl} method="post" enctype="multipart/form-data">
            <div class="block">
                <label for="nl-si-file">{'CSV file'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <input id="nl-si-file" type="file" name="SuppressionFile" accept=".csv,.txt,text/csv,text/plain" />
                <span class="nl-hint">{'One address per row, in a column "email" or the first column with an address; ";", ",", tab or "|" between the columns; at most %max rows.'|i18n( 'cjw_newsletter/deliverability',, hash( '%max', $max_rows ) )|wash}</span>
            </div>
            <div class="block">
                <label for="nl-si-text">{'Or paste the addresses'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <textarea id="nl-si-text" class="box" name="SuppressionText" rows="5" cols="60"></textarea>
            </div>
            <div class="block">
                <label for="nl-si-reason">{'Reason'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <select id="nl-si-reason" name="Reason">
                    {foreach $reasons as $r}<option value="{$r|wash}"{if eq( $r, $reason )} selected="selected"{/if}>{cond( eq( $r, 'legal' ), 'legal request, do-not-contact list'|i18n( 'cjw_newsletter/deliverability' ), eq( $r, 'admin' ), 'administrator'|i18n( 'cjw_newsletter/deliverability' ), eq( $r, 'bounce' ), 'bounce'|i18n( 'cjw_newsletter/deliverability' ), eq( $r, 'complaint' ), 'complaint'|i18n( 'cjw_newsletter/deliverability' ), 'stop all optional e-mail'|i18n( 'cjw_newsletter/deliverability' ) )}</option>{/foreach}
                </select>
            </div>
            <div class="block">
                <label for="nl-si-note">{'Note'|i18n( 'cjw_newsletter/deliverability' )}</label>
                <input id="nl-si-note" class="halfbox" type="text" name="Note" value="{$note|wash}" maxlength="200" />
                <span class="nl-hint">{'For the administrators, stored with every entry (addresses are taken out of it).'|i18n( 'cjw_newsletter/deliverability' )}</span>
            </div>
            <div class="controlbar">
                <input class="button" type="submit" name="CheckButton" value="{'Check (change nothing)'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
                <input class="defaultbutton" type="submit" name="ImportButton" value="{'Import'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            </div>
        </form>
        <p class="nl-hint">{'On the console:'|i18n( 'cjw_newsletter/deliverability' )} <code>./console ext:cjw_newsletter:deliverability suppression-import --file=list.csv --reason=legal --dry-run</code></p>
        {/if}
    </div>
</div>
</div>
