{*  newsletter/statistics/send_form_part.tpl (cjw_newsletter 4.2.0, area statistics)

    The part of the send form ([ExtensionPointSettings] SendFormParts[]): the tracking of this send and the A/B subject
    test. Posts CjwNewsletterStatistics[...], read by CjwNewsletterStatisticsHooks::sendFormValidate()/sendFormStored().
    node_id, object_version
*}
{ezcss_require( 'newsletter_statistics.css' )}
{def $i18n = 'cjw_newsletter/statistics'
     $ab_variants = ezini( 'ABTestSettings', 'Variants', 'cjw_newsletter.ini' )|int
     $ab_sample = ezini( 'ABTestSettings', 'SamplePercent', 'cjw_newsletter.ini' )
     $ab_wait = ezini( 'ABTestSettings', 'WaitHours', 'cjw_newsletter.ini' )
     $ab_criterion = ezini( 'ABTestSettings', 'Criterion', 'cjw_newsletter.ini' )
     $ab_keys = array( 'B', 'C', 'D', 'E' )
     $site_on = eq( ezini( 'TrackingSettings', 'Tracking', 'cjw_newsletter.ini' ), 'enabled' )}
{if $ab_variants|lt( 2 )}{set $ab_variants = 2}{/if}
{if $ab_variants|gt( 5 )}{set $ab_variants = 5}{/if}
<div class="nl-stat-form">
<fieldset>
    <legend>{'Statistics'|i18n( $i18n )}</legend>
    {if $site_on|not}<p class="nl-hint">{'Tracking is switched off for the site ([TrackingSettings] Tracking), so nothing of this send is counted.'|i18n( $i18n )}</p>{/if}
    <div class="nl-stat-row">
        <label for="cjwnl-stat-tracking">{'Tracking of this send'|i18n( $i18n )}</label>
        <select id="cjwnl-stat-tracking" name="CjwNewsletterStatistics[TrackingMode]">
            <option value="list" selected="selected">{'As the list says'|i18n( $i18n )}</option>
            <option value="0">{'Off'|i18n( $i18n )}</option>
            <option value="1">{'Anonymous totals'|i18n( $i18n )}</option>
            <option value="2">{'Per person with consent'|i18n( $i18n )}</option>
        </select>
    </div>
    <p class="nl-hint">{'Per person counts only the recipients who agreed to the newsletter statistics on their e-mail preference page; everybody else is counted anonymously.'|i18n( $i18n )}</p>

    <div class="nl-stat-row">
        <label><input type="checkbox" name="CjwNewsletterStatistics[AbTest]" value="1" /> {'A/B subject test'|i18n( $i18n )}</label>
    </div>
    <p class="nl-hint">{"A sample of the list gets each subject; after the wait the subject with the best rate goes to the rest of the list. Subject A is the edition's own."|i18n( $i18n )}</p>
    {for 1 to sub( $ab_variants, 1 ) as $n}
    <div class="nl-stat-row">
        <label for="cjwnl-stat-subject-{$n}">{'Subject %key'|i18n( $i18n,, hash( '%key', $ab_keys[sub( $n, 1 )] ) )}</label>
        <input type="text" class="nl-stat-subject" id="cjwnl-stat-subject-{$n}" name="CjwNewsletterStatistics[AbSubject][]" value="" maxlength="255" />
    </div>
    {/for}
    <div class="nl-stat-row">
        <label for="cjwnl-stat-sample">{'Sample per variant (%)'|i18n( $i18n )}</label>
        <input type="text" class="nl-stat-short" id="cjwnl-stat-sample" name="CjwNewsletterStatistics[AbSamplePercent]" value="{$ab_sample|wash}" inputmode="numeric" />
        <label for="cjwnl-stat-wait">{'Wait (hours)'|i18n( $i18n )}</label>
        <input type="text" class="nl-stat-short" id="cjwnl-stat-wait" name="CjwNewsletterStatistics[AbWaitHours]" value="{$ab_wait|wash}" inputmode="decimal" />
        <label for="cjwnl-stat-criterion">{'Winner by'|i18n( $i18n )}</label>
        <select id="cjwnl-stat-criterion" name="CjwNewsletterStatistics[AbCriterion]">
            <option value="open"{if eq( $ab_criterion, 'open' )} selected="selected"{/if}>{'open rate'|i18n( $i18n )}</option>
            <option value="click"{if eq( $ab_criterion, 'click' )} selected="selected"{/if}>{'click rate'|i18n( $i18n )}</option>
        </select>
    </div>
</fieldset>
</div>
{undef $ab_keys $i18n $ab_variants $ab_sample $ab_wait $ab_criterion $site_on}
