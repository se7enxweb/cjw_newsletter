{*  newsletter/dashboard/editorial.tpl (cjw_newsletter 4.2.0, area editorial)

    The dashboard block: the recurring sends with their next runs, the editions that wait for an approval, the
    article pools. Data: summary.areas.CjwNewsletterEditorialHooks (CjwNewsletterEditorialHooks::dashboardSummary()).
*}
{if is_set( $summary.areas.CjwNewsletterEditorialHooks )}
{def $e = $summary.areas.CjwNewsletterEditorialHooks
     $can_editorial = fetch( 'user', 'has_access_to', hash( 'module', 'newsletter', 'function', 'editorial' ) )}
<section class="nl-area nl-area-editorial">
    <h2>{'Editorial'|i18n( 'cjw_newsletter/editorial' )}</h2>
    <div class="nl-cards">
        <section class="nl-card">
            <h3>{'Recurring sends'|i18n( 'cjw_newsletter/editorial' )}</h3>
            <ul class="nl-stats">
                <li><strong>{$e.active_count}</strong><span class="nl-muted">{'active'|i18n( 'cjw_newsletter/editorial' )}</span></li>
                <li><strong>{sub( $e.schedule_count, $e.active_count )}</strong><span class="nl-muted">{'paused'|i18n( 'cjw_newsletter/editorial' )}</span></li>
            </ul>
            {if $e.next|count}
            <ul class="nl-ed-next">
                {foreach $e.next as $s}
                <li><time>{$s.next_run_text|wash}</time> <span>{$s.list_name|wash}</span> <span class="nl-pill is-muted">{if eq( $s.mode, 'copy' )}{'copy'|i18n( 'cjw_newsletter/editorial' )}{else}{'latest'|i18n( 'cjw_newsletter/editorial' )}{/if}</span></li>
                {/foreach}
            </ul>
            {else}
            <p class="nl-muted">{'No recurring send is planned.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {/if}
            <p class="nl-muted">
                {if $e.enabled}<span class="nl-pill is-ok">{'The cronjob runs them'|i18n( 'cjw_newsletter/editorial' )}</span>{else}<span class="nl-pill is-warn">{'Not run by the cronjob'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}
                {if and( is_set( $e.last_run.time ), $e.last_run.time )} {'Last run'|i18n( 'cjw_newsletter/editorial' )}: <time>{$e.last_run.time|l10n( 'shortdatetime' )}</time>{/if}
            </p>
            {if $can_editorial}
            <div class="nl-links">
                <a class="button" href={'newsletter/schedule_list'|ezurl}>{'Recurring sends'|i18n( 'cjw_newsletter/editorial' )}</a>
                <a class="button" href={'newsletter/schedule_edit/0'|ezurl}>{'New recurring send'|i18n( 'cjw_newsletter/editorial' )}</a>
            </div>
            {/if}
        </section>

        <section class="nl-card">
            <h3>{'Approvals'|i18n( 'cjw_newsletter/editorial' )}</h3>
            <ul class="nl-stats">
                <li><strong{if $e.pending_count} class="nl-ed-wait"{/if}>{$e.pending_count}</strong><span class="nl-muted">{'waiting for approval'|i18n( 'cjw_newsletter/editorial' )}</span></li>
            </ul>
            {if $e.pending|count}
            <ul class="nl-ed-next">
                {foreach $e.pending as $a max 5}
                <li><a href={concat( 'newsletter/approval/', $a.edition_contentobject_id, '/', $a.edition_contentobject_version )|ezurl}>{$a.edition_name|wash}</a> <span class="nl-muted">{$a.list_name|wash}, {'asked %time by %name'|i18n( 'cjw_newsletter/editorial',, hash( '%time', $a.requested|l10n( 'shortdatetime' ), '%name', $a.requester_name ) )|wash}</span></li>
                {/foreach}
            </ul>
            {else}
            <p class="nl-muted">{'No edition waits for an approval.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {/if}
            <div class="nl-links">
                <a class="button" href={'collaboration/view/summary'|ezurl}>{'Collaboration inbox'|i18n( 'cjw_newsletter/editorial' )}</a>
            </div>
        </section>

        <section class="nl-card">
            <h3>{'Article pools'|i18n( 'cjw_newsletter/editorial' )}</h3>
            <ul class="nl-stats">
                <li><strong>{$e.pool_count}</strong><span class="nl-muted">{'pools'|i18n( 'cjw_newsletter/editorial' )}</span></li>
            </ul>
            <p class="nl-muted">{'Editors pick articles for an edition from the pool of its list; recurring copies are filled from it.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {if $can_editorial}
            <div class="nl-links">
                <a class="button" href={'newsletter/article_pool_list'|ezurl}>{'Article pools'|i18n( 'cjw_newsletter/editorial' )}</a>
            </div>
            {/if}
        </section>
    </div>
</section>
{ezcss_require( 'newsletter_editorial.css' )}
{undef $e $can_editorial}
{/if}
