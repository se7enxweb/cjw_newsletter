{* The mail accounts the bounces are collected from, admin4. Same data and actions as design/standard/templates/newsletter/mailbox_list.tpl *}
<div class="a4-page newsletter newsletter-mailbox_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-inbox cjwnl-a4-lead" aria-hidden="true"></span>{'Mail accounts'|i18n( 'cjw_newsletter/mailbox_item_list' )} [{$mailbox_list_count}]</h1>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

    <p class="a4-intro">{'The newsletter reads these accounts for bounces and replies. Only active accounts are read.'|i18n( 'extension/cjw_newsletter' )}</p>

    <form class="a4-toolbar" action={'newsletter/mailbox_edit/0'|ezurl} method="get">
        <input class="a4-btn a4-btn-primary" type="submit" name="AddMailbox" value="{'Add mail account'|i18n( 'cjw_newsletter/mailbox_list' )|wash}" title="{'Add new mailbox.'|i18n( 'cjw_newsletter/mailbox_list' )|wash}" />
    </form>

    {if $mailbox_list|count}
    <div class="a4-table-wrap">
    <table class="a4-table">
        <thead>
        <tr>
            <th scope="col" class="a4-num">{'ID'|i18n( 'cjw_newsletter/mailbox_list' )}</th>
            <th scope="col">{'Email'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
            <th scope="col">{'Server'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
            <th scope="col">{'User'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
            <th scope="col">{'Type'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
            <th scope="col">{'Active'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
            <th scope="col">{'Last connect'|i18n( 'cjw_newsletter/mailbox_list' )}</th>
            <th scope="col">{'Actions'|i18n( 'extension/cjw_newsletter' )}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $mailbox_list as $mailbox_item}
        <tr>
            <td class="a4-num">{$mailbox_item.id|wash}</td>
            <td class="a4-wrap">{$mailbox_item.email|wash}</td>
            <td class="a4-wrap">{$mailbox_item.server|wash}{if $mailbox_item.port|gt( 0 )}:{$mailbox_item.port|wash}{/if}{if $mailbox_item.is_ssl|gt( 0 )} <span class="a4-badge">SSL</span>{/if}</td>
            <td class="a4-wrap">{$mailbox_item.user_name|wash}</td>
            <td>{$mailbox_item.type|wash|upcase}</td>
            <td>{if $mailbox_item.is_activated|gt( 0 )}<span class="a4-badge is-ok">{'active'|i18n( 'extension/cjw_newsletter' )}</span>{else}<span class="a4-badge">{'not active'|i18n( 'extension/cjw_newsletter' )}</span>{/if}</td>
            <td>{if eq( $mailbox_item.last_server_connect, 0 )}{'n/a'|i18n( 'cjw_newsletter/mailbox_list' )}{else}{$mailbox_item.last_server_connect|l10n( 'shortdatetime' )}{/if}</td>
            <td class="a4-nowrap">
                <div class="a4-actions">
                    <a class="a4-btn a4-btn-small" href={concat( '/newsletter/mailbox_edit/', $mailbox_item.id )|ezurl} title="{'Edit mailbox.'|i18n( 'cjw_newsletter/mailbox_list' )|wash}">{'Edit'|i18n( 'extension/cjw_newsletter' )}</a>
                    <form action={concat( '/newsletter/mailbox_edit/', $mailbox_item.id )|ezurl} method="post"><input class="a4-btn a4-btn-small a4-btn-outline-danger" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'extension/cjw_newsletter' )|wash}" /></form>
                </div>
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
    </div>
    {else}
    <p class="a4-empty">{'There is no mail account yet.'|i18n( 'extension/cjw_newsletter' )}
        {'Add the account that receives the bounces of the newsletter mails.'|i18n( 'extension/cjw_newsletter' )}</p>
    {/if}
</div>
