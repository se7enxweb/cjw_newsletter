{* Skin "shop" (cjw_newsletter 4.2.0), text part (TextFormat=plain, see the company skin). *}
{def $map = $contentobject.data_map
     $i18n = 'cjw_newsletter/rendering'
     $nl = "\n"
     $title = cond( $map.title.has_content, $map.title.content, $contentobject.name )
     $articles = fetch( 'content', 'list', hash( 'parent_node_id', $contentobject.contentobject.main_node_id,
                                                 'sort_by', array( 'priority', true() ),
                                                 'class_filter_type', 'include',
                                                 'class_filter_array', array( 'cjw_newsletter_article' ) ) )}
{set-block variable=$subject scope=root}{cond( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' )|ne( '' ), concat( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' ), ' ' ), '' )}{$title}{/set-block}
{'[[list_name]]'}
{$nl}{$title|cjwnl_text_underline( '=' )}
{$nl}{if $map.description.has_content}{include uri='design:newsletter/rendering/plaintext_attribute.tpl' attribute=$map.description}{$nl}{$nl}{/if}
{foreach $articles as $article}
* {$article.data_map.title.content}{$nl}
{if $article.data_map.short_description.has_content}{include uri='design:newsletter/rendering/plaintext_attribute.tpl' attribute=$article.data_map.short_description}{$nl}{/if}
{'View'|i18n( $i18n )}: {concat( '/', $article.url_alias )|cjwnl_abs_url()}
{$nl}{/foreach}
{cjwnl_interests_block( 4, 'Picked for you'|i18n( $i18n ) )}{$nl}
------------------------------------------------------------------------
{'You receive this newsletter because you subscribed to %list.'|i18n( $i18n,, hash( '%list', '[[list_name]]' ) )}
{'Change your newsletter settings'|i18n( $i18n )}: [[manage_url]]
{'Unsubscribe'|i18n( $i18n )}: [[unsubscribe_url]]
{undef $map $i18n $nl $title $articles}
