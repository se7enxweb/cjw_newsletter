{*  newsletter/rendering/interest_edit.tpl (cjw_newsletter 4.2.0, area rendering)

    One interest. Variables: interest, data (the form's values), errors, lists (object id => name), eztags, users.
*}
{ezcss_require( array( 'newsletter_ui.css' ) )}
{def $i18n = 'cjw_newsletter/rendering'}
<div class="newsletter newsletter-interest_edit">
<form class="context-block nl" action={concat( 'newsletter/interest_edit/', first_set( $interest.id, 0 )|int )|ezurl} method="post">
    <div class="box-header">
        <h1 class="context-title">{if $interest.id}{'Interest "%name"'|i18n( $i18n,, hash( '%name', $interest.name ) )|wash}{else}{'New interest'|i18n( $i18n )}{/if}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        {if $errors|count}
        <div class="message-error">
            <h2>{'The interest was not saved'|i18n( $i18n )}</h2>
            <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
        </div>
        {/if}

        <div class="block">
            <label for="Interest_name">{'Name'|i18n( $i18n )}</label>
            <input class="halfbox" id="Interest_name" type="text" name="Interest_name" value="{$data.name|wash}" maxlength="255" />
            <p class="nl-hint">{'What subscribers see on their preference page.'|i18n( $i18n )}</p>
        </div>
        <div class="block">
            <label for="Interest_identifier">{'Identifier'|i18n( $i18n )}</label>
            <input class="halfbox" id="Interest_identifier" type="text" name="Interest_identifier" value="{$data.identifier|wash}" maxlength="100" />
            <p class="nl-hint">{'Letters a-z, digits and underscores; made from the name when empty. A "newsletter condition" tests it (interest).'|i18n( $i18n )}</p>
        </div>
        <div class="block">
            <label for="Interest_list">{'List'|i18n( $i18n )}</label>
            <select id="Interest_list" name="Interest_list_contentobject_id">
                <option value="0">{'Every list'|i18n( $i18n )}</option>
                {foreach $lists as $list_id => $list_name}<option value="{$list_id}"{if $data.list_contentobject_id|eq( $list_id )} selected="selected"{/if}>{$list_name|wash}</option>{/foreach}
            </select>
        </div>
        <div class="block">
            <label>{'Source'|i18n( $i18n )}</label>
            <label class="nl-inline"><input type="radio" name="Interest_source" value="topic"{if $data.source|ne( 'eztags' )} checked="checked"{/if} /> {'Topic (a list offers it when its interest source is "Topics of the list")'|i18n( $i18n )}</label><br />
            <label class="nl-inline"><input type="radio" name="Interest_source" value="eztags"{if $data.source|eq( 'eztags' )} checked="checked"{/if}{if $eztags|not} disabled="disabled"{/if} /> {'Tag (a list offers it when its interest source is "Tags")'|i18n( $i18n )}</label>
        </div>
        <div class="block">
            <label for="Interest_eztags_id">{'Tag id (eztags)'|i18n( $i18n )}</label>
            <input class="box" style="max-width:10em" id="Interest_eztags_id" type="number" min="0" name="Interest_eztags_id" value="{$data.eztags_id|wash}" />
            <p class="nl-hint">{'Needed for a tag. For a topic optional: the "articles for your interests" block finds articles with this tag; without one it looks for the identifier among the keywords of an article.'|i18n( $i18n )}</p>
        </div>
        <div class="block">
            <label for="Interest_priority">{'Order'|i18n( $i18n )}</label>
            <input class="box" style="max-width:10em" id="Interest_priority" type="number" name="Interest_priority" value="{$data.priority|wash}" />
        </div>
        <div class="block">
            <label class="nl-inline"><input type="checkbox" name="Interest_is_active" value="1"{if $data.is_active} checked="checked"{/if} /> {'Offered to subscribers'|i18n( $i18n )}</label>
            {if $users}<p class="nl-hint">{'%count subscribers picked it.'|i18n( $i18n,, hash( '%count', $users ) )}</p>{/if}
        </div>
    </div>
    <div class="controlbar">
        <div class="block">
            <input class="defaultbutton" type="submit" name="StoreButton" value="{'Save'|i18n( $i18n )|wash}" />
            <input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( $i18n )|wash}" />
        </div>
    </div>
</form>
</div>
{undef $i18n}
