{* newsletter/segments/segment_list.tpl, admin4. Variables: list, segments, notices. *}
{def $i18n = 'cjw_newsletter/segments'}
<div class="a4-page newsletter newsletter-segment_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main"><h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-signpost-split" aria-hidden="true"></span> {'Segments'|i18n( $i18n )}: {$list.name|wash} [{$segments|count}]</h1></div>
        <div class="a4-pagehead-actions"><a class="a4-btn a4-btn-primary" href={concat( 'newsletter/segment_edit/0/', $list.id )|ezurl}>{'New segment'|i18n( $i18n )}</a></div>
    </div>
    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    <div class="a4-table-wrap">{if $segments|count}
    <table class="a4-table">
        <thead><tr><th scope="col">{'Name'|i18n( $i18n )}</th><th scope="col">{'Match'|i18n( $i18n )}</th><th scope="col">{'Conditions'|i18n( $i18n )}</th><th scope="col">{'Modified'|i18n( $i18n )}</th></tr></thead>
        <tbody>
        {foreach $segments as $s}
        <tr><td><a href={concat( 'newsletter/segment_edit/', $s.id, '/', $list.id )|ezurl}>{$s.name|wash}</a></td>
            <td>{if eq( $s.match_mode, 'any' )}{'any condition'|i18n( $i18n )}{else}{'all conditions'|i18n( $i18n )}{/if}</td>
            <td>{$s.condition_count}</td><td>{$s.modified|l10n( 'shortdatetime' )}</td></tr>
        {/foreach}
        </tbody>
    </table>
    {else}<p>{'There are no segments for this list yet.'|i18n( $i18n )}</p>{/if}</div>
</div>
{undef $i18n}
