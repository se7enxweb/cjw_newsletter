{* The A/B subject test of a send, admin4. Same data and actions as design/standard/templates/newsletter/statistics/ab_test.tpl
   Variables: test, report, can_send, notices *}
{def $i18n = 'cjw_newsletter/statistics'}
<div class="a4-page">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'A/B subject test'|i18n( $i18n )}: {if $report.edition.name}{$report.edition.name|wash}{else}#{$report.id}{/if}</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    {include uri='design:newsletter/statistics/ab_table.tpl' test=$test}

    <p class="a4-notice is-info" role="status">
        {if eq( $test.criterion, 'open' )}{'The open rate counts the first open of each person who agreed to the newsletter statistics, per sent mail of the variant; the people are split at random, so the rates compare fairly.'|i18n( $i18n )}
        {else}{'The click rate decides: clicks per sent mail of the variant (anonymous totals, or no opens known).'|i18n( $i18n )}{/if}
    </p>

    {if and( $test.open, $can_send )}
    <form action={concat( 'newsletter/ab_test/', $test.edition_send_id )|ezurl} method="post" class="a4-actions" data-nl-confirm="{'Change the test now?'|i18n( $i18n )|wash}">
        <input class="a4-btn a4-btn-primary" type="submit" name="ChooseWinnerButton" value="{'Choose the winner now'|i18n( $i18n )|wash}" />
        <input class="a4-btn" type="submit" name="CancelTestButton" value="{'Cancel the test'|i18n( $i18n )|wash}" data-nl-confirm-button="" />
    </form>
    <p class="a4-meta">{'Without you, the mail queue chooses the winner when the wait is over and then sends the rest of the list.'|i18n( $i18n )}</p>
    {/if}

    <div class="a4-toolbar is-bottom">
        <a class="a4-btn" href={concat( 'newsletter/report/', $test.edition_send_id )|ezurl}>{'The report of the send'|i18n( $i18n )}</a>
    </div>
</div>
{undef $i18n}
{include uri='design:parts/newsletter/script.tpl'}
