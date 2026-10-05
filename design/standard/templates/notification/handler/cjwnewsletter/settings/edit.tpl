{* The newsletter card of notification/settings (cjw_newsletter 4.2.0, notification handler cjwnewsletter). The page
   wraps it (media and admin4: a card with its own form and Save button; the old admin design: one form for all).
   It lists the parts of cjw_newsletter.ini [NotificationCardSettings] Parts[]; each area appends its own. The link to the
   e-mail preferences closes the card, after every part.
   Variables: handler (CjwNewsletterHandler: card, parts) *}
{def $card = $handler.card}
<p class="nf-lead">{'The newsletters you receive by e-mail, in which language, and what you are interested in. Whether you get newsletters at all is decided on your e-mail preferences page.'|i18n( 'cjw_newsletter/rendering' )}</p>
{foreach $handler.parts as $cjwnl_part}
{include uri=$cjwnl_part handler=$handler card=$card}
{/foreach}
{if $card.preferences_url}<p class="nf-hint"><a href={$card.preferences_url|ezurl}>{'All your e-mail preferences'|i18n( 'cjw_newsletter/rendering' )}</a></p>{/if}
{undef $card}
