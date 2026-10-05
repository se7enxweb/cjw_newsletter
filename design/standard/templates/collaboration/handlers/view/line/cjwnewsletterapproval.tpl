{* A newsletter approval in the list of the collaboration inbox (cjw_newsletter 4.2.0, area editorial).
   Variables: item, item_class. *}
{def $edition = fetch( 'content', 'object', hash( 'object_id', $item.content.content_object_id ) )
     $name = cond( $edition, $edition.name, concat( '#', $item.content.content_object_id ) )
     $text = ''}
{switch match=$item.data_int3}
{case match=1}{set $text = '"%1" was approved for sending'|i18n( 'cjw_newsletter/editorial',, array( $name|wash ) )}{/case}
{case match=2}{set $text = '"%1" was rejected'|i18n( 'cjw_newsletter/editorial',, array( $name|wash ) )}{/case}
{case match=3}{set $text = '"%1": the request was replaced by a newer one'|i18n( 'cjw_newsletter/editorial',, array( $name|wash ) )}{/case}
{case}{if $item.is_creator}{set $text = '"%1" waits for the approval of the newsletter'|i18n( 'cjw_newsletter/editorial',, array( $name|wash ) )}{else}{set $text = '"%1" waits for your approval before it is sent'|i18n( 'cjw_newsletter/editorial',, array( $name|wash ) )}{/if}{/case}
{/switch}
<p class="{$item_class}"><a class="{$item_class}" href={concat( 'collaboration/item/full/', $item.id )|ezurl}>{$text}</a></p>
{undef $edition $name $text}
