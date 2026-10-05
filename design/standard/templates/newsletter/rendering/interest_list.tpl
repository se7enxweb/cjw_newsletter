{*  newsletter/rendering/interest_list.tpl (cjw_newsletter 4.2.0, area rendering)

    The interests subscribers can pick. Variables: rows (hash( interest, list_name, list_node_id, users )),
    confirm_remove (an interest or null), confirm_users, eztags (bool), notices.
*}
{ezcss_require( array( 'newsletter_ui.css' ) )}
{def $i18n = 'cjw_newsletter/rendering'}
<div class="newsletter newsletter-interest_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Interests'|i18n( $i18n )} [{$rows|count}]</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        {if $confirm_remove}
        <form class="nl-confirm" action={'newsletter/interest_list'|ezurl} method="post">
            <h2>{'Remove this interest?'|i18n( $i18n )}</h2>
            <p><b>{$confirm_remove.name|wash}</b> ({$confirm_remove.identifier|wash})</p>
            <p>{'%count subscribers picked it; their picks are removed with it.'|i18n( $i18n,, hash( '%count', $confirm_users ) )}</p>
            <input class="button nl-danger" type="submit" name="ConfirmRemoveButton[{$confirm_remove.id}]" value="{'Yes, remove'|i18n( $i18n )|wash}" />
            <a class="button" href={'newsletter/interest_list'|ezurl}>{'Cancel'|i18n( $i18n )}</a>
        </form>
        {/if}

        <div class="nl-status">
            <div class="nl-status-facts">
                <span>{'Subscribers pick interests on their e-mail preference page. A list offers them when its interest source is set (list edit form).'|i18n( $i18n )}</span>
                {if $eztags|not}<span class="nl-pill is-muted">{'eztags is not installed: only topics'|i18n( $i18n )}</span>{/if}
            </div>
            <div>
                <a class="defaultbutton" href={'newsletter/interest_edit/0'|ezurl}>{'New interest'|i18n( $i18n )}</a>
            </div>
        </div>

        {if $rows|count}
        <form action={'newsletter/interest_list'|ezurl} method="post">
        <table class="list nl-table">
            <tr>
                <th>{'Name'|i18n( $i18n )}</th>
                <th>{'Identifier'|i18n( $i18n )}</th>
                <th>{'List'|i18n( $i18n )}</th>
                <th>{'Source'|i18n( $i18n )}</th>
                <th class="nl-num">{'Subscribers'|i18n( $i18n )}</th>
                <th>{'State'|i18n( $i18n )}</th>
                <th class="tight">{'Actions'|i18n( $i18n )}</th>
            </tr>
            {foreach $rows as $row sequence array( 'bglight', 'bgdark' ) as $style}
            <tr class="{$style}">
                <td><a href={concat( 'newsletter/interest_edit/', $row.interest.id )|ezurl}>{$row.interest.name|wash}</a></td>
                <td><code>{$row.interest.identifier|wash}</code></td>
                <td>{if $row.list_node_id}<a href={concat( 'content/view/full/', $row.list_node_id )|ezurl}>{$row.list_name|wash}</a>{else}{$row.list_name|wash}{/if}</td>
                <td>{if $row.interest.source|eq( 'eztags' )}{'Tag'|i18n( $i18n )} #{$row.interest.eztags_id}{else}{'Topic'|i18n( $i18n )}{if $row.interest.eztags_id|gt( 0 )} ({'tag'|i18n( $i18n )} #{$row.interest.eztags_id}){/if}{/if}</td>
                <td class="nl-num">{$row.users}</td>
                <td>{if $row.interest.is_active}<span class="nl-pill is-ok">{'offered'|i18n( $i18n )}</span>{else}<span class="nl-pill is-muted">{'hidden'|i18n( $i18n )}</span>{/if}</td>
                <td class="tight">
                    <a class="button" href={concat( 'newsletter/interest_edit/', $row.interest.id )|ezurl}>{'Edit'|i18n( $i18n )}</a>
                    <input class="button" type="submit" name="RemoveButton[{$row.interest.id}]" value="{'Remove'|i18n( $i18n )|wash}" />
                </td>
            </tr>
            {/foreach}
        </table>
        </form>
        {else}
        <p class="nl-hint">{'No interest yet.'|i18n( $i18n )}</p>
        {/if}
    </div>
</div>
</div>
{undef $i18n}
