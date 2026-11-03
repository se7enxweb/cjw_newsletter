{* The messages left by the last action, admin4: parameter $notices is a list of hashes with type (feedback, warning, error) and text.
   Same data and actions as design/standard/templates/parts/newsletter/notices.tpl *}
{foreach $notices as $notice}
<div class="a4-notice {cond( eq( $notice.type, 'error' ), 'is-bad', eq( $notice.type, 'warning' ), 'is-warn', 'is-ok' )}" role="{if eq( $notice.type, 'error' )}alert{else}status{/if}">{$notice.text|wash}</div>
{/foreach}
