{*  newsletter/statistics/ab_test.tpl (cjw_newsletter 4.2.0, area statistics)

    The A/B subject test of a send. test: CjwNewsletterAbTester::summary(), report: the send's report, can_send, notices
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_statistics.css' ) )}
{def $i18n = 'cjw_newsletter/statistics'}
<div class="newsletter newsletter-ab_test">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'A/B subject test'|i18n( $i18n )}: {if $report.edition.name}{$report.edition.name|wash}{else}#{$report.id}{/if}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        {include uri='design:newsletter/statistics/ab_table.tpl' test=$test}

        <p class="nl-stat-privacy">
            {if eq( $test.criterion, 'open' )}{'The open rate counts the first open of each person who agreed to the newsletter statistics, per sent mail of the variant; the people are split at random, so the rates compare fairly.'|i18n( $i18n )}
            {else}{'The click rate decides: clicks per sent mail of the variant (anonymous totals, or no opens known).'|i18n( $i18n )}{/if}
        </p>

        {if and( $test.open, $can_send )}
        <form action={concat( 'newsletter/ab_test/', $test.edition_send_id )|ezurl} method="post" class="nl-links" data-nl-confirm="{'Change the test now?'|i18n( $i18n )|wash}">
            <input class="defaultbutton" type="submit" name="ChooseWinnerButton" value="{'Choose the winner now'|i18n( $i18n )|wash}" />
            <input class="button" type="submit" name="CancelTestButton" value="{'Cancel the test'|i18n( $i18n )|wash}" data-nl-confirm-button="" />
        </form>
        <p class="nl-hint">{'Without you, the mail queue chooses the winner when the wait is over and then sends the rest of the list.'|i18n( $i18n )}</p>
        {/if}

        <div class="nl-links">
            <a class="button" href={concat( 'newsletter/report/', $test.edition_send_id )|ezurl}>{'The report of the send'|i18n( $i18n )}</a>
        </div>
    </div>
</div>
</div>
{undef $i18n}
