{* The page that confirms and schedules the send of a newsletter edition, admin4. Same data and actions as design/standard/templates/newsletter/send.tpl. *}

{if is_unset( $node_id )}
    {def $node_id = $object_version.contentobject.main_node_id}
{/if}

{def $newsletter_edition_attribute = $object_version.data_map.newsletter_edition
     $edition_attribute_content = $newsletter_edition_attribute.content
     $list_attribute_content = $newsletter_edition_attribute.content.list_attribute_content
     $email_receiver_test = $list_attribute_content.email_receiver_test}

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
            <h1 class="a4-pagehead-title">{"Send out newsletter"|i18n("cjw_newsletter/send")} - {$node_name|wash}</h1>
        </div>
    </div>

    {if $has_message|not}
    <p>{'Do you really want to send out this newsletter?'|i18n( 'cjw_newsletter/send.tpl' )}</p>
    {/if}

    <form class="a4-form" action={'newsletter/send'|ezurl()} method="post">
{def $itsData = hash(
 'id', 'noid',
 'contentclassattribute_id', 'noid',
 'contentclass_attribute_identifier', 'schedule',
 'content',
 hash(   'is_valid', true(),
            'year', currentdate()|datetime(custom, '%Y'),
            'month', currentdate()|datetime(custom, '%m'),
            'day', currentdate()|datetime(custom, '%d'),
            'hour', currentdate()|datetime(custom, '%H'),
            'minute', currentdate()|datetime(custom, '%i')))}

        {include uri='design:content/datatype/edit/ezdatetime.tpl' attribute_base='CJWNL' attribute=$itsData}

        {* the parts the feature areas add to the send form ([ExtensionPointSettings] SendFormParts[]) *}
        {foreach $send_form_parts as $cjwnl_part}{include uri=$cjwnl_part node_id=$node_id object_version=first_set( $object_version, false() )}{/foreach}

        <input type="hidden" name="TopLevelNode" value="{$node_id}" />
        <input type="hidden" name="ContentNodeID" value="{$node_id}" />
        <input type="hidden" name="ContentObjectID" value="{$node_id}" />
        <input type="hidden" name="mail_newsletter" value="true" />
        <input type="hidden" name="SendOutConfirmationInput" value="true" />

        <div class="a4-actions">
            <input class="a4-btn a4-btn-primary" type="submit" name="SendNewsletterButton" value="{"Send Newsletter"|i18n("cjw_newsletter/send")}" />
            <a class="a4-btn" href={$node_url|ezurl}>{"Cancel"|i18n("cjw_newsletter/send")}</a>
        </div>
    </form>

</div>
