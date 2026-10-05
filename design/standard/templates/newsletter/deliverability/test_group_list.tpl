{*  newsletter/deliverability/test_group_list.tpl (cjw_newsletter 4.2.0, area deliverability): the test groups *}
{ezcss_require( 'newsletter_ui.css' )}
<div class="newsletter newsletter-test_group_list">
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{'Test groups'|i18n( 'cjw_newsletter/deliverability' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
        <p class="nl-muted">{'A test group is a named set of addresses that gets the test mails of an edition. Its addresses are added to those typed into the test send form. Every test mail starts its subject with "%prefix" and carries the header X-Cjwnl-Test; test mails never pass the mail gate and are never counted.'|i18n( 'cjw_newsletter/deliverability',, hash( '%prefix', $subject_prefix ) )|wash}</p>
        <form action={'newsletter/test_group_edit/0'|ezurl} method="post" style="margin: 0 0 0.9em 0">
            <input class="defaultbutton" type="submit" name="NewButton" value="{'New test group'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
        </form>
        {if $groups|count}
        <table class="list nl-table">
            <tr>
                <th>{'Name'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th>{'List'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th class="nl-num">{'Addresses'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th>{'Changed'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th class="tight">{'Actions'|i18n( 'cjw_newsletter/deliverability' )}</th>
            </tr>
            {foreach $groups as $group sequence array( 'bglight', 'bgdark' ) as $seq}
            <tr class="{$seq}">
                <td class="nl-wrap"><a href={concat( 'newsletter/test_group_edit/', $group.id )|ezurl}>{$group.name|wash}</a></td>
                <td class="nl-wrap">{if $group.list_id}{if $group.list_name}{$group.list_name|wash}{else}#{$group.list_id}{/if}{else}<span class="nl-muted">{'every list'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                <td class="nl-num">{$group.address_count} / {$max_size}</td>
                <td>{if $group.modified}{$group.modified|l10n( 'shortdatetime' )}{/if}</td>
                <td class="nl-actions"><a class="button" href={concat( 'newsletter/test_group_edit/', $group.id )|ezurl}>{'Edit'|i18n( 'cjw_newsletter/deliverability' )}</a></td>
            </tr>
            {/foreach}
        </table>
        {else}
        <div class="nl-empty"><p><strong>{'There is no test group yet.'|i18n( 'cjw_newsletter/deliverability' )}</strong></p></div>
        {/if}
    </div>
</div>
</div>
