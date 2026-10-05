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

*/ ?>
