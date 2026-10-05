{* Skin "news" (cjw_newsletter 4.2.0), text part (TextFormat=plain, see the company skin). *}
{def $map = $contentobject.data_map
     $i18n = 'cjw_newsletter/rendering'
     $nl = "\n"
     $title = cond( $map.title.has_content, $map.title.content, $contentobject.name )
     $articles = fetch( 'content', 'list', hash( 'parent_node_id', $contentobject.contentobject.main_node_id,
                                                 'sort_by', array( 'priority', true() ),
                                                 'class_filter_type', 'include',
                                                 'class_filter_array', array( 'cjw_newsletter_article' ) ) )}
{set-block variable=$subject scope=root}{cond( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' )|ne( '' ), concat( ezini( 'NewsletterMailSettings', 'EmailSubjectPrefix', 'cjw_newsletter.ini' ), ' ' ), '' )}{$title}{/set-block}
{'[[list_name]]'} | {currentdate()|l10n( 'date' )}
========================================================================
{$nl}{$title|upcase}
{$nl}{if $map.description.has_content}{include uri='design:newsletter/rendering/plaintext_attribute.tpl' attribute=$map.description}{$nl}{$nl}{/if}
{foreach $articles as $index => $article}
{if $index|eq( 0 )}{concat( 'Top story'|i18n( $i18n )|upcase, ': ', $article.data_map.title.content )|cjwnl_text_underline( '=' )}{else}{$article.data_map.title.content|cjwnl_text_underline( '-' )}{/if}{$nl}
{$nl}{if $article.data_map.short_description.has_content}{include uri='design:newsletter/rendering/plaintext_attribute.tpl' attribute=$article.data_map.short_description}{$nl}{$nl}{/if}
{/foreach}
{cjwnl_interests_block( 5, 'More for your interests'|i18n( $i18n ) )}{$nl}
========================================================================
{'You receive this newsletter because you subscribed to %list.'|i18n( $i18n,, hash( '%list', '[[list_name]]' ) )}
{'Change your newsletter settings'|i18n( $i18n )}: [[manage_url]]
{'Unsubscribe'|i18n( $i18n )}: [[unsubscribe_url]]
{undef $map $i18n $nl $title $articles}
