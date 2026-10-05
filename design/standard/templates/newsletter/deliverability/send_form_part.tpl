{*  newsletter/deliverability/send_form_part.tpl (cjw_newsletter 4.2.0, area deliverability)

    Part of the send form ([ExtensionPointSettings] SendFormParts[]): how the send will go out (rate limits, batches,
    soft-bounce retries). Nothing to choose; the transport of the newsletter cronjob is kept on the send.
    Variables: node_id, object_version.
*}
{def $cjwnl_throttle = ezini( 'ThrottleSettings', 'Throttle', 'cjw_newsletter.ini' )
     $cjwnl_transport = ezini( 'NewsletterMailSettings', 'TransportMethodCronjob', 'cjw_newsletter.ini' )
     $cjwnl_retries = ezini( 'DeliverabilitySettings', 'SoftBounceMaxRetries', 'cjw_newsletter.ini' )}
<div class="block nl-deliverability-send">
    <label>{'Delivery'|i18n( 'cjw_newsletter/deliverability' )}</label>
    <p class="nl-hint">
        {if eq( $cjwnl_throttle, 'enabled' )}{'The mails go out with "%transport" in batches of %size, within the rate limits of the transport; a send that is stopped goes on with the next run.'|i18n( 'cjw_newsletter/deliverability',, hash( '%transport', $cjwnl_transport, '%size', ezini( 'ThrottleSettings', 'BatchSize', 'cjw_newsletter.ini' ) ) )|wash}
        {else}{'The mails go out with "%transport", without a rate limit.'|i18n( 'cjw_newsletter/deliverability',, hash( '%transport', $cjwnl_transport ) )|wash}{/if}
        {if $cjwnl_retries|gt( 0 )}{'A soft bounce is sent again up to %count times.'|i18n( 'cjw_newsletter/deliverability',, hash( '%count', $cjwnl_retries ) )|wash}{/if}
    </p>
</div>
{undef $cjwnl_throttle $cjwnl_transport $cjwnl_retries}
