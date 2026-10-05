{* The state of a mobile number as a pill: status 0 none, 1 pending, 2 confirmed, 3 stopped; confirmed = when. *}
{if $status|eq( 1 )}<span class="nl-pill is-warn">{'waiting for the code'|i18n( 'cjw_newsletter/sms' )}</span>
{elseif $status|eq( 2 )}<span class="nl-pill is-ok">{'confirmed'|i18n( 'cjw_newsletter/sms' )}</span>{if $confirmed} <time class="nl-muted">{$confirmed|l10n( 'shortdatetime' )}</time>{/if}
{elseif $status|eq( 3 )}<span class="nl-pill is-bad">{'stopped by SMS'|i18n( 'cjw_newsletter/sms' )}</span>
{else}<span class="nl-pill is-muted">{'not confirmed'|i18n( 'cjw_newsletter/sms' )}</span>{/if}
