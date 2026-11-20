{* The segment part of the send form ([ExtensionPointSettings] SendFormParts[]), admin4. Posts CjwNewsletterSegmentId (0 = the whole
   list), read by CjwNewsletterSegmentHooks::sendFormValidate()/sendFormStored(). Same data and actions as
   design/standard/templates/newsletter/segments/send_form_part.tpl. node_id, object_version *}
{def $i18n = 'cjw_newsletter/segments'
     $options = fetch( 'newsletter', 'segment_send_options', hash( 'object_version', $object_version ) )}
{if $options|count}
<section class="a4-card nl-stat-form" aria-labelledby="cjwnl-a4-send-segment">
<div class="a4-card-body">
    <h2 class="a4-h2" id="cjwnl-a4-send-segment">{'Segment'|i18n( $i18n )}</h2>
    <div class="a4-field">
        <label for="cjwnl-segment-id">{'Send to'|i18n( $i18n )}</label>
        <select class="a4-input" id="cjwnl-segment-id" name="CjwNewsletterSegmentId">
            <option value="0" selected="selected">{'Whole list'|i18n( $i18n )}</option>
            {foreach $options as $option}
            <option value="{$option.id}">{$option.name|wash} ({if $option.few}{'fewer than %minimum'|i18n( $i18n,, hash( '%minimum', $option.minimum ) )}{else}{'%count recipients'|i18n( $i18n,, hash( '%count', $option.count ) )}{/if})</option>
            {/foreach}
        </select>
        <p class="a4-help">{'Only the current members of the segment get this send. The size is the number now; unsubscribes until the send is queued are left out.'|i18n( $i18n )}</p>
    </div>
</div>
</section>
{/if}
{undef $i18n $options}
