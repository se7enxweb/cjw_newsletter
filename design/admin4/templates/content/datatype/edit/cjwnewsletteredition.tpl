{* The newsletter edition attribute in the object edit form, admin4. Same data as
   design/standard/templates/content/datatype/edit/cjwnewsletteredition.tpl: the state of the edition as admin4's
   badge (cjw_newsletter_edition_status.tpl) and the preview of the edition. *}
{if is_unset( $attribute_base )}
    {def $attribute_base = 'ContentObjectAttribute'}
{/if}
{def $edition_object = $attribute.content
     $newsletter_edition_status = $edition_object.status}

<div class="a4-stack">
    <dl class="a4-facts">
        <div><dt>{'Status:'|i18n( 'cjw_newsletter/datatype/cjwnewsletteredition' )|trim( ':' )}</dt><dd>{include uri='design:cjw_newsletter_edition_status.tpl' newsletter_edition_status=$newsletter_edition_status}</dd></div>
    </dl>
    {include uri='design:includes/cjwnewsletteredition_preview.tpl' newsletter_edition_attribute=$attribute}
</div>
{undef}
