<?php
/**
 * newsletter/segment_edit/<id>/<list>: create, edit, size-check or remove a segment (cjw_newsletter 4.3.0, segments).
 *
 * POST buttons: StoreButton, ShowSizeButton (evaluates the posted conditions without saving), AddConditionButton,
 * RemoveConditionButton[<row>], DeleteButton (refused while a waiting send uses the segment), CancelButton.
 * Fields: Name, MatchMode (all|any), Condition[<row>][type] and Condition[<row>][<param>].
 *
 * Template design:newsletter/segments/segment_edit.tpl receives:
 * - list: hash(id, name)
 * - segment: hash(id (0 = new), name, match_mode (all|any))
 * - rows: array of hash(type, <param> => string; salutation values and a few more are arrays of strings); the rows as posted
 *   or stored, in order. Params per type: subscribed_within field/days; language values (comma separated text);
 *   output_format format; salutation values[]; profile field/op/value; interest interest_id/picked ('1'|'0');
 *   opened/clicked window/n; not_opened/not_clicked n; clicked_object contentobject_id; clicked_tag tag_id.
 * - types: string[] the condition types to choose from
 * - tags_available: bool whether the type clicked_tag can be used
 * - errors: string[] messages about the form as a whole (name, delete refused)
 * - row_errors: hash row index => error code (CjwNewsletterSegmentCondition::ERROR_*)
 * - preview: false, or hash(size, few (bool), minimum, not_consented (int|null), not_consented_few (bool),
 *   sample (array of hash(email, name, created); empty for users without access to newsletter/subscription_list))
 * When few is true, only "fewer than <minimum>" is to be shown instead of size.
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SegmentEdit extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $id = isset( $Params['SegmentId'] ) ? (int)$Params['SegmentId'] : 0;
        $listId = isset( $Params['ListContentObjectId'] ) ? (int)$Params['ListContentObjectId'] : 0;
        $segment = $id > 0 ? \CjwNewsletterSegment::fetch( $id )
                           : \CjwNewsletterSegment::create( array( 'list_contentobject_id' => $listId ) );
        if ( !$segment || (int)$segment->attribute( 'list_contentobject_id' ) !== $listId
            || !\CjwNewsletterSubscription::isNewsletterListObject( $listId ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $listObject = \eZContentObject::fetch( $listId );
        $back = '/newsletter/segment_list/' . $listId;

        $post = array();
        foreach ( array( 'Name', 'MatchMode', 'Condition' ) as $field )
            if ( $http->hasPostVariable( $field ) )
                $post[$field] = $http->postVariable( $field );
        $posted = $http->hasPostVariable( 'StoreButton' ) || $http->hasPostVariable( 'ShowSizeButton' )
            || $http->hasPostVariable( 'AddConditionButton' ) || $http->hasPostVariable( 'RemoveConditionButton' );

        if ( $http->hasPostVariable( 'CancelButton' ) )
            return $this->viewResult( null, $module->redirectTo( $back ) );

        $errors = array();
        $rowErrors = array();
        $preview = false;
        $name = $posted ? trim( isset( $post['Name'] ) ? (string)$post['Name'] : '' ) : (string)$segment->attribute( 'name' );
        $matchMode = $posted ? \CjwNewsletterSegmentForm::matchMode( $post ) : (string)$segment->attribute( 'match_mode' );
        $rows = $posted ? \CjwNewsletterSegmentForm::rows( $post ) : \CjwNewsletterSegmentForm::fromConditions( $segment->conditionArray() );
        $evaluator = new \CjwNewsletterSegmentEvaluator( new \CjwNewsletterSegmentEzDb() );
        $tagsAvailable = $evaluator->tagsAvailable();

        if ( $http->hasPostVariable( 'DeleteButton' ) && $id > 0 )
        {
            if ( $segment->isReferencedByWaitingSend() )
                $errors[] = \ezpI18n::tr( 'cjw_newsletter/segments', 'The segment cannot be removed while a send that is waiting or scheduled uses it.' );
            else
            {
                $segment->remove();
                \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/segments', 'The segment was removed.' ) );
                return $this->viewResult( null, $module->redirectTo( $back ) );
            }
        }
        else if ( $http->hasPostVariable( 'AddConditionButton' ) )
            $rows = \CjwNewsletterSegmentForm::addRow( $rows );
        else if ( $http->hasPostVariable( 'RemoveConditionButton' ) )
        {
            $remove = $http->postVariable( 'RemoveConditionButton' );
            $rows = \CjwNewsletterSegmentForm::removeRow( $rows, is_array( $remove ) ? key( $remove ) : -1 );
        }
        else if ( $http->hasPostVariable( 'StoreButton' ) || $http->hasPostVariable( 'ShowSizeButton' ) )
        {
            $store = $http->hasPostVariable( 'StoreButton' );
            if ( $store && $name === '' )
                $errors[] = \ezpI18n::tr( 'cjw_newsletter/segments', 'Enter a name for the segment.' );
            $checked = \CjwNewsletterSegmentForm::validate( $rows, $tagsAvailable );
            $rowErrors = $checked['errors'];
            if ( !$rows )
                $errors[] = \ezpI18n::tr( 'cjw_newsletter/segments', 'Add at least one condition.' );
            if ( !$errors && !$rowErrors )
            {
                if ( $store )
                {
                    $now = time();
                    $segment->setAttribute( 'name', $name );
                    $segment->setAttribute( 'match_mode', $matchMode );
                    $segment->setConditionArray( $checked['conditions'] );
                    if ( $id < 1 )
                    {
                        $segment->setAttribute( 'creator_contentobject_id', (int)\eZUser::currentUserID() );
                        $segment->setAttribute( 'created', $now );
                    }
                    $segment->setAttribute( 'modified', $now );
                    $segment->store();
                    \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/segments', 'The segment "%name" was stored.', null,
                                                                       array( '%name' => $name ) ) );
                    return $this->viewResult( null, $module->redirectTo( $back ) );
                }
                $preview = \CjwNewsletterSegmentHooks::preview( $listId, $matchMode, $checked['conditions'] );
            }
        }

        include_once( 'kernel/common/template.php' );
        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'list', array( 'id' => $listId, 'name' => $listObject ? (string)$listObject->attribute( 'name' ) : '' ) );
        $tpl->setVariable( 'segment', array( 'id' => $id, 'name' => $name, 'match_mode' => $matchMode ) );
        $tpl->setVariable( 'rows', $rows );
        $tpl->setVariable( 'types', \CjwNewsletterSegmentCondition::$types );
        $tpl->setVariable( 'tags_available', $tagsAvailable );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'row_errors', $rowErrors );
        $tpl->setVariable( 'preview', $preview );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/segments/segment_edit.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => 'newsletter/segment_list/' . $listId, 'text' => \ezpI18n::tr( 'cjw_newsletter/segments', 'Segments' ) ),
                                 array( 'url' => false, 'text' => $id ? $name : \ezpI18n::tr( 'cjw_newsletter/segments', 'New segment' ) ) );
        return $this->viewResult( $Result, null );
    }
}

}
