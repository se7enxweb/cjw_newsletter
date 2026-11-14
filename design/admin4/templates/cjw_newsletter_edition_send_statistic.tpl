{* The sending statistics of a newsletter edition (counts per send, cronjob status with the abort button), admin4. Same data and actions as design/standard/templates/cjw_newsletter_edition_send_statistic.tpl. *}
{def $current_edition_send_object = false()}
{if $newsletter_edition_attribute_content.edition_send_array.current|count|ne( 0 )}
    {set $current_edition_send_object = $newsletter_edition_attribute_content.edition_send_array.current[0]}
{/if}

<section class="a4-card" aria-labelledby="cjwnl-a4-send-statistic">
    <div class="a4-card-body">
        <h2 class="a4-h2" id="cjwnl-a4-send-statistic">{'Statistics'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</h2>
        <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr>
                <th scope="col">{'Id'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</th>
                <th scope="col">{'Emails count'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</th>
                <th scope="col">{'Emails sent'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</th>
                <th scope="col">{'Emails opened'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</th>
                <th scope="col">{'Emails not sent'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</th>
                <th scope="col">{'Emails bounced'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $newsletter_edition_attribute_content.edition_send_array.all as $edition_send_object}
            <tr>
                <td class="a4-num">{if and( $current_edition_send_object, $current_edition_send_object.id|eq($edition_send_object.id) )}({$edition_send_object.id}){else}{$edition_send_object.id}{/if}</td>
                <td class="a4-num">{$edition_send_object.send_items_statistic.items_count}</td>
                <td class="a4-num">{$edition_send_object.send_items_statistic.items_send}</td>
                <td class="a4-num">&nbsp;</td>
                <td class="a4-num">{$edition_send_object.send_items_statistic.items_not_send}</td>
                <td class="a4-num">{$edition_send_object.send_items_statistic.items_bounced}</td>
            </tr>
            {/foreach}
            </tbody>
        </table>
        </div>
    </div>
</section>

{if $current_edition_send_object}
<section class="a4-card" aria-labelledby="cjwnl-a4-send-processing">
    <div class="a4-card-body">
        <h2 class="a4-h2" id="cjwnl-a4-send-processing">{'Newsletter processing info'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</h2>

        <div class="a4-toolbar">
            <h3 class="a4-h3">{'Cronjob status'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</h3>
            {* only show abort button if not  finished (3) or aborted (9) *}
            {if or( $current_edition_send_object.status|eq(3), $current_edition_send_object.status|eq(9) )}
                <button type="button" class="a4-btn" disabled="disabled">{'Abort cronjob'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</button>
            {else}
                <a class="a4-btn a4-btn-danger" href={concat('newsletter/send_abort/',$current_edition_send_object.id)|ezurl}>{'Abort cronjob'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</a>
            {/if}
        </div>

        <ul class="a4-plain">
            <li>{if $current_edition_send_object.status|eq(4)}<strong>{/if}4 - mailqueue_process_scheduled ( {cond( $current_edition_send_object.mailqueue_process_scheduled|eq(0), '-',  $current_edition_send_object.mailqueue_process_scheduled|l10n( shortdatetime ) )} ){if $current_edition_send_object.status|eq(4)}</strong>{/if}</li>
            <li>{if $current_edition_send_object.status|eq(0)}<strong>{/if}0 - wait_for_process ( {$current_edition_send_object.created|l10n( shortdatetime )} ){if $current_edition_send_object.status|eq(0)}</strong>{/if}</li>
            <li>{if $current_edition_send_object.status|eq(1)}<strong>{/if}1 - mailqueue_created ( {cond( $current_edition_send_object.mailqueue_created|eq(0), '-',  $current_edition_send_object.mailqueue_created|l10n( shortdatetime ) )} ){if $current_edition_send_object.status|eq(1)}</strong>{/if}</li>
            <li>{if $current_edition_send_object.status|eq(2)}<strong>{/if}2 - mailqueue_process_started ( {cond( $current_edition_send_object.mailqueue_process_started|eq(0), '-',  $current_edition_send_object.mailqueue_process_started|l10n( shortdatetime ) )} ){if $current_edition_send_object.status|eq(2)}</strong>{/if}</li>
            <li>{if $current_edition_send_object.status|eq(3)}<strong>{/if}3 - mailqueue_process_finished ( {cond( $current_edition_send_object.mailqueue_process_finished|eq(0), '-',  $current_edition_send_object.mailqueue_process_finished|l10n( shortdatetime ) )} ){if $current_edition_send_object.status|eq(3)}</strong>{/if}</li>
            <li>{if $current_edition_send_object.status|eq(9)}<strong>{/if}9 - mailqueue_process_aborted ( {cond( $current_edition_send_object.mailqueue_process_aborted|eq(0), '-',  $current_edition_send_object.mailqueue_process_aborted|l10n( shortdatetime ) )} ){if $current_edition_send_object.status|eq(9)}</strong>{/if}</li>
        </ul>
    </div>
</section>
{/if}
{undef $current_edition_send_object}
