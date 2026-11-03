{* One interest, admin4. Same data and actions as design/standard/templates/newsletter/rendering/interest_edit.tpl.
   Variables: interest, data (the form's values), errors, lists (object id => name), eztags, users. *}
{def $i18n = 'cjw_newsletter/rendering'}
<div class="a4-page newsletter newsletter-interest_edit">
    <div class="a4-pagehead">
        <div class="a4-pagehead-main">
            <h1 class="a4-pagehead-title">{if $interest.id}{'Interest "%name"'|i18n( $i18n,, hash( '%name', $interest.name ) )|wash}{else}{'New interest'|i18n( $i18n )}{/if}</h1>
        </div>
    </div>

    {if $errors|count}
    <div class="a4-notice is-bad a4-error-summary" role="alert">
        <h2 class="a4-notice-title">{'The interest was not saved'|i18n( $i18n )}</h2>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}

    <form action={concat( 'newsletter/interest_edit/', first_set( $interest.id, 0 )|int )|ezurl} method="post">
    <div class="a4-card">
        <div class="a4-form is-wide">
            <div class="a4-field">
                <label for="Interest_name">{'Name'|i18n( $i18n )}</label>
                <input id="Interest_name" type="text" name="Interest_name" value="{$data.name|wash}" maxlength="255" aria-describedby="Interest_name_help" />
                <p class="a4-help" id="Interest_name_help">{'What subscribers see on their preference page.'|i18n( $i18n )}</p>
            </div>
            <div class="a4-field">
                <label for="Interest_identifier">{'Identifier'|i18n( $i18n )}</label>
                <input id="Interest_identifier" type="text" name="Interest_identifier" value="{$data.identifier|wash}" maxlength="100" aria-describedby="Interest_identifier_help" />
                <p class="a4-help" id="Interest_identifier_help">{'Letters a-z, digits and underscores; made from the name when empty. A "newsletter condition" tests it (interest).'|i18n( $i18n )}</p>
            </div>
            <div class="a4-field">
                <label for="Interest_list">{'List'|i18n( $i18n )}</label>
                <select id="Interest_list" name="Interest_list_contentobject_id">
                    <option value="0">{'Every list'|i18n( $i18n )}</option>
                    {foreach $lists as $list_id => $list_name}<option value="{$list_id}"{if $data.list_contentobject_id|eq( $list_id )} selected="selected"{/if}>{$list_name|wash}</option>{/foreach}
                </select>
            </div>
            <fieldset class="a4-field">
                <legend>{'Source'|i18n( $i18n )}</legend>
                <label class="a4-check nl-inline"><input type="radio" name="Interest_source" value="topic"{if $data.source|ne( 'eztags' )} checked="checked"{/if} /> <span>{'Topic (a list offers it when its interest source is "Topics of the list")'|i18n( $i18n )}</span></label>
                <label class="a4-check nl-inline"><input type="radio" name="Interest_source" value="eztags"{if $data.source|eq( 'eztags' )} checked="checked"{/if}{if $eztags|not} disabled="disabled"{/if} /> <span>{'Tag (a list offers it when its interest source is "Tags")'|i18n( $i18n )}</span></label>
            </fieldset>
            <div class="a4-field">
                <label for="Interest_eztags_id">{'Tag id (eztags)'|i18n( $i18n )}</label>
                <input id="Interest_eztags_id" class="cjwnl-a4-narrow" type="number" min="0" name="Interest_eztags_id" value="{$data.eztags_id|wash}" aria-describedby="Interest_eztags_id_help" />
                <p class="a4-help" id="Interest_eztags_id_help">{'Needed for a tag. For a topic optional: the "articles for your interests" block finds articles with this tag; without one it looks for the identifier among the keywords of an article.'|i18n( $i18n )}</p>
            </div>
            <div class="a4-field">
                <label for="Interest_priority">{'Order'|i18n( $i18n )}</label>
                <input id="Interest_priority" class="cjwnl-a4-narrow" type="number" name="Interest_priority" value="{$data.priority|wash}" />
            </div>
            <div class="a4-field">
                <label class="a4-check nl-inline"><input type="checkbox" name="Interest_is_active" value="1"{if $data.is_active} checked="checked"{/if} /> <span>{'Offered to subscribers'|i18n( $i18n )}</span></label>
                {if $users}<p class="a4-help">{'%count subscribers picked it.'|i18n( $i18n,, hash( '%count', $users ) )}</p>{/if}
            </div>
        </div>
    </div>
    <div class="a4-toolbar is-bottom">
        <div class="a4-actions">
            <input class="a4-btn a4-btn-primary" type="submit" name="StoreButton" value="{'Save'|i18n( $i18n )|wash}" />
            <input class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( $i18n )|wash}" />
        </div>
    </div>
    </form>
</div>
{undef $i18n}
