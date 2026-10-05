{* The rendering's part of the newsletter card (cjw_newsletter 4.2.0, [NotificationCardSettings] Parts[]): the lists of
   the user, the language of the newsletters and the interests (stored like on the preference page), the link to the
   e-mail preferences. Uses the classes of the notification cards (nf-*), so media and admin4 draw it alike.
   Variables: handler, card (CjwNewsletterHandler::card()) *}
{def $i18n = 'cjw_newsletter/rendering'
     $part = $card.part}
{if $card.available|not}
<p class="nf-lead">{'Sign in to see your newsletters.'|i18n( $i18n )}</p>
{elseif $part.lists|count|eq( 0 )}
<p class="nf-lead">{'You do not receive any newsletter.'|i18n( $i18n )} <a href={$card.subscribe_url|ezurl}>{'Subscribe to a newsletter'|i18n( $i18n )}</a></p>
{else}
{if $card.category_on|eq( false() )}
<p class="nf-lead"><b>{'Newsletters are switched off on your e-mail preferences page, so none of these is sent.'|i18n( $i18n )}</b></p>
{/if}
<ul class="nf-list cjwnl-card-lists">
{foreach $part.lists as $list}
    <li class="nf-row"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm1 2.2V17h16V7.2l-8 5.3-8-5.3zM5.6 7l6.4 4.2L18.4 7H5.6z"/></svg>
        <div><div class="nf-title">{$list.name|wash} <span class="nf-badge{if $list.active} ok{/if}">{$list.status|wash}</span></div>
        {if $list.interests|count|gt( 0 )}<div class="nf-meta">{'%count interests offered'|i18n( $i18n,, hash( '%count', $list.interests|count ) )}</div>{/if}</div></li>
{/foreach}
</ul>
<input type="hidden" name="MailPreferencePart[{$part.key|wash}][PartShown]" value="1" />
{if $part.languages|count|gt( 1 )}
<div class="nf-field">
    <label for="nf-cjwnl-language">{'Language of the newsletters'|i18n( $i18n )}</label>
    <select id="nf-cjwnl-language" name="MailPreferencePart[{$part.key|wash}][Language]">
        <option value="">{'The main language of each list'|i18n( $i18n )}</option>
{foreach $part.languages as $locale => $language_name}
        <option value="{$locale|wash}"{if $part.language|eq( $locale )} selected="selected"{/if}>{$language_name|wash}</option>
{/foreach}
    </select>
</div>
{/if}
{if $part.has_interests}
<fieldset class="nf-field cjwnl-card-interests">
    <legend>{'Your interests'|i18n( $i18n )}</legend>
{foreach $part.lists as $list}
{foreach $list.interests as $interest}
    <label class="nf-toggle"><input type="checkbox" name="MailPreferencePart[{$part.key|wash}][Interest][]" value="{$interest.id}"{if $interest.checked} checked="checked"{/if} /><div><b>{$interest.name|wash}</b></div></label>
{/foreach}
{/foreach}
</fieldset>
{/if}
{/if}
<p class="nf-lead"><a href={$card.preferences_url|ezurl}>{'All your e-mail preferences'|i18n( $i18n )}</a></p>
{undef $i18n $part}
