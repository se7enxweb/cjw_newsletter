{* The editorial block of the newsletter dashboard in admin4: the recurring sends with their next runs, the editions that
   wait for an approval, the article pools.
   Same data and actions as design/standard/templates/newsletter/dashboard/editorial.tpl
   Data: summary.areas.CjwNewsletterEditorialHooks (CjwNewsletterEditorialHooks::dashboardSummary()). *}
{if is_set( $summary.areas.CjwNewsletterEditorialHooks )}
{def $e = $summary.areas.CjwNewsletterEditorialHooks
     $can_editorial = fetch( 'user', 'has_access_to', hash( 'module', 'newsletter', 'function', 'editorial' ) )}
<section class="a4-section">
    <h2 class="a4-h2">{'Editorial'|i18n( 'cjw_newsletter/editorial' )}</h2>
    <div class="a4-grid is-wide cjwnl-a4-cards">
        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Recurring sends'|i18n( 'cjw_newsletter/editorial' )}</h3>
            <ul class="a4-figures">
                <li class="a4-figure"><strong>{$e.active_count}</strong><span>{'active'|i18n( 'cjw_newsletter/editorial' )}</span></li>
                <li class="a4-figure"><strong>{sub( $e.schedule_count, $e.active_count )}</strong><span>{'paused'|i18n( 'cjw_newsletter/editorial' )}</span></li>
            </ul>
            {if $e.next|count}
            <ul class="cjwnl-a4-list is-plain">
                {foreach $e.next as $s}
                <li><time>{$s.next_run_text|wash}</time> <span>{$s.list_name|wash}</span> <span class="a4-badge">{if eq( $s.mode, 'copy' )}{'copy'|i18n( 'cjw_newsletter/editorial' )}{else}{'latest'|i18n( 'cjw_newsletter/editorial' )}{/if}</span></li>
                {/foreach}
            </ul>
            {else}
            <p class="a4-meta">{'No recurring send is planned.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {/if}
            <p class="a4-meta">
                {if $e.enabled}<span class="a4-badge is-ok">{'The cronjob runs them'|i18n( 'cjw_newsletter/editorial' )}</span>{else}<span class="a4-badge is-warn">{'Not run by the cronjob'|i18n( 'cjw_newsletter/editorial' )}</span>{/if}
                {if and( is_set( $e.last_run.time ), $e.last_run.time )} {'Last run'|i18n( 'cjw_newsletter/editorial' )}: <time>{$e.last_run.time|l10n( 'shortdatetime' )}</time>{/if}
            </p>
            {if $can_editorial}
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/schedule_list'|ezurl}>{'Recurring sends'|i18n( 'cjw_newsletter/editorial' )}</a>
                <a class="a4-btn" href={'newsletter/schedule_edit/0'|ezurl}>{'New recurring send'|i18n( 'cjw_newsletter/editorial' )}</a>
            </div>
            {/if}
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Approvals'|i18n( 'cjw_newsletter/editorial' )}</h3>
            <ul class="a4-figures">
                <li class="a4-figure{if $e.pending_count} is-attention{/if}"><strong>{$e.pending_count}</strong><span>{'waiting for approval'|i18n( 'cjw_newsletter/editorial' )}</span></li>
            </ul>
            {if $e.pending|count}
            <ul class="cjwnl-a4-list is-plain">
                {foreach $e.pending as $a max 5}
                <li><a href={concat( 'newsletter/approval/', $a.edition_contentobject_id, '/', $a.edition_contentobject_version )|ezurl}>{$a.edition_name|wash}</a> <span class="a4-meta">{$a.list_name|wash}, {'asked %time by %name'|i18n( 'cjw_newsletter/editorial',, hash( '%time', $a.requested|l10n( 'shortdatetime' ), '%name', $a.requester_name ) )|wash}</span></li>
                {/foreach}
            </ul>
            {else}
            <p class="a4-meta">{'No edition waits for an approval.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {/if}
            <div class="a4-actions">
                <a class="a4-btn" href={'collaboration/view/summary'|ezurl}>{'Collaboration inbox'|i18n( 'cjw_newsletter/editorial' )}</a>
            </div>
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Article pools'|i18n( 'cjw_newsletter/editorial' )}</h3>
            <ul class="a4-figures">
                <li class="a4-figure"><strong>{$e.pool_count}</strong><span>{'pools'|i18n( 'cjw_newsletter/editorial' )}</span></li>
            </ul>
            <p class="a4-meta">{'Editors pick articles for an edition from the pool of its list; recurring copies are filled from it.'|i18n( 'cjw_newsletter/editorial' )}</p>
            {if $can_editorial}
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/article_pool_list'|ezurl}>{'Article pools'|i18n( 'cjw_newsletter/editorial' )}</a>
            </div>
            {/if}
        </section>
    </div>
</section>
{undef $e $can_editorial}
{/if}
