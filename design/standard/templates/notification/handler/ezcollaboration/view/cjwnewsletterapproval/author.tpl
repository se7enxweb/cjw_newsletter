{* The notification mail to the editor who asked for the approval of a newsletter edition (cjw_newsletter 4.2.0, area editorial). Variables: collaboration_item. *}
{def $edition = fetch( 'content', 'object', hash( 'object_id', $collaboration_item.content.content_object_id ) )
     $name = cond( $edition, $edition.name, '' )}
{set-block scope=root variable=subject}{'[%sitename] The approval of the newsletter "%name"'|i18n( 'cjw_newsletter/editorial',, hash( '%sitename', ezini( 'SiteSettings', 'SiteURL' ), '%name', $name ) )}{/set-block}
{'The approval of the newsletter edition "%name" at %sitename has changed. See the request:'|i18n( 'cjw_newsletter/editorial',, hash( '%sitename', ezini( 'SiteSettings', 'SiteURL' ), '%name', $name ) )}

http://{ezini( 'SiteSettings', 'SiteURL' )}{concat( 'collaboration/item/full/', $collaboration_item.id )|ezurl( no )}

{'If you do not want to receive these notifications, change your settings at:'|i18n( 'cjw_newsletter/editorial' )}
http://{ezini( 'SiteSettings', 'SiteURL' )}{'notification/settings/'|ezurl( no )}
{undef $edition $name}
