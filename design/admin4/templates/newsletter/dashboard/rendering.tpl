{* The rendering block of the newsletter dashboard in admin4: the skins, the lists with their skins and languages, interests,
   outputs per language.
   Same data and actions as design/standard/templates/newsletter/dashboard/rendering.tpl
   Data: summary.areas.CjwNewsletterRenderingHooks (CjwNewsletterRenderingHooks::dashboardSummary()). *}
{if is_set( $summary.areas.CjwNewsletterRenderingHooks )}
{def $r = $summary.areas.CjwNewsletterRenderingHooks
     $i18n = 'cjw_newsletter/rendering'}
<section class="a4-section">
    <h2 class="a4-h2">{'Skins, languages and interests'|i18n( $i18n )}</h2>
    <div class="a4-grid is-wide cjwnl-a4-cards">
        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Skins'|i18n( $i18n )}</h3>
            <p class="a4-badges">{foreach $r.skins as $skin}{def $skin_settings = cjwnl_rendering_data( 'skin_settings', $skin )}<span class="a4-badge is-info">{$skin_settings.description|wash}</span> {undef $skin_settings}{/foreach}</p>
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/skin_preview'|ezurl}>{'Preview the skins'|i18n( $i18n )}</a>
            </div>
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Lists'|i18n( $i18n )}</h3>
            {if $r.lists|count|eq( 0 )}
            <p class="a4-meta">{'No newsletter list yet.'|i18n( $i18n )}</p>
            {else}
            <dl class="a4-facts">
                {foreach $r.lists as $list}
                <div><dt>{$list.name|wash}</dt>
                <dd>{$list.skin|wash}{if $list.skins|count|gt( 1 )} <span class="a4-meta">({'%count skins allowed'|i18n( $i18n,, hash( '%count', $list.skins|count ) )})</span>{/if}
                    &middot; {if $list.languages|count|gt( 1 )}{$list.languages|implode( ', ' )|wash}{else}{$list.main_language|wash}{/if}
                    {if $list.interest_source|ne( '' )}&middot; {'%count interests'|i18n( $i18n,, hash( '%count', $list.interests ) )}{/if}</dd></div>
                {/foreach}
            </dl>
            {/if}
        </section>

        <section class="a4-card a4-stack cjwnl-a4-card">
            <h3 class="a4-h3">{'Subscribers'|i18n( $i18n )}</h3>
            <ul class="a4-figures">
                <li class="a4-figure"><strong>{$r.subscribers_with_interests}</strong><span>{'with interests'|i18n( $i18n )}</span></li>
                <li class="a4-figure"><strong>{$r.outputs}</strong><span>{'outputs in other languages'|i18n( $i18n )}</span></li>
            </ul>
            {if $r.subscribers_by_language|count|gt( 0 )}
            <dl class="a4-facts is-rows">
                {foreach $r.subscribers_by_language as $locale => $count}<div><dt><code>{$locale|wash}</code></dt><dd>{$count}</dd></div>{/foreach}
            </dl>
            {/if}
            <div class="a4-actions">
                <a class="a4-btn" href={'newsletter/interest_list'|ezurl}>{'Interests'|i18n( $i18n )}</a>
            </div>
        </section>
    </div>
</section>
{undef $r $i18n}
{/if}
