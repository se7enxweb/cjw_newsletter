{* The page that confirms the abort of a newsletter send out process (statistics, cronjob status), admin4. Same data and actions as design/standard/templates/newsletter/send_abort.tpl. *}

{def $newsletter_edition_attribute = $object_version.data_map.newsletter_edition
     $edition_attribute_content = $newsletter_edition_attribute.content
     $list_attribute_content = $newsletter_edition_attribute.content.list_attribute_content
     $email_receiver_test = $list_attribute_content.email_receiver_test}

{def $node_id = $object_version.contentobject.main_node_id
     $node_url = concat( 'content/view/full/', $node_id )
     $node_name = $object_version.contentobject.main_node.name}

<div class="a4-page newsletter newsletter-send">

    {if and( is_set( $message_warning ), $message_warning|ne('') )}
    <div class="a4-notice is-warn" role="alert">{$message_warning|wash}</div>
    {/if}
    {if and( is_set( $message_feedback ), $message_feedback|ne('') )}
    <div class="a4-notice is-ok" role="status">{$message_feedback|wash}</div>
    {/if}

    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{"Abort newsletter send out process"|i18n( 'cjw_newsletter/send_abort' )}</h1>
        </div>
    </div>

    {if $has_message|not}
    <p>{'Do you really want to abort the send out process?'|i18n( 'cjw_newsletter/send_abort.tpl' )}</p>
    {/if}

    <section class="a4-card" aria-labelledby="cjwnl-a4-abort-statistic">
        <div class="a4-card-body">
            <h2 class="a4-h2" id="cjwnl-a4-abort-statistic">{'Statistics'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</h2>
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
                <tr>
                    <td class="a4-num">{$edition_send_object.id}</td>
                    <td class="a4-num">{$edition_send_object.send_items_statistic.items_count}</td>
                    <td class="a4-num">{$edition_send_object.send_items_statistic.items_send}</td>
                    <td class="a4-num">&nbsp;</td>
                    <td class="a4-num">{$edition_send_object.send_items_statistic.items_not_send}</td>
                    <td class="a4-num">{$edition_send_object.send_items_statistic.items_bounced}</td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>
    </section>

    <section class="a4-card" aria-labelledby="cjwnl-a4-abort-cronjob">
        <div class="a4-card-body">
            <h2 class="a4-h2" id="cjwnl-a4-abort-cronjob">{'Cronjob status'|i18n( 'cjw_newsletter/cjw_newsletter_edition_send_statistic' )}</h2>
            <ul class="a4-plain">
                <li>{if $edition_send_object.status|eq(0)}<strong>{/if}0 - wait_for_process ( {$edition_send_object.created|l10n( shortdatetime )} ){if $edition_send_object.status|eq(0)}</strong>{/if}</li>
                <li>{if $edition_send_object.status|eq(1)}<strong>{/if}1 - mailqueue_created ( {cond( $edition_send_object.mailqueue_created|eq(0), '-',  $edition_send_object.mailqueue_created|l10n( shortdatetime ) )} ){if $edition_send_object.status|eq(1)}</strong>{/if}</li>
                <li>{if $edition_send_object.status|eq(2)}<strong>{/if}2 - mailqueue_process_started ( {cond( $edition_send_object.mailqueue_process_started|eq(0), '-',  $edition_send_object.mailqueue_process_started|l10n( shortdatetime ) )} ){if $edition_send_object.status|eq(2)}</strong>{/if}</li>
                <li>{if $edition_send_object.status|eq(3)}<strong>{/if}3 - mailqueue_process_finished ( {cond( $edition_send_object.mailqueue_process_finished|eq(0), '-',  $edition_send_object.mailqueue_process_finished|l10n( shortdatetime ) )} ){if $edition_send_object.status|eq(3)}</strong>{/if}</li>
                <li>{if $edition_send_object.status|eq(9)}<strong>{/if}9 - mailqueue_process_aborted ( {cond( $edition_send_object.mailqueue_process_aborted|eq(0), '-',  $edition_send_object.mailqueue_process_aborted|l10n( shortdatetime ) )} ){if $edition_send_object.status|eq(9)}</strong>{/if}</li>
            </ul>
        </div>
    </section>

    <form class="a4-form" action={concat( 'newsletter/send_abort/', $edition_send_id)|ezurl()} method="post">
        <div class="a4-actions">
            {if $has_message|not}
            <input class="a4-btn a4-btn-danger" type="submit" name="AbortSendOutButton" value="{"Abort send out process"|i18n("cjw_newsletter/send_abort")}" />
            <input class="a4-btn" type="submit" name="CancelButton" value="{"Cancel"|i18n("cjw_newsletter/send_abort")}" />
            {else}
            <input class="a4-btn" type="submit" name="CancelButton" value="{"Back"|i18n("cjw_newsletter/send_abort")}" />
            {/if}
        </div>
    </form>

</div>
