<?php
/**
 * File containing the CjwNewsletterStatistics class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage statistics
 */

/**
 * The template fetch functions of the statistics (cjw_newsletter 4.2.0, area N4 Statistics), declared in
 * modules/newsletter/function_definition.php:
 *
 *   fetch( 'newsletter', 'article_statistics', hash( 'contentobject_id', $node.contentobject_id ) )
 *   fetch( 'newsletter', 'send_statistics', hash( 'edition_send_id', $id ) )
 *
 * @package cjw_newsletter
 * @subpackage statistics
 */
class CjwNewsletterStatistics
{
    /** @return array result: the article box's numbers (CjwNewsletterStatisticsReport::article()) */
    function fetchArticleStatistics( $contentObjectId )
    {
        return array( 'result' => CjwNewsletterStatisticsReport::article( (int)$contentObjectId ) );
    }

    /** @return array result: the report of a send, or false */
    function fetchSendStatistics( $editionSendId )
    {
        $send = CjwNewsletterEditionSend::fetch( (int)$editionSendId );
        return array( 'result' => is_object( $send ) ? CjwNewsletterStatisticsReport::send( $send ) : false );
    }
}

?>
