{* Dashboard block of the area import/export and migration: the last imports and eznewsletter migration runs.
   Gets summary; its data is summary.areas.CjwNewsletterImportExportHooks. *}
{if is_set( $summary.areas.CjwNewsletterImportExportHooks )}
{ezcss_require( 'newsletter_importexport.css' )}
{def $ie = $summary.areas.CjwNewsletterImportExportHooks}
<section class="nl-ie-dashboard">
    <h2>{'Imports and migration'|i18n( 'cjw_newsletter/importexport' )}</h2>
    <div class="nl-cards">
        <section class="nl-card">
            <h3>{'Last imports'|i18n( 'cjw_newsletter/importexport' )} <span class="nl-muted">[{$ie.import_count}]</span></h3>
            {if $ie.imports|count}
            <ul class="nl-ie-list">
                {foreach $ie.imports as $item}
                <li>
                    <a href={cond( $item.mapped, concat( 'newsletter/import_mapping/', $item.id ), concat( 'newsletter/import_view/', $item.id ) )|ezurl}>#{$item.id}</a>
                    <span class="nl-wrap">{$item.list|wash}</span>
                    {if $item.imported}<span class="nl-pill is-ok">{$item.subscriptions} {'subscriptions'|i18n( 'cjw_newsletter/importexport' )}</span>
                    {elseif $item.dry_run}<span class="nl-pill is-info">{'dry run'|i18n( 'cjw_newsletter/importexport' )}</span>
                    {elseif eq( $item.status, 2 )}<span class="nl-pill is-warn">{'running'|i18n( 'cjw_newsletter/importexport' )}</span>
                    {else}<span class="nl-pill is-muted">{'not imported'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}
                    {if $item.skipped}<span class="nl-pill is-warn">{'%count skipped'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $item.skipped ) )}</span>{/if}
                    {if $item.errors}<span class="nl-pill is-bad">{'%count failed'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $item.errors ) )}</span>{/if}
                    <time class="nl-muted">{$item.created|l10n( 'shortdate' )}</time>
                </li>
                {/foreach}
            </ul>
            {else}
            <p class="nl-muted">{'No import yet.'|i18n( 'cjw_newsletter/importexport' )}</p>
            {/if}
            <div class="nl-links">
                <a class="button" href={'newsletter/import_list'|ezurl}>{'Imports'|i18n( 'cjw_newsletter/importexport' )}</a>
            </div>
        </section>
        <section class="nl-card">
            <h3>{'eznewsletter migration'|i18n( 'cjw_newsletter/importexport' )} <span class="nl-muted">[{$ie.run_count}]</span></h3>
            {if $ie.runs|count}
            <ul class="nl-ie-list">
                {foreach $ie.runs as $run}
                <li>
                    <a href={concat( 'newsletter/migration_log/', $run.run_id )|ezurl}><code>{$run.run_id|wash}</code></a>
                    {if $run.dry_run}<span class="nl-pill is-info">{'dry run'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}
                    <span class="nl-muted">{'%created created, %merged merged, %skipped skipped'|i18n( 'cjw_newsletter/importexport',, hash( '%created', $run.created, '%merged', $run.merged, '%skipped', $run.skipped ) )}</span>
                    {if $run.failed}<span class="nl-pill is-bad">{'%count failed'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $run.failed ) )}</span>{/if}
                </li>
                {/foreach}
            </ul>
            {else}
            <p class="nl-muted">{'Sites that ran eznewsletter take their subscribers over with ext:cjw_newsletter:import-eznewsletter.'|i18n( 'cjw_newsletter/importexport' )}</p>
            {/if}
            <div class="nl-links">
                <a class="button" href={'newsletter/migration_log'|ezurl}>{'Migration log'|i18n( 'cjw_newsletter/importexport' )}</a>
            </div>
        </section>
    </div>
</section>
{undef $ie}
{/if}
