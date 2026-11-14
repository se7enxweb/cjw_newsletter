{* The editorial part of the list attribute edit ([ExtensionPointSettings] ListEditParts[]), admin4. Same data and POST
   names as design/standard/templates/newsletter/editorial/list_edit_part.tpl: approval before sending, and the
   article pool of the list. Variables: attribute, attribute_base, list_object *}
{def $prefix = concat( $attribute_base, '_CjwNewsletterList_' )
     $postfix = concat( '_', $attribute.id )
     $pools = fetch( 'newsletter', 'article_pool_list', hash() )}
<input type="hidden" name="{$prefix}EditorialPart{$postfix}" value="1" />
<fieldset class="a4-field">
    <legend>{'Approval'|i18n( 'cjw_newsletter/editorial' )}</legend>
    <label class="a4-check"><input type="radio" name="{$prefix}ApprovalRequired{$postfix}" value="0"{if $list_object.approval_required|not} checked="checked"{/if} /> <span>{'no'|i18n( 'cjw_newsletter/editorial' )}</span></label>
    <label class="a4-check"><input type="radio" name="{$prefix}ApprovalRequired{$postfix}" value="1"{if $list_object.approval_required} checked="checked"{/if} /> <span>{'yes, an edition is sent only after its approval (collaboration inbox)'|i18n( 'cjw_newsletter/editorial' )}</span></label>
</fieldset>
<div class="a4-field">
    <label for="{$prefix}ArticlePoolId{$postfix}">{'Article pool'|i18n( 'cjw_newsletter/editorial' )}</label>
    <select id="{$prefix}ArticlePoolId{$postfix}" name="{$prefix}ArticlePoolId{$postfix}">
        <option value="0">{'The pool made for the list, else the global default'|i18n( 'cjw_newsletter/editorial' )}</option>
        {foreach $pools as $p}<option value="{$p.id}"{if eq( $p.id, $list_object.article_pool_id )} selected="selected"{/if}>{$p.label|wash}{if $p.list_name} ({$p.list_name|wash}){/if}</option>{/foreach}
    </select>
</div>
{undef $prefix $postfix $pools}
