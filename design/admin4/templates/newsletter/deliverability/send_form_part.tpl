{* The deliverability part of the send form ([ExtensionPointSettings] SendFormParts[]), admin4: how the send will go out (rate limits,
   batches, soft-bounce retries). Nothing to choose; the transport of the newsletter cronjob is kept on the send. Same data
   and actions as design/standard/templates/newsletter/deliverability/send_form_part.tpl. Variables: node_id, object_version. *}
{def $cjwnl_throttle = ezini( 'ThrottleSettings', 'Throttle', 'cjw_newsletter.ini' )
     $cjwnl_transport = ezini( 'NewsletterMailSettings', 'TransportMethodCronjob', 'cjw_newsletter.ini' )
     $cjwnl_retries = ezini( 'DeliverabilitySettings', 'SoftBounceMaxRetries', 'cjw_newsletter.ini' )}
<section class="a4-card nl-deliverability-send" aria-labelledby="cjwnl-a4-send-delivery">
    <div class="a4-card-body">
    <h2 class="a4-h2" id="cjwnl-a4-send-delivery">{'Delivery'|i18n( 'cjw_newsletter/deliverability' )}</h2>
    <p class="a4-help">
        {if eq( $cjwnl_throttle, 'enabled' )}{'The mails go out with "%transport" in batches of %size, within the rate limits of the transport; a send that is stopped goes on with the next run.'|i18n( 'cjw_newsletter/deliverability',, hash( '%transport', $cjwnl_transport, '%size', ezini( 'ThrottleSettings', 'BatchSize', 'cjw_newsletter.ini' ) ) )|wash}
        {else}{'The mails go out with "%transport", without a rate limit.'|i18n( 'cjw_newsletter/deliverability',, hash( '%transport', $cjwnl_transport ) )|wash}{/if}
        {if $cjwnl_retries|gt( 0 )}{'A soft bounce is sent again up to %count times.'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $cjwnl_retries ) )|wash}{/if}
    </p>
    </div>
</section>
{undef $cjwnl_throttle $cjwnl_transport $cjwnl_retries}
