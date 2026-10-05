# cjw_newsletter documentation

cjw_newsletter is the newsletter system of Exponential: newsletter editions are content, visitors subscribe to lists
with a double opt-in, editions go out from the cron in the background, and bounces, the blacklist and the e-mail
preferences of Exponential keep the lists clean.

This folder holds the guides of the extension. The 4.0 manual (`cjw_newsletter_documentation.pdf`, `.odt`) still
describes the content classes, the lists, the editions and the sending; the guides below describe what 4.2.0 adds.
`INSTALL` and `FAQ` in the extension's root folder cover the installation.

## Start here

| Page | For | What it covers |
|---|---|---|
| [Upgrade from 4.1 to 4.2](upgrade-4.2.md) | administrators | The database update for MySQL, PostgreSQL and SQLite, the one-time commands, the defaults, the settings to switch on, the cron parts, the requirements |
| [CHANGELOG](../CHANGELOG) | everyone | What changed in every release |

## The guides of 4.2.0

| Guide | Area | What it covers |
|---|---|---|
| [Deliverability](deliverability.md) | sending | Hard bounces and complaints on the suppression list, retries of soft bounces, rate limits, batches and pauses, test groups, subscribe and unsubscribe by e-mail, the suppression import |
| [Editorial](editorial.md) | editors | Recurring sends (weekdays, weekly, monthly; a copy of a template or the latest edition), article pools and the article picker, the approval of editions through the collaboration inbox |
| [Rendering](rendering.md) | editors, designers | Placeholders, the newsletter condition (a part only some subscribers get), interests and the block "articles for your interests", one edition in several languages, the skins company, news and shop, plain text views, the preview as a subscriber |
| [Statistics](statistics.md) | editors, data protection | Opens and clicks (per person only with consent, anonymous totals otherwise), the report of a send, the dashboard trend, the article box, A/B subject tests, the CSV export, retention and erasure |
| [SMS](sms.md) | administrators | Newsletters by SMS: the transports (file, generic HTTP/JSON, a Twilio-style preset), the mobile number confirmed with a code, STOP, the rate limit |
| [Import, export and migration](importexport.md) | administrators | The CSV import with a column mapping, a preview, a dry run and a consent source; the subscriber export; the migration from the old eznewsletter extension |
| [The schema of 4.2.0](schema-4.2.md) | developers | Every table and column 4.2.0 adds, with its meaning |

## Where the features show

| Page | What 4.2.0 adds there |
|---|---|
| Newsletter dashboard (`newsletter/index`) | A block per area: deliverability (rate limits, batches, bounces, suppressions, test groups, mail-in addresses), editorial (recurring sends, approvals waiting, pools), rendering (skins, languages), statistics (totals, trend, A/B tests), SMS (queue, consents, STOP replies), import and migration runs |
| E-mail preference page (`mailpreferences/settings`, public and admin) | In the row of the newsletter category: the language of the newsletters, the interests per list, the mail-in addresses. The categories "Newsletter statistics" (the consent to counting per person) and "Newsletters by SMS" (with the mobile number and the code) |
| Notification settings (`notification/settings`, public and admin) | The newsletter card: the subscribed lists, the language, the interests, the SMS state, a link to the e-mail preferences |
| The list object | Allowed skins, languages and the main language, the interest source, approval, the article pool, the tracking mode, SMS |
| The send page of an edition | The skin of the send, the tracking of the send, the A/B subject test, the rate-limit transport, the approval state, the article picker, the preview as a subscriber, the SMS send |

## Requirements

- Exponential 6.0.15 or later, with the e-mail preferences (`expMailGate`, the consent log, the suppression list, the
  bounce reader with `[BounceSettings] MessageListeners[]`, the category handlers with `erased()` and the category
  part hook `partTemplate()` / `partVariables()` / `storePart()`). On an older kernel the newsletter still sends; the
  features that need the e-mail preferences stay off.
- PHP 8.0 to 8.5.
- MySQL / MariaDB, PostgreSQL or SQLite (Oracle through the kernel's driver, untested for 4.2.0).

## License

GNU General Public License v2.0 (or any later version), see `LICENSE`.
