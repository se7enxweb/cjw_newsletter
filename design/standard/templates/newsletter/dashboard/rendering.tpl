{*  newsletter/dashboard/rendering.tpl (cjw_newsletter 4.2.0, area rendering)

    The dashboard block: the skins, the lists with their skins and languages, interests, outputs per language.
    Data: summary.areas.CjwNewsletterRenderingHooks (CjwNewsletterRenderingHooks::dashboardSummary()).
*}
{if is_set( $summary.areas.CjwNewsletterRenderingHooks )}
{def $r = $summary.areas.CjwNewsletterRenderingHooks
     $i18n = 'cjw_newsletter/rendering'}
<section class="nl-area nl-area-rendering">
    <h2>{'Skins, languages and interests'|i18n( $i18n )}</h2>
    <div class="nl-cards">
        <section class="nl-card">
            <h3>{'Skins'|i18n( $i18n )}</h3>
            <p>{foreach $r.skins as $skin}{def $skin_settings = cjwnl_rendering_data( 'skin_settings', $skin )}<span class="nl-pill is-info">{$skin_settings.description|wash}</span> {undef $skin_settings}{/foreach}</p>
            <div class="nl-links">
                <a class="button" href={'newsletter/skin_preview'|ezurl}>{'Preview the skins'|i18n( $i18n )}</a>
            </div>
        </section>

        <section class="nl-card">
            <h3>{'Lists'|i18n( $i18n )}</h3>
            {if $r.lists|count|eq( 0 )}
            <p class="nl-muted">{'No newsletter list yet.'|i18n( $i18n )}</p>
            {else}
            <dl class="nl-kv">
                {foreach $r.lists as $list}
                <dt>{$list.name|wash}</dt>
                <dd>{$list.skin|wash}{if $list.skins|count|gt( 1 )} <span class="nl-muted">({'%count skins allowed'|i18n( $i18n,, hash( '%count', $list.skins|count ) )})</span>{/if}
                    &middot; {if $list.languages|count|gt( 1 )}{$list.languages|implode( ', ' )|wash}{else}{$list.main_language|wash}{/if}
                    {if $list.interest_source|ne( '' )}&middot; {'%count interests'|i18n( $i18n,, hash( '%count', $list.interests ) )}{/if}</dd>
                {/foreach}
            </dl>
            {/if}
        </section>

        <section class="nl-card">
            <h3>{'Subscribers'|i18n( $i18n )}</h3>
            <ul class="nl-stats">
                <li><strong>{$r.subscribers_with_interests}</strong><span class="nl-muted">{'with interests'|i18n( $i18n )}</span></li>
                <li><strong>{$r.outputs}</strong><span class="nl-muted">{'outputs in other languages'|i18n( $i18n )}</span></li>
            </ul>
            {if $r.subscribers_by_language|count|gt( 0 )}
            <dl class="nl-kv">
                {foreach $r.subscribers_by_language as $locale => $count}<dt><code>{$locale|wash}</code></dt><dd>{$count}</dd>{/foreach}
            </dl>
            {/if}
            <div class="nl-links">
                <a class="button" href={'newsletter/interest_list'|ezurl}>{'Interests'|i18n( $i18n )}</a>
            </div>
        </section>
    </div>
</section>
{undef $r $i18n}
{/if}
