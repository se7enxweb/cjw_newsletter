{* The SMS part of the newsletter card on the notification settings pages (cjw_newsletter 4.2.0, area N5,
   [NotificationCardSettings] Parts[]): the mobile number and its state, and the link to the e-mail preferences,
   where the number is changed and confirmed. Uses the classes of the notification cards (nf-*).
   Variables: handler, card (CjwNewsletterHandler::card(): newsletter_user, preferences_url) *}
{if and( ezini( 'SmsSettings', 'Sms', 'cjw_newsletter.ini' )|eq( 'enabled' ), $card.available, $card.newsletter_user )}
{def $i18n = 'cjw_newsletter/sms'
     $user = $card.newsletter_user
     $phone = cond( is_set( $user.phone_number ), $user.phone_number, '' )
     $status = cond( is_set( $user.phone_status ), $user.phone_status, 0 )}
<ul class="nf-list cjwnl-card-sms">
    <li class="nf-row"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v14h10V4H7zm5 15.2a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
        <div><div class="nf-title">{'Newsletters by SMS'|i18n( $i18n )}
        {if $phone|eq( '' )}<span class="nf-badge">{'no number'|i18n( $i18n )}</span>
        {elseif $status|eq( 2 )}<span class="nf-badge ok">{'confirmed'|i18n( $i18n )}</span>
        {elseif $status|eq( 1 )}<span class="nf-badge">{'waiting for the code'|i18n( $i18n )}</span>
        {elseif $status|eq( 3 )}<span class="nf-badge">{'stopped by SMS'|i18n( $i18n )}</span>
        {else}<span class="nf-badge">{'not confirmed'|i18n( $i18n )}</span>{/if}</div>
        <div class="nf-meta">{if $phone}{concat( $phone|extract_left( 3 ), ' ••• ', $phone|extract_right( 4 ) )|wash} · {/if}<a href={$card.preferences_url|ezurl}>{'Number and consent on your e-mail preferences page'|i18n( $i18n )}</a></div></div></li>
</ul>
{undef $i18n $user $phone $status}
{/if}
