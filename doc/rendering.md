# Rendering: placeholders, conditions, interests, languages, skins and text parts (cjw_newsletter 4.2.0)

This page covers how an edition becomes the mail of one subscriber: the placeholders, the "newsletter condition",
the interests and the block "articles for your interests", the language of each subscriber, the skins (with the
skin chosen per send) and the plain text part. It also covers the preview as a subscriber and the pages where
subscribers choose their language and interests.

## 1. How a mail is made

1. **The send is made** (the send form): the output of the edition is rendered once in the main language, in the
   skin of the send, and stored in `cjwnl_edition_send.output_xml`. A "newsletter condition" leaves markers in this
   output; nothing personal is in it.
2. **The queue is made** (`cjw_newsletter_mailqueue_create`): if the list offers more languages and the edition has
   translations in them, every other language gets its own output (`cjwnl_edition_send_output`). The statistics
   handler then rewrites the links in all outputs.
3. **Each mail** (`cjw_newsletter_mailqueue_process`): the rendering handler picks the subscriber's language, takes
   that output, keeps or drops each conditional part for this subscriber, fills the interests block and adds the
   link placeholders. The runner then replaces every placeholder, escaped in the HTML part and plain in the text
   part and the subject. The item remembers its language (`cjwnl_edition_send_item.language`).

The rendering handler (`CjwNewsletterRenderingHooks`) runs before the statistics handler at every extension point,
so link tracking sees the final bodies. SMS sends (`channel = sms`) are left alone.

## 2. Placeholders

| Placeholder | Value | Only when the list personalises |
|---|---|---|
| `[[name]]`, `[[salutation_name]]`, `[[first_name]]`, `[[last_name]]` | the subscriber's name | yes |
| `[[email]]`, `[[organisation]]` | the subscriber's address and organisation | yes |
| `[[custom_1]]` .. `[[custom_4]]` | the custom fields of the subscriber | yes |
| `[[<key>]]` | `[PlaceholderSettings] Placeholders[<key>]=<cjwnl_user attribute>` | yes |
| `[[list_name]]` | the name of the list | no |
| `[[unsubscribe_url]]` | the unsubscribe page of this subscription | no |
| `[[configure_url]]` | the newsletter's own settings page of the subscriber | no |
| `[[manage_url]]` | the personal link of the e-mail preference page (else the configure page) | no |
| `#_hash_unsubscribe_#`, `#_hash_configure_#`, `#_hash_item_#`, `#_hash_edition_#` | the hashes, as before | no |

Every value is HTML-escaped in the HTML part. The hash and the internal fields of a subscriber are never
placeholders, whatever the settings say. Other channels use `CjwNewsletterPlaceholders::valuesForSubscriber(
$user, $listObjectId, $personalize )` and `replaceInText( $text, $values, $html )`.

## 3. The newsletter condition

A part of an edition that only some subscribers get. In ezxmltext it is the custom tag `newsletter_condition`
(registered in `settings/content.ini.append.php`); in ezrichtext (DocBook) the eztemplate `newsletter_condition`
with the same settings as `ezconfig` values. On the web site the content is shown as it is; only a newsletter
output carries the markers.

| Setting | Meaning |
|---|---|
| `field` | `salutation`, `first_name`, `last_name`, `organisation`, `email`, `language`, `custom_1` .. `custom_4` |
| `operator` | `eq` (default with a value), `ne`, `contains`, `starts`, `in` (value: a list with commas), `empty`, `not_empty` (default without a value) |
| `value` | compared without regard to case |
| `list` | ids of list objects, with commas |
| `language` | locales with commas: the language the subscriber gets |
| `interest` | identifiers of interests or eztags ids, with commas |
| `negate` | `1`: the opposite |

All settings that are filled must match. Conditions can be nested; the same decision is made in the HTML and the
text part. A preview without a subscriber (`newsletter/preview`, test mails, `skin_preview`) shows every part; the
web archive of a sent edition shows what a reader without a subscription would get (field and interest conditions
are false there). `[ConditionTagSettings] ConditionTag=disabled` shows every condition's content to everybody.

## 4. Interests

- A list offers interests when its **interest source** is set in the list edit form: *Topics of the list*
  (`cjwnl_interest` rows of source `topic`, of the list or of every list) or *Tags* (source `eztags`, one row per
  tag).
- Manage them in **Newsletter > Interests** (`newsletter/interest_list`, `interest_edit`): name, identifier, list (or
  every list), source, tag id, order, offered or hidden. Removing an interest removes the subscribers' picks.
- Subscribers pick them on the **e-mail preference page** (`mailpreferences/settings`, the personal link, and the
  administrator's page for a user), in the row of the newsletter category, and on the newsletter card of
  `notification/settings`. Only interests that are offered are stored.
- The block **"articles for your interests"**: a skin prints `{cjwnl_interests_block( 4, $heading, $accent )}`. For
  each subscriber it lists articles of the list's article pool (`CjwNewsletterArticlePool::forList()`,
  `CjwNewsletterArticlePoolFinder::find()` of the editorial area, only content anonymous users may read, without the
  edition's own articles) that carry the tag of one of his interests. A topic without a tag matches articles whose
  keywords or tags name its identifier. No interests or no articles: no block at all, not even the heading.
  The block is per subscriber, so its articles are not added to the edition. `[InterestSettings]
  MaxArticlesPerBlock` is the most articles.
- Privacy: the picks are kept in `cjwnl_user_interest`. They are removed with the subscriber, and when the person is
  erased through the e-mail preferences (`erased()` of the category handler, together with his language).

## 5. Languages

- In the list edit form: **Languages** (`language_array_string`) and the **Main language** (`main_language`, empty =
  the locale of the list's main siteaccess).
- One edition with translations: translate the edition and its articles as any content object. The text fields
  of the newsletter classes must be translatable; the class installer of 4.2.0 does it, an existing installation
  runs once:

  ```bash
  ./console ext:cjw_newsletter:translatable-fields --dry-run
  ./console ext:cjw_newsletter:translatable-fields
  ```

  It sets the edition's title, short title, short description and description and the article's title and text
  translatable, leaves every other field as it is, and is idempotent.
- Each subscriber chooses the **language of the newsletters** on the preference page (or the notification card);
  an administrator can see it in the preview as a subscriber.
- A subscriber gets his language when the list offers it and the edition has a translation in it; otherwise the
  main language. `[LanguageSettings] FallbackToListMainLanguage=disabled` leaves such a subscriber out of that
  edition instead.
- The output of a language is rendered in that language: the content, the texts of the skin (translation context
  `cjw_newsletter/rendering`) and the dates.

## 6. Skins

| Skin | Look | Text part |
|---|---|---|
| `default` | the skin of 4.1 | converted from the HTML part |
| `company` | a calm corporate letter: a coloured header, articles with an accent rule | plain text views |
| `news` | a news digest: a top story, the others as a list with rules | plain text views |
| `shop` | a banner, articles as cards two in a row with a button | plain text views |

The new skins are 600px table layouts with every style inline, no web fonts and no background images, so they read
the same in Outlook, Gmail and Apple Mail, and with images blocked (the images are only a logo mark). Each has a
group `[Skin_<name>]` in `cjw_newsletter.ini`: `Description`, `PreviewImage`, `TextFormat` (`plain` or `html`) and
`AccentColor`.

- **Allowed skins per list**: the list edit form (`skin_name_array_string`; none ticked = every skin). The list's own
  skin is always allowed.
- **Skin per send**: the send form offers the allowed skins; a send in another skin than the list's is rendered
  again in it (`cjwnl_edition_send.skin_name`).
- **Preview**: `newsletter/skin_preview` lists the skins with their preview images and shows one rendered with the
  newest edition (`/(edition)/<node id>` for another one).

### Writing a skin

`design:newsletter/skin/<name>/outputformat/html.tpl` and `text.tpl`. Both set the subject with
`{set-block variable=$subject scope=root}...{/set-block}`. They get `$contentobject` (the edition version),
`$newsletter_list`, `$newsletter_language`, `$newsletter_skin` (the settings above) and `$newsletter_site_url`.
Useful operators: `cjwnl_interests_block`, `cjwnl_abs_url`, `cjwnl_text_underline`, `cjwnl_text_wrap`,
`cjwnl_richtext( 'text'|'html' )`, `cjwnl_rendering_data( ... )`. Add the name to `[NewsletterSettings]
AvailableSkinArray[]` in an override.

With `TextFormat=plain` the text template writes the text part itself: include
`design:newsletter/rendering/plaintext_attribute.tpl` for each field. The template engine drops line breaks next
to block tags (`{if}`, `{foreach}`), so write those line breaks as `{"\n"}` (see the company skin).

## 7. Plain text views

`design/standard/templates/content/datatype/view/plaintext/` has a plain text view of every field type of the
installation (ezstring, eztext, ezxmltext, ezrichtext, dates, numbers, selections, prices, images, files, URLs,
relations, keywords, eztags, matrix ...), and `plaintext/ezxmltags/` one of every ezxmltext tag (paragraphs,
headings underlined, lists numbered or with dashes and indented, links as `text (address)`, tables as rows with
`|`, embeds as `[name] (address)`, the custom tags). Nothing in a text view is escaped: the text part is not HTML.
ezrichtext (DocBook) is converted by `CjwNewsletterRichText` to text and to HTML with inline styles; links to content
become the absolute address of their node, and only http, https and mailto links are kept.

`[TextViewSettings] PlainTextViews=disabled` makes every skin convert its text part from HTML as before 4.2.0.

## 8. Preview as a subscriber

`newsletter/preview_as/<edition node>/<newsletter user id>/<0 html | 1 text>` (also linked from the send form):
search a subscriber by address or pick one of the list, choose a skin, and see the subject, the language, the
interests, the HTML part (in a sandboxed frame) and the text part as he would get them. A sent edition is shown
from its stored send. Nothing is sent or stored. The view needs the `preview` function of the newsletter module.

## 9. The pages subscribers see

- **E-mail preference page** (`mailpreferences/settings`, media design and admin4/admin4l from the same template):
  the newsletter category's row has the language of the newsletters, the interests per list, the mail-in addresses
  of the lists (when the deliverability area has them) and the link to the newsletter's own settings. It is the
  category part hook of the kernel (`partTemplate()`, `partVariables()`, `storePart()` of
  `CjwNewsletterMailCategoryHandler`; see the kernel's mail preferences developer guide), stored with "Save my
  choices".
- **Notification settings** (`notification/settings`): the notification handler `cjwnewsletter` (it sends
  nothing) shows a card with the subscribed lists, the language, the interests and a link to the e-mail
  preferences. `[NotificationCardSettings] Parts[]` lists the parts of the card; other areas append theirs.
- **Subscribe form** (`newsletter/subscribe`) and the admin user pages: `SubscribeFormParts[]`, `UserEditParts[]`,
  `UserViewParts[]` with the points `subscribeValidate( $http )`, `subscribeInput( $user, $http )` (only a new
  subscriber), `userInput( $user, $http )` and `userStored( $user, $http )`.

## 10. Settings (cjw_newsletter.ini, N3 block)

| Group | Setting | Default | Meaning |
|---|---|---|---|
| `PlaceholderSettings` | `Placeholders[<key>]` | none | more `[[<key>]]` placeholders from subscriber attributes |
| `ConditionTagSettings` | `ConditionTag` | enabled | disabled: every condition's content goes to everybody |
| `InterestSettings` | `MaxArticlesPerBlock` | 5 | the most articles of the interests block |
| `LanguageSettings` | `FallbackToListMainLanguage` | enabled | disabled: no mail without a translation in the subscriber's language |
| `TextViewSettings` | `PlainTextViews` | enabled | disabled: text parts converted from HTML as before |
| `Skin_<name>` | `Description`, `PreviewImage`, `TextFormat`, `AccentColor` | | per skin |
| `NotificationCardSettings` | `Parts[]` | the lists part | the parts of the notification card |

## 11. Upgrade from 4.1

1. Apply the 4.2.0 database update (the rendering's tables and columns are part of it).
2. `./console ext:cjw_newsletter:translatable-fields` once, then clear the class and content caches.
3. Clear the INI, template and template override caches (new templates, a new notification handler and new
   template operators), and restart persistent PHP workers (Velocity) so they load the new classes.
4. Optionally set the languages, allowed skins and interest source of each list, and create interests.
