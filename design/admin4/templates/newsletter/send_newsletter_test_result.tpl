{* The result page of a test send of a newsletter edition, admin4. Same data and actions as design/standard/templates/newsletter/send_newsletter_test_result.tpl. *}

{def $newsletter_edition_attribute = $object_version.data_map.newsletter_edition
     $edition_attribute_content = $newsletter_edition_attribute.content
     $list_attribute_content = $newsletter_edition_attribute.content.list_attribute_content
     $email_receiver_test = $list_attribute_content.email_receiver_test}

{if is_unset( $node_id )}
    {def $node_id = $object_version.contentobject.main_node_id}
{/if}

{def $node_url = concat( 'content/view/full/', $node_id )
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
            <h1 class="a4-pagehead-title">{"Test newsletter sent result"|i18n("cjw_newsletter/send")}: {$node_name|wash}</h1>
        </div>
    </div>

    {if is_set( $email_test_send_result_array )}

        {* Newsletter test sent result *}
        <div class="a4-table-wrap">
        <table class="a4-table">
            <thead>
            <tr>
                <th scope="col">{"Nr"|i18n("cjw_newsletter/send")}</th>
                <th scope="col">{"Subject"|i18n("cjw_newsletter/send")}</th>
                <th scope="col">{"Email Sender"|i18n("cjw_newsletter/send")}<br />{"Email Receiver"|i18n("cjw_newsletter/send")}</th>
                <th scope="col">{"Content Type"|i18n("cjw_newsletter/send")}<br />{"Charset"|i18n("cjw_newsletter/send")}<br />{"Transport"|i18n("cjw_newsletter/send")}</th>
            </tr>
            </thead>
            <tbody>
            {def $i=1}
            {foreach $email_test_send_result_array as $result}
                <tr>
                    <td class="a4-num">{$i}.</td>
                    <td>{$result.email_subject|wash}</td>
                    <td>{$result.email_sender|wash}<br />{$result.email_receiver|wash}</td>
                    <td><strong>{$result.email_content_type|wash}</strong><br />{$result.email_charset|wash}<br />{$result.transport_method|wash}</td>
                </tr>
                {set $i=inc($i)}
            {/foreach}
            {undef $i}
            </tbody>
        </table>
        </div>
    {/if}

    <div class="a4-actions">
        <a class="a4-btn" href={$node_url|ezurl}>{"Back"|i18n("cjw_newsletter/send")}</a>
    </div>

</div>
