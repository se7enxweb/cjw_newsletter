# Deliverability: bounces, retries, rate limits, test groups, mail-in, suppression import

cjw_newsletter 4.2.0. This page covers how the newsletter's mails reach their readers and what it does when they do
not. Everything works through the e-mail preferences of Exponential 6.0.15 and later: the suppression list, the consent
log, the bounce reader and the double opt-in. The newsletter keeps no parallel system.

| Feature | Where | Settings |
|---|---|---|
| Hard bounces and complaints go on the suppression list | automatic | `cjw_newsletter.ini [DeliverabilitySettings]` |
| Soft bounces and temporary refusals are sent again | automatic | `[DeliverabilitySettings]` |
| Rate limits per transport, batches, pauses, resumable sending | Newsletter > Rate limits and batches (`newsletter/throttle`) | `[ThrottleSettings]` |
| Test groups for the test sends | Newsletter > Test groups (`newsletter/test_group_list`) | `[TestSendSettings]` |
| Subscribe and unsubscribe by e-mail | Newsletter > Mail-in addresses (`newsletter/mailin_address_list`) | `[MailInSettings]`, `mailpreferences.ini [BounceSettings]` |
| CSV import into the suppression list | Newsletter > Suppression import (`newsletter/suppression_import`) | `[DeliverabilitySettings] SuppressionImportMaxRows` |

All the admin pages, and the block "Deliverability" on the newsletter dashboard, need the policy function
`newsletter/deliverability`. The console command is `ext:cjw_newsletter:deliverability` (see the end of this page).

## 1. Bounces

A returned mail reaches the newsletter in one of two ways:

- through a **newsletter mail account** (Newsletter > Mail accounts, `ext:cjw_newsletter:mailbox`, the cronjob part
  `cjw_newsletter_mailbox`), as before;
- through the **bounce reader of the e-mail preferences** (`exp:mail:bounces`, the cronjob part `mailbounces`). The
  extension's `settings/mailpreferences.ini.append.php` names `CjwNewsletterMailin` in `[BounceSettings]
  MessageListeners[]`, so every message the kernel reader reads is also given to the newsletter. The reader stays off
  until its mailbox is set in a settings override.

The kind of the message comes from the kernel's classifier (`expMailBounceReader::classify()`: delivery status
notifications and feedback loop reports). A message the kernel cannot read is judged by the SMTP code in it. The mail
and the person are found by the newsletter's own headers (`X-Cjwnl-Senditem`, `X-Cjwnl-User`), which a mail server
copies into the returned message.

| Kind | What happens |
|---|---|
| **hard** (the address or the domain does not exist: the kernel's `[BounceSettings] HardStatusCodes[]`, `5.1.x`, `5.2.1`, `5.4.4` by default) | The address goes on the **suppression list** with the reason `bounce`. The consent log records it (action `suppress`, source `system`). The suppression listener of the newsletter puts the address on the newsletter blacklist. No optional mail of the site goes to it any more, not only the newsletter. |
| **complaint** (a spam report of a feedback loop) | The same, with the reason `complaint`. |
| **soft** (a full mailbox, a server that is down, a delay, a policy refusal such as `5.7.x`) | The newsletter user's bounce count rises as before (`[BounceSettings] BounceThresholdValue`). The mail is **sent again** (section 2). |

With `SuppressHardBounces=disabled`, a hard bounce only marks the newsletter user as bounced, as before 4.2.0.

A mail the mail server refuses at once while the queue sends it is handled the same way. A permanent failure is a hard
bounce. A temporary refusal (`4xx`) or a server that cannot be reached is sent again. Other errors, such as a mail file
that cannot be written, close the item as before. A `450` reply is no longer taken for a hard bounce.

The suppression list is the kernel's: Setup > E-mail preferences > Suppression list shows and lifts entries. A lifted
address also comes off the newsletter blacklist.

## 2. Soft bounces: sending again

```ini
[DeliverabilitySettings]
SoftBounceMaxRetries=2      # 0 = never again
SoftBounceRetryDelay=3600   # seconds before the first retry; each further retry waits twice as long
SoftBounceRetryMaxAge=3     # days: an older send is not opened again
```

A soft-bounced item goes back to "not sent" with a time before which it is not taken (`cjwnl_edition_send_item.next_retry`,
`retry_count`). A send that had finished is opened again until the retry is done. No retry is made for a user who is
bounced, blacklisted, removed or suppressed. The dashboard and the throttle page show how many mails wait for a retry.
A delivered mail sets the user's soft-bounce count back to 0.

## 3. Rate limits, batches and pauses

```ini
[ThrottleSettings]
Throttle=enabled
BatchSize=200
PauseBetweenBatches=0
MaxPerMinute[smtp]=120
MaxPerHour[smtp]=5000
```

- With `Throttle=enabled`, each send goes out in **batches** (`cjwnl_send_batch`). A batch takes at most `BatchSize`
  mails, and no more than the limits of the transport allow in the current minute and hour.
- When a limit is reached, the send stops. It is **resumed** by the next run of the queue (the cronjob, or "Send now"),
  from where it stopped.
- `PauseBetweenBatches` holds the next batch of a send until that many seconds after the previous one finished. No run
  waits or sleeps: a later run starts the batch.
- The limits are per transport (`smtp`, `sendmail`, `file`, and the SMS transports of the SMS area, which use the same
  counter). A send is counted under the newsletter transport it was made with (`cjwnl_edition_send.throttle_transport`).
- **Pause**: Newsletter > Rate limits and batches pauses a transport for some minutes (or
  `ext:cjw_newsletter:deliverability pause`). A paused transport sends nothing, even with `Throttle=disabled`. The
  dashboard lists the pause as a warning.

For other code: `CjwNewsletterThrottle::acquire( $transport, $wanted )` returns how many may be sent now (0 = wait), and
`CjwNewsletterThrottle::record( $transport, $sent )` counts what was sent.

## 4. Test groups

Newsletter > Test groups: a named set of addresses, for one list or for every list, at most `MaxTestGroupSize`. The test
send form of an edition offers the groups of its list. The group's addresses are added to the addresses typed into the
form.

Every test mail is marked:

- the subject starts with `[TestSendSettings] SubjectPrefix` (`[Test]`);
- the header `X-Cjwnl-Test: 1` is set;
- with `OneMailPerAddress=enabled` (the default), each address gets a mail of its own, so testers do not see each
  other's addresses.

Test mails go through the preview transport (`TransportMethodPreview`). They never pass the mail gate and are never
counted in the statistics.

## 5. Subscribe and unsubscribe by e-mail

Newsletter > Mail-in addresses: an address of a list that takes subscribe and unsubscribe mails. Switch the feature on
with `[MailInSettings] MailIn=enabled`.

| Field | Meaning |
|---|---|
| Address | `news@example.org`: the mails must arrive in the bounce mailbox of the e-mail preferences, or in the newsletter mail account chosen as Mailbox |
| Plus tag | `weekly` makes the address `news+weekly@example.org` (`PlusAddressing=enabled`). Without a row of its own, `news+subscribe@` and `news+unsubscribe@` reach `news@` with the tag as the request |
| Takes | `subscribe`, `unsubscribe`, or both (the keyword in the plus tag, the subject or the first line decides: `SubscribeKeywords[]`, `UnsubscribeKeywords[]`) |
| List | The list; "every list" only takes unsubscribe mails |

**The From header is never trusted alone.**

- **Subscribe**:
  - A new address gets a pending subscription and the newsletter's normal confirmation mail. The person must open its
    link (double opt-in), as with the subscribe form.
  - A known address gets the mail with the link to its own settings page, where the person subscribes.
  - The message itself confirms nothing. A suppressed or blacklisted address gets no mail.
- **Unsubscribe**:
  - The mail is honoured at once when it proves the address. That means a reply to an edition that quotes the
    newsletter's header `X-Cjwnl-User` of that person, or a mail that contains the code of the person or of the
    subscription.
  - Otherwise the address on record gets a mail with its unsubscribe links (template
    `design:newsletter/deliverability/mail/mailin_unsubscribe.tpl`).
  - An unknown address gets no answer, so a mail does not reveal who is subscribed.
- **Rejected**:
  - automatic messages (`Auto-Submitted`, `Precedence: bulk/junk/list`);
  - `IgnoreSenders[]` and the site's own addresses;
  - a sender with more than `MaxRequestsPerSenderPerDay` requests;
  - a message without a request.
- Every message is handled once, by its `Message-Id`. The table `cjwnl_mailin_message` and the list "Last messages"
  keep what was done. **Privacy**: the sender's address is kept masked once the message is handled.

## 6. Suppression import

Newsletter > Suppression import: a CSV file (or pasted addresses) into the kernel suppression list.

- **Typical sources**: a do-not-contact list (Robinson list), the bounces of an earlier mail system, legal requests.
- **The file**: one address per row, in a column `email` (also `e-mail`, `mail`, `address`) or, without such a header,
  the first column with an address. `;`, `,`, tab or `|` between columns; at most `SuppressionImportMaxRows` rows.
- **Reason**: `legal`, `admin`, `bounce`, `complaint` or `unsubscribe_all`. `bridge` belongs to the newsletter
  blacklist and cannot be chosen.
- **Check** counts and changes nothing: rows, valid addresses, duplicates, already suppressed, and the lines that hold
  no address.
- **Import** adds the new addresses. An address that is already suppressed keeps its entry and reason. Each new entry
  is written to the consent log (action `suppress`, source `import`) and mirrored on the newsletter blacklist.
- **Privacy**: the uploaded file is read and forgotten. The suppression list stores only a salted hash of each
  address, and addresses are taken out of the note.

## 7. The console

```
./console ext:cjw_newsletter:deliverability status [--json]
./console ext:cjw_newsletter:deliverability suppression-import --file=list.csv --reason=legal [--note="..."] [--dry-run]
./console ext:cjw_newsletter:deliverability pause --transport=smtp --minutes=30 [--dry-run]
./console ext:cjw_newsletter:deliverability resume --transport=smtp [--dry-run]
./console ext:cjw_newsletter:deliverability read --file=saved.eml|directory [--mailbox=<id>] [--dry-run]
```

`read` handles saved messages the way the mailbox readers would: a bounce of a newsletter mail, or a mail to a mail-in
address. With `--dry-run` it says what would happen. Every action ends with `PASS` or `FAIL` and exits 0 or 1.

## 8. Tables and extension points

- **Tables**: `cjwnl_throttle_state`, `cjwnl_send_batch`, `cjwnl_mailin_address`, `cjwnl_mailin_message`,
  `cjwnl_test_group`. **Columns**: `cjwnl_edition_send.test_group_id` (reserved), `.throttle_transport`;
  `cjwnl_edition_send_item.retry_count`, `.next_retry`, `.batch_id`; `cjwnl_user.soft_bounce_count`, `.last_bounce`
  (`doc/schema-4.2.md`).
- **The handler** `CjwNewsletterDeliverabilityHooks` uses the points `sendProcessAllowed` (pause), `itemBeforeSend`
  (a full batch defers the send), `itemSent` (batch counts), `testSendRecipients`, `sendFormStored` (the transport of
  the send) and `dashboardSummary`.
- **The runner** takes the batch of a send after the points have allowed it, leaves out the items that wait for a
  retry, and hands a refused mail to `CjwNewsletterBounce::sendFailed()`.
- **Template parts**: `design:newsletter/dashboard/deliverability.tpl`,
  `design:newsletter/deliverability/send_form_part.tpl`, `design:newsletter/deliverability/test_form_part.tpl`.
  Fetch function: `fetch( 'newsletter', 'test_group_list', hash( 'list_contentobject_id', $id ) )`.
- **Tests**: `tests/tests/extension/cjw_newsletter/cjwNewsletterDeliverabilityTest.php` of the Exponential
  installation, with the messages in `fixtures/deliverability/`, and the kernel's
  `MailPreferencesBounceListenersTest`.
