<?php /* #?ini charset="utf-8"?

# The bridge to the e-mail preferences of Exponential 6.0.15 and later (older versions do not read this file).

[Category_newsletter]
# The newsletter subscriptions are the state of the category "newsletter" for a person who has made no choice
# on the preference page, and the lists are shown there.
HandlerClass=CjwNewsletterMailCategoryHandler

[SuppressionSettings]
# An address suppressed there goes on the newsletter blacklist, one lifted comes off it; the blacklist tells the
# suppression list the same way.
Listeners[]=CjwNewsletterMailPreferences

# ---- cjw_newsletter 4.2.0: categories of the feature areas, switched on by the area when its code exists.
# Both are optional and off by default (the person opts in on the central preference page).

# N1 Deliverability: the kernel bounce reader hands newsletter bounces and mail-in messages to the newsletter
# Every message the bounce reader of the e-mail preferences reads (exp:mail:bounces, the cronjob part mailbounces) is
# given to CjwNewsletterMailin after the kernel's own work: a bounce of a newsletter mail marks the item and the
# newsletter user (a soft bounce is sent again), a mail to a mail-in address of a list starts a subscription with
# double opt-in or an unsubscribe (cjw_newsletter.ini [MailInSettings]). Nothing is read while the reader is disabled.
[BounceSettings]
MessageListeners[]=CjwNewsletterMailin

# N4 Statistics: consent to per-person open and click counting
[CategorySettings]
Categories[]=newsletter_statistics
[Category_newsletter_statistics]
Name=Newsletter statistics
Description=Count which newsletters I open and which links I click, so the newsletters get better
Essential=false
DefaultOn=false
DoubleOptIn=false
HandlerClass=CjwNewsletterStatisticsCategoryHandler

# N5 SMS: newsletters by SMS, confirmed with a code
# The double opt-in of this category is the code sent by SMS to the number (CjwNewsletterSmsCategoryHandler), not
# the e-mail link of the kernel, so DoubleOptIn stays false. An SMS goes out only when the category is on AND the
# number is confirmed; a STOP reply switches the category off.
[CategorySettings]
Categories[]=sms
[Category_sms]
Name=Newsletters by SMS
Description=Short newsletters to my mobile phone
Essential=false
DefaultOn=false
DoubleOptIn=false
HandlerClass=CjwNewsletterSmsCategoryHandler

*/ ?>
