# CJW Newsletter for Exponential

CJW Newsletter is the newsletter system of Exponential. Newsletter editions are ordinary content (lists, editions and
articles in the content tree), visitors subscribe to lists with a double opt-in, editions go out from the cron in the
background, and bounces, the blacklist and the kernel's e-mail preferences keep the lists clean.

It is for site owners and editors who want to send newsletters from the same system as their web site, and for
developers who need a newsletter they can theme, extend and run from the console.

What you get:

- A **Newsletter** tab in the administration (admin4 draws it with the `envelope` icon) with a dashboard of the
  lists, the subscribers, the sends and the problems found.
- Lists, editions and articles as content classes; HTML and plain text output; the skins `default`, `company`,
  `news` and `shop`, and your own.
- Public pages to subscribe, confirm (double opt-in), change the subscription and unsubscribe; a web archive of the
  sent editions.
- Subscriber management: CSV import with a column mapping, preview and dry run; CSV export; blacklist; bounce
  handling from mail accounts.
- Deliverability: suppression of hard bounces and complaints, retries of soft bounces, rate limits and batches,
  test groups, subscribe and unsubscribe by e-mail.
- Editorial tools: recurring sends, article pools and an article picker, approval of editions through the
  collaboration inbox.
- Personalization: placeholders, a "newsletter condition" tag for parts only some subscribers get, interests, one
  edition in several languages, preview as a subscriber.
- Statistics with privacy in mind: opens and clicks per person only with consent, anonymous totals otherwise, A/B
  subject tests, CSV export, retention.
- Newsletters by SMS (file, generic HTTP/JSON or a Twilio-style transport).
- Console commands and cronjob parts for everything that runs in the background.
- A migration from the old eznewsletter extension.
- Translations for 18 locales.

## Requirements

- Exponential 6.0.15 or later. The module views, the cronjob parts and the console commands are built on the
  kernel's `Exponential\Runnable` classes, and the deliverability, consent and approval features use the kernel's
  e-mail preferences and collaboration inbox (see [doc/upgrade-4.2.md](doc/upgrade-4.2.md#1-requirements)).
- PHP 8.0 or later (tested up to 8.5).
- MySQL / MariaDB, PostgreSQL or SQLite.
- The PHP command line on the server, for the cron, the preview and background jobs.
- A way to send mail: SMTP, sendmail, or the `file` transport for testing.

## Installation

### 1. Get the files

With Composer, from the root of your Exponential installation:

```bash
composer require se7enxweb/cjw_newsletter
```

The legacy extension installer puts the files in `extension/cjw_newsletter`.

By hand: download a release from https://github.com/se7enxweb/cjw_newsletter/releases (or
`git clone https://github.com/se7enxweb/cjw_newsletter.git extension/cjw_newsletter`) so that the files are in
`extension/cjw_newsletter`. The folder must be called `cjw_newsletter`.

### 2. Activate the extension

In `settings/override/site.ini.append.php`:

```ini
[ExtensionSettings]
ActiveExtensions[]=cjw_newsletter
```

If you use the website toolbar (`ezwt`), list `cjw_newsletter` before it.

### 3. Create the database tables

A new installation takes the schema of its database engine:

```bash
# MySQL / MariaDB
mysql -u <user> -p <database> < extension/cjw_newsletter/sql/mysql/schema.sql
# PostgreSQL
psql -U <user> -d <database> -f extension/cjw_newsletter/sql/postgresql/schema.sql
# SQLite
sqlite3 var/storage/sqlite3/sqlite.db < extension/cjw_newsletter/sql/sqlite/schema.sql
```

`share/db_schema.dba` holds the same schema in the kernel's database-independent format, for installers.

### 4. Regenerate the autoloads and clear the caches

```bash
php bin/php/ezpgenerateautoloads.php -e
php bin/php/ezcache.php --clear-all
```

### 5. Import the content classes and create the newsletter tree

The classes are in two packages, `packages/cjw_newsletter_classes-1.0-1.ezpkg` (root, system, list, edition,
article) and `packages/cjw_newsletter_list_virtual-1.0-1.ezpkg` (virtual list). Either import them in the
administration (**Setup > Packages > Import new package**), or let the class installer do it together with the
section and a first newsletter tree. Save this as a script, for example `cjw_newsletter_setup.php` in the
installation's root, and run it once with `php bin/php/ezexec.php cjw_newsletter_setup.php`:

```php
<?php
// Imports the missing newsletter classes, creates the section "CJW Newsletter" and a newsletter tree
// (root, system, one list) below the content root, and writes RootFolderNodeId to
// settings/override/cjw_newsletter.ini.append.php. Every step leaves what exists alone.
$admin = eZUser::fetchByName( 'admin' );
eZUser::setCurrentlyLoggedInUser( $admin, $admin->attribute( 'contentobject_id' ) );

print_r( CjwNewsletterClassInstaller::install() );
$report = array();
CjwNewsletterClassInstaller::installSection( $report );
$rootNodeID = CjwNewsletterClassInstaller::installTree( null, $report );
if ( $rootNodeID )
    $report['setting'] = CjwNewsletterClassInstaller::writeRootFolderSetting( $rootNodeID );
print_r( $report );
```

Done by hand instead, the steps are: create a section "CJW Newsletter" with the navigation part "CJW Newsletter",
create a `cjw_newsletter_root` object and assign it that section, set `[NewsletterSettings] RootFolderNodeId` to its
node, and build the tree below it:

```
Newsletter Root
  - Newsletter System
      - Newsletter List 1
          - Newsletter Edition 1
              - Newsletter Article 1
              - Newsletter Article 2
      - Newsletter List 2
```

### 6. Configure sending and the cron

Set the sender and the transports (see [Configuration](#configuration)), check that `[SiteSettings] SiteURL` of the
siteaccess that renders the newsletter is the public host name (it makes the absolute links and image URLs of the
mails), and add the cron jobs (see [Cron jobs](#cron-jobs)).

The **Newsletter** tab now shows in the administration's top menu. If one of your siteaccesses keeps its own
`menu.ini.append.php` with a `[TopAdminMenu] Tabs[]` list, add `Tabs[]=newsletter` to it.

## Configuration

The defaults are in `extension/cjw_newsletter/settings/cjw_newsletter.ini`, where every setting is explained. Put
your changes in `settings/override/cjw_newsletter.ini.append.php` (or a siteaccess's settings folder). The settings
most installations change:

```ini
[NewsletterSettings]
# Node of the container below which the newsletter systems are looked for
RootFolderNodeId=1
# The PHP command line for the preview and background runs; auto = the PHP that serves the request
PhpCli=auto
# Skins offered when editing a list (design:newsletter/skin/<name>)
AvailableSkinArray[]=default

[NewsletterMailSettings]
# smtp, sendmail or file (file writes every mail as an .eml file to FileTransportMailDir and sends nothing)
TransportMethodCronjob=smtp
TransportMethodPreview=smtp
TransportMethodDirectly=smtp
FileTransportMailDir=var/log/mail
SmtpTransportServer=smtp.example.com
SmtpTransportPort=587
SmtpTransportUser=newsletter@example.com
SmtpTransportPassword=secret
SmtpTransportConnectionType=tls
# Sender of the subscribe and unsubscribe mails
EmailSender=newsletter@example.com
EmailSenderName=Example Newsletter
# Prefix of every subject; empty = "[Newsletter <host of SiteURL>]"
EmailSubjectPrefix=

[BounceSettings]
# Bounces after which a subscriber counts as bounced
BounceThresholdValue=3
```

`TransportMethodCronjob` is used for the editions the cron sends, `TransportMethodPreview` for test mails and
`TransportMethodDirectly` for the subscribe and information mails. To try a whole installation without a mail server,
set all three to `file` and read the mails in `FileTransportMailDir`.

The other blocks of `cjw_newsletter.ini` and the guide that explains them:

| Blocks | Guide |
|---|---|
| `[NewsletterUserSettings]`, `[NewsletterFilterSettings]`, `[NewsletterCsvImportSettings]`, `[ImportMappingSettings]`, `[EznewsletterImportSettings]` | [Import, export and migration](doc/importexport.md) |
| `[DeliverabilitySettings]`, `[ThrottleSettings]`, `[TestSendSettings]`, `[MailInSettings]` | [Deliverability](doc/deliverability.md) |
| `[ScheduleSettings]`, `[ArticlePoolSettings]`, `[ApprovalSettings]` | [Editorial](doc/editorial.md) |
| `[PlaceholderSettings]`, `[ConditionTagSettings]`, `[InterestSettings]`, `[LanguageSettings]`, `[TextViewSettings]`, `[Skin_*]` | [Rendering](doc/rendering.md) |
| `[TrackingSettings]`, `[ABTestSettings]`, `[StatisticsSettings]` | [Statistics](doc/statistics.md) |
| `[SmsSettings]`, `[SmsTransport_*]` | [SMS](doc/sms.md) |
| `[ExtensionPointSettings]`, `[NotificationCardSettings]`, `[DebugSettings]` | comments in `cjw_newsletter.ini` |

What is on and off by default (tracking, SMS, rate limits and mail-in are off) is listed in
[doc/upgrade-4.2.md](doc/upgrade-4.2.md#6-defaults-what-is-on-what-is-off).

## Usage

### Send your first newsletter

1. In the **Newsletter** tab, open your list (or create one below a newsletter system) and set its sender, skin and
   output formats (HTML, text).
2. Create a **Newsletter Edition** below the list and add **Newsletter Article** objects below the edition.
3. Open the edition and click **Send**. Send a test mail to yourself first, then schedule the send.
4. The cron part `cjw_newsletter_mailqueue_create` creates one mail per subscriber, and
   `cjw_newsletter_mailqueue_process` sends them. The send page and the dashboard show the progress.

### Let visitors subscribe

The public views are on any siteaccess:

| URL | Page |
|---|---|
| `/newsletter/subscribe` | The subscribe form (a confirmation mail is sent: double opt-in) |
| `/newsletter/configure/<user hash>` | Confirm, choose lists, change the details |
| `/newsletter/unsubscribe/<hash>` | Unsubscribe from a list |
| `/newsletter/archive/<send hash>/<format>/<subscription hash>` | The web version of a sent edition |

Give the Anonymous role the functions `newsletter/subscribe`, `newsletter/configure`, `newsletter/unsubscribe` and
`newsletter/archive`.

### Template operators and fetch functions

```
{* Number of confirmed subscriptions of a list *}
{fetch( 'newsletter', 'subscription_list_count', hash( 'list_contentobject_id', $node.contentobject_id,
                                                       'status', 'confirmed' ) )}

{* The subscriptions of a list, 20 per page *}
{def $subscriptions = fetch( 'newsletter', 'subscription_list', hash( 'list_contentobject_id', $node.contentobject_id,
                                                                      'status', 'confirmed',
                                                                      'limit', 20, 'offset', 0 ) )}

{* The mails sent to one newsletter user *}
{fetch( 'newsletter', 'edition_send_item_list', hash( 'newsletter_user_id', $newsletter_user.id ) )}

{* Plain text of a rich text field, for the text part of a mail *}
{$node.data_map.description|cjwnl_plaintext()}

{* The subscription states, id => name *}
{cjw_newsletter_variable( 'available_subscription_status_id_name_array' )}

{* String helpers *}
{$text|cjw_newsletter_str_replace( 'old', 'new' )}
{$text|cjw_newsletter_preg_replace( '/\s+/', ' ' )}
```

The `status` of the subscription fetches is one of `pending`, `confirmed`, `approved`, `removed`, `bounced` or
`blacklisted`. The module also has the fetch functions `subscription_list`, `import_subscription_list`, `user_list`
(each with a `_count` twin), `test_group_list`, `article_statistics`, `send_statistics`, `approval_state`,
`edition_articles`, `list_article_pool`, `article_pool_list` and `schedule_list`
(`modules/newsletter/function_definition.php`). The rendering operators `cjwnl_*` are described in
[doc/rendering.md](doc/rendering.md).

### Your own skin

A skin is a folder of templates in your design. Copy `design/standard/templates/newsletter/skin/default` to your
design, change it, and offer it in the settings:

```
my_design/templates/newsletter/skin/my_custom_skin/outputformat/html.tpl
my_design/templates/newsletter/skin/my_custom_skin/outputformat/text.tpl
```

```ini
[NewsletterSettings]
AvailableSkinArray[]=my_custom_skin
```

Clear the template caches after adding templates. The skin is now offered when you edit a list.

## Cron jobs

```bash
php runcronjobs.php -s <siteaccess> cjw_newsletter              # create the mail queue and send it
php runcronjobs.php -s <siteaccess> cjw_newsletter_mailbox      # read the newsletter's own mail accounts (bounces)
php runcronjobs.php -s <siteaccess> mailbounces                 # the kernel's bounce reader (also mail-in)
```

`cjw_newsletter` runs the parts `cjw_newsletter_mailqueue_create` and `cjw_newsletter_mailqueue_process`, which can
also run on their own. A crontab that sends every five minutes:

```
*/5 * * * * cd /path/to/exponential && php runcronjobs.php -q -s <siteaccess> cjw_newsletter
```

## Console commands

Every command shows its options with `--help`; most have `--dry-run`.

```bash
./console ext:cjw_newsletter:status                         # lists, subscribers, sends, outbox, problems
./console ext:cjw_newsletter:queue --dry-run                # the two cron parts by hand (--create-only, --send-only)
./console ext:cjw_newsletter:mailbox                        # collect and parse the mail accounts
./console ext:cjw_newsletter:repair --dry-run               # orphaned subscriptions and mails
./console ext:cjw_newsletter:import                         # a CSV import uploaded in the admin
./console ext:cjw_newsletter:schedule list                  # recurring sends (list|run)
./console ext:cjw_newsletter:deliverability status          # status|suppression-import|pause|resume|read
./console ext:cjw_newsletter:statistics --cleanup           # retention cleanup, --decide for A/B winners
./console ext:cjw_newsletter:import-eznewsletter --dry-run  # migrate from eznewsletter
./console ext:cjw_newsletter:translatable-fields --dry-run  # upgrade step of 4.2.0
./console ext:cjw_newsletter:class-languages --dry-run      # upgrade step of 4.2.1
```

Without `./console`, run the scripts directly, for example `php extension/cjw_newsletter/bin/php/status.php`.

## Permissions

The module `newsletter` has these policy functions (`modules/newsletter/module.php`):

| Function | Gives |
|---|---|
| `admin` | Shows the admin menu of the newsletter |
| `index` | The dashboard |
| `subscribe`, `configure`, `unsubscribe`, `archive` | The public pages (for the Anonymous role) |
| `preview`, `send`, `settings` | Previewing and sending editions, the settings view |
| `subscription_list`, `subscription_view`, `subscription_list_csvimport`, `subscription_list_csvimport_import`, `subscription_list_csvexport` | Subscriptions, their import and export |
| `user_list`, `user_view`, `user_create`, `user_edit`, `user_remove` | Newsletter users |
| `mailbox_list`, `mailbox_edit`, `mailbox_item_list`, `mailbox_item_view` | Mail accounts and their messages (bounces) |
| `blacklist_item` | The blacklist |
| `import_list`, `import_view` | Past imports |
| `deliverability` | Rate limits and batches, test groups, mail-in addresses, suppression import |
| `editorial`, `approve` | Recurring sends, article pools, asking for and deciding approvals |
| `rendering` | Interests, the skin preview |
| `statistics`, `track` | Reports and A/B tests; the open pixel and click redirect |
| `sms`, `sms_public` | The SMS send page; the SMS confirmation and the provider endpoint |
| `import_export` | Import with a column mapping, subscriber export, migration log |

`settings/site.ini.append.php` opens `newsletter/o`, `newsletter/r`, `newsletter/sms_inbound` and
`newsletter/sms_confirm` to everybody (`PolicyOmitList[]`); each checks its own signature or secret. The
Administrator role of a standard installation has `newsletter/*`.

## Upgrading

Replace the files (or run `composer update se7enxweb/cjw_newsletter`), run the database updates of the releases in
between from `update/database/<engine>/`, regenerate the autoloads and clear the caches. From 4.1, follow
[doc/upgrade-4.2.md](doc/upgrade-4.2.md) step by step. Every release is described in [CHANGELOG](CHANGELOG).

## Known limits

- Oracle works only through the kernel's driver and is untested for 4.2.
- Tracking, SMS, rate limits and mail-in are off until you switch them on.
- On a kernel older than 6.0.15 the extension does not run.

## Troubleshooting

- **The preview or the test mail is empty**: set `[NewsletterSettings] PhpCli` to the PHP binary of the web server
  if `auto` does not find it; a `php` on the PATH can be another PHP version.
- **No mails arrive**: check the transports; with `file` they are written to `FileTransportMailDir`, not sent. Run
  `./console ext:cjw_newsletter:status` for the outbox and the problems found.
- **Links or images in mails point to the wrong host**: set `[SiteSettings] SiteURL` of the siteaccess that renders
  the newsletter.
- **The Newsletter tab is missing**: check that the extension is active, the autoloads were regenerated and the caches
  cleared, and that a siteaccess `menu.ini.append.php` does not replace the tab list. The dashboard behind the tab
  needs `newsletter/index`.

The 4.0 manual (`doc/cjw_newsletter_documentation.pdf`) still describes the content classes, lists, editions and
sending in detail; [doc/README.md](doc/README.md) lists all guides.

## Contributing

Issues and pull requests are welcome at https://github.com/se7enxweb/cjw_newsletter. The tests are in `tests/`.
A release lists the checksums of its files in `share/filelist.md5`.

## License

GNU General Public License v2.0 or (at your option) any later version. See [LICENSE](LICENSE).

## Credits and history

CJW Newsletter was created in 2007 by the CJW Network (coolscreen.de, JAC Systeme and Webmanufaktur) for
eZ Publish, the system Exponential grew out of. 7x maintains it for Exponential and added the 4.2 features.
