{* The import/export and migration block of the newsletter dashboard in admin4: the last imports and eznewsletter migration runs.
   Same data and actions as design/standard/templates/newsletter/dashboard/importexport.tpl
   Gets summary; its data is summary.areas.CjwNewsletterImportExportHooks. *}
{if is_set( $summary.areas.CjwNewsletterImportExportHooks )}
{def $ie = $summary.areas.CjwNewsletterImportExportHooks}
<section class="a4-section">
    <h2 class="a4-h2">{'Imports and migration'|i18n( 'cjw_newsletter/importexport' )}</h2>
    <div class="a4-grid is-wide cjwnl-a4-cards">
        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Last imports'|i18n( 'cjw_newsletter/importexport' )} <span class="a4-meta">[{$ie.import_count}]</span></h3>
            {if $ie.imports|count}
            <ul class="cjwnl-a4-list is-plain">
                {foreach $ie.imports as $item}
                <li>
                    <a href={cond( $item.mapped, concat( 'newsletter/import_mapping/', $item.id ), concat( 'newsletter/import_view/', $item.id ) )|ezurl}>#{$item.id}</a>
                    <span class="cjwnl-a4-grow">{$item.list|wash}</span>
                    {if $item.imported}<span class="a4-badge is-ok">{$item.subscriptions} {'subscriptions'|i18n( 'cjw_newsletter/importexport' )}</span>
                    {elseif $item.dry_run}<span class="a4-badge is-info">{'dry run'|i18n( 'cjw_newsletter/importexport' )}</span>
                    {elseif eq( $item.status, 2 )}<span class="a4-badge is-warn">{'running'|i18n( 'cjw_newsletter/importexport' )}</span>
                    {else}<span class="a4-badge">{'not imported'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}
                    {if $item.skipped}<span class="a4-badge is-warn">{'%count skipped'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $item.skipped ) )}</span>{/if}
                    {if $item.errors}<span class="a4-badge is-bad">{'%count failed'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $item.errors ) )}</span>{/if}
                    <time class="a4-meta">{$item.created|l10n( 'shortdate' )}</time>
                </li>
                {/foreach}
            </ul>
            {else}
            <p class="a4-meta">{'No import yet.'|i18n( 'cjw_newsletter/importexport' )}</p>
            {/if}
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/import_list'|ezurl}><span class="a4-icon a4-bi-upload" aria-hidden="true"></span>{'Imports'|i18n( 'cjw_newsletter/importexport' )}</a>
            </div>
        </section>
        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'eznewsletter migration'|i18n( 'cjw_newsletter/importexport' )} <span class="a4-meta">[{$ie.run_count}]</span></h3>
            {if $ie.runs|count}
            <ul class="cjwnl-a4-list is-plain">
                {foreach $ie.runs as $run}
                <li>
                    <a href={concat( 'newsletter/migration_log/', $run.run_id )|ezurl}><code>{$run.run_id|wash}</code></a>
                    {if $run.dry_run}<span class="a4-badge is-info">{'dry run'|i18n( 'cjw_newsletter/importexport' )}</span>{/if}
                    <span class="a4-meta">{'%created created, %merged merged, %skipped skipped'|i18n( 'cjw_newsletter/importexport',, hash( '%created', $run.created, '%merged', $run.merged, '%skipped', $run.skipped ) )}</span>
                    {if $run.failed}<span class="a4-badge is-bad">{'%count failed'|i18n( 'cjw_newsletter/importexport',, hash( '%count', $run.failed ) )}</span>{/if}
                </li>
                {/foreach}
            </ul>
            {else}
            <p class="a4-meta">{'Sites that ran eznewsletter take their subscribers over with ext:cjw_newsletter:import-eznewsletter.'|i18n( 'cjw_newsletter/importexport' )}</p>
            {/if}
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/migration_log'|ezurl}>{'Migration log'|i18n( 'cjw_newsletter/importexport' )}</a>
            </div>
        </section>
    </div>
</section>
{undef $ie}
{/if}
