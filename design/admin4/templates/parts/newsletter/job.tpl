{* The progress of a background run in admin4: parameter $job_id (empty: nothing to show). The page polls newsletter/job/<id>
   (cjw_newsletter.js). Same data and actions as design/standard/templates/parts/newsletter/job.tpl *}
{if $job_id}
<section class="a4-card a4-stack cjwnl-a4-card" id="nl-job" data-url="{concat( 'newsletter/job/', $job_id )|ezurl( 'no' )}" aria-live="polite"
         data-labels="{json_encode( hash( 'starting', 'starting'|i18n( 'extension/cjw_newsletter' ),
                                          'running', 'running'|i18n( 'extension/cjw_newsletter' ),
                                          'done', 'finished'|i18n( 'extension/cjw_newsletter' ),
                                          'failed', 'failed'|i18n( 'extension/cjw_newsletter' ),
                                          'unknown', 'unknown'|i18n( 'extension/cjw_newsletter' ) ) )|wash}">
    <h2 class="a4-h2">{'Background run'|i18n( 'extension/cjw_newsletter' )} <span class="a4-badge is-info" id="nl-job-status">{'starting'|i18n( 'extension/cjw_newsletter' )}</span></h2>
    <p class="a4-meta" id="nl-job-result"></p>
    <pre class="a4-pre nl-log" id="nl-job-log"></pre>
</section>
{ezscript( array( 'cjw_newsletter.js' ) )}
{/if}
