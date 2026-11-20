# Segments

A segment is a saved selection of the subscribers of **one list**: a name, a match mode and a set of conditions.
It is evaluated on demand, so it always reflects the current subscriptions. Segments are stored in the table
`cjwnl_segment` (conditions as JSON).

Segments are the recommended way to select recipients for new work. Virtual lists stay as they are: they are list
objects with their own sends and an installation-specific external filter, and they keep working unchanged.

How a segment is chosen for a send and how the pages look is described in the sections added with those features.

## Members

The members of a segment are the **approved subscriptions** of its list that match the conditions. Pending,
removed and blacklisted subscriptions are never members. Members are computed when they are needed (for a send:
when the mail queue is made) and are then fixed by the queue items; changing the segment later does not change a
queue that already exists.

## Match mode

| Mode | Meaning |
|---|---|
| `all` | A subscription must match every condition (intersection) |
| `any` | A subscription must match at least one condition (union) |

A segment without conditions, with an unknown match mode, or with an unknown or invalid condition has no members.
An invalid condition is never silently ignored.

## Condition types

Every condition is an object with the key `type` and the parameters of that type. Integer parameters must be
whole numbers of 1 or more.

| Type | Parameters | Matches |
|---|---|---|
| `subscribed_within` | `field` (`created` or `approved`), `days` | The subscription was created or approved within the last `days` days |
| `language` | `values` (list of language codes) | The subscriber's language is one of the values |
| `output_format` | `format` (`html` or `text`) | The subscription receives that output format |
| `salutation` | `values` (list of 0, 1, 2) | The subscriber's salutation is one of the values |
| `profile` | `field`, `op`, `value` | A profile field test, see below |
| `interest` | `interest_id`, `picked` (bool, default true) | The subscriber has (`true`) or has not (`false`) picked that interest of this list |
| `opened` | `window` (`sends` or `days`), `n` | At least one known send in the window was opened |
| `clicked` | `window` (`sends` or `days`), `n` | At least one known send in the window had a click |
| `not_opened` | `n` | At least `n` known sends, and none of the last `n` was opened |
| `not_clicked` | `n` | At least `n` known sends, and none of the last `n` had a click |
| `clicked_object` | `contentobject_id` | A click on a link to that content object in a known send |
| `clicked_tag` | `tag_id` | As `clicked_object`, for content objects carrying that eZ Tags tag |

**`profile`.** `field` is one of `first_name`, `last_name`, `organisation`, `custom_data_text_1` to
`custom_data_text_4`. `op` is one of:

- `set`: the field is not empty.
- `not_set`: the field is empty or missing.
- `equals`: the field equals `value` (a non-empty string).
- `contains`: the field contains `value` (a non-empty string); `%` and `_` in the value are taken literally.

`value` is required for `equals` and `contains` only.

**`window`.** For `opened` and `clicked`, `window` `sends` looks at the last `n` known sends of the subscriber
(fewer if the subscriber has fewer), and `days` looks at the known sends of the last `n` days. `not_opened` and
`not_clicked` always look at the last `n` sends.

**`clicked_tag`** is offered only when the table `eztags_attribute_link` exists (the eZ Tags extension is
installed). Without it the condition is rejected.

## Behavior conditions: consent and known sends

The six behavior types (`opened`, `clicked`, `not_opened`, `not_clicked`, `clicked_object`, `clicked_tag`) read
the send history. Two rules keep them honest.

**Consent rule.** A behavior condition only matches subscribers who have given tracking consent **now**, as
decided by the tracking consent check of the package. Someone who withdrew consent matches no behavior condition,
whatever the history says.

**Known-send rule.** Only *known sends* count. A send to a subscriber is known when all of these hold:

- the item was sent (status sent), in an e-mail send of this list that is not a test send;
- the send is not older than the retention cutoff (`[TrackingSettings] PersonRetentionMonths`, default 12);
- the item was tracked **per person** for the signal in question. The item stores a `person_tracking` mark: one
  bit when its links were tracked per person (clicks), one bit when the HTML body carried the open pixel (opens).
  Sends with only anonymous tracking carry no mark.

Everything else is unknown and is neither "opened" nor "not opened". Consequences:

- **Sends made before the upgrade to schema 4.3.0 are unknown**, because their items have no `person_tracking`
  mark. Behavior conditions start to see data with the first send after the upgrade.
- A new subscriber with fewer than `n` known sends does not match `not_opened` or `not_clicked`.
- Sends older than the retention cutoff drop out; when a subscriber's counters are forgotten or expire, the mark is
  reset together with them, so a subscriber who withdraws and consents again is not counted as "not opened".

## Sending to a segment

The send form of an edition has a select **Send to**: "Whole list" or one of the valid segments of the list, each
with its size (a size below `[SegmentSettings] MinimumVisibleCount` is shown as "fewer than N" to users who may not
see subscribers). The choice is stored as `segment_id` on the send. Without a choice, or with "Whole list", the send
is unchanged. A segment of another list, or an invalid one, is refused when the form is saved. The choice applies to mail sends only, not to SMS sends.

The members are evaluated when the send is queued, not when the form is saved, so a segment edited after queuing
applies to mails not yet created. A segment that was deleted or became invalid before queuing gives the send no
recipients, and the reason is written to the newsletter log.

### The `sendRecipients` extension point

`CjwNewsletterEditionSend::getSubscriptionObjectArray()` passes its result through
`CjwNewsletterExtensionPoints::filter( 'sendRecipients', $subscriptions, array( $send ) )`. A handler registered in
`[ExtensionPointSettings] Handlers[]` that has a static `sendRecipients( $subscriptions, $send )` method can narrow the
approved subscriptions of the send and must return the same kind of list (an empty array for no recipients). The
segments use this point (`CjwNewsletterSegmentHooks`); other handlers run in the order of the setting. The filter runs after `$limit`/`$offset`, so a caller that pages
through the recipients can get a short page; the queue runner reads them in one piece and is not affected.

## Segment pages

| URL | Page |
|---|---|
| `/newsletter/segment_list/<list id>` | The segments of the list: name, match mode, number of conditions, last change |
| `/newsletter/segment_edit/<id>/<list id>` | Create (id `0`), edit, size-check or remove a segment of that list |

The edit page has a name, the match mode and one row per condition (type and its parameters). Buttons:

- **Save** validates the form and saves the segment. A name and at least one valid condition are required; an
  invalid row is marked with its error and nothing is saved.
- **Show size** evaluates the posted conditions without saving and shows the number of members, plus how many of
  them have not given tracking consent (behavior conditions do not match those). Below `[SegmentSettings]
  MinimumVisibleCount` only "fewer than N" is shown.
- **Add a condition** and **Remove this condition** (per row) change the rows of the form only.
- **Remove the segment** deletes the segment, but is refused while a send that is waiting or scheduled uses it.
- **Cancel** returns to the list.

**Permissions.** Both pages need the policy function `newsletter/editorial`. The sample of members in the size check
(e-mail, name, subscription date) is a list of personal data and is shown only to users who also have
`newsletter/subscription_list`; others see the size only.

## Performance

Each condition is evaluated on its own, with one query for the non-behavior types and a batched read for the
behavior types, and the results are combined in memory. Behavior conditions read the known items of the list in
batches (the batch size is internal: default 5000, a constructor parameter of the evaluator) and keep only a few counters per subscriber, so memory
grows with the number of subscribers, not with the number of items. Prefer a few precise conditions over many
overlapping ones.
