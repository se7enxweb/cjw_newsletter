{* The state of a mobile number as a badge in admin4: status 0 none, 1 pending, 2 confirmed, 3 stopped; confirmed = when.
   Same data as design/standard/templates/newsletter/sms/phone_status.tpl *}
{if $status|eq( 1 )}<span class="a4-badge is-warn">{'waiting for the code'|i18n( 'cjw_newsletter/sms' )}</span>
{elseif $status|eq( 2 )}<span class="a4-badge is-ok">{'confirmed'|i18n( 'cjw_newsletter/sms' )}</span>{if $confirmed} <time class="a4-meta">{$confirmed|l10n( 'shortdatetime' )}</time>{/if}
{elseif $status|eq( 3 )}<span class="a4-badge is-bad">{'stopped by SMS'|i18n( 'cjw_newsletter/sms' )}</span>
{else}<span class="a4-badge">{'not confirmed'|i18n( 'cjw_newsletter/sms' )}</span>{/if}
