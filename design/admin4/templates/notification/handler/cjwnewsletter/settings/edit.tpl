{* The newsletter card of notification/settings, admin4 and admin4l. Same parts as
   design/standard/templates/notification/handler/cjwnewsletter/settings/edit.tpl: admin4's notification/settings.tpl
   draws the card (a4-card, heading, form and Save button) around it. This copy is needed because the admin4 based
   siteaccesses keep design admin in their design list, whose edit.tpl (the fieldset of the classic page, one form
   for every handler) would otherwise be read before design/standard's.
   Variables: handler (CjwNewsletterHandler: card, parts) *}
{def $card = $handler.card}
<p class="a4-help">{'The newsletters you receive by e-mail, in which language, and what you are interested in. Whether you get newsletters at all is decided on your e-mail preferences page.'|i18n( 'cjw_newsletter/rendering' )}</p>
{foreach $handler.parts as $cjwnl_part}
{include uri=$cjwnl_part handler=$handler card=$card}
{/foreach}
{if $card.preferences_url}<p class="a4-help"><a href={$card.preferences_url|ezurl}>{'All your e-mail preferences'|i18n( 'cjw_newsletter/rendering' )}</a></p>{/if}
{undef $card}
