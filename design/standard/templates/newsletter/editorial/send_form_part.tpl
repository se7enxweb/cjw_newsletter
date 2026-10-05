{*  newsletter/editorial/send_form_part.tpl (cjw_newsletter 4.2.0, area editorial)

    The part of the send form ([ExtensionPointSettings] SendFormParts[]): the approval state of the version and the
    articles taken from the pool, with links to the approval and to the picker. It is inside the send form, so it
    has links only, no form of its own. Variables: node_id, object_version.
*}
{if and( $object_version, is_set( $object_version.contentobject_id ) )}
{def $ap = fetch( 'newsletter', 'approval_state', hash( 'edition_contentobject_id', $object_version.contentobject_id, 'version', $object_version.version ) )
     $picks = fetch( 'newsletter', 'edition_articles', hash( 'edition_contentobject_id', $object_version.contentobject_id ) )}
{ezcss_require( 'newsletter_editorial.css' )}
<div class="nl nl-ed-part nl-ed-send-part">
    <h4>{'Editorial'|i18n( 'cjw_newsletter/editorial' )}</h4>
    {if and( $ap, $ap.required )}
    <p>{'Approval'|i18n( 'cjw_newsletter/editorial' )}: {include uri='design:newsletter/editorial/approval_pill.tpl' state=$ap.state}
        <a href={concat( 'newsletter/approval/', $object_version.contentobject_id, '/', $object_version.version )|ezurl}>{if $ap.may_send}{'Details'|i18n( 'cjw_newsletter/editorial' )}{else}{'Ask for or give the approval'|i18n( 'cjw_newsletter/editorial' )}{/if}</a></p>
    {if $ap.may_send|not}<p class="nl-danger">{'The list needs an approval: this version cannot be sent before it is approved.'|i18n( 'cjw_newsletter/editorial' )}</p>{/if}
    {/if}
    <p>{'Articles from the pool'|i18n( 'cjw_newsletter/editorial' )}: <strong>{$picks|count}</strong>
        {if $node_id}<a href={concat( 'newsletter/article_pool/', $node_id )|ezurl}>{'Pick articles'|i18n( 'cjw_newsletter/editorial' )}</a>{/if}</p>
</div>
{undef $ap $picks}
{/if}
