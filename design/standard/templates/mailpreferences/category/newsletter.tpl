{* The newsletter's part of its row on the e-mail preference page (cjw_newsletter 4.2.0, CjwNewsletterMailCategoryHandler::
   partTemplate()): the language of the newsletters, the interests of the lists and their mail-in addresses. It is
   inside the category form and is stored with "Save my choices" (storePart()). The classes are the page's own
   (parts/preferences.tpl), so the media design and admin4 style it alike.
   Variables: part (partVariables()), category, email, mode *}
{def $i18n = 'cjw_newsletter/rendering'
     $field = concat( 'MailPreferencePart[', $part.key, ']' )
     $id = concat( 'mp-', $part.key|wash )}
<input type="hidden" name="{$field}[PartShown]" value="1" />
{if $part.languages|count|gt( 1 )}
<div class="mp-field">
    <label for="{$id}-language">{'Language of the newsletters'|i18n( $i18n )}</label>
    <select id="{$id}-language" name="{$field}[Language]">
        <option value="">{'The main language of each list'|i18n( $i18n )}</option>
{foreach $part.languages as $locale => $language_name}
        <option value="{$locale|wash}"{if $part.language|eq( $locale )} selected="selected"{/if}>{$language_name|wash}</option>
{/foreach}
    </select>
    <p class="mp-hint">{'An edition that has no translation in your language comes in the main language of its list.'|i18n( $i18n )}</p>
</div>
{/if}
{if $part.has_interests}
<div class="mp-field mp-interests" role="group" aria-labelledby="{$id}-interests">
    <span class="mp-label" id="{$id}-interests">{'Your interests'|i18n( $i18n )}</span>
    <p class="mp-hint">{'Newsletters that have a block of articles for your interests fill it from what you pick here. Nothing picked: no such block.'|i18n( $i18n )}</p>
{foreach $part.lists as $list}
{if $list.interests|count|gt( 0 )}
    {if $part.lists|count|gt( 1 )}<p class="mp-hint"><b>{$list.name|wash}</b></p>{/if}
    <div class="mp-freq">
{foreach $list.interests as $interest}
        <label><input type="checkbox" name="{$field}[Interest][]" value="{$interest.id}"{if $interest.checked} checked="checked"{/if} /> {$interest.name|wash}</label>
{/foreach}
    </div>
{/if}
{/foreach}
</div>
{/if}
{def $mailin_count = 0}
{foreach $part.lists as $list}{set $mailin_count = $mailin_count|sum( $list.mailin|count )}{/foreach}
{if $mailin_count|gt( 0 )}
<div class="mp-field">
    <span class="mp-label">{'By e-mail'|i18n( $i18n )}</span>
{foreach $part.lists as $list}
{foreach $list.mailin as $address}
    <p class="mp-hint">{$list.name|wash}: {if $address.action|eq( 'unsubscribe' )}{'to unsubscribe, write to'|i18n( $i18n )}{elseif $address.action|eq( 'subscribe' )}{'to subscribe, write to'|i18n( $i18n )}{else}{'to subscribe or unsubscribe, write to'|i18n( $i18n )}{/if} <a href="mailto:{$address.email|wash}">{$address.email|wash}</a></p>
{/foreach}
{/foreach}
</div>
{/if}
{if and( $part.configure_url, $mode|ne( 'admin' ) )}
<p class="mp-hint"><a href="{$part.configure_url|wash}">{'Formats and lists of your newsletters'|i18n( $i18n )}</a></p>
{/if}
{undef $i18n $field $id $mailin_count}
