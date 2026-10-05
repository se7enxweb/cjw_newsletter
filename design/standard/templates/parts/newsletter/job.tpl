{* The progress of a background run: parameter $job_id (empty: nothing to show). The page polls newsletter/job/<id>. *}
{if $job_id}
<section class="nl-card" id="nl-job" data-url="{concat( 'newsletter/job/', $job_id )|ezurl( 'no' )}" aria-live="polite">
    <h2>{'Background run'|i18n( 'extension/cjw_newsletter' )} <span class="nl-pill is-info" id="nl-job-status">{'starting'|i18n( 'extension/cjw_newsletter' )}</span></h2>
    <p class="nl-muted" id="nl-job-result"></p>
    <pre class="nl-log" id="nl-job-log"></pre>
</section>
<script>
(function () {ldelim}
    var box = document.getElementById('nl-job');
    var labels = {ldelim} starting: '{'starting'|i18n( 'extension/cjw_newsletter' )|wash( 'javascript' )}', running: '{'running'|i18n( 'extension/cjw_newsletter' )|wash( 'javascript' )}',
                          done: '{'finished'|i18n( 'extension/cjw_newsletter' )|wash( 'javascript' )}', failed: '{'failed'|i18n( 'extension/cjw_newsletter' )|wash( 'javascript' )}', unknown: '{'unknown'|i18n( 'extension/cjw_newsletter' )|wash( 'javascript' )}' {rdelim};
    var classes = {ldelim} starting: 'is-info', running: 'is-warn', done: 'is-ok', failed: 'is-bad', unknown: 'is-muted' {rdelim};
    function poll() {ldelim}
        fetch(box.getAttribute('data-url'), {ldelim} credentials: 'same-origin', headers: {ldelim} 'Accept': 'application/json' {rdelim} {rdelim})
            .then(function (r) {ldelim} return r.json(); {rdelim})
            .then(function (job) {ldelim}
                var status = document.getElementById('nl-job-status');
                status.textContent = labels[job.status] || job.status;
                status.className = 'nl-pill ' + (classes[job.status] || 'is-muted');
                document.getElementById('nl-job-log').textContent = (job.log || []).join('\n');
                var result = job.result || {ldelim}{rdelim};
                var parts = [];
                ['sends', 'items', 'sent', 'failed', 'finished', 'waiting', 'mailboxes', 'collected', 'parsed', 'subscriptions', 'send_items', 'rows', 'users', 'invalid'].forEach(function (key) {ldelim}
                    if (typeof result[key] === 'number') {ldelim} parts.push(key + ': ' + result[key]); {rdelim}
                {rdelim});
                if (result.error) {ldelim} parts.push(result.error); {rdelim}
                document.getElementById('nl-job-result').textContent = parts.join(' · ');
                if (job.status === 'starting' || job.status === 'running') {ldelim} window.setTimeout(poll, 2000); {rdelim}
            {rdelim})
            .catch(function () {ldelim} window.setTimeout(poll, 5000); {rdelim});
    {rdelim}
    poll();
{rdelim})();
</script>
{/if}
