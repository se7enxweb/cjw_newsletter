# Statistics: opens, clicks, A/B subject tests (cjw_newsletter 4.2.0)

cjw_newsletter counts how editions are opened and clicked, shows a report per send, totals and trends on the
dashboard and a box on the admin preview of an article, runs A/B tests of the subject, and exports the numbers as
CSV. Privacy comes first: **nothing is counted per person without that person's consent**, and the consent is the
optional e-mail preference category "Newsletter statistics" of Exponential (6.0.15 and later), off by default.

## 1. What is counted, and for whom

| Tracking mode | Set on | What is counted |
|---|---|---|
| 0 off (the default) | list, send | Nothing. Sent, delivered, bounced and unsubscribes still come from the mail queue and the list. |
| 1 anonymous totals | list, send | Opens (pixel) and clicks per link and per day, without a person. No unique numbers. |
| 2 per person with consent | list, send | For recipients who switched on "Newsletter statistics": their opens and clicks (first open, counts, each click), so unique opens and clicks and rates exist. Everybody else is counted as in mode 1. |

- The site switch `[TrackingSettings] Tracking` is `disabled` in the shipped settings: then nothing is counted at
  all, whatever the lists say. Switch it on in `settings/override/cjw_newsletter.ini.append.php`.
- A list chooses its mode in the list attribute ("Statistics: tracking of the sends"); a send may choose another in
  the send form ("Tracking of this send", default "As the list says"). A send made by a schedule takes the list's mode.
- SMS sends and test sends are never tracked.
- The consent is checked when the mail is made **and again on every open and click**: someone who withdraws it is
  counted anonymously from then on.

## 2. How it works

When the mail queue of a send is made (`ext:cjw_newsletter:queue`, the cronjob part `cjw_newsletter`):

1. Every `http(s)` link of the edition (the HTML and the text part, of the main output and of every language output)
   is stored as a row of `cjwnl_link` and rewritten to `<site>/newsletter/r/<link id>/#_cjwnl_track_#`. Never
   rewritten: `mailto:`, `tel:`, anchors, relative links, links with a placeholder (`#_hash_unsubscribe_#`,
   `[[name]]` ...), the unsubscribe, configure and preference links, and every URL that matches
   `[TrackingSettings] NoTrackPatterns[]`.
2. The open pixel `<site>/newsletter/o/#_cjwnl_track_#` (a 1x1 GIF) is added before `</body>` when
   `[TrackingSettings] OpenPixel=enabled`.
3. For each recipient the placeholder becomes `<key>/<signature>`: `p<send item hash>` with consent in mode 2,
   `a<send id>x<A/B variant id>` otherwise. The signature is an HMAC-SHA-256 of the key under a key derived from the
   site secret of the e-mail preferences (24 hex characters). No address or name is ever in a URL.

`newsletter/o` and `newsletter/r` are public (`site.ini [RoleSettings] PolicyOmitList[]`), need no session and set no
cookie, and work under Velocity:

- The pixel always answers the GIF with `Cache-Control: no-store`; only a valid signature is counted.
- The redirect goes only to the URL stored for the link id when the edition was queued, and only when the key is valid
  and belongs to the same send; else 404. There is no open redirect: no URL of the request is ever followed. A link
  without key (the web archive of an edition shows the links before the recipient's part is filled in) goes to the
  stored URL and is not counted.

What is stored:

| Table | Per person? | Content |
|---|---|---|
| `cjwnl_link` | no | the URL of each link of a send, its total clicks, the content object it shows (article statistics) |
| `cjwnl_stat_total` | no | totals per send, link, type (`open`, `unique_open`, `click`, `unique_click`) and day |
| `cjwnl_ab_test`, `cjwnl_ab_variant` | no | the A/B test and the counts of each variant |
| `cjwnl_open`, `cjwnl_link_click` | **yes**, only with consent | one row per open or click, with the send item |
| `cjwnl_edition_send_item.first_opened`, `open_count`, `click_count` | **yes**, only with consent | |

## 3. Retention, withdrawal and erasure

- Per-person rows are removed after `[TrackingSettings] PersonRetentionMonths` (12): the rows of `cjwnl_open` and
  `cjwnl_link_click` older than that, and the per-person columns of the send items of sends older than that. The
  totals stay. The mail queue does this once a day by itself; by hand:
  `./console ext:cjw_newsletter:statistics --cleanup [--dry-run] [--at=<date>]`.
- **Withdrawal** (the person switches "Newsletter statistics" off, on the page, by a link or by an administrator):
  the category handler removes the person's rows at once.
- **Erasure** (`exp:mail:preferences erase`, the erasure on request, the removal of the account): the kernel calls the
  handler's `erased()`, which removes the person's rows at once.
- As a safety net, every cleanup also removes the rows of people whose consent is no longer on.

## 4. Setup

1. Run the 4.2.0 database update (the tables above).
2. The category is switched on in the extension's `settings/mailpreferences.ini.append.php`
   (`[Category_newsletter_statistics]`, optional, `DefaultOn=false`, handler `CjwNewsletterStatisticsCategoryHandler`).
   It appears on the central preference page (`mailpreferences/settings`) with a short explanation of what is counted.
3. Switch on the site: `[TrackingSettings] Tracking=enabled` in `settings/override/cjw_newsletter.ini.append.php`.
4. Choose a tracking mode per list (list attribute) or per send (send form).
5. Clear the INI cache; under Velocity, deploy (new classes, the new category handler).
6. Say in your privacy notice what is counted and for how long; link the e-mail preference page from the newsletters
   (the mail gate's footer does).

## 5. A/B subject test

In the send form: tick "A/B subject test" and type one or more other subjects (variant A is the edition's own subject).

- Defaults (`[ABTestSettings]`): 2 subjects, 10 % of the list per variant, winner by open rate, after 4 hours. Every
  value can be changed per send (sample per variant, wait in hours, open or click).
- When the queue is made, a random sample per variant gets its subject; the rest of the list waits. The queue sends
  the samples first, then holds the send.
- After the wait the queue chooses the winner: the best open rate (first opens of the people who agreed, per sent mail
  of the variant). In mode 1 (anonymous totals only), or when no opens are known, the click rate decides. Ties go to the
  earlier variant. The rest of the list then gets the winner's subject.
- `newsletter/ab_test/<send id>` shows the variants; an editor with the `send` policy can choose the winner at once or
  cancel the test (the rest then gets the edition's own subject). `--decide` of the command moves the tests on by hand.
- A test needs a tracking mode (1 or 2) and the site switch on.

## 6. Pages

| Page | What |
|---|---|
| `newsletter/index` | dashboard block "Statistics": totals and the weekly trend of the last `[StatisticsSettings] TrendDays`, consent count, lists per mode, last tracked sends, A/B tests running; problems (consent category missing, an A/B test overdue) |
| `newsletter/report` | every send with sent, bounced, opens, clicks, unsubscribes |
| `newsletter/report/<send id>` | the report of a send: sent, delivered, bounced, opens, clicks (unique, rates), unsubscribes (removed from the list between this send and the next), clicks per link, opens and clicks per day, the A/B test |
| `newsletter/ab_test/<send id>` | the A/B test |
| `newsletter/statistics_export[/<send id>][/(type)/links|days]` | CSV: one row per send (all or one), the links of a send, opens and clicks per day. Totals only; cells that a spreadsheet would run as a formula are prefixed with `'` |
| admin preview of a `cjw_newsletter_article` | the box "Newsletter statistics": the editions it went out in, the sends, mails sent and clicks on its links (`[StatisticsSettings] ArticleStatsBox`) |
| `mailpreferences/settings` | the consent "Newsletter statistics" with its explanation |

Template fetches: `fetch( 'newsletter', 'article_statistics', hash( 'contentobject_id', $id ) )`,
`fetch( 'newsletter', 'send_statistics', hash( 'edition_send_id', $id ) )`.

Policies: `newsletter/statistics` for the pages and the export, `newsletter/send` to change an A/B test. The public
views `newsletter/o` and `newsletter/r` are in `PolicyOmitList` (the function `track` stays for sites that remove them
from that list and grant it to the anonymous role instead).

## 7. Settings (`cjw_newsletter.ini`)

| Block | Key | Default | Meaning |
|---|---|---|---|
| `TrackingSettings` | `Tracking` | `disabled` | the site switch |
| | `ConsentCategory` | `newsletter_statistics` | the category whose consent allows per-person counting |
| | `OpenPixel` | `enabled` | add the pixel |
| | `PersonRetentionMonths` | `12` | |
| | `BaseURL` | empty | base of the tracking links; empty = the site URL of the list's main siteaccess |
| | `NoTrackPatterns[]` | none | regular expressions of URLs never rewritten |
| `ABTestSettings` | `SamplePercent`, `Variants`, `Criterion`, `WaitHours` | `10`, `2`, `open`, `4` | defaults of the send form |
| `StatisticsSettings` | `ArticleStatsBox` | `enabled` | the article box |
| | `TrendDays` | `90` | days of the dashboard trend |

## 8. Command

```
./console ext:cjw_newsletter:statistics                summary: site switch, consent category, lists per mode, totals
./console ext:cjw_newsletter:statistics --cleanup      retention cleanup (--dry-run counts only)
./console ext:cjw_newsletter:statistics --decide       move the A/B tests on (winner when the wait is over)
            --at=<date or Unix time>                   act as if it were then
```

## 9. Privacy notes

- No tracking without the site switch and a list or send mode; no per-person tracking without the person's own
  consent, given on the preference page and logged in the kernel's consent log with the text the person saw.
- The anonymous key carries the send and the A/B variant only; the personal key is the random send item hash, which is
  the person's only in this one mail. Signatures prevent forged counts.
- Reports, the dashboard, the article box and the CSV export show totals only, never an address or a name.
- Retention, withdrawal and erasure: section 3. The compliance checklist of Exponential lists these points with their
  tests (`doc/specifications/6.0/mail-preferences-compliance.md`, "Newsletter statistics").
