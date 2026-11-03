{* The test groups, admin4: same data and actions as design/standard/templates/newsletter/deliverability/test_group_list.tpl *}
<div class="a4-page newsletter newsletter-test_group_list">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title context-title">{'Test groups'|i18n( 'cjw_newsletter/deliverability' )}</h1>
        </div>
        <div class="a4-pagehead-actions">
            <form action={'newsletter/test_group_edit/0'|ezurl} method="post">
                <input class="a4-btn a4-btn-primary" type="submit" name="NewButton" value="{'New test group'|i18n( 'cjw_newsletter/deliverability' )|wash}" />
            </form>
        </div>
    </div>

    {include uri='design:parts/newsletter/notices.tpl' notices=$notices}
    <p class="a4-intro">{'A test group is a named set of addresses that gets the test mails of an edition. Its addresses are added to those typed into the test send form. Every test mail starts its subject with "%prefix" and carries the header X-Cjwnl-Test; test mails never pass the mail gate and are never counted.'|i18n( 'cjw_newsletter/deliverability',, hash( '%prefix', $subject_prefix ) )|wash}</p>

    {if $groups|count}
    <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr>
                <th scope="col">{'Name'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col">{'List'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col" class="a4-num">{'Addresses'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col">{'Changed'|i18n( 'cjw_newsletter/deliverability' )}</th>
                <th scope="col">{'Actions'|i18n( 'cjw_newsletter/deliverability' )}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $groups as $group}
            <tr>
                <td class="a4-wrap"><a href={concat( 'newsletter/test_group_edit/', $group.id )|ezurl}>{$group.name|wash}</a></td>
                <td class="a4-wrap">{if $group.list_id}{if $group.list_name}{$group.list_name|wash}{else}#{$group.list_id}{/if}{else}<span class="a4-muted">{'every list'|i18n( 'cjw_newsletter/deliverability' )}</span>{/if}</td>
                <td class="a4-num">{$group.address_count} / {$max_size}</td>
                <td>{if $group.modified}{$group.modified|l10n( 'shortdatetime' )}{/if}</td>
                <td><a class="a4-btn a4-btn-small" href={concat( 'newsletter/test_group_edit/', $group.id )|ezurl}>{'Edit'|i18n( 'cjw_newsletter/deliverability' )}</a></td>
            </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
    {else}
    <p class="a4-empty"><strong>{'There is no test group yet.'|i18n( 'cjw_newsletter/deliverability' )}</strong></p>
    {/if}
</div>
