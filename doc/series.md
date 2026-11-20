# Welcome series

A welcome series greets a new subscriber of a list with a fixed sequence of mails: a welcome mail right after the
subscription becomes active, a second one a few days later, a third after two weeks. Each list has at most one
series. A series is a list of **steps**; every step is an existing edition of the same list plus a delay in days.

## Steps

A step names an edition object of the list and a delay in days after the previous step. The delay of the first
step counts from the moment the subscriber enters the series. Each run sends the **current published version** of
the edition, so an edition is written, previewed, approved and tested as content, exactly like any other edition.

| Step | Edition | Delay |
|---|---|---|
| 1 | Welcome | 0 days |
| 2 | Getting started | 3 days |
| 3 | Tips | 14 days |

A step whose edition no longer exists, or does not belong to the list, is **broken**: the series page marks it,
and the run skips it and writes a log line.

## Entering the series

A subscriber enters the series when the subscription to the list becomes **approved**, the state in which the list
sends to it: after confirmation on a list that approves confirmed subscriptions automatically, or after an
administrator approves the subscription.

- A subscription that waits for confirmation or for approval does not enter, so no welcome mail is sent yet.
- Entering twice changes nothing. Approving a subscription again that is already in the series leaves the
  progress as it is, and no step is sent twice.
- **Imports do not enter.** Subscriptions that become approved through the CSV import or the mapped import,
  do not enter the series, so importing 500 addresses does not send 500 welcome mails.

## Sending the steps

Each step goes out through the normal send path, so the list's skin, language, tracking setting, approval rules,
privacy rules and statistics apply unchanged. The opens and clicks of a step are counted like those of any edition.

- A step is sent in the first run after its delay has passed since the previous step was sent to that subscriber.
- **At most one step is sent to a subscriber per run**, and each subscriber receives each step at most once.
- The run is part of the cron part that creates the mail queue (`cjw_newsletter_mailqueue_create`); no extra
  cron part is needed.
- If the edition of a step is being sent at that moment, the step waits for the next run. Use dedicated editions
  for the steps (see below).
- Within one run the order is: the due steps make their sends, then the same run creates the mail queue for them.
  The mails themselves go out in the **next send run** (`cjw_newsletter_mailqueue_process`), as for any edition.
  With the cron of the README (every five minutes) that is the next call; a step is therefore at most one cron
  interval late, and a subscriber moves to the next step once the queue for the send has been created.

## When no step is sent

The run looks at the subscription and the series each time, so the rules need no extra state.

| Situation | What happens |
|---|---|
| The subscription is not approved (unsubscribed, removed, blacklisted, soft or hard bounced) | No step is sent. The progress is kept. When the subscription is approved again, the series continues with the next step not yet received, and later steps keep their delays from there |
| The series has an audience and the subscriber is not a member of that segment | No step is sent. The progress is kept; the series continues when the subscriber is a member again |
| The series is paused | No step is sent. After the resume, each subscriber continues with the next step not yet received, one step per run |

Example: a subscriber unsubscribes one day after "Welcome". "Getting started" and "Tips" are not sent. If the
subscriber is approved again a week later, "Welcome" is not sent again, "Getting started" is sent in the next run
and "Tips" 14 days after that.

## Audience

A series may be limited to a [segment](segments.md) of its list. A subscriber then enters only while a member of the
segment, and receives a step only while still a member. A subscription that becomes approved while the subscriber
is not a member sends nothing; the welcome mail follows in the first run after the subscriber becomes a member.

## The page

The series of a list is edited on `newsletter/series_edit/<list object id>`. The page belongs to the policy
function `editorial` of the module `newsletter`, like the segment pages; the Administrator role has it with
`newsletter/*`. It stores the steps, delays and audience, pauses and resumes the series and sends the test mail.

## Pause and resume

A **new series starts paused**, so no mail goes out before an editor resumes it. Subscribers approved in the
meantime have entered the series and receive the first step in the first run after the resume. Pausing keeps all
progress. A series that was paused for three weeks while a subscriber was due for two steps sends the first of them
in the next run and the second one after its own delay.

## Test send

Every step can be sent to the editor's own address with **Send to me**. Only the editor receives the mail; no send
is created and the progress of the subscribers does not change.

## Risks and recommendations

- **Archive.** An edition that was sent as a step appears in the list's public archive like any sent edition. Hide
  it as you hide other editions; there is no filter for series editions yet.
- **Dedicated editions.** Create one edition per step and do not reuse an edition that you also send to the whole
  list. A send of the same edition that is still running delays the step by one run, and changing the published
  version changes what new subscribers receive at that step.

## For developers

### Extension point `subscriptionStatusChanged`

`CjwNewsletterSubscription::store()` calls the extension point `subscriptionStatusChanged( $subscription,
$oldStatus, $bulk )` whenever a subscription is stored with a different status. `$oldStatus` is the stored status
before the change, `null` for a new subscription. The series registers a handler (`CjwNewsletterSeriesHooks`):
a subscription that becomes approved enters the series of its list. Other areas may register a handler in
`[ExtensionPointSettings]` like for the other extension points (see `CjwNewsletterExtensionPoints`).

### Bulk counter

`CjwNewsletterExtensionPoints::beginBulk()` and `endBulk()` count the bulk operations that are running;
`inBulk()` is true while the counter is above 0. The handlers get this as `$bulk`. Both imports (the CSV import
of 4.1 and the import with a column mapping) wrap their run in it, so the nesting is safe and the counter never
goes below 0. An operation that approves many subscriptions at once and must not trigger per-subscription
reactions calls `beginBulk()` before and `endBulk()` in a `finally` block after the run.

The series ignores a subscription that becomes approved inside a bulk operation: nobody receives a welcome mail
because of an import (see [Import, export and migration](importexport.md)).
