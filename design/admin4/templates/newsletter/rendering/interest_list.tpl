{* The interests subscribers can pick, admin4. Same data and actions as design/standard/templates/newsletter/rendering/interest_list.tpl.
   Variables: rows (hash( interest, list_name, list_node_id, users )), confirm_remove (an interest or null), confirm_users,
   eztags (bool), notices. *}
{def $i18n = 'cjw_newsletter/rendering'}
<div class="a4-page newsletter newsletter-interest_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{'Interests'|i18n( $i18n )} [{$rows|count}]</h1>
        </div>
        <div class="a4-pagehead-actions">
            <a class="a4-btn a4-btn-primary" href={'newsletter/interest_edit/0'|ezurl}>{'New interest'|i18n( $i18n )}</a>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    {if $confirm_remove}
    <form class="a4-notice is-warn" action={'newsletter/interest_list'|ezurl} method="post">
        <h2 class="a4-notice-title">{'Remove this interest?'|i18n( $i18n )}</h2>
        <p><b>{$confirm_remove.name|wash}</b> ({$confirm_remove.identifier|wash})</p>
        <p>{'%count subscribers picked it; their picks are removed with it.'|i18n( $i18n,, hash( '%count', $confirm_users ) )}</p>
        <div class="a4-actions">
            <input class="a4-btn a4-btn-danger nl-danger" type="submit" name="ConfirmRemoveButton[{$confirm_remove.id}]" value="{'Yes, remove'|i18n( $i18n )|wash}" />
            <a class="a4-btn" href={'newsletter/interest_list'|ezurl}>{'Cancel'|i18n( $i18n )}</a>
        </div>
    </form>
    {/if}

    <p class="a4-intro">{'Subscribers pick interests on their e-mail preference page. A list offers them when its interest source is set (list edit form).'|i18n( $i18n )}
        {if $eztags|not} <span class="a4-badge">{'eztags is not installed: only topics'|i18n( $i18n )}</span>{/if}</p>

    {if $rows|count}
    <form action={'newsletter/interest_list'|ezurl} method="post">
    <div class="a4-table-wrap">
    <table class="a4-table">
        <thead>
        <tr>
            <th scope="col">{'Name'|i18n( $i18n )}</th>
            <th scope="col">{'Identifier'|i18n( $i18n )}</th>
            <th scope="col">{'List'|i18n( $i18n )}</th>
            <th scope="col">{'Source'|i18n( $i18n )}</th>
            <th scope="col" class="a4-num">{'Subscribers'|i18n( $i18n )}</th>
            <th scope="col">{'State'|i18n( $i18n )}</th>
            <th scope="col" class="tight">{'Actions'|i18n( $i18n )}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $rows as $row}
        <tr>
            <td><a href={concat( 'newsletter/interest_edit/', $row.interest.id )|ezurl}>{$row.interest.name|wash}</a></td>
            <td><code>{$row.interest.identifier|wash}</code></td>
            <td>{if $row.list_node_id}<a href={concat( 'content/view/full/', $row.list_node_id )|ezurl}>{$row.list_name|wash}</a>{else}{$row.list_name|wash}{/if}</td>
            <td>{if $row.interest.source|eq( 'eztags' )}{'Tag'|i18n( $i18n )} #{$row.interest.eztags_id}{else}{'Topic'|i18n( $i18n )}{if $row.interest.eztags_id|gt( 0 )} ({'tag'|i18n( $i18n )} #{$row.interest.eztags_id}){/if}{/if}</td>
            <td class="a4-num">{$row.users}</td>
            <td>{if $row.interest.is_active}<span class="a4-badge is-ok">{'offered'|i18n( $i18n )}</span>{else}<span class="a4-badge">{'hidden'|i18n( $i18n )}</span>{/if}</td>
            <td>
                <div class="a4-actions">
                    <a class="a4-btn a4-btn-small" href={concat( 'newsletter/interest_edit/', $row.interest.id )|ezurl}>{'Edit'|i18n( $i18n )}</a>
                    <input class="a4-btn a4-btn-small" type="submit" name="RemoveButton[{$row.interest.id}]" value="{'Remove'|i18n( $i18n )|wash}" />
                </div>
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
    </div>
    </form>
    {else}
    <p class="a4-empty">{'No interest yet.'|i18n( $i18n )}</p>
    {/if}
</div>
{undef $i18n}
