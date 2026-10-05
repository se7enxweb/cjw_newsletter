<?php
/**
 * File containing the CjwNewsletterDeliverabilityFetch class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * The template fetch functions of the area "deliverability" (modules/newsletter/function_definition.php):
 *
 * \code
 * {fetch( 'newsletter', 'test_group_list', hash( 'list_contentobject_id', $list_object_id ) )}
 * \endcode
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterDeliverabilityFetch
{
    /**
     * The test groups of a list and those of every list.
     *
     * @param int $listContentObjectId
     * @return array result: hash( id, name, list_contentobject_id, address_count )[]
     */
    static function fetchTestGroupList( $listContentObjectId )
    {
        $out = array();
        foreach ( CjwNewsletterTestSend::groupsForList( (int)$listContentObjectId ) as $group )
            $out[] = array( 'id' => (int)$group->attribute( 'id' ), 'name' => (string)$group->attribute( 'name' ),
                            'list_contentobject_id' => (int)$group->attribute( 'list_contentobject_id' ),
                            'address_count' => count( CjwNewsletterTestSend::groupAddresses( $group ) ) );
        return array( 'result' => $out );
    }
}

?>
