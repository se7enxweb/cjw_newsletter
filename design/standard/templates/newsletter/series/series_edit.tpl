{* newsletter/series/series_edit.tpl. Variables: list, series, steps, editions, segments, errors, max_steps, can_add. @copyright Copyright CJW Network and Exponential Foundation. All rights reserved. GNU General Public License v2.0 (or any later version). *}
{def $i18n = 'cjw_newsletter/series'}
{ezcss_require( array( 'newsletter_ui.css', 'newsletter_editorial.css' ) )}
<div class="newsletter newsletter-series_edit">
<div class="context-block nl">
    <div class="box-header"><h1 class="context-title">{'Welcome series'|i18n( $i18n )} ({$list.name|wash})</h1><div class="header-mainline"></div></div>
    <div class="box-content">
    {if $errors|count}
    <div class="message-error" role="alert"><h2>{'Please check the form'|i18n( $i18n )}</h2>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}
    <form method="post" action={concat( 'newsletter/series_edit/', $list.id )|ezurl}>
    <input type="submit" name="StoreButton" value="{'Save'|i18n( $i18n )|wash}" hidden="hidden" tabindex="-1" />
    <p>{if $series.id|eq( 0 )}<span class="nl-status">{'Not stored yet'|i18n( $i18n )}</span>{elseif $series.paused}<span class="nl-status">{'Paused'|i18n( $i18n )}</span>{else}<span class="nl-status">{'Active'|i18n( $i18n )}</span>{/if}</p>
    <label for="nl-series-audience">{'Audience'|i18n( $i18n )}</label><div><select id="nl-series-audience" name="Audience"><option value="0"{if eq( $series.segment_id, 0 )} selected="selected"{/if}>{'The whole list'|i18n( $i18n )}</option>{foreach $segments as $segment}<option value="{$segment.id}"{if eq( $series.segment_id, $segment.id )} selected="selected"{/if}>{$segment.name|wash}</option>{/foreach}</select></div>
    {if $steps|count}
    <table class="list nl-table"><thead><tr><th scope="col">{'Step'|i18n( $i18n )}</th><th scope="col">{'Edition'|i18n( $i18n )}</th><th scope="col">{'Delay (days)'|i18n( $i18n )}</th><th scope="col">{'Members at this step'|i18n( $i18n )}</th><th scope="col">{'Actions'|i18n( $i18n )}</th></tr></thead><tbody>
    {foreach $steps as $step}
    <tr>
        <td>{$step.position}{if $step.broken} <strong class="nl-field-error">{'Edition missing'|i18n( $i18n )}</strong>{/if}</td>
        <td><label style="position:absolute;left:-9999px" for="nl-series-ed-{$step.id}">{'Edition'|i18n( $i18n )}</label><select id="nl-series-ed-{$step.id}" name="StepEdition[{$step.id}]">{if $step.broken}<option value="{$step.edition_contentobject_id}" selected="selected">{if $step.edition_name}{$step.edition_name|wash}{else}#{$step.edition_contentobject_id}{/if}</option>{/if}{foreach $editions as $edition}<option value="{$edition.id}"{if eq( $step.edition_contentobject_id, $edition.id )} selected="selected"{/if}>{$edition.name|wash}</option>{/foreach}</select></td>
        <td><label style="position:absolute;left:-9999px" for="nl-series-delay-{$step.id}">{'Delay (days)'|i18n( $i18n )}</label><input id="nl-series-delay-{$step.id}" type="number" min="0" name="StepDelay[{$step.id}]" value="{$step.delay_days}" /></td>
        <td>{$step.members}</td>
        <td><input class="button" type="submit" name="MoveUpButton[{$step.id}]" value="{'Move up'|i18n( $i18n )|wash}" /> <input class="button" type="submit" name="MoveDownButton[{$step.id}]" value="{'Move down'|i18n( $i18n )|wash}" /> <input class="button" type="submit" name="SendTestButton[{$step.id}]" value="{'Send to me'|i18n( $i18n )|wash}" /> <input class="button nl-danger" type="submit" name="RemoveStepButton[{$step.id}]" value="{'Remove this step'|i18n( $i18n )|wash}" /></td>
    </tr>
    {/foreach}
    </tbody></table>
    {else}
    <p class="nl-hint">{'The series has no steps yet.'|i18n( $i18n )}</p>
    {/if}
    {if $can_add}
    <fieldset class="nl-ed-fieldset">
        <legend>{'Add a step'|i18n( $i18n )}</legend>
        <label for="nl-series-new-ed">{'Edition'|i18n( $i18n )}</label><div><select id="nl-series-new-ed" name="NewEdition"><option value="0">{'- choose -'|i18n( $i18n )}</option>{foreach $editions as $edition}<option value="{$edition.id}">{$edition.name|wash}</option>{/foreach}</select></div>
        <label for="nl-series-new-delay">{'Delay (days)'|i18n( $i18n )}</label><div><input id="nl-series-new-delay" type="number" min="0" name="NewDelay" value="0" /></div>
        <input class="button" type="submit" name="AddStepButton" value="{'Add the step'|i18n( $i18n )|wash}" />
    </fieldset>
    {else}
    <p class="nl-hint">{'A series has at most %max steps.'|i18n( $i18n,, hash( '%max', $max_steps ) )}</p>
    {/if}
    <div class="block">
        <input class="button" type="submit" name="StoreButton" value="{'Save'|i18n( $i18n )|wash}" />
        {if and( gt( $series.id, 0 ), $series.paused )}<input class="button" type="submit" name="ResumeButton" value="{'Resume'|i18n( $i18n )|wash}" />{/if}
        {if and( gt( $series.id, 0 ), $series.paused|not )}<input class="button" type="submit" name="PauseButton" value="{'Pause'|i18n( $i18n )|wash}" />{/if}
    </div>
    </form>
    </div>
</div>
</div>
{undef $i18n}
