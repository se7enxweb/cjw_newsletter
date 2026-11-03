{* Create or edit a recurring send, admin4. Same data and actions as design/standard/templates/newsletter/editorial/schedule_edit.tpl.
   Variables: schedule, schedule_id, lists (id => hash), editions (id => hash), pools, condition_handlers (class => name),
   weekday_names (1..7 => name), timezones, default_timezone, preview (the next runs as text), errors (field => text), enabled. *}
{def $type = $schedule.recurrence_type
     $days = $schedule.weekday_array
     $tz = $schedule.timezone}
<div class="a4-page newsletter newsletter-schedule_edit">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{if $schedule_id}{'Edit the recurring send %id'|i18n( 'cjw_newsletter/editorial',, hash( '%id', $schedule_id ) )}{else}{'New recurring send'|i18n( 'cjw_newsletter/editorial' )}{/if}</h1>
        </div>
    </div>

    {if $errors|count}
    <div class="a4-notice is-bad a4-error-summary" role="alert"><h2 class="a4-notice-title">{'Please check the form'|i18n( 'cjw_newsletter/editorial' )}</h2>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}
    {if $enabled|not}
    <div class="a4-notice is-warn" role="status">{'[ScheduleSettings] Schedules is disabled: the cronjob does not run recurring sends. ext:cjw_newsletter:schedule run does.'|i18n( 'cjw_newsletter/editorial' )}</div>
    {/if}

    <form method="post" action={concat( 'newsletter/schedule_edit/', $schedule_id )|ezurl}>
    <fieldset class="a4-card">
        <legend class="a4-h2">{'What is sent'|i18n( 'cjw_newsletter/editorial' )}</legend>
        <div class="a4-form is-wide">
            <div class="a4-field">
                <label for="nl-ed-list">{'List'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-list" name="ListId"{if is_set( $errors.list )} aria-invalid="true"{/if}>
                    <option value="0">{'- choose -'|i18n( 'cjw_newsletter/editorial' )}</option>
                    {foreach $lists as $l}<option value="{$l.id}"{if eq( $l.id, $schedule.list_contentobject_id )} selected="selected"{/if}>{$l.name|wash}{if $l.approval_required} ({'with approval'|i18n( 'cjw_newsletter/editorial' )}){/if}</option>{/foreach}
                </select>
            </div>

            <fieldset class="a4-field">
                <legend>{'Mode'|i18n( 'cjw_newsletter/editorial' )}</legend>
                <label class="a4-check"><input type="radio" name="Mode" value="latest"{if ne( $schedule.mode, 'copy' )} checked="checked"{/if} /> <span>{'Send the latest edition of the list that was not sent yet'|i18n( 'cjw_newsletter/editorial' )}</span></label>
                <label class="a4-check"><input type="radio" name="Mode" value="copy"{if eq( $schedule.mode, 'copy' )} checked="checked"{/if} /> <span>{'Copy a template edition and send the copy'|i18n( 'cjw_newsletter/editorial' )}</span></label>
            </fieldset>

            <div class="a4-field">
                <label for="nl-ed-template">{'Template edition'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-template" name="TemplateEditionId"{if is_set( $errors.template )} aria-invalid="true"{/if}>
                    <option value="0">{'- only for mode copy -'|i18n( 'cjw_newsletter/editorial' )}</option>
                    {foreach $editions as $ed}<option value="{$ed.id}"{if eq( $ed.id, $schedule.template_edition_contentobject_id )} selected="selected"{/if}>{$ed.list_name|wash} / {$ed.name|wash}</option>{/foreach}
                </select>
                <p class="a4-help">{'The copy gets the title of the template and the date, and the newsletter articles of the template.'|i18n( 'cjw_newsletter/editorial' )}</p>
            </div>

            <fieldset class="a4-field">
                <legend>{'Auto-fill'|i18n( 'cjw_newsletter/editorial' )}</legend>
                <label class="a4-check"><input type="checkbox" name="AutoFill" value="1"{if $schedule.auto_fill} checked="checked"{/if} /> <span>{'Fill the copy with the articles of the pool published since the last send'|i18n( 'cjw_newsletter/editorial' )}</span></label>
                <div class="a4-field">
                    <label for="nl-ed-pool">{'Article pool'|i18n( 'cjw_newsletter/editorial' )}</label>
                    <select id="nl-ed-pool" name="ArticlePoolId">
                        <option value="0">{'The pool of the list'|i18n( 'cjw_newsletter/editorial' )}</option>
                        {foreach $pools as $p}<option value="{$p.id}"{if eq( $p.id, $schedule.article_pool_id )} selected="selected"{/if}>{$p.label|wash}{if $p.list_name} ({$p.list_name|wash}){/if}</option>{/foreach}
                    </select>
                </div>
                <label class="a4-check"><input type="checkbox" name="SkipIfEmpty" value="1"{if $schedule.skip_if_empty} checked="checked"{/if} /> <span>{'Skip the run when there is nothing new (it is logged)'|i18n( 'cjw_newsletter/editorial' )}</span></label>
            </fieldset>

            <div class="a4-field">
                <label for="nl-ed-condition">{'Condition'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-condition" name="ConditionHandler"{if is_set( $errors.condition )} aria-invalid="true"{/if}>
                    <option value="">{'None'|i18n( 'cjw_newsletter/editorial' )}</option>
                    {foreach $condition_handlers as $class => $name}<option value="{$class|wash}"{if eq( $class, $schedule.condition_handler )} selected="selected"{/if}>{$name|wash}</option>{/foreach}
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset class="a4-card">
        <legend class="a4-h2">{'When'|i18n( 'cjw_newsletter/editorial' )}</legend>
        <div class="a4-form is-wide">
            <fieldset class="a4-field">
                <legend>{'Repeat'|i18n( 'cjw_newsletter/editorial' )}</legend>
                <label class="a4-check"><input type="radio" name="RecurrenceType" value="d"{if eq( $type, 'd' )} checked="checked"{/if}{if is_set( $errors.recurrence )} aria-invalid="true"{/if} /> <span>{'On these weekdays'|i18n( 'cjw_newsletter/editorial' )}</span></label>
                <div class="cjwnl-a4-choices">
                    {foreach $weekday_names as $n => $name}<label class="a4-check"><input type="checkbox" name="Weekdays[]" value="{$n}"{if and( eq( $type, 'd' ), $days|contains( $n ) )} checked="checked"{/if} /> <span>{$name|wash}</span></label>{/foreach}
                </div>
                <div class="cjwnl-a4-inline">
                    <label class="a4-check"><input type="radio" name="RecurrenceType" value="w"{if eq( $type, 'w' )} checked="checked"{/if} /> <span>{'Weekly on'|i18n( 'cjw_newsletter/editorial' )}</span></label>
                    <select name="Weekday" aria-label="{'Weekday'|i18n( 'cjw_newsletter/editorial' )|wash}">{foreach $weekday_names as $n => $name}<option value="{$n}"{if and( eq( $type, 'w' ), $days|contains( $n ) )} selected="selected"{/if}>{$name|wash}</option>{/foreach}</select>
                </div>
                <div class="cjwnl-a4-inline">
                    <label class="a4-check"><input type="radio" name="RecurrenceType" value="m"{if eq( $type, 'm' )} checked="checked"{/if} /> <span>{'Monthly on day'|i18n( 'cjw_newsletter/editorial' )}</span></label>
                    <select name="MonthDay" aria-label="{'Day of the month'|i18n( 'cjw_newsletter/editorial' )|wash}">{for 1 to 31 as $d}<option value="{$d}"{if and( eq( $type, 'm' ), eq( $schedule.recurrence_value, $d ) )} selected="selected"{/if}>{$d}</option>{/for}</select>
                </div>
                <p class="a4-help">{'A day beyond the end of a month means its last day (the 31st is the 30th in April).'|i18n( 'cjw_newsletter/editorial' )}</p>
            </fieldset>

            <div class="a4-field">
                <label for="nl-ed-time">{'Time'|i18n( 'cjw_newsletter/editorial' )}</label>
                <input id="nl-ed-time" class="cjwnl-a4-narrow" type="text" name="SendTime" value="{$schedule.send_time_text|wash}" size="6" maxlength="5" placeholder="08:00" inputmode="numeric"{if is_set( $errors.time )} aria-invalid="true"{/if} />
            </div>

            <div class="a4-field">
                <label for="nl-ed-tz">{'Time zone'|i18n( 'cjw_newsletter/editorial' )}</label>
                <select id="nl-ed-tz" name="Timezone"{if is_set( $errors.timezone )} aria-invalid="true"{/if}>
                    <option value="">{'Default (%zone)'|i18n( 'cjw_newsletter/editorial',, hash( '%zone', $default_timezone ) )|wash}</option>
                    {foreach $timezones as $zone}<option value="{$zone|wash}"{if eq( $zone, $tz )} selected="selected"{/if}>{$zone|wash}</option>{/foreach}
                </select>
            </div>

            <fieldset class="a4-field">
                <legend>{'State'|i18n( 'cjw_newsletter/editorial' )}</legend>
                <div class="cjwnl-a4-choices">
                    <label class="a4-check"><input type="radio" name="Status" value="0"{if ne( $schedule.status, 1 )} checked="checked"{/if} /> <span>{'active'|i18n( 'cjw_newsletter/editorial' )}</span></label>
                    <label class="a4-check"><input type="radio" name="Status" value="1"{if eq( $schedule.status, 1 )} checked="checked"{/if} /> <span>{'paused'|i18n( 'cjw_newsletter/editorial' )}</span></label>
                </div>
            </fieldset>

            {if $preview|count}
            <div class="a4-field">
                <span class="a4-label" id="nl-ed-next-label">{'Next runs'|i18n( 'cjw_newsletter/editorial' )}</span>
                <ul class="cjwnl-a4-next" aria-labelledby="nl-ed-next-label">{foreach $preview as $p}<li><time>{$p|wash}</time></li>{/foreach}</ul>
            </div>
            {/if}
        </div>
    </fieldset>

    <div class="a4-toolbar is-bottom">
        <div class="a4-actions">
            <input class="a4-btn a4-btn-primary" type="submit" name="StoreButton" value="{'Save'|i18n( 'cjw_newsletter/editorial' )|wash}" />
            <input class="a4-btn" type="submit" name="DiscardButton" value="{'Cancel'|i18n( 'cjw_newsletter/editorial' )|wash}" />
        </div>
    </div>
    </form>
</div>
{undef $type $days $tz}
