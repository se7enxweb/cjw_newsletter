{* The part of the category "sms" on the e-mail preference page (CjwNewsletterSmsCategoryHandler::partTemplate()):
   the mobile number, its state and the field for the confirmation code. Included inside the category form of
   mailpreferences/parts/preferences.tpl, so the fields are saved with "Save my choices".

   part      hash( enabled, has_user, phone, phone_masked, status: none|pending|confirmed|stopped, confirmed, code_waiting,
             code_expires, wording, stop_keyword, code_length, mode )
   category  the category row of the page (identifier, on, pending ...)
   email, mode *}
{def $i18n = 'cjw_newsletter/sms'
     $name = concat( 'MailPreferencePart[', $category.identifier|wash, ']' )
     $field_id = concat( 'mp-', $category.identifier|wash, '-phone' )}
{if $part.enabled|not}
<p class="mp-hint">{'Newsletters by SMS are not offered at the moment.'|i18n( $i18n )}</p>
{elseif $part.has_user|not}
<p class="mp-hint">{'Newsletters by SMS go to the subscribers of our newsletters. Subscribe to a newsletter first; then you can add your mobile number here.'|i18n( $i18n )}</p>
{else}
{if $part.code_error}<p class="mp-hint" role="alert"><b>{$part.code_error|wash}</b></p>
{elseif $part.code_sent}<p class="mp-hint" role="status"><b>{'We sent a code to %phone.'|i18n( $i18n,, hash( '%phone', $part.phone_masked ) )|wash}</b></p>{/if}
<div class="mp-field">
    <label for="{$field_id}">{'Mobile number'|i18n( $i18n )}
    {if $part.status|eq( 'confirmed' )}<span class="mp-badge on">{'Confirmed'|i18n( $i18n )}</span>
    {elseif $part.status|eq( 'pending' )}<span class="mp-badge pending">{'Waiting for the code'|i18n( $i18n )}</span>
    {elseif $part.status|eq( 'stopped' )}<span class="mp-badge">{'Stopped by SMS'|i18n( $i18n )}</span>{/if}</label>
    <input type="text" id="{$field_id}" name="{$name}[Phone]" value="{$part.phone|wash}" inputmode="tel" autocomplete="tel" maxlength="40"
           placeholder="+49 151 23456789" aria-describedby="{$field_id}-hint" />
    <p class="mp-hint" id="{$field_id}-hint">{'With the country code. Leave the field empty to remove the number.'|i18n( $i18n )}</p>
</div>
{if $part.status|eq( 'pending' )}
<div class="mp-field">
    <label for="{$field_id}-code">{'Code from the SMS'|i18n( $i18n )}</label>
    <input type="text" id="{$field_id}-code" name="{$name}[Code]" value="" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*"
           maxlength="{sum( $part.code_length, 4 )}" aria-describedby="{$field_id}-code-hint" />
    <p class="mp-hint" id="{$field_id}-code-hint">{if $part.code_waiting}{'We sent a code to %phone. Enter it and save to confirm the number.'|i18n( $i18n,, hash( '%phone', $part.phone_masked ) )|wash}
    {else}{'The code has expired.'|i18n( $i18n )}{/if}
    {$part.wording|wash}</p>
    <label><input type="checkbox" name="{$name}[Resend]" value="1" /> {'Send me a new code'|i18n( $i18n )}</label>
</div>
{elseif $part.status|eq( 'confirmed' )}
<p class="mp-hint">{'SMS go to this number while this is on. Reply %keyword to any of them to stop them.'|i18n( $i18n,, hash( '%keyword', $part.stop_keyword ) )|wash}</p>
{elseif $part.status|eq( 'stopped' )}
<p class="mp-hint">{'You stopped the SMS with a reply. Turn this on and save to get a new code and start again.'|i18n( $i18n )}</p>
{else}
<p class="mp-hint">{'When this is on and you save, we send a code to the number. The SMS start once you enter the code.'|i18n( $i18n )}</p>
{/if}
{/if}
{undef $i18n $name $field_id}
