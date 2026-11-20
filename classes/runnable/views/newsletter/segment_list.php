<?php
/**
 * newsletter/segment_list/<list>: the segments of one list (cjw_newsletter 4.3.0, segments).
 *
 * Template design:newsletter/segments/segment_list.tpl receives:
 * - list: hash(id, name) the list (content object id and name)
 * - segments: array of hash(id, name, match_mode (all|any), condition_count, modified (timestamp)), by name
 * - notices: what CjwNewsletterUI::takeNotices() returned
 * New segments are created at newsletter/segment_edit/0/<list id>, existing ones edited at newsletter/segment_edit/<id>/<list id>.
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SegmentList extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $listId = isset( $Params['ListContentObjectId'] ) ? (int)$Params['ListContentObjectId'] : 0;
        if ( !\CjwNewsletterSubscription::isNewsletterListObject( $listId ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $listObject = \eZContentObject::fetch( $listId );

        include_once( 'kernel/common/template.php' );
        $tpl = \eZTemplate::factory();
        $segments = array();
        foreach ( \CjwNewsletterSegment::fetchByList( $listId ) as $segment )
        {
            $segments[] = array( 'id' => (int)$segment->attribute( 'id' ), 'name' => (string)$segment->attribute( 'name' ),
                                 'match_mode' => (string)$segment->attribute( 'match_mode' ),
                                 'condition_count' => count( $segment->conditionArray() ),
                                 'modified' => (int)$segment->attribute( 'modified' ) );
        }
        $tpl->setVariable( 'list', array( 'id' => $listId, 'name' => $listObject ? (string)$listObject->attribute( 'name' ) : '' ) );
        $tpl->setVariable( 'segments', $segments );
        $tpl->setVariable( 'notices', \CjwNewsletterUI::takeNotices() );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/segments/segment_list.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/segments', 'Segments' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
