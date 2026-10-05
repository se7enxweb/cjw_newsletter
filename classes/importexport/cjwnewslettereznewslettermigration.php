<?php
/**
 * File containing the CjwNewsletterEznewsletterMigration class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage importexport
 */

/**
 * Takes the lists, subscribers and subscriptions of an old eznewsletter installation over into cjw_newsletter
 * (ext:cjw_newsletter:import-eznewsletter). The old tables are only read (CjwNewsletterEznewsletterSource).
 *
 * - Lists (ezsubscription_list): each old list goes into the cjw list named by --list-map=old:new, or into a new
 *   cjw list created under the newsletter system node (--create-lists --system-node=...). A list without a target
 *   is reported and its subscriptions are skipped.
 * - People (ezsubscriptionuserdata) and subscriptions (ezsubscription): one cjw newsletter user per address
 *   (an existing one is used, never duplicated: empty names and the phone number are filled in), one
 *   subscription per list with the old state and the old dates (created, confirmed, approved, removed).
 *   Pending (never confirmed) subscriptions are skipped unless --include-pending; old unsubscriptions are taken
 *   over as unsubscriptions, so the person is not subscribed again later.
 * - Addresses on the old do-not-contact list (ezrobinsonlist) are never imported; --robinson-to-suppression puts
 *   them on the kernel suppression list (reason "legal").
 * - Consent: one row per person in the kernel consent log with the source "import", naming the run and the
 *   original opt-in date.
 * - Sends (eznewsletter, ezsendnewsletteritem) are recorded in the migration log as history (name, date, state,
 *   mails); the old editions themselves are not re-created.
 *
 * Every old row read is logged in cjwnl_migration_log with the run id. A second run skips what an earlier run
 * (not a dry run) took over, so the command can be repeated. A dry run checks and logs everything, and writes
 * nothing else.
 *
 * @package cjw_newsletter
 * @subpackage importexport
 */
class CjwNewsletterEznewsletterMigration
{
    // the states of the old tables (eznewsletter 1.6 schema)
    const OLD_PENDING = 0;
    const OLD_CONFIRMED = 1;
    const OLD_APPROVED = 2;
    const OLD_REMOVED_SELF = 3;
    const OLD_REMOVED_ADMIN = 4;
    const OLD_FORMAT_TEXT = 0;
    const OLD_FORMAT_HTML = 1;
    const OLD_FORMAT_EXTERNAL_HTML = 2;
    const OLD_ROBINSON_EMAIL = 0;
    const OLD_BOUNCE_LIMIT = 2;

    /** @var CjwNewsletterEznewsletterSource */
    protected $source;

    protected $options;
    protected $runId;
    protected $totals;
    /** @var array old list id => cjw list content object id (0 = none) */
    protected $listTargets = array();
    protected $listNames = array();
    /** @var array lower-case address => true */
    protected $robinson = array();
    /** @var array lower-case address => old person row */
    protected $people = array();
    /** @var array cjw user id => hash( active => earliest opt-in, removed => latest removal ) */
    protected $consent = array();
    protected $out;

    /**
     * @param CjwNewsletterEznewsletterSource $source
     * @param array $options dry_run, list_map (old id => cjw list object id), create_lists, system_node_id,
     *                       include_pending, robinson_to_suppression, batch_size, out (a sink with output())
     */
    function __construct( $source, $options = array() )
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $batch = $ini->hasVariable( 'EznewsletterImportSettings', 'BatchSize' ) ? (int)$ini->variable( 'EznewsletterImportSettings', 'BatchSize' ) : 500;
        $this->source = $source;
        $this->options = array_merge( array( 'dry_run' => false, 'list_map' => array(), 'create_lists' => false, 'system_node_id' => 0,
                                             'include_pending' => false, 'robinson_to_suppression' => false,
                                             'batch_size' => $batch > 0 ? $batch : 500, 'out' => false ), $options );
        $this->out = $this->options['out'];
        $this->runId = 'ezn-' . date( 'Ymd-His' ) . '-' . bin2hex( random_bytes( 3 ) );
        $this->totals = array( 'run_id' => $this->runId, 'dry_run' => (bool)$this->options['dry_run'], 'source' => $source->label(),
                               'lists' => array( 'created' => 0, 'merged' => 0, 'skipped' => 0 ),
                               'users' => array( 'created' => 0, 'merged' => 0 ),
                               'subscriptions' => array( 'created' => 0, 'merged' => 0, 'skipped' => 0, 'failed' => 0 ),
                               'reasons' => array(), 'robinson' => array( 'entries' => 0, 'suppressed' => 0 ),
                               'sends' => 0, 'consent' => 0, 'error' => '' );
    }

    /** @return string */
    function runId()
    {
        return $this->runId;
    }

    /**
     * @return array the totals (see the constructor); error is set when the run could not start
     */
    function run()
    {
        $missing = $this->source->missingTables();
        if ( $missing )
        {
            $this->totals['error'] = 'The eznewsletter tables are missing: ' . implode( ', ', $missing );
            return $this->totals;
        }
        $this->say( ( $this->options['dry_run'] ? 'Dry run ' : 'Run ' ) . $this->runId . ', reading from ' . $this->source->label() );
        $this->readRobinson();
        $this->migrateLists();
        $this->readPeople();
        $this->migrateSubscriptions();
        $this->recordConsent();
        $this->recordSends();
        if ( !$this->options['dry_run'] )
            $this->audit();
        $this->say( sprintf( 'Lists: %d created, %d merged, %d skipped. Users: %d created, %d merged. Subscriptions: %d created, %d merged, %d skipped, %d failed. Sends recorded: %d.',
                             $this->totals['lists']['created'], $this->totals['lists']['merged'], $this->totals['lists']['skipped'],
                             $this->totals['users']['created'], $this->totals['users']['merged'],
                             $this->totals['subscriptions']['created'], $this->totals['subscriptions']['merged'],
                             $this->totals['subscriptions']['skipped'], $this->totals['subscriptions']['failed'], $this->totals['sends'] ) );
        return $this->totals;
    }

    protected function say( $text )
    {
        if ( $this->out )
            $this->out->output( $text );
    }

    /**
     * One row of the migration log.
     */
    protected function log( $sourceTable, $sourceId, $targetTable, $targetId, $action, $message = '' )
    {
        $row = CjwNewsletterMigrationLog::create( array(
            'run_id' => $this->runId, 'source_table' => $sourceTable, 'source_id' => (string)$sourceId,
            'target_table' => $targetTable, 'target_id' => (int)$targetId, 'action' => $action,
            'is_dry_run' => $this->options['dry_run'] ? 1 : 0, 'message' => mb_substr( (string)$message, 0, 2000 ), 'created' => time() ) );
        $row->store();
    }

    /**
     * @return int the target id an earlier run (not a dry run) wrote for an old row, 0 when none
     */
    static function previousTarget( $sourceTable, $sourceId )
    {
        $db = eZDB::instance();
        $rows = $db->arrayQuery( "SELECT target_id FROM cjwnl_migration_log WHERE source_table = '" . $db->escapeString( $sourceTable ) . "'"
                                 . " AND source_id = '" . $db->escapeString( (string)$sourceId ) . "' AND is_dry_run = 0"
                                 . " AND action IN ( 'created', 'merged', 'recorded' ) ORDER BY id DESC", array( 'limit' => 1 ) );
        return isset( $rows[0]['target_id'] ) ? max( 1, (int)$rows[0]['target_id'] ) : 0;
    }

    protected function skip( $reason, $sourceId, $message = '' )
    {
        $this->totals['subscriptions']['skipped']++;
        $this->totals['reasons'][$reason] = isset( $this->totals['reasons'][$reason] ) ? $this->totals['reasons'][$reason] + 1 : 1;
        $this->log( 'ezsubscription', $sourceId, 'cjwnl_subscription', 0, 'skipped', $reason . ( $message !== '' ? ': ' . $message : '' ) );
    }

    // ------------------------------------------------------------------ the do-not-contact list

    protected function readRobinson()
    {
        if ( !$this->source->hasTable( 'ezrobinsonlist' ) )
            return;
        $batch = $this->options['batch_size'];
        for ( $offset = 0; ; $offset += $batch )
        {
            $rows = $this->source->select( 'SELECT id, value, type FROM ezrobinsonlist WHERE type = ' . self::OLD_ROBINSON_EMAIL . ' ORDER BY id', $batch, $offset );
            foreach ( $rows as $row )
            {
                $email = mb_strtolower( trim( (string)$row['value'] ) );
                if ( $email === '' )
                    continue;
                $this->robinson[$email] = true;
                $this->totals['robinson']['entries']++;
                if ( !$this->options['robinson_to_suppression'] || !class_exists( 'expMailSuppression' ) || !ezcMailTools::validateEmailAddress( $email ) )
                    continue;
                if ( expMailSuppression::isSuppressed( $email ) )
                {
                    $this->log( 'ezrobinsonlist', $row['id'], 'expmail_suppression', 0, 'skipped', 'already suppressed' );
                    continue;
                }
                if ( !$this->options['dry_run'] )
                    expMailSuppression::add( $email, 'legal', 'eznewsletter do-not-contact list' );
                $this->totals['robinson']['suppressed']++;
                $this->log( 'ezrobinsonlist', $row['id'], 'expmail_suppression', 0, 'created', 'suppressed (legal)' );
            }
            if ( count( $rows ) < $batch )
                break;
        }
    }

    // ------------------------------------------------------------------ lists

    protected function migrateLists()
    {
        $lists = array();
        // the published row of a list, else its draft
        foreach ( $this->source->select( 'SELECT id, name, description, status FROM ezsubscription_list ORDER BY id, status DESC' ) as $row )
            if ( !isset( $lists[(int)$row['id']] ) )
                $lists[(int)$row['id']] = $row;
        foreach ( $lists as $id => $row )
        {
            $name = trim( (string)$row['name'] ) !== '' ? trim( (string)$row['name'] ) : 'eznewsletter list ' . $id;
            $this->listNames[$id] = $name;
            $this->listTargets[$id] = 0;
            if ( isset( $this->options['list_map'][$id] ) )
            {
                $target = (int)$this->options['list_map'][$id];
                if ( !self::isListObject( $target ) )
                {
                    $this->totals['lists']['skipped']++;
                    $this->log( 'ezsubscription_list', $id, 'cjwnl_list', 0, 'skipped', 'the mapped object ' . $target . ' is not a newsletter list' );
                    $this->say( 'List ' . $id . ' "' . $name . '": object ' . $target . ' is not a newsletter list, skipped' );
                    continue;
                }
                $this->listTargets[$id] = $target;
                $this->totals['lists']['merged']++;
                $this->log( 'ezsubscription_list', $id, 'cjwnl_list', $target, 'merged', $name );
                $this->say( 'List ' . $id . ' "' . $name . '" -> list object ' . $target );
                continue;
            }
            $previous = self::previousTarget( 'ezsubscription_list', $id );
            if ( $previous && self::isListObject( $previous ) )
            {
                $this->listTargets[$id] = $previous;
                $this->totals['lists']['merged']++;
                $this->log( 'ezsubscription_list', $id, 'cjwnl_list', $previous, 'merged', $name . ' (taken over by an earlier run)' );
                $this->say( 'List ' . $id . ' "' . $name . '" -> list object ' . $previous . ' (earlier run)' );
                continue;
            }
            if ( !$this->options['create_lists'] )
            {
                $this->totals['lists']['skipped']++;
                $this->log( 'ezsubscription_list', $id, 'cjwnl_list', 0, 'skipped', $name . ': no target list (--list-map or --create-lists)' );
                $this->say( 'List ' . $id . ' "' . $name . '": no target list, skipped' );
                continue;
            }
            $target = 0;
            if ( !$this->options['dry_run'] )
            {
                $target = $this->createList( $name );
                if ( !$target )
                {
                    $this->totals['lists']['skipped']++;
                    $this->log( 'ezsubscription_list', $id, 'cjwnl_list', 0, 'failed', $name . ': the list could not be created' );
                    continue;
                }
            }
            // a dry run counts the subscriptions of a list it would create
            $this->listTargets[$id] = $this->options['dry_run'] ? -1 : $target;
            $this->totals['lists']['created']++;
            $this->log( 'ezsubscription_list', $id, 'cjwnl_list', $target, 'created', $name );
            $this->say( 'List ' . $id . ' "' . $name . '" created' . ( $target ? ' as object ' . $target : '' ) );
        }
    }

    /** @return bool the content object is a newsletter list */
    static function isListObject( $objectId )
    {
        $object = (int)$objectId > 0 ? eZContentObject::fetch( (int)$objectId ) : null;
        if ( !$object instanceof eZContentObject )
            return false;
        foreach ( $object->attribute( 'data_map' ) as $attribute )
            if ( $attribute->attribute( 'data_type_string' ) === 'cjwnewsletterlist' )
                return true;
        return false;
    }

    /** @return int the content object id of the new list, 0 on failure */
    protected function createList( $name )
    {
        $parent = eZContentObjectTreeNode::fetch( (int)$this->options['system_node_id'] );
        if ( !$parent instanceof eZContentObjectTreeNode || $parent->attribute( 'class_identifier' ) !== 'cjw_newsletter_system' )
            return 0;
        $node = CjwNewsletterClassInstaller::createNode( $parent->attribute( 'node_id' ), 'cjw_newsletter_list', $name, $parent->attribute( 'object' )->attribute( 'section_id' ) );
        if ( !$node )
            return 0;
        CjwNewsletterClassInstaller::storeListSettings( $node->attribute( 'object' ) );
        return (int)$node->attribute( 'contentobject_id' );
    }

    // ------------------------------------------------------------------ people and subscriptions

    protected function readPeople()
    {
        $batch = $this->options['batch_size'];
        for ( $offset = 0; ; $offset += $batch )
        {
            $rows = $this->source->select( 'SELECT id, email, firstname, name, mobile FROM ezsubscriptionuserdata ORDER BY id', $batch, $offset );
            foreach ( $rows as $row )
            {
                $email = mb_strtolower( trim( (string)$row['email'] ) );
                if ( $email !== '' && !isset( $this->people[$email] ) )
                    $this->people[$email] = $row;
            }
            if ( count( $rows ) < $batch )
                break;
        }
    }

    protected function migrateSubscriptions()
    {
        $batch = $this->options['batch_size'];
        $done = array();
        for ( $offset = 0; ; $offset += $batch )
        {
            // the published row of a subscription first, then its draft (skipped when the published one was read)
            $rows = $this->source->select( 'SELECT id, version_status, subscriptionlist_id, email, status, output_format, created, confirmed, approved, removed, bounce_count'
                                           . ' FROM ezsubscription ORDER BY id, version_status DESC', $batch, $offset );
            foreach ( $rows as $row )
            {
                $id = (int)$row['id'];
                if ( isset( $done[$id] ) )
                    continue;
                $done[$id] = true;
                $db = eZDB::instance();
                try
                {
                    if ( !$this->options['dry_run'] )
                        $db->begin();
                    $this->migrateSubscription( $row );
                    if ( !$this->options['dry_run'] )
                        $db->commit();
                }
                catch ( Throwable $e )
                {
                    if ( !$this->options['dry_run'] )
                        $db->rollback();
                    $this->totals['subscriptions']['failed']++;
                    eZDebug::writeError( 'eznewsletter migration, subscription ' . $id . ': ' . $e->getMessage(), __METHOD__ );
                    $this->log( 'ezsubscription', $id, 'cjwnl_subscription', 0, 'failed', $e->getMessage() );
                }
            }
            if ( count( $rows ) < $batch )
                break;
        }
    }

    /** @return int the cjw subscription status of an old status */
    static function mapStatus( $old )
    {
        switch ( (int)$old )
        {
            case self::OLD_CONFIRMED: return CjwNewsletterSubscription::STATUS_CONFIRMED;
            case self::OLD_APPROVED: return CjwNewsletterSubscription::STATUS_APPROVED;
            case self::OLD_REMOVED_SELF: return CjwNewsletterSubscription::STATUS_REMOVED_SELF;
            case self::OLD_REMOVED_ADMIN: return CjwNewsletterSubscription::STATUS_REMOVED_ADMIN;
        }
        return CjwNewsletterSubscription::STATUS_PENDING;
    }

    /**
     * @param string $old the old output formats ("0,1")
     * @return int[] the cjw output formats (0 HTML, 1 text); HTML when nothing is left
     */
    static function mapFormats( $old )
    {
        $formats = array();
        foreach ( preg_split( '/[^0-9]+/', (string)$old, -1, PREG_SPLIT_NO_EMPTY ) as $format )
        {
            if ( (int)$format === self::OLD_FORMAT_TEXT )
                $formats[1] = 1;
            else if ( (int)$format === self::OLD_FORMAT_HTML || (int)$format === self::OLD_FORMAT_EXTERNAL_HTML )
                $formats[0] = 0;
        }
        ksort( $formats );
        return $formats ? array_values( $formats ) : array( 0 );
    }

    protected function migrateSubscription( $row )
    {
        $id = (int)$row['id'];
        $email = mb_strtolower( trim( (string)$row['email'] ) );
        $listId = (int)$row['subscriptionlist_id'];
        $oldStatus = (int)$row['status'];
        $target = isset( $this->listTargets[$listId] ) ? $this->listTargets[$listId] : 0;
        if ( !$target )
            return $this->skip( 'no_list', $id, 'old list ' . $listId );
        if ( $email === '' || !ezcMailTools::validateEmailAddress( $email ) )
            return $this->skip( 'invalid', $id );
        if ( isset( $this->robinson[$email] ) )
            return $this->skip( 'robinson', $id );
        if ( $oldStatus === self::OLD_PENDING && !$this->options['include_pending'] )
            return $this->skip( 'never_confirmed', $id );
        if ( (int)$row['bounce_count'] >= self::OLD_BOUNCE_LIMIT && in_array( $oldStatus, array( self::OLD_CONFIRMED, self::OLD_APPROVED ), true ) )
            return $this->skip( 'bounced', $id, (int)$row['bounce_count'] . ' bounces' );
        $active = in_array( $oldStatus, array( self::OLD_CONFIRMED, self::OLD_APPROVED ), true );
        if ( $active || $oldStatus === self::OLD_PENDING )
        {
            $blocked = CjwNewsletterImportConsent::blockReason( $email );
            if ( $blocked !== null )
                return $this->skip( $blocked, $id );
        }
        $previous = self::previousTarget( 'ezsubscription', $id );
        if ( $previous && eZDB::instance()->arrayQuery( 'SELECT id FROM cjwnl_subscription WHERE id = ' . (int)$previous ) )
            return $this->skip( 'already_migrated', $id, 'subscription ' . $previous );

        $person = isset( $this->people[$email] ) ? $this->people[$email] : array( 'id' => 0, 'firstname' => '', 'name' => '', 'mobile' => '' );
        $created = (int)$row['created'] > 0 ? (int)$row['created'] : time();
        $confirmed = (int)$row['confirmed'];
        $approved = (int)$row['approved'];
        $removed = (int)$row['removed'];

        // the newsletter user: an existing one is used
        $user = CjwNewsletterUser::fetchByEmail( $email );
        $userAction = is_object( $user ) ? 'merged' : 'created';
        if ( is_object( $user ) && $active && in_array( (int)$user->attribute( 'status' ), array( CjwNewsletterUser::STATUS_REMOVED_SELF, CjwNewsletterUser::STATUS_BLACKLISTED, CjwNewsletterUser::STATUS_BOUNCED_HARD ), true ) )
            return $this->skip( 'removed_self', $id, 'the newsletter user unsubscribed in cjw_newsletter' );
        if ( !is_object( $user ) && !$this->options['dry_run'] )
        {
            $user = CjwNewsletterUser::create( $email, 0, (string)$person['firstname'], (string)$person['name'], false,
                                               CjwNewsletterUser::STATUS_PENDING, 'eznewsletter', '', '', '', '' );
            $phone = CjwNewsletterMappedImport::phone( (string)$person['mobile'] );
            if ( $phone !== null )
                $user->setAttribute( 'phone_number', $phone );
            $user->setAttribute( 'status', $active ? CjwNewsletterUser::STATUS_CONFIRMED : ( $oldStatus === self::OLD_PENDING ? CjwNewsletterUser::STATUS_PENDING : CjwNewsletterUser::STATUS_CONFIRMED ) );
            $user->setAttribute( 'created', $created );
            if ( $active || $confirmed )
                $user->setAttribute( 'confirmed', $confirmed ? $confirmed : ( $approved ? $approved : $created ) );
            $user->setAttribute( 'remote_id', 'eznewsletter:user:' . ( (int)$person['id'] ? (int)$person['id'] : md5( $email ) ) );
            $user->store();
            $this->log( 'ezsubscriptionuserdata', (int)$person['id'] ? (int)$person['id'] : $email, 'cjwnl_user', $user->attribute( 'id' ), 'created', '' );
            $this->totals['users']['created']++;
        }
        else if ( !is_object( $user ) )
        {
            // dry run: count the person once
            if ( !isset( $this->consent['dry:' . $email] ) )
                $this->totals['users']['created']++;
            if ( !isset( $this->consent['dry:' . $email] ) )
                $this->consent['dry:' . $email] = array( 'active' => 0, 'removed' => 0 );
        }
        else
        {
            $changed = false;
            foreach ( array( 'first_name' => (string)$person['firstname'], 'last_name' => (string)$person['name'] ) as $field => $value )
                if ( trim( $value ) !== '' && trim( (string)$user->attribute( $field ) ) === '' )
                {
                    $user->setAttribute( $field, trim( $value ) );
                    $changed = true;
                }
            $phone = CjwNewsletterMappedImport::phone( (string)$person['mobile'] );
            if ( $phone !== null && trim( (string)$user->attribute( 'phone_number' ) ) === '' )
            {
                $user->setAttribute( 'phone_number', $phone );
                $changed = true;
            }
            if ( $changed && !$this->options['dry_run'] )
                $user->store();
            if ( !isset( $this->consent[(int)$user->attribute( 'id' )] ) )
            {
                $this->totals['users']['merged']++;
                $this->consent[(int)$user->attribute( 'id' )] = array( 'active' => 0, 'removed' => 0, 'user' => $user );
            }
        }
        if ( is_object( $user ) && !isset( $this->consent[(int)$user->attribute( 'id' )] ) )
            $this->consent[(int)$user->attribute( 'id' )] = array( 'active' => 0, 'removed' => 0, 'user' => $user );

        // the subscription
        if ( $target < 0 || !is_object( $user ) )
        {
            // dry run of a list or a user that would be created
            $this->totals['subscriptions']['created']++;
            $this->log( 'ezsubscription', $id, 'cjwnl_subscription', 0, 'created', 'status ' . $oldStatus );
            $this->noteConsent( is_object( $user ) ? (int)$user->attribute( 'id' ) : 'dry:' . $email, $active, $oldStatus, $confirmed, $approved, $created, $removed );
            return;
        }
        $userId = (int)$user->attribute( 'id' );
        $existing = CjwNewsletterSubscription::fetchByListIdAndNewsletterUserId( $target, $userId );
        if ( is_object( $existing ) )
        {
            // cjw_newsletter is the truth for a subscription it has
            $this->totals['subscriptions']['merged']++;
            $this->log( 'ezsubscription', $id, 'cjwnl_subscription', $existing->attribute( 'id' ), 'merged', 'kept with status ' . (int)$existing->attribute( 'status' ) );
            return;
        }
        if ( $this->options['dry_run'] )
        {
            $this->totals['subscriptions']['created']++;
            $this->log( 'ezsubscription', $id, 'cjwnl_subscription', 0, 'created', 'status ' . $oldStatus );
            $this->noteConsent( $userId, $active, $oldStatus, $confirmed, $approved, $created, $removed );
            return;
        }
        $subscription = CjwNewsletterSubscription::create( $target, $userId, self::mapFormats( $row['output_format'] ), self::mapStatus( $oldStatus ), 'eznewsletter' );
        // the old dates, over the ones create() set
        $subscription->setAttribute( 'created', $created );
        $subscription->setAttribute( 'confirmed', $confirmed );
        $subscription->setAttribute( 'approved', (int)$subscription->attribute( 'status' ) === CjwNewsletterSubscription::STATUS_APPROVED ? ( $approved ? $approved : ( $confirmed ? $confirmed : $created ) ) : $approved );
        $subscription->setAttribute( 'removed', in_array( $oldStatus, array( self::OLD_REMOVED_SELF, self::OLD_REMOVED_ADMIN ), true ) ? ( $removed ? $removed : time() ) : 0 );
        $subscription->setAttribute( 'remote_id', 'eznewsletter:' . $id );
        CjwNewsletterImportConsent::storeQuietly( $subscription );
        $this->totals['subscriptions']['created']++;
        $this->log( 'ezsubscription', $id, 'cjwnl_subscription', $subscription->attribute( 'id' ), 'created', 'status ' . $oldStatus . ' -> ' . (int)$subscription->attribute( 'status' ) );
        $this->noteConsent( $userId, $active, $oldStatus, $confirmed, $approved, $created, $removed );
    }

    protected function noteConsent( $userId, $active, $oldStatus, $confirmed, $approved, $created, $removed )
    {
        if ( $active )
        {
            $date = $confirmed ? $confirmed : ( $approved ? $approved : $created );
            $this->consent[$userId]['active'] = $this->consent[$userId]['active'] ? min( $this->consent[$userId]['active'], $date ) : $date;
        }
        else if ( $oldStatus === self::OLD_REMOVED_SELF )
            $this->consent[$userId]['removed'] = max( $this->consent[$userId]['removed'], $removed ? $removed : 1 );
    }

    // ------------------------------------------------------------------ consent

    protected function recordConsent()
    {
        foreach ( $this->consent as $key => $state )
        {
            if ( !isset( $state['user'] ) && !$this->options['dry_run'] )
                continue;
            if ( $state['active'] )
            {
                $this->totals['consent']++;
                if ( !$this->options['dry_run'] )
                    CjwNewsletterImportConsent::recordOn( $state['user'], ezpI18n::tr( 'cjw_newsletter/importexport',
                        'Newsletter subscriptions taken over from eznewsletter (run %run); the person opted in on %date', null,
                        array( '%run' => $this->runId, '%date' => gmdate( 'Y-m-d', $state['active'] ) ) ) );
            }
            else if ( $state['removed'] )
            {
                $this->totals['consent']++;
                if ( !$this->options['dry_run'] )
                    CjwNewsletterImportConsent::recordOff( $state['user'], ezpI18n::tr( 'cjw_newsletter/importexport',
                        'Newsletter unsubscription taken over from eznewsletter (run %run); the person unsubscribed on %date', null,
                        array( '%run' => $this->runId, '%date' => $state['removed'] > 1 ? gmdate( 'Y-m-d', $state['removed'] ) : '?' ) ) );
            }
        }
    }

    // ------------------------------------------------------------------ sends (history only)

    protected function recordSends()
    {
        if ( !$this->source->hasTable( 'eznewsletter' ) )
            return;
        $items = array();
        if ( $this->source->hasTable( 'ezsendnewsletteritem' ) )
            foreach ( $this->source->select( 'SELECT newsletter_id, send_status, COUNT(*) AS c FROM ezsendnewsletteritem GROUP BY newsletter_id, send_status' ) as $row )
                $items[(int)$row['newsletter_id']][(int)$row['send_status']] = (int)$row['c'];
        $seen = array();
        foreach ( $this->source->select( 'SELECT id, name, send_date, send_status, status FROM eznewsletter ORDER BY id, status DESC' ) as $row )
        {
            $id = (int)$row['id'];
            if ( isset( $seen[$id] ) )
                continue;
            $seen[$id] = true;
            if ( self::previousTarget( 'eznewsletter', $id ) )
                continue;
            $counts = isset( $items[$id] ) ? $items[$id] : array();
            $message = json_encode( array( 'name' => (string)$row['name'],
                                           'send_date' => (int)$row['send_date'] ? gmdate( 'Y-m-d\TH:i:s\Z', (int)$row['send_date'] ) : '',
                                           'send_status' => (int)$row['send_status'],
                                           'items' => array_sum( $counts ), 'sent' => isset( $counts[1] ) ? $counts[1] : 0,
                                           'on_hold' => isset( $counts[2] ) ? $counts[2] : 0 ) );
            $this->log( 'eznewsletter', $id, '', 0, 'recorded', $message );
            $this->totals['sends']++;
        }
    }

    protected function audit()
    {
        if ( !class_exists( 'expAudit' ) )
            return;
        expAudit::event( 'system.cjw_newsletter.import', array(
            'object' => 'cjw_newsletter:import-eznewsletter',
            'after' => array( 'by' => PHP_SAPI === 'cli' ? 'console' : 'web', 'run_id' => $this->runId,
                              'totals' => array( 'lists' => $this->totals['lists'], 'users' => $this->totals['users'],
                                                 'subscriptions' => $this->totals['subscriptions'], 'sends' => $this->totals['sends'] ) ) ) );
    }

    /**
     * The runs in the migration log, newest first.
     *
     * @param int $limit
     * @return array[] run_id, started, finished, dry_run, rows, created, merged, skipped, failed, recorded
     */
    static function runs( $limit = 20, $offset = 0 )
    {
        $db = eZDB::instance();
        $rows = $db->arrayQuery( 'SELECT run_id, MIN(created) AS started, MAX(created) AS finished, MAX(is_dry_run) AS dry_run, COUNT(*) AS rows_count,'
                                 . " SUM(CASE WHEN action = 'created' THEN 1 ELSE 0 END) AS created_count,"
                                 . " SUM(CASE WHEN action = 'merged' THEN 1 ELSE 0 END) AS merged_count,"
                                 . " SUM(CASE WHEN action = 'skipped' THEN 1 ELSE 0 END) AS skipped_count,"
                                 . " SUM(CASE WHEN action = 'failed' THEN 1 ELSE 0 END) AS failed_count,"
                                 . " SUM(CASE WHEN action = 'recorded' THEN 1 ELSE 0 END) AS recorded_count"
                                 . ' FROM cjwnl_migration_log GROUP BY run_id ORDER BY started DESC',
                                 array( 'limit' => (int)$limit, 'offset' => (int)$offset ) );
        $out = array();
        foreach ( is_array( $rows ) ? $rows : array() as $row )
            $out[] = array( 'run_id' => $row['run_id'], 'started' => (int)$row['started'], 'finished' => (int)$row['finished'],
                            'dry_run' => (int)$row['dry_run'] === 1, 'rows' => (int)$row['rows_count'], 'created' => (int)$row['created_count'],
                            'merged' => (int)$row['merged_count'], 'skipped' => (int)$row['skipped_count'], 'failed' => (int)$row['failed_count'],
                            'recorded' => (int)$row['recorded_count'] );
        return $out;
    }

    /** @return int the runs in the migration log */
    static function runCount()
    {
        $rows = eZDB::instance()->arrayQuery( 'SELECT COUNT(DISTINCT run_id) AS c FROM cjwnl_migration_log' );
        return isset( $rows[0]['c'] ) ? (int)$rows[0]['c'] : 0;
    }
}

?>
