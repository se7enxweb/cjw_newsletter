{* newsletter/segments/segment_list.tpl, standard. Variables: list, segments, notices. *}
{def $i18n = 'cjw_newsletter/segments'}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_editorial.css' ) )}
<div class="newsletter newsletter-segment_list">
<div class="context-block nl">
    <div class="box-header"><h1 class="context-title">{'Segments'|i18n( $i18n )}: {$list.name|wash} [{$segments|count}]</h1><div class="header-mainline"></div></div>
    <div class="box-content">
    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    {if $segments|count}
    <table class="list nl-table">
        <thead><tr><th scope="col">{'Name'|i18n( $i18n )}</th><th scope="col">{'Match'|i18n( $i18n )}</th><th scope="col">{'Conditions'|i18n( $i18n )}</th><th scope="col">{'Modified'|i18n( $i18n )}</th></tr></thead>
        <tbody>
        {foreach $segments as $s}
        <tr><td><a href={concat( 'newsletter/segment_edit/', $s.id, '/', $list.id )|ezurl}>{$s.name|wash}</a></td>
            <td>{if eq( $s.match_mode, 'any' )}{'any condition'|i18n( $i18n )}{else}{'all conditions'|i18n( $i18n )}{/if}</td>
            <td>{$s.condition_count}</td><td>{$s.modified|l10n( 'shortdatetime' )}</td></tr>
        {/foreach}
        </tbody>
    </table>
    {else}<p>{'There are no segments for this list yet.'|i18n( $i18n )}</p>{/if}
    <p><a class="button" href={concat( 'newsletter/segment_edit/0/', $list.id )|ezurl}>{'New segment'|i18n( $i18n )}</a></p>
    </div>
</div>
</div>
{undef $i18n}
