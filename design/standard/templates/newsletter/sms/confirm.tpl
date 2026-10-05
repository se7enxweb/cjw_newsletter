{* newsletter/sms/confirm.tpl: the public page where a subscriber enters the code of their mobile number (area N5)

    state         form, confirmed, already or no_phone
    error, info   a message of the last post, or ''
    user_hash     the subscriber's hash (the page address)
    phone_masked  the number with all but the last four digits hidden
    wording       the consent the code confirms (recorded as shown)
    code_length   digits of the code
    configure_url the subscriber's own newsletter settings
*}
{def $i18n = 'cjw_newsletter/sms'}
<div class="newsletter newsletter-sms_confirm">

    <div class="border-box">
    <div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
    <div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

    <h1>{'Confirm your mobile number'|i18n( $i18n )}</h1>

    {if $error}<div class="message-warning" role="alert"><h2>{$error|wash}</h2></div>{/if}
    {if $info}<div class="message-feedback" role="status"><h2>{$info|wash}</h2></div>{/if}

    {if $state|eq( 'confirmed' )}
    <div class="message-feedback" role="status"><h2>{'Thank you. Your mobile number %phone is confirmed.'|i18n( $i18n,, hash( '%phone', $phone_masked ) )|wash}</h2></div>
    <p>{'You will receive our newsletters by SMS. To stop them, reply STOP to one of them or turn them off in your settings.'|i18n( $i18n )}</p>
    {elseif $state|eq( 'already' )}
    <p class="newsletter-maintext">{'Your mobile number %phone is already confirmed.'|i18n( $i18n,, hash( '%phone', $phone_masked ) )|wash}</p>
    {elseif $state|eq( 'no_phone' )}
    <p class="newsletter-maintext">{'There is no mobile number to confirm. You can add one in your newsletter settings.'|i18n( $i18n )}</p>
    {else}
    <p class="newsletter-maintext">{'We sent a code by SMS to %phone. Enter it here to confirm the number.'|i18n( $i18n,, hash( '%phone', $phone_masked ) )|wash}</p>
    <form action={concat( 'newsletter/sms_confirm/', $user_hash )|ezurl} method="post">
        <div class="block">
            <label for="nl-sms-code">{'Code from the SMS'|i18n( $i18n )}:</label>
            <input class="halfbox" id="nl-sms-code" type="text" name="SmsCode" value="" inputmode="numeric" autocomplete="one-time-code"
                   pattern="[0-9 ]*" maxlength="{sum( $code_length, 4 )}" size="{$code_length}" required="required" aria-describedby="nl-sms-wording" />
        </div>
        <p id="nl-sms-wording">{$wording|wash}</p>
        <div class="buttonblock">
            <input class="button defaultbutton" type="submit" name="SmsConfirmButton" value="{'Confirm'|i18n( $i18n )|wash}" />
            <input class="button" type="submit" name="SmsResendButton" value="{'Send a new code'|i18n( $i18n )|wash}" formnovalidate="formnovalidate" />
        </div>
    </form>
    {/if}

    <p><a href={$configure_url|ezurl}>{'Your newsletter settings'|i18n( $i18n )}</a></p>

    </div></div></div>
    <div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
    </div>

</div>
{undef $i18n}
