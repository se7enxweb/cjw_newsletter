{* The newsletter card of notification/settings in the old admin design (cjw_newsletter 4.2.0). That design renders
   every handler in one form and each handler brings its own fieldset, like the collaboration handler; the media and
   admin4 designs use design/standard's edit.tpl inside a card of their own. Same parts as there
   (cjw_newsletter.ini [NotificationCardSettings] Parts[]), stored with "Apply changes".
   Variables: handler (CjwNewsletterHandler: card, parts) *}
{def $card = $handler.card}
<div class="block">
<fieldset>
    <legend>{$handler.name|wash}</legend>
    <p>{'The newsletters you receive by e-mail, in which language, and what you are interested in. Whether you get newsletters at all is decided on your e-mail preferences page.'|i18n( 'cjw_newsletter/rendering' )}</p>
{foreach $handler.parts as $cjwnl_part}
{include uri=$cjwnl_part handler=$handler card=$card}
{/foreach}
{if $card.preferences_url}<p><a href={$card.preferences_url|ezurl}>{'All your e-mail preferences'|i18n( 'cjw_newsletter/rendering' )}</a></p>{/if}
</fieldset>
</div>
{undef $card}
