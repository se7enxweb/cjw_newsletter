{* newsletter/segments/segment_edit.tpl, admin4. Variables: list, segment, rows, types, tags_available, errors, row_errors, preview. *}
{def $i18n = 'cjw_newsletter/segments'
     $type_names = hash(
       'subscribed_within', 'Subscribed within'|i18n( $i18n ),
       'language', 'Language'|i18n( $i18n ),
       'output_format', 'Format'|i18n( $i18n ),
       'salutation', 'Salutation'|i18n( $i18n ),
       'profile', 'Profile field'|i18n( $i18n ),
       'interest', 'Interest'|i18n( $i18n ),
       'opened', 'Opened'|i18n( $i18n ),
       'clicked', 'Clicked'|i18n( $i18n ),
       'not_opened', 'Did not open'|i18n( $i18n ),
       'not_clicked', 'Did not click'|i18n( $i18n ),
       'clicked_object', 'Clicked a content object'|i18n( $i18n ),
       'clicked_tag', 'Clicked a tag'|i18n( $i18n ) )
     $error_names = hash(
       'unknown_type', 'Choose a condition type.'|i18n( $i18n ),
       'missing_param', 'A value is missing.'|i18n( $i18n ),
       'invalid_param', 'A value is not valid.'|i18n( $i18n ),
       'field_not_allowed', 'This field cannot be used.'|i18n( $i18n ),
       'tags_unavailable', 'Tags are not available here.'|i18n( $i18n ) )}
<div class="a4-page newsletter newsletter-segment_edit">
    <div class="a4-pagehead"><div class="a4-pagehead-main"><h1 class="a4-pagehead-title"><span class="a4-icon a4-bi-signpost-split" aria-hidden="true"></span> {if $segment.id}{'Edit the segment'|i18n( $i18n )}: {$segment.name|wash}{else}{'New segment'|i18n( $i18n )}{/if} ({$list.name|wash})</h1></div></div>
    {if $errors|count}
    <div class="a4-notice is-bad a4-error-summary" role="alert"><h2 class="a4-notice-title">{'Please check the form'|i18n( 'cjw_newsletter/segments' )}</h2>
        <ul>{foreach $errors as $error}<li>{$error|wash}</li>{/foreach}</ul>
    </div>
    {/if}
    <form method="post" action={concat( 'newsletter/segment_edit/', $segment.id, '/', $list.id )|ezurl}>
    <input type="submit" name="StoreButton" value="{'Save'|i18n( 'cjw_newsletter/segments' )|wash}" hidden="hidden" tabindex="-1" />
    <div class="a4-field"><label for="nl-seg-name">{'Name'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-name" type="text" name="Name" value="{$segment.name|wash}" maxlength="255" /></div>
    <fieldset class="a4-field"><legend>{'Members must match'|i18n( 'cjw_newsletter/segments' )}</legend><label class="a4-check"><input type="radio" name="MatchMode" value="all"{if ne( $segment.match_mode, 'any' )} checked="checked"{/if} /> {'all conditions'|i18n( 'cjw_newsletter/segments' )}</label> <label class="a4-check"><input type="radio" name="MatchMode" value="any"{if eq( $segment.match_mode, 'any' )} checked="checked"{/if} /> {'any condition'|i18n( 'cjw_newsletter/segments' )}</label></fieldset>
    <div>
        {foreach $rows as $r => $row}
        <fieldset class="a4-card">
            <legend>{'Condition %n'|i18n( $i18n,, hash( '%n', $r|inc ) )}</legend>
            {if is_set( $row_errors[$r] )}<p class="a4-bad-text" role="alert">{$error_names[$row_errors[$r]]|wash}</p>{/if}
            <div class="a4-field"><label for="nl-seg-{$r}-type">{'Type'|i18n( 'cjw_newsletter/segments' )}</label><select id="nl-seg-{$r}-type" name="Condition[{$r}][type]"><option value="">{'- choose -'|i18n( 'cjw_newsletter/segments' )}</option>{foreach $types as $ty}<option value="{$ty}"{if eq( first_set( $row.type, '' ), $ty )} selected="selected"{/if}>{$type_names[$ty]|wash}</option>{/foreach}</select></div>
            {switch match=first_set( $row.type, '' )}
{case match='subscribed_within'}
<div class="a4-field"><label for="nl-seg-{$r}-field">{'Date'|i18n( 'cjw_newsletter/segments' )}</label><select id="nl-seg-{$r}-field" name="Condition[{$r}][field]"><option value="created"{if eq( first_set( $row.field, '' ), 'created' )} selected="selected"{/if}>{'subscribed'|i18n( 'cjw_newsletter/segments' )}</option><option value="approved"{if eq( first_set( $row.field, '' ), 'approved' )} selected="selected"{/if}>{'confirmed'|i18n( 'cjw_newsletter/segments' )}</option></select></div>
<div class="a4-field"><label for="nl-seg-{$r}-days">{'Within the last days'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-days" type="number" min="1" name="Condition[{$r}][days]" value="{first_set( $row.days, '' )|wash}" /></div>
{/case}
{case match='language'}
<div class="a4-field"><label for="nl-seg-{$r}-values">{'Languages (comma separated codes)'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-values" type="text" name="Condition[{$r}][values]" value="{first_set( $row.values, '' )|wash}" /></div>
{/case}
{case match='output_format'}
<div class="a4-field"><label for="nl-seg-{$r}-format">{'Format'|i18n( 'cjw_newsletter/segments' )}</label><select id="nl-seg-{$r}-format" name="Condition[{$r}][format]"><option value="html"{if eq( first_set( $row.format, '' ), 'html' )} selected="selected"{/if}>HTML</option><option value="text"{if eq( first_set( $row.format, '' ), 'text' )} selected="selected"{/if}>{'Text'|i18n( 'cjw_newsletter/segments' )}</option></select></div>
{/case}
{case match='salutation'}
<fieldset class="a4-field"><legend>{'Salutation'|i18n( 'cjw_newsletter/segments' )}</legend><label class="a4-check"><input type="checkbox" name="Condition[{$r}][values][]" value="0"{if and( is_array( first_set( $row.values, false() ) ), $row.values|contains( '0' ) )} checked="checked"{/if} /> {'not specified'|i18n( 'cjw_newsletter/segments' )}</label><label class="a4-check"><input type="checkbox" name="Condition[{$r}][values][]" value="1"{if and( is_array( first_set( $row.values, false() ) ), $row.values|contains( '1' ) )} checked="checked"{/if} /> {'Mr'|i18n( 'cjw_newsletter/segments' )}</label><label class="a4-check"><input type="checkbox" name="Condition[{$r}][values][]" value="2"{if and( is_array( first_set( $row.values, false() ) ), $row.values|contains( '2' ) )} checked="checked"{/if} /> {'Ms'|i18n( 'cjw_newsletter/segments' )}</label></fieldset>
{/case}
{case match='profile'}
<div class="a4-field"><label for="nl-seg-{$r}-field">{'Profile field'|i18n( 'cjw_newsletter/segments' )}</label><select id="nl-seg-{$r}-field" name="Condition[{$r}][field]"><option value="first_name"{if eq( first_set( $row.field, '' ), 'first_name' )} selected="selected"{/if}>first_name</option><option value="last_name"{if eq( first_set( $row.field, '' ), 'last_name' )} selected="selected"{/if}>last_name</option><option value="organisation"{if eq( first_set( $row.field, '' ), 'organisation' )} selected="selected"{/if}>organisation</option><option value="custom_data_text_1"{if eq( first_set( $row.field, '' ), 'custom_data_text_1' )} selected="selected"{/if}>custom_data_text_1</option><option value="custom_data_text_2"{if eq( first_set( $row.field, '' ), 'custom_data_text_2' )} selected="selected"{/if}>custom_data_text_2</option><option value="custom_data_text_3"{if eq( first_set( $row.field, '' ), 'custom_data_text_3' )} selected="selected"{/if}>custom_data_text_3</option><option value="custom_data_text_4"{if eq( first_set( $row.field, '' ), 'custom_data_text_4' )} selected="selected"{/if}>custom_data_text_4</option></select></div>
<div class="a4-field"><label for="nl-seg-{$r}-op">{'Operator'|i18n( 'cjw_newsletter/segments' )}</label><select id="nl-seg-{$r}-op" name="Condition[{$r}][op]"><option value="set"{if eq( first_set( $row.op, '' ), 'set' )} selected="selected"{/if}>{'is filled'|i18n( 'cjw_newsletter/segments' )}</option><option value="not_set"{if eq( first_set( $row.op, '' ), 'not_set' )} selected="selected"{/if}>{'is empty'|i18n( 'cjw_newsletter/segments' )}</option><option value="equals"{if eq( first_set( $row.op, '' ), 'equals' )} selected="selected"{/if}>{'equals'|i18n( 'cjw_newsletter/segments' )}</option><option value="contains"{if eq( first_set( $row.op, '' ), 'contains' )} selected="selected"{/if}>{'contains'|i18n( 'cjw_newsletter/segments' )}</option></select></div>
<div class="a4-field"><label for="nl-seg-{$r}-value">{'Value (for equals and contains)'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-value" type="text" name="Condition[{$r}][value]" value="{first_set( $row.value, '' )|wash}" /></div>
{/case}
{case match='interest'}
<div class="a4-field"><label for="nl-seg-{$r}-interest_id">{'Interest ID'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-interest_id" type="number" min="1" name="Condition[{$r}][interest_id]" value="{first_set( $row.interest_id, '' )|wash}" /></div>
<div class="a4-field"><label for="nl-seg-{$r}-picked">{'Picked'|i18n( 'cjw_newsletter/segments' )}</label><select id="nl-seg-{$r}-picked" name="Condition[{$r}][picked]"><option value="1"{if eq( first_set( $row.picked, '' ), '1' )} selected="selected"{/if}>{'picked'|i18n( 'cjw_newsletter/segments' )}</option><option value="0"{if eq( first_set( $row.picked, '' ), '0' )} selected="selected"{/if}>{'not picked'|i18n( 'cjw_newsletter/segments' )}</option></select></div>
{/case}
{case match='opened'}
<div class="a4-field"><label for="nl-seg-{$r}-window">{'Within'|i18n( 'cjw_newsletter/segments' )}</label><select id="nl-seg-{$r}-window" name="Condition[{$r}][window]"><option value="sends"{if eq( first_set( $row.window, '' ), 'sends' )} selected="selected"{/if}>{'the last sends'|i18n( 'cjw_newsletter/segments' )}</option><option value="days"{if eq( first_set( $row.window, '' ), 'days' )} selected="selected"{/if}>{'the last days'|i18n( 'cjw_newsletter/segments' )}</option></select></div>
<div class="a4-field"><label for="nl-seg-{$r}-n">{'Number'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-n" type="number" min="1" name="Condition[{$r}][n]" value="{first_set( $row.n, '' )|wash}" /></div>
{/case}
{case match='clicked'}
<div class="a4-field"><label for="nl-seg-{$r}-window">{'Within'|i18n( 'cjw_newsletter/segments' )}</label><select id="nl-seg-{$r}-window" name="Condition[{$r}][window]"><option value="sends"{if eq( first_set( $row.window, '' ), 'sends' )} selected="selected"{/if}>{'the last sends'|i18n( 'cjw_newsletter/segments' )}</option><option value="days"{if eq( first_set( $row.window, '' ), 'days' )} selected="selected"{/if}>{'the last days'|i18n( 'cjw_newsletter/segments' )}</option></select></div>
<div class="a4-field"><label for="nl-seg-{$r}-n">{'Number'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-n" type="number" min="1" name="Condition[{$r}][n]" value="{first_set( $row.n, '' )|wash}" /></div>
{/case}
{case match='not_opened'}
<div class="a4-field"><label for="nl-seg-{$r}-n">{'In a row (sends)'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-n" type="number" min="1" name="Condition[{$r}][n]" value="{first_set( $row.n, '' )|wash}" /></div>
{/case}
{case match='not_clicked'}
<div class="a4-field"><label for="nl-seg-{$r}-n">{'In a row (sends)'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-n" type="number" min="1" name="Condition[{$r}][n]" value="{first_set( $row.n, '' )|wash}" /></div>
{/case}
{case match='clicked_object'}
<div class="a4-field"><label for="nl-seg-{$r}-contentobject_id">{'Content object ID'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-contentobject_id" type="number" min="1" name="Condition[{$r}][contentobject_id]" value="{first_set( $row.contentobject_id, '' )|wash}" /></div>
{/case}
{case match='clicked_tag'}
<div class="a4-field"><label for="nl-seg-{$r}-tag_id">{'Tag ID'|i18n( 'cjw_newsletter/segments' )}</label><input id="nl-seg-{$r}-tag_id" type="number" min="1" name="Condition[{$r}][tag_id]" value="{first_set( $row.tag_id, '' )|wash}" /></div>
{/case}
{case}
<p class="a4-help">{'Choose a type and press "Show size"; the parameters of the type then appear.'|i18n( 'cjw_newsletter/segments' )}</p>
{/case}
{/switch}
            <input class="a4-btn" type="submit" name="RemoveConditionButton[{$r}]" value="{'Remove this condition'|i18n( 'cjw_newsletter/segments' )|wash}" />
        </fieldset>
        {/foreach}
    </div>
    <p><input class="a4-btn" type="submit" name="AddConditionButton" value="{'Add a condition'|i18n( 'cjw_newsletter/segments' )|wash}" /></p>
    {if $preview}
    <div class="a4-notice is-ok" role="status">
        <h2 class="a4-notice-title">{'Size'|i18n( 'cjw_newsletter/segments' )}</h2>
        <p>{if $preview.few}{'fewer than %minimum recipients'|i18n( 'cjw_newsletter/segments',, hash( '%minimum', $preview.minimum ) )}{else}<strong>{$preview.size}</strong> {'recipients'|i18n( 'cjw_newsletter/segments' )}{/if}</p>
        {if $preview.not_consented|ne( null )}<p>{if $preview.not_consented_few}{'Of these, fewer than %minimum have not yet consented to this kind of mail.'|i18n( 'cjw_newsletter/segments',, hash( '%minimum', $preview.minimum ) )}{else}{'Of these, %count have not yet consented to this kind of mail.'|i18n( 'cjw_newsletter/segments',, hash( '%count', $preview.not_consented ) )}{/if}</p>{/if}
        {if $preview.sample|count}
        <table class="a4-table"><thead><tr><th scope="col">{'E-mail'|i18n( 'cjw_newsletter/segments' )}</th><th scope="col">{'Name'|i18n( 'cjw_newsletter/segments' )}</th><th scope="col">{'Subscribed'|i18n( 'cjw_newsletter/segments' )}</th></tr></thead><tbody>
        {foreach $preview.sample as $s}<tr><td>{$s.email|wash}</td><td>{$s.name|wash}</td><td>{$s.created|l10n( 'shortdate' )}</td></tr>{/foreach}
        </tbody></table>
        {/if}
    </div>
    {/if}
    <div class="a4-toolbar is-bottom">
        <input class="a4-btn a4-btn-primary" type="submit" name="StoreButton" value="{'Save'|i18n( 'cjw_newsletter/segments' )|wash}" /> <input class="a4-btn" type="submit" name="ShowSizeButton" value="{'Show size'|i18n( 'cjw_newsletter/segments' )|wash}" /> <input class="a4-btn" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'cjw_newsletter/segments' )|wash}" /> {if gt( $segment.id, 0 )}<input class="a4-btn a4-btn-danger nl-danger" type="submit" name="DeleteButton" value="{'Remove the segment'|i18n( 'cjw_newsletter/segments' )|wash}" />{/if}
    </div>
    </form>
</div>
{undef $i18n $type_names $error_names}
