# Editorial: recurring sends, article pools, the article picker, the approval of editions

cjw_newsletter 4.2.0. This page covers the work of the editors: newsletters that go out on their own on a schedule,
the pools that the articles of an edition come from, and the optional approval of an edition before it is sent.

| Feature | Where | Settings |
|---|---|---|
| Recurring sends (chosen weekdays, weekly, monthly) | Newsletter > Recurring sends (`newsletter/schedule_list`, `newsletter/schedule_edit/<id>`) | `cjw_newsletter.ini [ScheduleSettings]` |
| Article pools, per list and a global default | Newsletter > Article pools (`newsletter/article_pool_list`, `newsletter/article_pool_edit/<id>`) | `[ArticlePoolSettings]` |
| The article picker of an edition | send page of an edition > "Pick articles" (`newsletter/article_pool/<edition node>`) | the pool of the list |
| Approval of editions through the collaboration inbox | the list attribute ("Approval"), `newsletter/approval/<edition>/<version>`, Collaboration | `[ApprovalSettings]`, `collaboration.ini` |

The admin pages need the policy function `newsletter/editorial`. Deciding an approval needs `newsletter/approve`.
The approval page opens for either one. On the dashboard (`newsletter/index`), the block "Editorial" shows the
recurring sends with their next runs, the editions that wait for an approval, and the pools.

## 1. Recurring sends

A recurring send (table `cjwnl_schedule`) belongs to a newsletter list and runs at a time of day in a time zone:

- **On chosen weekdays** (type `d`, e.g. Monday, Wednesday and Friday), **weekly** on one weekday (`w`), or
  **monthly** on a day of the month (`m`).
- A day beyond the end of a month means the month's last day: the 31st is the 30th in April and the 28th or 29th in
  February. The run never moves into the next month.
- The time zone is the schedule's own, else `[ScheduleSettings] DefaultTimezone`, else PHP's `date.timezone`.
- When the clocks change, a send time that does not exist that night (02:30 in spring in Central Europe) moves
  forward by the change. A time that exists twice runs once.
- The next run is computed from the time of a run. Runs missed while the cron was down are not made up.

Each schedule has one of two modes:

- **latest** sends the newest edition of the list that was never sent: there is no send of any version of it.
  If there is none, the run is logged as `skipped_empty`.
- **copy** copies a template edition under the list, with its newsletter articles, and sends the copy.
  - The copy's title is `[ScheduleSettings] CopyTitle`, by default `%title %date`.
  - With **auto-fill**, the copy is filled from the article pool (the schedule's own pool, else the list's) with
    content published since the schedule's last send. Only content the anonymous user may read is taken, because a
    newsletter is public. Articles that earlier copies of the schedule carried are left out.
  - With **skip if empty** (the default) and nothing new, no copy is made and the run is logged as `skipped_empty`.
    Without it, the copy goes out with the template's own articles only.

An optional **condition** is asked before each run.
- It is a class that implements `CjwNewsletterScheduleConditionInterface` (`isMet( $schedule, $now )`, `name()`) and is
  listed in `[ScheduleSettings] ConditionHandlers[]`; a class that is not listed is never called.
- The extension ships `CjwNewsletterScheduleConditionNewArticles`: the run goes ahead only when the pool has new
  content, in either mode.
- A condition that is not met logs `skipped_condition`.

### When it runs

- The cronjob part `cjw_newsletter_mailqueue_create` runs the due schedules first, through the extension point
  `queueCreateBefore`, when `[ScheduleSettings] Schedules=enabled` (the default). The sends it makes get their mail
  queue in the same run, and `cjw_newsletter_mailqueue_process` sends them.
- If the list needs an approval and the edition is not approved, the run asks for the approval and the send waits
  (see 3).
- One run at a time (lock `schedule`). At most `MaxRunsPerCall` schedules per run.
- Each run is a row of `cjwnl_schedule_log`, with one of these results: `sent`, `skipped_empty`,
  `skipped_condition` or `failed` (with the error).
- Every send that a schedule made has its id in `cjwnl_edition_send.schedule_id`.
- The other feature areas set their values of the send (skin, tracking, throttling, channel) as for a send of the
  form without choices, through `sendFormStored`: the list's defaults.

On the page Recurring sends:
- **Try** says what a run would do now and changes nothing.
- **Run now** runs a schedule at once, whether it is due or not.
- **Pause** keeps the schedule and stops its runs; **Resume** computes the next run from now.
- **Remove** removes the schedule and its log. The editions and sends it made stay.

### Command

```
./console ext:cjw_newsletter:schedule list                 # the schedules, their next and last runs
./console ext:cjw_newsletter:schedule run                  # run the due schedules
./console ext:cjw_newsletter:schedule run --dry-run        # say what would happen, write nothing
./console ext:cjw_newsletter:schedule run --id=3 --force   # run schedule 3 now, due or not
./console ext:cjw_newsletter:schedule run --at="2026-10-12 08:00" --dry-run   # as if it were that time (tests)
```

(`php extension/cjw_newsletter/bin/php/schedule.php ...` is the same.) Exit codes: 0 success, 1 a failed run or
another run is active, 2 a bad action or `--at`. The run records itself on the dashboard and in the audit log as
`system.cjw_newsletter.schedule`.

## 2. Article pools and the picker

A pool (table `cjwnl_article_pool`) says where the articles for editions come from:

- the nodes searched under (the whole subtree of each), and the content classes;
- optional filters: sections, eztags tags (an article needs one of them), object states, and the age in days;
- how many articles the auto-fill takes, and in which order (newest, last changed, priority or name).

A list uses:

1. the pool named in its list attribute ("Article pool");
2. else the pool made for the list (the field "For" of the pool);
3. else the global default pool (a global pool with "default");
4. else a pool built from `[ArticlePoolSettings]`: `DefaultParentNodes[]` (empty = the content root),
   `DefaultClassIdentifiers[]`, `DefaultMaxItems` and `DefaultMaxAgeDays`.

**"Show the articles"** on the pool form lists what the pool finds now.

**The picker** (`newsletter/article_pool/<edition node>`, "Pick articles" on the send page) lists the articles of the
pool of the edition's list.
- Editors filter by class, published from/to, section, tag and state. The filters are view parameters, and each one
  is checked and cast before it is used.
- **Take the chosen articles into the edition** makes a newsletter article (`cjw_newsletter_article`) under the
  edition for each article. It carries the content's title, its intro (the first attribute of
  `[ArticlePoolSettings] IntroAttributes[]` with content) and a "Read more" link to the content. Every skin shows it
  as it shows the articles an editor writes, and the editor can change its text ("Edit the text").
- The pick itself is a row of `cjwnl_edition_article`: the content object id of the source, the pool, the position,
  and who added it (0 an editor, 1 the auto-fill, 2 the interests block of the rendering). The article statistics
  read these rows. The newsletter article has the remote id `cjwnl-pick-<edition>-<source>`.
- **Take out** removes both. An edition that is being sent, or was sent, cannot change.
- A change of the picks replaces an approval of the edition: it must be approved again.

### For developers

```php
$pool  = CjwNewsletterArticlePool::forList( $listObjectId );          // never null
$nodes = CjwNewsletterArticlePoolFinder::find( $pool, array(
    'since' => $time, 'until' => $time, 'limit' => 5, 'offset' => 0,
    'tag_ids' => array( 12 ), 'language' => 'ger-DE', 'exclude_object_ids' => array( 99 ),
    'class_identifiers' => array( 'article' ), 'section_ids' => array( 1 ), 'state_ids' => array( 1 ),
    'sort_by' => 'published', 'anonymous' => true ) );               // eZContentObjectTreeNode[] (main nodes)
$count = CjwNewsletterArticlePoolFinder::count( $pool, $options );
CjwNewsletterEditionBuilder::addArticle( $editionObject, $node, CjwNewsletterEditionArticle::ADDED_BY_EDITOR, $poolId );
CjwNewsletterEditionBuilder::removeArticle( $editionObjectId, $sourceObjectId );
```

How the finder works:
- Only content fetch filters are used, the parameters of `fetch( content, tree )`. Nothing is written into SQL by
  hand, and every value is cast first.
- The options only narrow the pool: a class or section outside the pool finds nothing.
- `tag_ids` needs eztags, and without it the result is empty.
- `anonymous` restricts to what the anonymous user may read. Use it for anything that goes into a mail.

Template fetch functions:
- `fetch( 'newsletter', 'edition_articles', hash( 'edition_contentobject_id', $id ) )`: the picks the current user
  may read.
- `fetch( 'newsletter', 'approval_state', hash( 'edition_contentobject_id', $id, 'version', $v ) )`
- `fetch( 'newsletter', 'list_article_pool', hash( 'list_contentobject_id', $id ) )`
- `fetch( 'newsletter', 'article_pool_list', hash() )`
- `fetch( 'newsletter', 'schedule_list', hash( 'list_contentobject_id', $id ) )`

## 3. Approval of editions

Switch it on per list in the list attribute: **Approval: yes**, column `cjwnl_list.approval_required`. From then on:

- The send form refuses to send an edition version that is not approved.
- The send part of the form shows the state, with a link to the approval page.
- The queue holds every send of such a version (extension point `sendProcessAllowed`), whether it was made by hand or
  by a recurring send, until the version is approved.

The flow:

1. An editor asks for the approval on `newsletter/approval/<edition>/<version>` ("Ask for approval", with a message).
   A recurring send asks for it itself.
2. The request (table `cjwnl_approval`) becomes an item of the **collaboration inbox** (handler
   `cjwnewsletterapproval`) for the approvers:
   - the users of `[ApprovalSettings] ApproverUserIds[]`;
   - the members of the user groups `ApproverGroupIds[]` (by default 12, the Administrator users of a standard
     installation);
   - never the one who asked.
   With `Notification=enabled`, approvers who subscribed to collaboration notifications get a mail.
3. A user with the policy `newsletter/approve` approves or rejects it, with a comment. He can do that in the inbox
   (admin4 and the classic admin have their own item page) or on the approval page. Nobody decides his own request
   unless `AllowSelfApproval=enabled`.
4. After a rejection, the editor changes the edition and asks again. A new request, or a change of the picks,
   replaces the earlier one (state "replaced").

The approval is per **version**: a new version of the edition needs its own approval.

## 4. Settings

`cjw_newsletter.ini`, block N2 Editorial:

| Setting | Default | Meaning |
|---|---|---|
| `[ScheduleSettings] Schedules` | `enabled` | The cronjob runs the due recurring sends. |
| `DefaultTimezone` | empty | The time zone of a schedule without its own (empty: PHP's). |
| `ConditionHandlers[]` | `CjwNewsletterScheduleConditionNewArticles` | The conditions a schedule may name. |
| `MaxRunsPerCall` | `10` | The most schedules per run. |
| `CopyTitle`, `CopyTitleDateFormat` | `%title %date`, `Y-m-d` | The title of a copy. |
| `[ArticlePoolSettings] DefaultParentNodes[]`, `DefaultClassIdentifiers[]`, `DefaultMaxItems`, `DefaultMaxAgeDays` | content root, `cjw_newsletter_article`, 10, 31 | The pool when none is stored. |
| `IntroAttributes[]` | `short_description`, `teaser_intro`, `full_intro`, `intro`, `description`, `summary` | Where the intro of a picked article comes from. |
| `[ApprovalSettings] ApproverUserIds[]`, `ApproverGroupIds[]` | none, `12` | Who gets the request. |
| `AllowSelfApproval` | `disabled` | May the one who asked approve it himself? |
| `Notification` | `enabled` | Collaboration notifications of new requests. |

`settings/collaboration.ini.append.php` registers the collaboration handler. A Velocity installation sees a new
handler or class only after it has been deployed.

## 5. Privacy

- Recurring sends and pools hold no personal data.
- An approval stores who asked and who decided (user ids) and their comments. The inbox item has the same
  participants.
- Picked articles are public content of the site. The auto-fill takes only what the anonymous user may read.
