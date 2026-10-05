# SMS: newsletters by SMS, the phone number, the code confirmation and STOP

cjw_newsletter 4.2.0. An edition can go out as a short text message besides (or instead of) the mail. The channel is
provider-neutral: the SMS go through a **transport** configured in the INI, and the extension ships three of them: a
file transport that only writes files (tests, trying it out), a generic HTTP/JSON transport mapped in the INI, and a
preset for APIs in the style of Twilio. The consent is a category of the kernel's e-mail preferences (Exponential
6.0.15 and later), confirmed with a code sent by SMS.

| Feature | Where | Settings |
|---|---|---|
| Send an edition by SMS | the edition's send page links to Newsletter > Send by SMS (`newsletter/sms_send/<edition node>`) | `cjw_newsletter.ini [SmsSettings]` |
| SMS allowed per list, sender per list | the list object (fields "Editions of this list may be sent by SMS", "SMS sender") | `cjwnl_list.sms_enabled`, `.sms_sender` |
| Mobile number of a subscriber | the preference page, the subscribe form, the admin user page | `[SmsSettings] DefaultCountryCode` |
| Consent "Newsletters by SMS" | the central preference page (`mailpreferences/settings`), category `sms` | `settings/mailpreferences.ini.append.php [Category_sms]` |
| Confirmation code (double opt-in) | the code SMS, the preference page, the public page `newsletter/sms_confirm/<hash>` | `[SmsSettings] Code*` |
| STOP keyword | the provider calls `newsletter/sms_inbound/<transport>` | `[SmsSettings] StopKeywords[]`, `[SmsTransport_<name>] Inbound*` |
| Rate limit | the throttle of the deliverability area, transport name `sms` | `[ThrottleSettings] MaxPerMinute[sms]`, `MaxPerHour[sms]` |
| Dashboard | Newsletter dashboard, block "SMS" | |

Policy functions: `newsletter/sms` for the send page (editors and administrators). The two public views,
`newsletter/sms_inbound` (the endpoint the provider calls) and `newsletter/sms_confirm` (the code page), are in the
extension's `settings/site.ini.append.php` `[RoleSettings] PolicyOmitList[]`, so they work for everybody with no
role change on install or upgrade: the endpoint checks the provider's signature or secret itself and refuses
everything else, and the code page is reached only with the subscriber's secret hash, like `newsletter/configure`.
The policy function `newsletter/sms_public` stays for a site that takes the two views out of `PolicyOmitList`; it
then has to grant `newsletter/sms_public` to the anonymous role (Roles and policies > Anonymous > New policy >
newsletter > sms_public), or the provider's calls and the code page answer "access denied".

## 1. Switching it on

1. `settings/override/cjw_newsletter.ini.append.php` of the installation (never the extension's own file):

   ```ini
   [SmsSettings]
   Sms=enabled
   Transport=file          # first try it with the file transport
   DefaultCountryCode=49   # numbers typed as 0151... become +49151...
   ```

2. The category `sms` is on in the extension's `settings/mailpreferences.ini.append.php` (it is part of 4.2.0). It is
   optional and off by default: nobody gets an SMS until they turn it on themselves and confirm their number.
3. Nothing to grant: the public views are in `PolicyOmitList` (see above).
4. Clear the INI and template caches; on Velocity a deploy is needed (a new class is named in the INI).
5. On the list object tick "Editions of this list may be sent by SMS"; optionally set the sender.

With `Transport=file` every SMS is written as a JSON file into `[SmsTransport_file] Dir` (default
`var/tmp/cjwnl-sms-outbox`): `to`, `from`, `text`, `segments`, `time`. Nothing leaves the server. The dashboard
shows the directory.

## 2. Who gets an SMS

A subscriber of the list gets the SMS of an edition only when **both** are true:

- the mobile number is **confirmed with the code** (`cjwnl_user.phone_status` = 2), and
- the kernel category **`sms` is on** for the person (`expMailPreferences::allows( 'sms' )`: the master switch "optional
  e-mail" is on, the category is on, the address is not on the suppression list).

Everyone else is left out when the queue is made, and checked again right before each SMS: a STOP or a withdrawal
between the queue and the sending is respected. The admin can enter or remove a number on the user page, but never
confirm it: the code always goes to the person.

## 3. The confirmation code (double opt-in)

The category `sms` has `DoubleOptIn=false` in the kernel sense, because its double opt-in is not the kernel's e-mail
link but the code:

1. The person enters the number (preference page, subscribe form) and turns "Newsletters by SMS" on.
2. The code SMS goes to the number: "Your confirmation code for the newsletters of <site>: 123456", with a link to
   `newsletter/sms_confirm/<the subscriber's hash>`. The number is now *pending* (`phone_status` = 1).
3. The person enters the code on the preference page (the field appears under the category) or on the code page.
4. The right code confirms the number (`phone_status` = 2, `phone_confirmed` = the time) and writes the consent:
   the consent log gets the row `confirm` of the category `sms`, source `confirm`, with the exact wording shown
   ("I confirm my mobile number with the code and want to receive newsletters by SMS ...").

Safety: only a hash of the code is stored (HMAC-SHA256 with a key derived from the site secret of the mail
preferences), a code is valid `CodeTTL` seconds (900) and once, a new code makes the older one void, at most
`CodeMaxAttempts` (5) wrong entries per code and `CodeMaxPerHour` (3) codes per person and hour, and the code SMS go
through the throttle too.

## 4. STOP

Every SMS of an edition ends with "Reply STOP to stop." (`AppendStopHint`, counted in the length). When a reply's
first word is one of `StopKeywords[]` (STOP, STOPP, UNSUBSCRIBE, CANCEL, END, QUIT), the number is stopped:
`phone_status` = 3, every SMS still waiting for it is dropped, and the category `sms` is switched off (consent log
`off`, source `system`, "The person replied STOP by SMS."). The person can start again on the preference page, with a
new code.

The provider calls `https://<site>/newsletter/sms_inbound/<transport name>` (POST). The endpoint needs no session and
no login, answers fast, and works under Velocity. Every request is checked by the transport first; a request that
fails is answered **403 and changes nothing**. Verified messages are kept in `cjwnl_sms_inbound` (keyword, action
`stop` or `ignored`); a retried delivery with the same provider id is stored once.

| Check (`InboundSignature`) | The provider sends | Settings |
|---|---|---|
| `hmac-sha256` (default) | header `InboundSignatureHeader` (`X-Signature`) = hex HMAC-SHA256 of the raw body with the secret (`sha256=` prefix allowed) | `InboundSecret` |
| `token` | the secret as the value `InboundTokenField` (`token`) in the query or the form | `InboundSecret` |
| `twilio` (the preset) | header `X-Twilio-Signature` = base64 HMAC-SHA1 over the full URL followed by the POST fields sorted by name (name directly followed by value), with the auth token | `AuthToken`, `InboundUrl` |

Without a secret every inbound request is refused. Behind a proxy set `InboundUrl` to the exact address configured
at the provider, because that is what it signs.

## 5. Sending an edition by SMS

The send page of an edition (`newsletter/send`) has the link "Send this edition by SMS instead". The SMS page shows
how many subscribers have a confirmed number, the text with a live counter (characters, parts, GSM 7-bit or Unicode,
what is left in the part), the placeholders, a test SMS to a typed number, and the SMS sends of the edition.

- **Length**: GSM 03.38 text has 160 characters in one SMS and 153 per part of a longer one (the extension characters
  `^ { } [ ] ~ | € \` count twice); any other character makes the SMS Unicode: 70, 67 per part. An SMS edition may
  have at most `MaxSegments` (3) parts, the stop line included.
- **Placeholders**: the same as in the mails (`[[first_name]]`, `[[name]]`, `[[list_name]]`, `[[unsubscribe_url]]`
  ...), filled with the plain values (an SMS is text). The counter counts them as typed.
- **Sending**: "Send by SMS" makes a send with `channel = sms`. The next queue run (cronjob part `cjw_newsletter`,
  "Send now" on the dashboard) makes one `cjwnl_sms_message` per subscriber who allows it and sends them within the
  rate limit, at most `MaxPerRun` (500) per run; a run that stops is resumed by the next one. The mail runner never
  mails an SMS send. When nothing is left the send is finished.

## 6. Transports

`[SmsSettings] Transport=<name>` chooses the group `[SmsTransport_<name>]`; `Class` is a class that implements
`CjwNewsletterSmsTransport` (`send()`, `verifyInbound()`, `parseInbound()`, `inboundResponse()`), so a provider with
an API of its own can get a class of its own.

**Credentials belong only in `settings/override/cjw_newsletter.ini.append.php` of the installation**, never in the
extension and never in a commit: `AuthPassword`, `AuthToken`, `AuthHeaderValue`, `AccountSid`, `InboundSecret`. The
dashboard masks them.

### The generic HTTP/JSON transport (`CjwNewsletterSmsTransportHttp`)

| Key | Default | Meaning |
|---|---|---|
| `Url` | | The API address; `{Key}` is replaced by the setting `Key` (URL-encoded). Plain `http` only to 127.0.0.1/localhost unless `AllowInsecureHttp=enabled`. |
| `Method` | `POST` | `POST`, `PUT` or `GET` (GET sends the fields as the query). |
| `Format` | `json` | `json` or `form`. |
| `Auth` | `none` | `basic` (`AuthUser`, `AuthPassword`), `bearer` (`AuthToken`), `header` (`AuthHeader`: `AuthHeaderValue`). |
| `FieldTo`, `FieldFrom`, `FieldText` | `to`, `from`, `text` | The field names; a dotted name builds nested JSON (`message.to`). |
| `ExtraFields[<name>]` | | Fixed fields (`{Key}` allowed). |
| `Headers[]` | | More headers, `Name: value`. |
| `From` | | The sender when the list has none. |
| `SuccessStatus` | `2xx` | Comma list of codes; `2xx` = 200-299. |
| `SuccessJsonPath`, `SuccessJsonValue` | | Optional: this value of the JSON answer must be truthy, or equal the value. |
| `MessageIdPath` | `id` | The provider's message id in the answer (dotted path, `messages.0.id`). |
| `ErrorJsonPath` | `error` | The error text in the answer. |
| `Timeout` | `10` | Seconds. |
| `InboundSignature`, `InboundSignatureHeader`, `InboundTokenField`, `InboundSecret` | `hmac-sha256`, `X-Signature`, `token` | The check of the inbound endpoint (section 4). |
| `InboundFromField`, `InboundTextField`, `InboundIdField` | `from`, `text`, `id` | Where the inbound SMS has its number, text and id (form field or JSON path). |

Example, a JSON API with an API key header:

```ini
# settings/override/cjw_newsletter.ini.append.php
[SmsSettings]
Sms=enabled
Transport=http

[SmsTransport_http]
Url=https://api.sms-provider.example/v1/messages
Auth=header
AuthHeader=X-Api-Key
AuthHeaderValue=<the key>
FieldTo=recipient
FieldText=message.text
FieldFrom=sender
From=Newsletter
SuccessStatus=200,201
SuccessJsonPath=status
SuccessJsonValue=accepted
MessageIdPath=message_id
ErrorJsonPath=error.description
InboundSecret=<the webhook secret>
InboundFromField=originator
InboundTextField=message
InboundIdField=id
```

### The Twilio-style preset (`CjwNewsletterSmsTransportTwilio`)

The HTTP transport with the settings of a Twilio-style Messages API: a form POST to
`https://api.twilio.com/2010-04-01/Accounts/{AccountSid}/Messages.json` with `To`, `From`, `Body`, basic
authentication with the account id and the auth token, success on 200/201 with the id in `sid`, the inbound check
`X-Twilio-Signature`, and an empty TwiML answer. Only the account is needed:

```ini
# settings/override/cjw_newsletter.ini.append.php
[SmsSettings]
Sms=enabled
Transport=twilio

[SmsTransport_twilio]
AccountSid=AC...
AuthToken=...
From=+1...
# or, with a messaging service instead of a number:
#From=
#ExtraFields[MessagingServiceSid]=MG...
InboundUrl=https://www.example.org/newsletter/sms_inbound/twilio
```

At the provider set the incoming-message webhook of the number to the `InboundUrl` (HTTP POST). A compatible provider
with another address only needs `Url=`.

### Provider setup checklist

1. Create the sender (number or alphanumeric id) at the provider; note that alphanumeric senders cannot receive
   replies, so STOP needs a number or the provider's own opt-out handling.
2. Put the credentials into the settings override, never into the extension.
3. Set the webhook for incoming messages to `https://<site>/newsletter/sms_inbound/<transport>` and its secret
   (the endpoint needs no role, see the policy note at the top).
4. Send a test SMS from the SMS page of an edition to your own number.
5. Reply STOP from that number and check the dashboard ("STOP replies") and the consent log of the person.
6. Set the rate limits of the provider: `[ThrottleSettings] Throttle=enabled`, `MaxPerMinute[sms]=...`.

## 7. Privacy notes

- The number is personal data of the subscriber (`cjwnl_user.phone_number`) and is shown masked on public pages
  (`+49 ••• 6789`). It is removed by emptying the field; a STOP keeps it with the state "stopped" so that the
  withdrawal can be shown.
- **Erasure** (`exp:mail:preferences erase`, a request, the removal of the account) and the removal of the
  subscriber remove the number, the codes, the SMS to the person and the SMS from him (`CjwNewsletterSms::forgetUser()`,
  through `erased()` of the category handler and the extension point `userRemoved`). The consent log keeps the
  anonymised record, as the kernel does for every category.
- The consent and its withdrawal are in the kernel consent log with the wording shown, the source and the time
  (GDPR art. 7(1)); the code confirmation is the proof that the number belongs to the person.
- Codes are stored only as a hash and expire; incoming messages are kept in `cjwnl_sms_inbound`.
- The SMS text goes to the provider, who processes it as a processor: name the provider in the privacy notice and
  have a data processing agreement.
- The tests never call a provider: they use the file transport and a stub server on 127.0.0.1, and only the reserved
  numbers +1 555 0100 to 0199.

## 8. Tables

`cjwnl_sms_code` (codes, hashed), `cjwnl_sms_message` (one row per SMS; the text of a send is its row with
`newsletter_user_id` 0 and status 8), `cjwnl_sms_inbound` (incoming SMS), and the columns `cjwnl_edition_send.channel`,
`cjwnl_list.sms_enabled`, `.sms_sender`, `cjwnl_user.phone_number`, `.phone_status`, `.phone_confirmed`
(see `doc/schema-4.2.md`).
