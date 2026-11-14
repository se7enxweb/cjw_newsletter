# The newsletter administration in admin4

From 4.2.22 every page of the newsletter administration has its own copy for admin4 (and admin4l and the designs
built on them). The copies are drawn with admin4's shared components and the kernel's Bootstrap icons, in light and
dark mode and at any width. The classic administration designs (admin, admin2) keep their look.

## What has an admin4 copy

The admin4 copies have the same relative path under `design/admin4/templates/` and `design/admin4/override/templates/`
as the templates they replace. They keep those templates' variables, fetches, form fields, button names, POST targets,
extension points and translation strings. Only the markup changes.

| Area | Templates |
|---|---|
| Module views (`newsletter/*`) | Dashboard, users, subscriptions, blacklist, bounces and mail accounts, imports and exports, editorial (schedules, article pools, approval), deliverability (rate limits, test groups, mail-in addresses, suppression import), rendering (interests, skin preview, preview as a subscriber), statistics (reports, A/B tests), send and test send |
| Content views | The full view of a list, a virtual list and an edition, the line view of an edition, the left menu of the newsletter tree |
| Object edit form | The attributes of a list, a virtual list (with its filters), an edition and a subscription, and the list parts of the feature areas (`[ExtensionPointSettings] ListEditParts[]`: approval and article pool, skins and languages, tracking, SMS) |
| Object view | The subscription attribute (yes and no as badges) |
| Notification settings | The newsletter card (`notification/handler/cjwnewsletter/settings/edit.tpl`) |

The admin4 based siteaccesses keep design admin in their design list (for extensions that only have admin
templates). Without an admin4 copy, the extension's `design/admin` template would win over `design/standard`. The
notification card is the case where this happened. It showed the classic fieldset inside admin4's card.

Templates that subscribers and mails see have no admin4 copy and stay in `design/standard`. These are the pages to
subscribe, confirm, configure and unsubscribe, the subscription and mail-in mails, the skins and the e-mail
preference parts.

## Components, icons and the stylesheet

- **Components:** `a4-page`, `a4-pagehead`, `a4-card`, `a4-table`, `a4-btn`, `a4-notice`, `a4-badge`, `a4-facts`,
  `a4-figures`, `a4-filterbar`, `a4-meter`, `a4-field`, `a4-check` and `a4-help`
  (`design/admin4/stylesheets/components.css` of the kernel).
- **Icons:** the kernel's Bootstrap icons (`<span class="a4-icon a4-bi-NAME" aria-hidden="true"></span>`) in the
  colour of the text, in place of the Crystal images. Each function uses the same icon everywhere:

  | Function | Icon |
  |---|---|
  | Administer | `tools` |
  | Dashboard | `speedometer2` |
  | Users | `people` |
  | Blacklists | `lock` |
  | Bounces | `exclamation-triangle` |
  | Settings | `gear` |
  | Mail accounts | `inbox` |
  | Imports | `upload` |
  | Subscription form | `input-cursor-text` |
  | INI settings | `sliders` |

  The edition states are draft `pencil-square`, sending `hourglass-split`, archived `archive` and aborted
  `x-circle-fill`. The subscription states are pending `hourglass-split`, confirmed `person-check`, approved
  `check-circle-fill`, removed `person-slash`, removed by an administrator `x-circle-fill`, bounced
  `exclamation-triangle` and blacklisted `lock-fill`. An icon without text takes its state's text as its
  accessible name.
- **Stylesheet:** `design/standard/stylesheets/cjw_newsletter_admin4.css`, named in `design.ini`
  `[StylesheetSettings] BackendCSSFileList[]`. It holds only what the components cannot do, as `.cjwnl-a4-*` rules.
  Every colour and radius comes from a `--a4-*` token, which has a light and a dark value, and the file defines no
  token of its own. The one fixed surface is the preview of an edition. It keeps a white page in dark mode too,
  because a mailed newsletter has one (`color-scheme: light` with `Canvas`).
  - The file sits in `design/standard`, so every administration design finds it. Until 4.2.22 it was in
    `design/admin4`, and every page of design admin and admin2 logged `Could not find:
    stylesheets/cjw_newsletter_admin4.css`.
  - Only the admin4 templates use its classes, so the other designs look the same.
- **No inline CSS or script:** no `<style>` block, no `style=""` attribute, no inline script, no jQuery or YUI.
  Amounts such as a bar's length use the kernel's `a4-pct-*` classes.

After a change to a template or the stylesheet, deploy with the packer
(`./console exp:velocity deploy --packer --allow-root-user`), so the packed stylesheets and the template-block
cache are renewed.

## Tests

`tests/cjwNewsletterAdmin4Test.php` reads the files only (no kernel, no database):

```bash
php vendor/bin/phpunit extension/cjw_newsletter/tests/cjwNewsletterAdmin4Test.php
```

| Check | What it ensures |
|---|---|
| A4-01 | No `<style>` block or `style=""` attribute in an admin4 template |
| A4-02 | Every administration template has an admin4 copy: the templates of design admin and admin2, the pages of the module views, the parts of the extension points and the datatype edit forms |
| A4-03 | No box frames, `table.list`, classic buttons or Crystal images |
| A4-04 | The stylesheet is registered, takes every colour and radius from a token and defines no token |
| A4-05 | Every rule of the stylesheet is used by a template |
| A4-06 | Inside an installation, every icon is one the kernel's `components.css` defines |

When you add an administration template, add its admin4 copy too, or A4-02 fails.

## Content tree icons

The content tree shows the newsletter classes with Bootstrap icons only on a kernel whose icon operator falls back to
the icon theme's own mapping when a file of the extension's `icon.ini` is missing in the theme. An older kernel shows
the Crystal image or the default icon there.
