{* The status of a newsletter edition as a badge, admin4. Same data and actions as design/standard/templates/cjw_newsletter_edition_status.tpl (reads $newsletter_edition_status of the including template). *}
{switch match=$newsletter_edition_status}
    {case match='draft'}
        <span class="a4-badge is-info" title="{$newsletter_edition_status|wash}"><span class="a4-icon a4-bi-pencil-square is-small" aria-hidden="true"></span>{'Status draft'|i18n( 'cjw_newsletter/cjw_newsletter_edition_status' )}</span>
    {/case}
    {case match='process'}
        <span class="a4-badge is-warn" title="{$newsletter_edition_status|wash}"><span class="a4-icon a4-bi-hourglass-split is-small" aria-hidden="true"></span>{'Status process'|i18n( 'cjw_newsletter/cjw_newsletter_edition_status' )}</span>
    {/case}
    {case match='archive'}
        <span class="a4-badge is-ok" title="{$newsletter_edition_status|wash}"><span class="a4-icon a4-bi-archive is-small" aria-hidden="true"></span>{'Status archive'|i18n( 'cjw_newsletter/cjw_newsletter_edition_status' )}</span>
    {/case}
    {case match='abort'}
        <span class="a4-badge is-bad" title="{$newsletter_edition_status|wash}"><span class="a4-icon a4-bi-x-circle-fill is-small" aria-hidden="true"></span>{'Status abort'|i18n( 'cjw_newsletter/cjw_newsletter_edition_status' )}</span>
    {/case}
{/switch}
