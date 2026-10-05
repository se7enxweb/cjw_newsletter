{*  newsletter/mailbox_list.tpl

    the mail accounts the bounces are collected from
*}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-mailbox_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Mail accounts'|i18n( 'cjw_newsletter/mailbox_item_list' )} [{$mailbox_list_count}]</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}

        <p class="nl-muted">{'The newsletter reads these accounts for bounces and replies. Only active accounts are read.'|i18n( 'extension/cjw_newsletter' )}</p>

        <form action={'newsletter/mailbox_edit/0'|ezurl} method="get" style="margin: 0 0 0.9em 0">
            <input class="defaultbutton" type="submit" name="AddMailbox" value="{'Add mail account'|i18n( 'cjw_newsletter/mailbox_list' )|wash}" title="{'Add new mailbox.'|i18n( 'cjw_newsletter/mailbox_list' )|wash}" />
        </form>

        {if $mailbox_list|count}
        <table class="list nl-table">
            <tr>
                <th class="nl-num">{'ID'|i18n( 'cjw_newsletter/mailbox_list' )}</th>
                <th>{'Email'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
                <th>{'Server'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
                <th>{'User'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
                <th>{'Type'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
                <th>{'Active'|i18n( 'cjw_newsletter/mailbox_edit' )}</th>
                <th>{'Last connect'|i18n( 'cjw_newsletter/mailbox_list' )}</th>
                <th class="tight">{'Actions'|i18n( 'extension/cjw_newsletter' )}</th>
            </tr>
            {foreach $mailbox_list as $mailbox_item sequence array( 'bglight', 'bgdark' ) as $style}
            <tr class="{$style}">
                <td class="nl-num">{$mailbox_item.id|wash}</td>
                <td class="nl-wrap">{$mailbox_item.email|wash}</td>
                <td class="nl-wrap">{$mailbox_item.server|wash}{if $mailbox_item.port|gt( 0 )}:{$mailbox_item.port|wash}{/if}{if $mailbox_item.is_ssl|gt( 0 )} <span class="nl-pill is-muted">SSL</span>{/if}</td>
                <td class="nl-wrap">{$mailbox_item.user_name|wash}</td>
                <td>{$mailbox_item.type|wash|upcase}</td>
                <td>{if $mailbox_item.is_activated|gt( 0 )}<span class="nl-pill is-ok">{'active'|i18n( 'extension/cjw_newsletter' )}</span>{else}<span class="nl-pill is-muted">{'not active'|i18n( 'extension/cjw_newsletter' )}</span>{/if}</td>
                <td>{if eq( $mailbox_item.last_server_connect, 0 )}{'n/a'|i18n( 'cjw_newsletter/mailbox_list' )}{else}{$mailbox_item.last_server_connect|l10n( 'shortdatetime' )}{/if}</td>
                <td class="nl-actions">
                    <a class="button" href={concat( '/newsletter/mailbox_edit/', $mailbox_item.id )|ezurl} title="{'Edit mailbox.'|i18n( 'cjw_newsletter/mailbox_list' )|wash}">{'Edit'|i18n( 'extension/cjw_newsletter' )}</a>
                    <form action={concat( '/newsletter/mailbox_edit/', $mailbox_item.id )|ezurl} method="post" style="display:inline"><input class="button" type="submit" name="RemoveButton" value="{'Remove'|i18n( 'extension/cjw_newsletter' )|wash}" /></form>
                </td>
            </tr>
            {/foreach}
        </table>
        {else}
        <div class="nl-empty">
            <p><strong>{'There is no mail account yet.'|i18n( 'extension/cjw_newsletter' )}</strong></p>
            <p>{'Add the account that receives the bounces of the newsletter mails.'|i18n( 'extension/cjw_newsletter' )}</p>
        </div>
        {/if}
    </div>
</div>
</div>
