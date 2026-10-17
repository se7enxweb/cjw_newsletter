{* The progress of a background run: parameter $job_id (empty: nothing to show). The page polls newsletter/job/<id>
   (cjw_newsletter.js). *}
{if $job_id}
<section class="nl-card" id="nl-job" data-url="{concat( 'newsletter/job/', $job_id )|ezurl( 'no' )}" aria-live="polite"
         data-labels="{json_encode( hash( 'starting', 'starting'|i18n( 'extension/cjw_newsletter' ),
                                          'running', 'running'|i18n( 'extension/cjw_newsletter' ),
                                          'done', 'finished'|i18n( 'extension/cjw_newsletter' ),
                                          'failed', 'failed'|i18n( 'extension/cjw_newsletter' ),
                                          'unknown', 'unknown'|i18n( 'extension/cjw_newsletter' ) ) )|wash}">
    <h2>{'Background run'|i18n( 'extension/cjw_newsletter' )} <span class="nl-pill is-info" id="nl-job-status">{'starting'|i18n( 'extension/cjw_newsletter' )}</span></h2>
    <p class="nl-muted" id="nl-job-result"></p>
    <pre class="nl-log" id="nl-job-log"></pre>
</section>
{ezscript( array( 'cjw_newsletter.js' ) )}
{/if}
