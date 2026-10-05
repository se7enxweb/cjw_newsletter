{*?template charset=utf-8?*}{set-block variable=$subject scope=root}{cond( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' )|ne( '' ), ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' ), concat( '[Newsletter ', ezini( 'SiteSettings', 'SiteURL', 'site.ini' )|explode( '/' )|extract( 0, 1 )|implode( '' ), ']' ) )} {'Your unsubscribe request'|i18n( 'cjw_newsletter/deliverability' )}{/set-block}
{*  newsletter/deliverability/mail/mailin_unsubscribe.tpl (cjw_newsletter 4.2.0, area deliverability)

    The answer to an unsubscribe mail that did not prove the address: the unsubscribe links of the subscriptions.
    Variables: newsletter_user, links (hash( name, url )). Plain text.
*}
{def $cjwnl_lines = ''}
{foreach $links as $link}{set $cjwnl_lines = concat( $cjwnl_lines, "\n- ", $link.name, ":\n  ", $link.url )}{/foreach}
{'Hello,

we received a request by e-mail to unsubscribe this address from our newsletter. To unsubscribe, open the link of the newsletter:
%links

If you did not send this request, ignore this mail: nothing changes.'|i18n( 'cjw_newsletter/deliverability',, hash( '%links', $cjwnl_lines ) )}
{include uri="design:newsletter/mail/footer.tpl"}
