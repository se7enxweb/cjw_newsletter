{* The state of an approval as a badge, admin4. Same data and actions as design/standard/templates/newsletter/editorial/approval_pill.tpl. Parameter: state (none, pending, approved, rejected, withdrawn). *}
{switch match=$state}
{case match='approved'}<span class="a4-badge is-ok">{'approved'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{case match='pending'}<span class="a4-badge is-warn">{'waiting for approval'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{case match='rejected'}<span class="a4-badge is-bad">{'rejected'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{case match='withdrawn'}<span class="a4-badge">{'replaced'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{case}<span class="a4-badge">{'not asked for yet'|i18n( 'cjw_newsletter/editorial' )}</span>{/case}
{/switch}
