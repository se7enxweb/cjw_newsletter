{* The state of an approval as a pill. Parameter: state (none, pending, approved, rejected, withdrawn). *}
{switch match=$state}
{case match='approved'}<span class="nl-pill is-ok">{'approved'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{case match='pending'}<span class="nl-pill is-warn">{'waiting for approval'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{case match='rejected'}<span class="nl-pill is-bad">{'rejected'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{case match='withdrawn'}<span class="nl-pill is-muted">{'replaced'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{case}<span class="nl-pill is-muted">{'not asked for yet'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{/switch}
