{* The live length counter of an SMS text: a textarea with data-nl-sms-counter="<id of the counter>" (and optional
   data-nl-sms-hint, the line added to every SMS, and data-nl-sms-max, the most parts). It counts as
   CjwNewsletterSms::segments() does: GSM 03.38 (160 / 153 per part, extension characters twice) or UCS-2 (70 / 67).
   Without JavaScript the server's count of the last post is shown. The code is in cjw_newsletter.js. *}
{ezscript( array( 'cjw_newsletter.js' ) )}
