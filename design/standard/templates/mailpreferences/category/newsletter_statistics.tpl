{* The part of the category "newsletter_statistics" on the e-mail preference page
   (CjwNewsletterStatisticsCategoryHandler::partTemplate()): what is counted with the consent, what without it, and for
   how long. No field of its own: the category's switch is the consent, logged in the consent log.

   part      hash( retention_months, tracking_enabled, per_person_lists )
   category  the category row of the page (identifier, on, pending ...)
   email, mode *}
{def $i18n = 'cjw_newsletter/statistics'}
<p class="mp-hint">{'When this is on, we count which of our newsletters you open and which of their links you click, under your name, to make the newsletters better. When it is off, your opens and clicks are only counted in totals without a name.'|i18n( $i18n )}</p>
<p class="mp-hint">{'We keep what is counted under your name for %months months; after that only the totals stay. When you switch this off or ask us to erase your data, it is removed at once.'|i18n( $i18n,, hash( '%months', $part.retention_months ) )|wash}</p>
{if $part.tracking_enabled|not}<p class="mp-hint"><span class="mp-badge">{'Nothing is counted at the moment'|i18n( $i18n )}</span></p>{/if}
{undef $i18n}
