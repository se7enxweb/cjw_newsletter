{*  newsletter/editorial/schedule_edit.tpl (cjw_newsletter 4.2.0, area editorial)

    Create or edit a recurring send. Variables: schedule, schedule_id, lists (id => hash), editions (id => hash),
    pools, condition_handlers (class => name), weekday_names (1..7 => name), timezones, default_timezone, preview
    (the next runs as text), errors (field => text), enabled.
*}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_editorial.css' ) )}
{def $type = $schedule.recurrence_type
     $days = $schedule.weekday_array
     $tz = $schedule.timezone}
<div class="newsletter newsletter-schedule_edit">
<form method="post" action={concat( 'newsletter/schedule_edit/', $schedule_id )|ezurl}>
<div class="context-block nl">
    <div class="box-header">
        <h1 class="context-title">{if $schedule_id}{'Edit the recurring send %id'|i18n( 'cjw_newsletter/editorial',, hash( '%id', $schedule_id ) )}{else}{'New recurring send'|i18n( 'cjw_newsletter/editorial' )}{/if}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">

        {if $errors|count}
        <div class="message-error" role="alert"><h2>{'Please check the form'|i18n( 'cjw_newsletter/editorial' )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}
        {if $enabled|not}
        <div class="message-warning"><h2>{'[ScheduleSettings] Schedules is disabled: the cronjob does not run recurring sends. ext:cjw_newsletter:schedule run does.'|i18n( 'cjw_newsletter/editorial' )}</h2></div>
        {/if}

        <fieldset class="nl-ed-fieldset">
            <legend>{'What is sent'|i18n( 'cjw_newsletter/editorial' )}</legend>
            <div class="nl-ed-form">
                <label for="nl-ed-list">{'List'|i18n( 'cjw_newsletter/editorial' )}</label>
                <div{if is_set( $errors.list )} class="nl-field-error"{/if}>
                    <select id="nl-ed-list" name="ListId">
                        <option value="0">{'- choose -'|i18n( 'cjw_newsletter/editorial' )}</option>
                        {foreach $lists as $l}<option value="{$l.id}"{if eq( $l.id, $schedule.list_contentobject_id )} selected="selected"{/if}>{$l.name|wash}{if $l.approval_required} ({'with approval'|i18n( 'cjw_newsletter/editorial' )}){/if}</option>{/foreach}
                    </select>
                </div>

                <span class="nl-ed-label">{'Mode'|i18n( 'cjw_newsletter/editorial' )}</span>
                <div class="nl-ed-choices" style="flex-direction: column">
                    <label><input type="radio" name="Mode" value="latest"{if ne( $schedule.mode, 'copy' )} checked="checked"{/if} /> {'Send the latest edition of the list that was not sent yet'|i18n( 'cjw_newsletter/editorial' )}</label>
                    <label><input type="radio" name="Mode" value="copy"{if eq( $schedule.mode, 'copy' )} checked="checked"{/if} /> {'Copy a template edition and send the copy'|i18n( 'cjw_newsletter/editorial' )}</label>
                </div>

                <label for="nl-ed-template">{'Template edition'|i18n( 'cjw_newsletter/editorial' )}</label>
                <div{if is_set( $errors.template )} class="nl-field-error"{/if}>
                    <select id="nl-ed-template" name="TemplateEditionId">
                        <option value="0">{'- only for mode copy -'|i18n( 'cjw_newsletter/editorial' )}</option>
                        {foreach $editions as $ed}<option value="{$ed.id}"{if eq( $ed.id, $schedule.template_edition_contentobject_id )} selected="selected"{/if}>{$ed.list_name|wash} / {$ed.name|wash}</option>{/foreach}
                    </select>
                    <p class="nl-hint">{'The copy gets the title of the template and the date, and the newsletter articles of the template.'|i18n( 'cjw_newsletter/editorial' )}</p>
                </div>

                <span class="nl-ed-label">{'Auto-fill'|i18n( 'cjw_newsletter/editorial' )}</span>
                <div>
                    <label><input type="checkbox" name="AutoFill" value="1"{if $schedule.auto_fill} checked="checked"{/if} /> {'Fill the copy with the articles of the pool published since the last send'|i18n( 'cjw_newsletter/editorial' )}</label>
                    <p>
                        <label for="nl-ed-pool">{'Article pool'|i18n( 'cjw_newsletter/editorial' )}</label>
                        <select id="nl-ed-pool" name="ArticlePoolId">
                            <option value="0">{'The pool of the list'|i18n( 'cjw_newsletter/editorial' )}</option>
                            {foreach $pools as $p}<option value="{$p.id}"{if eq( $p.id, $schedule.article_pool_id )} selected="selected"{/if}>{$p.label|wash}{if $p.list_name} ({$p.list_name|wash}){/if}</option>{/foreach}
                        </select>
                    </p>
                    <label><input type="checkbox" name="SkipIfEmpty" value="1"{if $schedule.skip_if_empty} checked="checked"{/if} /> {'Skip the run when there is nothing new (it is logged)'|i18n( 'cjw_newsletter/editorial' )}</label>
                </div>

                <label for="nl-ed-condition">{'Condition'|i18n( 'cjw_newsletter/editorial' )}</label>
                <div{if is_set( $errors.condition )} class="nl-field-error"{/if}>
                    <select id="nl-ed-condition" name="ConditionHandler">
                        <option value="">{'None'|i18n( 'cjw_newsletter/editorial' )}</option>
                        {foreach $condition_handlers as $class => $name}<option value="{$class|wash}"{if eq( $class, $schedule.condition_handler )} selected="selected"{/if}>{$name|wash}</option>{/foreach}
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset class="nl-ed-fieldset">
            <legend>{'When'|i18n( 'cjw_newsletter/editorial' )}</legend>
            <div class="nl-ed-form">
                <span class="nl-ed-label">{'Repeat'|i18n( 'cjw_newsletter/editorial' )}</span>
                <div{if is_set( $errors.recurrence )} class="nl-field-error"{/if}>
                    <p><label><input type="radio" name="RecurrenceType" value="d"{if eq( $type, 'd' )} checked="checked"{/if} /> {'On these weekdays'|i18n( 'cjw_newsletter/editorial' )}</label></p>
                    <div class="nl-ed-choices">
                        {foreach $weekday_names as $n => $name}<label><input type="checkbox" name="Weekdays[]" value="{$n}"{if and( eq( $type, 'd' ), $days|contains( $n ) )} checked="checked"{/if} /> {$name|wash}</label>{/foreach}
                    </div>
                    <p><label><input type="radio" name="RecurrenceType" value="w"{if eq( $type, 'w' )} checked="checked"{/if} /> {'Weekly on'|i18n( 'cjw_newsletter/editorial' )}</label>
                        <select name="Weekday" aria-label="{'Weekday'|i18n( 'cjw_newsletter/editorial' )|wash}">{foreach $weekday_names as $n => $name}<option value="{$n}"{if and( eq( $type, 'w' ), $days|contains( $n ) )} selected="selected"{/if}>{$name|wash}</option>{/foreach}</select></p>
                    <p><label><input type="radio" name="RecurrenceType" value="m"{if eq( $type, 'm' )} checked="checked"{/if} /> {'Monthly on day'|i18n( 'cjw_newsletter/editorial' )}</label>
                        <select name="MonthDay" aria-label="{'Day of the month'|i18n( 'cjw_newsletter/editorial' )|wash}">{for 1 to 31 as $d}<option value="{$d}"{if and( eq( $type, 'm' ), eq( $schedule.recurrence_value, $d ) )} selected="selected"{/if}>{$d}</option>{/for}</select></p>
                    <p class="nl-hint">{'A day beyond the end of a month means its last day (the 31st is the 30th in April).'|i18n( 'cjw_newsletter/editorial' )}</p>
                </div>

                <label for="nl-ed-time">{'Time'|i18n( 'cjw_newsletter/editorial' )}</label>
                <div{if is_set( $errors.time )} class="nl-field-error"{/if}>
                    <input id="nl-ed-time" type="text" name="SendTime" value="{$schedule.send_time_text|wash}" size="6" maxlength="5" placeholder="08:00" inputmode="numeric" />
                </div>

                <label for="nl-ed-tz">{'Time zone'|i18n( 'cjw_newsletter/editorial' )}</label>
                <div{if is_set( $errors.timezone )} class="nl-field-error"{/if}>
                    <select id="nl-ed-tz" name="Timezone">
                        <option value="">{'Default (%zone)'|i18n( 'cjw_newsletter/editorial',, hash( '%zone', $default_timezone ) )|wash}</option>
                        {foreach $timezones as $zone}<option value="{$zone|wash}"{if eq( $zone, $tz )} selected="selected"{/if}>{$zone|wash}</option>{/foreach}
                    </select>
                </div>

                <span class="nl-ed-label">{'State'|i18n( 'cjw_newsletter/editorial' )}</span>
                <div class="nl-ed-choices">
                    <label><input type="radio" name="Status" value="0"{if ne( $schedule.status, 1 )} checked="checked"{/if} /> {'active'|i18n( 'cjw_newsletter/editorial' )}</label>
                    <label><input type="radio" name="Status" value="1"{if eq( $schedule.status, 1 )} checked="checked"{/if} /> {'paused'|i18n( 'cjw_newsletter/editorial' )}</label>
                </div>

                {if $preview|count}
                <span class="nl-ed-label">{'Next runs'|i18n( 'cjw_newsletter/editorial' )}</span>
                <div><ul class="nl-ed-next">{foreach $preview as $p}<li><time>{$p|wash}</time></li>{/foreach}</ul></div>
                {/if}
            </div>
        </fieldset>
    </div>
    <div class="controlbar">
        <input class="defaultbutton" type="submit" name="StoreButton" value="{'Save'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        <input class="button" type="submit" name="DiscardButton" value="{'Cancel'|i18n( 'cjw_newsletter/editorial' )|wash}" />
    </div>
</div>
</form>
</div>
{undef $type $days $tz}
