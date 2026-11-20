<?php
/**
 * newsletter/series_edit/<list>: edit the welcome series of a list (steps, audience, pause and resume, test mail).
 *
 * POST buttons: StoreButton, AddStepButton, RemoveStepButton[<step id>], MoveUpButton[<step id>], MoveDownButton[<step id>],
 * PauseButton, ResumeButton, SendTestButton[<step id>] (test mail to the current user only).
 * Fields: Audience (segment id, 0 = whole list), StepEdition[<step id>], StepDelay[<step id>], NewEdition, NewDelay.
 * A series is created, paused, on the first store or added step. Removing a step leaves a gap in the positions;
 * members keep the position they last received.
 *
 * Template design:newsletter/series/series_edit.tpl receives:
 * - list: hash(id, name)
 * - series: hash(id (0 = not stored yet), paused (bool), segment_id)
 * - steps: array of hash(id, position, edition_contentobject_id, edition_name, delay_days, members, broken (bool));
 *   members = members waiting for this step (last position is the one before it, no send pending)
 * - editions: array of hash(id, name), the editions of the list
 * - segments: array of hash(id, name), the segments of the list
 * - errors: string[]
 * - max_steps: int
 * - can_add: bool whether another step fits
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class SeriesEdit extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $module = $Params['Module'];
        $http = \eZHTTPTool::instance();
        $listId = isset( $Params['ListContentObjectId'] ) ? (int)$Params['ListContentObjectId'] : 0;
        if ( !\CjwNewsletterSubscription::isNewsletterListObject( $listId ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $listObject = \eZContentObject::fetch( $listId );
        $self = '/newsletter/series_edit/' . $listId;

        $series = \CjwNewsletterSeries::fetchByList( $listId );
        $maxSteps = max( 1, (int)\eZINI::instance( 'cjw_newsletter.ini' )->variable( 'SeriesSettings', 'MaxSteps' ) );
        $editions = $this->editions( $listObject );
        $segmentNames = array();
        foreach ( \CjwNewsletterSegment::fetchByList( $listId ) as $segment )
            $segmentNames[(int)$segment->attribute( 'id' )] = (string)$segment->attribute( 'name' );

        $errors = array();
        $changed = false;
        $buttons = array( 'StoreButton', 'AddStepButton', 'RemoveStepButton', 'MoveUpButton', 'MoveDownButton',
                          'PauseButton', 'ResumeButton', 'SendTestButton' );
        $button = '';
        foreach ( $buttons as $candidate )
            if ( $http->hasPostVariable( $candidate ) )
                $button = $candidate;
        $target = 0;
        if ( $button !== '' && is_array( $http->postVariable( $button ) ) )
            $target = (int)key( $http->postVariable( $button ) );

        if ( $button !== '' )
        {
            $steps = $series ? $series->steps() : array();
            $now = time();
            $seriesId = $series ? (int)$series->attribute( 'id' ) : 0;

            if ( $button === 'StoreButton' || $button === 'AddStepButton' )
            {
                $audience = $http->hasPostVariable( 'Audience' ) ? (int)$http->postVariable( 'Audience' )
                          : ( $series ? (int)$series->attribute( 'segment_id' ) : 0 );
                if ( $audience !== 0 && !isset( $segmentNames[$audience] ) )
                    $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'The audience is not a segment of this list.' );

                $postEditions = $http->hasPostVariable( 'StepEdition' ) ? (array)$http->postVariable( 'StepEdition' ) : array();
                $postDelays = $http->hasPostVariable( 'StepDelay' ) ? (array)$http->postVariable( 'StepDelay' ) : array();
                $edits = array();
                foreach ( $steps as $step )
                {
                    $stepId = (int)$step->attribute( 'id' );
                    $editionId = isset( $postEditions[$stepId] ) ? (int)$postEditions[$stepId] : (int)$step->attribute( 'edition_contentobject_id' );
                    $delay = isset( $postDelays[$stepId] ) ? $postDelays[$stepId] : $step->attribute( 'delay_days' );
                    // a stored step may keep an edition that is broken now; only a changed one is checked
                    if ( $editionId !== (int)$step->attribute( 'edition_contentobject_id' ) && !isset( $editions[$editionId] ) )
                        $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'The edition of step %position is not an edition of this list.', null,
                                                  array( '%position' => (int)$step->attribute( 'position' ) ) );
                    if ( !self::validDelay( $delay ) )
                        $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'The delay of step %position must be a whole number of days, 0 or more.', null,
                                                  array( '%position' => (int)$step->attribute( 'position' ) ) );
                    $edits[] = array( $step, $editionId, (int)$delay );
                }

                $newStep = null;
                if ( $button === 'AddStepButton' )
                {
                    $editionId = $http->hasPostVariable( 'NewEdition' ) ? (int)$http->postVariable( 'NewEdition' ) : 0;
                    $delay = $http->hasPostVariable( 'NewDelay' ) ? $http->postVariable( 'NewDelay' ) : 0;
                    if ( count( $steps ) >= $maxSteps )
                        $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'A series has at most %max steps.', null, array( '%max' => $maxSteps ) );
                    if ( !isset( $editions[$editionId] ) )
                        $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'Choose an edition of this list for the new step.' );
                    if ( !self::validDelay( $delay ) )
                        $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'The delay of the new step must be a whole number of days, 0 or more.' );
                    $newStep = array( $editionId, (int)$delay );
                }

                if ( !$errors )
                {
                    if ( !$series )
                    {
                        $series = \CjwNewsletterSeries::create( array( 'list_contentobject_id' => $listId,
                            'creator_contentobject_id' => (int)\eZUser::currentUserID(), 'created' => $now ) );
                    }
                    $series->setAttribute( 'segment_id', $audience );
                    $series->setAttribute( 'modified', $now );
                    $series->store();
                    $seriesId = (int)$series->attribute( 'id' );
                    foreach ( $edits as $edit )
                    {
                        list( $step, $editionId, $delay ) = $edit;
                        $step->setAttribute( 'edition_contentobject_id', $editionId );
                        $step->setAttribute( 'delay_days', $delay );
                        $step->setAttribute( 'modified', $now );
                        $step->store();
                    }
                    if ( $newStep )
                    {
                        $position = self::nextPosition( $steps );
                        \CjwNewsletterSeriesStep::create( array( 'series_id' => $seriesId, 'position' => $position,
                            'edition_contentobject_id' => $newStep[0], 'delay_days' => $newStep[1],
                            'created' => $now, 'modified' => $now ) )->store();
                    }
                    $changed = true;
                    \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/series', 'The series was stored.' ) );
                }
            }
            else if ( !$series )
            {
                $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'Store the series first.' );
            }
            else if ( $button === 'RemoveStepButton' || $button === 'MoveUpButton' || $button === 'MoveDownButton' )
            {
                $index = null;
                foreach ( $steps as $i => $step )
                    if ( (int)$step->attribute( 'id' ) === $target )
                        $index = $i;
                if ( $index === null )
                    $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'The step does not exist.' );
                else if ( $button === 'RemoveStepButton' )
                {
                    $steps[$index]->remove();
                    $changed = true;
                }
                else
                {
                    $other = $button === 'MoveUpButton' ? $index - 1 : $index + 1;
                    if ( isset( $steps[$other] ) )
                    {
                        $positionA = (int)$steps[$index]->attribute( 'position' );
                        $steps[$index]->setAttribute( 'position', (int)$steps[$other]->attribute( 'position' ) );
                        $steps[$other]->setAttribute( 'position', $positionA );
                        $steps[$index]->setAttribute( 'modified', $now );
                        $steps[$other]->setAttribute( 'modified', $now );
                        $steps[$index]->store();
                        $steps[$other]->store();
                        $changed = true;
                    }
                }
            }
            else if ( $button === 'PauseButton' || $button === 'ResumeButton' )
            {
                if ( $button === 'ResumeButton' && !$steps )
                    $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'Add at least one step before the series is resumed.' );
                else
                {
                    $series->setAttribute( 'status', $button === 'ResumeButton' ? 0 : 1 );
                    $series->setAttribute( 'modified', $now );
                    $series->store();
                    $changed = true;
                }
            }
            else if ( $button === 'SendTestButton' )
            {
                // Only a test mail to the current user: no send, no send item, no member is touched.
                $email = (string)\eZUser::currentUser()->attribute( 'email' );
                $version = null;
                foreach ( $steps as $step )
                {
                    if ( (int)$step->attribute( 'id' ) !== $target )
                        continue;
                    $edition = $editions[(int)$step->attribute( 'edition_contentobject_id' )] ?? null;
                    $version = $edition ? $edition->attribute( 'current' ) : null;
                }
                if ( !is_object( $version ) )
                    $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'The edition of this step is missing or not an edition of this list.' );
                else if ( $email === '' )
                    $errors[] = \ezpI18n::tr( 'cjw_newsletter/series', 'Your account has no e-mail address.' );
                else
                {
                    $newsletterMail = new \CjwNewsletterMail();
                    $newsletterMail->sendNewsletterTestMail( $version, $email );
                    \CjwNewsletterUI::notice( 'feedback', \ezpI18n::tr( 'cjw_newsletter/series', 'A test mail was sent to %email.', null,
                                                                       array( '%email' => $email ) ) );
                    $changed = true;
                }
            }
        }

        if ( $changed )
            return $this->viewResult( null, $module->redirectTo( $self ) );

        $stepRows = array();
        $steps = $series ? $series->steps() : array();
        foreach ( $steps as $step )
        {
            $editionId = (int)$step->attribute( 'edition_contentobject_id' );
            $object = \eZContentObject::fetch( $editionId );
            $position = (int)$step->attribute( 'position' );
            $stepRows[] = array(
                'id' => (int)$step->attribute( 'id' ),
                'position' => $position,
                'edition_contentobject_id' => $editionId,
                'edition_name' => $object ? (string)$object->attribute( 'name' ) : '',
                'delay_days' => (int)$step->attribute( 'delay_days' ),
                'members' => (int)\CjwNewsletterSeriesMember::fetchListCount( array(
                    'series_id' => (int)$series->attribute( 'id' ), 'last_position' => $position - 1, 'pending_send_id' => 0 ) ),
                'broken' => !isset( $editions[$editionId] ) );
        }
        $editionRows = array();
        foreach ( $editions as $id => $object )
            $editionRows[] = array( 'id' => $id, 'name' => (string)$object->attribute( 'name' ) );
        $segmentRows = array();
        foreach ( $segmentNames as $id => $segmentName )
            $segmentRows[] = array( 'id' => $id, 'name' => $segmentName );

        include_once( 'kernel/common/template.php' );
        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'list', array( 'id' => $listId, 'name' => $listObject ? (string)$listObject->attribute( 'name' ) : '' ) );
        $tpl->setVariable( 'series', array( 'id' => $series ? (int)$series->attribute( 'id' ) : 0,
                                            'paused' => $series ? $series->isPaused() : true,
                                            'segment_id' => $series ? (int)$series->attribute( 'segment_id' ) : 0 ) );
        $tpl->setVariable( 'steps', $stepRows );
        $tpl->setVariable( 'editions', $editionRows );
        $tpl->setVariable( 'segments', $segmentRows );
        $tpl->setVariable( 'errors', $errors );
        $tpl->setVariable( 'max_steps', $maxSteps );
        $tpl->setVariable( 'can_add', count( $stepRows ) < $maxSteps );

        $Result = array();
        $Result['content'] = $tpl->fetch( 'design:newsletter/series/series_edit.tpl' );
        $Result['path'] = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                                 array( 'url' => false, 'text' => \ezpI18n::tr( 'cjw_newsletter/series', 'Welcome series' ) ) );
        return $this->viewResult( $Result, null );
    }

    /** @return bool whether the value is a whole number of days, 0 or more */
    public static function validDelay( $value )
    {
        return is_int( $value ) ? $value >= 0 : ( is_string( $value ) && preg_match( '/^\d{1,5}$/', trim( $value ) ) === 1 );
    }

    /** @param \CjwNewsletterSeriesStep[] $steps @return int the position after the highest one */
    public static function nextPosition( array $steps )
    {
        $max = 0;
        foreach ( $steps as $step )
            $max = max( $max, (int)$step->attribute( 'position' ) );
        return $max + 1;
    }

    /** @return \eZContentObject[] the editions directly under the list, by object id */
    private function editions( $listObject )
    {
        $result = array();
        if ( !$listObject || !$listObject->attribute( 'main_node_id' ) )
            return $result;
        $nodes = \eZContentObjectTreeNode::subTreeByNodeID( array( 'Depth' => 1, 'DepthOperator' => 'eq',
            'ClassFilterType' => 'include', 'ClassFilterArray' => array( 'cjw_newsletter_edition' ),
            'SortBy' => array( array( 'published', false ), array( 'node_id', false ) ),
            'Limitation' => array(), 'IgnoreVisibility' => true ), (int)$listObject->attribute( 'main_node_id' ) );
        foreach ( (array)$nodes as $node )
        {
            $object = $node->attribute( 'object' );
            if ( $object && \CjwNewsletterEditionBuilder::editionContent( $object ) )
                $result[(int)$object->attribute( 'id' )] = $object;
        }
        return $result;
    }
}

}
