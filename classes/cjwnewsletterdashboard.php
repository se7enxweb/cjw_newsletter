<?php
//
// Copyright (C) 1998 - 2026 7x & Exponential Foundation. All rights reserved.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 (or any later version).
//

/*!
  \class CjwNewsletterDashboard cjwnewsletterdashboard.php
  \brief What the start page of the module shows: the lists, the subscribers, the editions, the last sends, the
         transport and the outbox, the last runs of the cronjob parts and the problems found.
*/

class CjwNewsletterDashboard
{
    /*!
     \static
     \return array of the table names the extension needs (share/db_schema.dba)
    */
    static function tableNames()
    {
        return array( 'cjwnl_blacklist_item', 'cjwnl_edition', 'cjwnl_edition_send', 'cjwnl_edition_send_item', 'cjwnl_import',
                      'cjwnl_list', 'cjwnl_mailbox', 'cjwnl_mailbox_item', 'cjwnl_subscription', 'cjwnl_user',
                      // 4.2.0 (doc/schema-4.2.md): N1, N2, N3, N4, N5, N6
                      'cjwnl_throttle_state', 'cjwnl_send_batch', 'cjwnl_mailin_address', 'cjwnl_mailin_message', 'cjwnl_test_group',
                      'cjwnl_schedule', 'cjwnl_schedule_log', 'cjwnl_article_pool', 'cjwnl_edition_article', 'cjwnl_approval',
                      'cjwnl_interest', 'cjwnl_user_interest', 'cjwnl_edition_send_output',
                      'cjwnl_link', 'cjwnl_link_click', 'cjwnl_open', 'cjwnl_stat_total', 'cjwnl_ab_test', 'cjwnl_ab_variant',
                      'cjwnl_sms_code', 'cjwnl_sms_message', 'cjwnl_sms_inbound',
                      'cjwnl_import_mapping', 'cjwnl_migration_log' );
    }

    /*!
     \static
     \return array of the table names that do not exist yet
    */
    static function missingTables()
    {
        $db = eZDB::instance();
        if ( in_array( $db->databaseName(), array( 'sqlite', 'sqlite3' ) ) )
        {
            // eZSQLite3DB::relationList() only lists the tables whose names start with "ez"
            $list = array();
            foreach ( (array)$db->arrayQuery( "SELECT name FROM sqlite_master WHERE type = 'table'" ) as $row )
            {
                $list[] = $row['name'];
            }
        }
        else
        {
            $list = $db->relationList();
        }
        $existing = array_map( 'strtolower', is_array( $list ) ? $list : array() );
        return array_values( array_diff( self::tableNames(), $existing ) );
    }

    /*!
     \static
     \return the number from a COUNT query
    */
    protected static function count( $sql )
    {
        $rows = eZDB::instance()->arrayQuery( $sql );
        return $rows ? (int)array_shift( $rows[0] ) : 0;
    }

    /*!
     \static
     \return array( 'method', 'real', 'dir', 'files', 'last', 'sender', 'prefix' ): how the mails of the cronjob leave the
             server, and for the file transport what is in the outbox
    */
    static function transport()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $method = (string)$ini->variable( 'NewsletterMailSettings', 'TransportMethodCronjob' );
        $dir = trim( (string)$ini->variable( 'NewsletterMailSettings', 'FileTransportMailDir' ) );
        $info = array( 'method' => $method, 'real' => $method != 'file', 'dir' => $dir, 'files' => 0, 'last' => 0, 'writable' => null,
                       'sender' => (string)$ini->variable( 'NewsletterMailSettings', 'EmailSender' ),
                       'smtp_server' => (string)$ini->variable( 'NewsletterMailSettings', 'SmtpTransportServer' ) );
        if ( $method == 'file' && $dir !== '' )
        {
            $path = ( $dir[0] == '/' ? '' : eZSys::rootDir() . '/' ) . $dir;
            $info['writable'] = is_dir( $path ) ? is_writable( $path ) : null;
            $files = is_dir( $path ) ? glob( $path . '/*.eml' ) : array();
            $info['files'] = $files ? count( $files ) : 0;
            foreach ( (array)$files as $file )
            {
                $info['last'] = max( $info['last'], (int)@filemtime( $file ) );
            }
        }
        return $info;
    }

    /*!
     \static
     \return array with the keys 'tables_missing', 'lists', 'users', 'subscriptions', 'editions', 'sends', 'last_sends',
             'blacklist', 'mailboxes', 'imports', 'transport', 'runs', 'orphans', 'problems', 'areas' (4.2.0: handler class => its summary)
    */
    static function summary()
    {
        $summary = array( 'tables_missing' => self::missingTables(), 'lists' => array(), 'users' => array(), 'subscriptions' => array(),
                          'editions' => 0, 'sends' => array(), 'last_sends' => array(), 'blacklist' => 0, 'mailboxes' => array(),
                          'imports' => array(), 'transport' => self::transport(), 'runs' => array(), 'orphans' => array(), 'problems' => array() );
        $problems =& $summary['problems'];
        $i18n = 'extension/cjw_newsletter';

        if ( $summary['tables_missing'] )
        {
            $problems[] = array( 'level' => 'error', 'code' => 'tables',
                                 'text' => ezpI18n::tr( $i18n, 'The tables of the extension are not created yet: %tables.', null, array( '%tables' => implode( ', ', $summary['tables_missing'] ) ) ),
                                 'url' => false );
            unset( $problems );
            return $summary;
        }

        $db = eZDB::instance();

        // the lists: one row per version of the list object, the published object is what counts
        $rootNodeId = (int)eZINI::instance( 'cjw_newsletter.ini' )->variable( 'NewsletterSettings', 'RootFolderNodeId' );
        $summary['root'] = array( 'node_id' => $rootNodeId, 'exists' => $rootNodeId > 1 && is_object( eZContentObjectTreeNode::fetch( $rootNodeId ) ) );
        $rows = $db->arrayQuery( 'SELECT DISTINCT contentobject_id FROM cjwnl_list' );
        foreach ( (array)$rows as $row )
        {
            $object = eZContentObject::fetch( (int)$row['contentobject_id'] );
            if ( !$object || $object->attribute( 'status' ) != eZContentObject::STATUS_PUBLISHED )
            {
                continue;
            }
            $id = (int)$row['contentobject_id'];
            $perStatus = array();
            foreach ( (array)$db->arrayQuery( 'SELECT status AS s, COUNT(*) AS c FROM cjwnl_subscription WHERE list_contentobject_id = ' . $id .
                                              ' AND newsletter_user_id IN ( SELECT id FROM cjwnl_user ) GROUP BY status' ) as $r )
            {
                $perStatus[(int)$r['s']] = (int)$r['c'];
            }
            $summary['lists'][] = array( 'object_id' => $id, 'node_id' => (int)$object->attribute( 'main_node_id' ), 'name' => $object->attribute( 'name' ),
                                         'approved' => isset( $perStatus[CjwNewsletterSubscription::STATUS_APPROVED] ) ? $perStatus[CjwNewsletterSubscription::STATUS_APPROVED] : 0,
                                         'pending' => ( isset( $perStatus[CjwNewsletterSubscription::STATUS_PENDING] ) ? $perStatus[CjwNewsletterSubscription::STATUS_PENDING] : 0 )
                                                    + ( isset( $perStatus[CjwNewsletterSubscription::STATUS_CONFIRMED] ) ? $perStatus[CjwNewsletterSubscription::STATUS_CONFIRMED] : 0 ),
                                         'total' => array_sum( $perStatus ) );
        }

        // the users and the subscriptions
        $users = array( 'total' => 0, 'confirmed' => 0, 'pending' => 0, 'removed' => 0, 'bounced' => 0, 'blacklisted' => 0 );
        foreach ( (array)$db->arrayQuery( 'SELECT status AS s, COUNT(*) AS c FROM cjwnl_user GROUP BY status' ) as $r )
        {
            $c = (int)$r['c'];
            $users['total'] += $c;
            switch ( (int)$r['s'] )
            {
                case CjwNewsletterUser::STATUS_CONFIRMED: $users['confirmed'] += $c; break;
                case CjwNewsletterUser::STATUS_REMOVED_SELF:
                case CjwNewsletterUser::STATUS_REMOVED_ADMIN: $users['removed'] += $c; break;
                case CjwNewsletterUser::STATUS_BOUNCED_SOFT:
                case CjwNewsletterUser::STATUS_BOUNCED_HARD: $users['bounced'] += $c; break;
                case CjwNewsletterUser::STATUS_BLACKLISTED: $users['blacklisted'] += $c; break;
                default: $users['pending'] += $c;
            }
        }
        $summary['users'] = $users;
        $subs = array( 'total' => 0, 'approved' => 0, 'waiting' => 0 );
        foreach ( (array)$db->arrayQuery( 'SELECT status AS s, COUNT(*) AS c FROM cjwnl_subscription WHERE newsletter_user_id IN ( SELECT id FROM cjwnl_user ) GROUP BY status' ) as $r )
        {
            $subs['total'] += (int)$r['c'];
            if ( (int)$r['s'] == CjwNewsletterSubscription::STATUS_APPROVED )
            {
                $subs['approved'] += (int)$r['c'];
            }
            elseif ( in_array( (int)$r['s'], array( CjwNewsletterSubscription::STATUS_PENDING, CjwNewsletterSubscription::STATUS_CONFIRMED ) ) )
            {
                $subs['waiting'] += (int)$r['c'];
            }
        }
        $summary['subscriptions'] = $subs;

        // editions and sends
        $summary['editions'] = self::count( 'SELECT COUNT(DISTINCT e.contentobject_id) FROM cjwnl_edition e INNER JOIN ezcontentobject o ON o.id = e.contentobject_id WHERE o.status = 1' );
        $sends = array( 'scheduled' => 0, 'waiting' => 0, 'queued' => 0, 'sending' => 0, 'finished' => 0, 'aborted' => 0 );
        $map = array( CjwNewsletterEditionSend::STATUS_WAIT_FOR_SCHEDULE => 'scheduled', CjwNewsletterEditionSend::STATUS_WAIT_FOR_PROCESS => 'waiting',
                      CjwNewsletterEditionSend::STATUS_MAILQUEUE_CREATED => 'queued', CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_STARTED => 'sending',
                      CjwNewsletterEditionSend::STATUS_MAILQUEUE_PROCESS_FINISHED => 'finished', CjwNewsletterEditionSend::STATUS_ABORT => 'aborted' );
        foreach ( (array)$db->arrayQuery( 'SELECT status AS s, COUNT(*) AS c FROM cjwnl_edition_send GROUP BY status' ) as $r )
        {
            if ( isset( $map[(int)$r['s']] ) )
            {
                $sends[$map[(int)$r['s']]] += (int)$r['c'];
            }
        }
        $summary['sends'] = $sends;
        $statusNames = array( 4 => 'scheduled', 0 => 'waiting for the queue', 1 => 'queued', 2 => 'sending', 3 => 'finished', 9 => 'aborted' );
        foreach ( (array)$db->arrayQuery( 'SELECT id, edition_contentobject_id, status, created, mailqueue_process_started, mailqueue_process_finished FROM cjwnl_edition_send ORDER BY id DESC', array( 'limit' => 5 ) ) as $r )
        {
            $edition = eZContentObject::fetch( (int)$r['edition_contentobject_id'] );
            $items = array( 'all' => 0, 'sent' => 0, 'failed' => 0 );
            foreach ( (array)$db->arrayQuery( 'SELECT status AS s, COUNT(*) AS c FROM cjwnl_edition_send_item WHERE edition_send_id = ' . (int)$r['id'] . ' GROUP BY status' ) as $ir )
            {
                $items['all'] += (int)$ir['c'];
                if ( (int)$ir['s'] == CjwNewsletterEditionSendItem::STATUS_SEND ) $items['sent'] += (int)$ir['c'];
                if ( (int)$ir['s'] == CjwNewsletterEditionSendItem::STATUS_ABORT ) $items['failed'] += (int)$ir['c'];
            }
            $summary['last_sends'][] = array( 'id' => (int)$r['id'], 'name' => $edition ? $edition->attribute( 'name' ) : '#' . (int)$r['edition_contentobject_id'],
                                              'node_id' => $edition ? (int)$edition->attribute( 'main_node_id' ) : 0,
                                              'status' => isset( $statusNames[(int)$r['status']] ) ? $statusNames[(int)$r['status']] : (string)$r['status'],
                                              'status_code' => (int)$r['status'], 'created' => (int)$r['created'], 'finished' => (int)$r['mailqueue_process_finished'], 'items' => $items );
        }

        // blacklist, mail accounts, imports
        $summary['blacklist'] = self::count( 'SELECT COUNT(*) FROM cjwnl_blacklist_item' );
        $summary['mailboxes'] = array( 'total' => self::count( 'SELECT COUNT(*) FROM cjwnl_mailbox' ),
                                       'active' => self::count( 'SELECT COUNT(*) FROM cjwnl_mailbox WHERE is_activated = 1' ),
                                       'unparsed' => self::count( 'SELECT COUNT(*) FROM cjwnl_mailbox_item WHERE processed = 0' ),
                                       'items' => self::count( 'SELECT COUNT(*) FROM cjwnl_mailbox_item' ) );
        $summary['imports'] = array( 'total' => self::count( 'SELECT COUNT(*) FROM cjwnl_import' ),
                                     'open' => self::count( 'SELECT COUNT(*) FROM cjwnl_import WHERE imported = 0' ) );

        $summary['runs'] = array( 'queue_create' => CjwNewsletterRunner::lastRun( CjwNewsletterRunner::LAST_QUEUE_CREATE ),
                                  'queue_process' => CjwNewsletterRunner::lastRun( CjwNewsletterRunner::LAST_QUEUE_PROCESS ),
                                  'mailbox' => CjwNewsletterRunner::lastRun( CjwNewsletterRunner::LAST_MAILBOX ),
                                  'import' => CjwNewsletterRunner::lastRun( CjwNewsletterRunner::LAST_IMPORT ),
                                  'repair' => CjwNewsletterRunner::lastRun( CjwNewsletterRunner::LAST_REPAIR ) );
        $orphans = CjwNewsletterRunner::orphans();
        $summary['orphans'] = array( 'subscriptions' => count( $orphans['subscriptions'] ), 'send_items' => count( $orphans['send_items'] ) );

        // the problems
        $transport = $summary['transport'];
        if ( !$summary['root']['exists'] )
        {
            $problems[] = array( 'level' => 'warning', 'code' => 'root',
                                 'text' => ezpI18n::tr( $i18n, 'The newsletter folder (cjw_newsletter.ini, RootFolderNodeId=%node) does not exist. The newsletter systems cannot be listed.', null, array( '%node' => $rootNodeId ) ), 'url' => false );
        }
        if ( !$summary['lists'] )
        {
            $problems[] = array( 'level' => 'info', 'code' => 'no_lists',
                                 'text' => ezpI18n::tr( $i18n, 'There is no newsletter list yet. Create a newsletter system and a list below the newsletter folder.' ), 'url' => false );
        }
        if ( !$transport['real'] )
        {
            $problems[] = array( 'level' => 'info', 'code' => 'transport_file',
                                 'text' => ezpI18n::tr( $i18n, 'Mails are written to files (%dir), not sent: TransportMethodCronjob is "file".', null, array( '%dir' => $transport['dir'] ) ), 'url' => false );
            if ( $transport['writable'] === false )
            {
                $problems[] = array( 'level' => 'error', 'code' => 'outbox_not_writable',
                                     'text' => ezpI18n::tr( $i18n, 'The outbox %dir is not writable by the web server.', null, array( '%dir' => $transport['dir'] ) ), 'url' => false );
            }
        }
        elseif ( $transport['method'] == 'smtp' && $transport['smtp_server'] === '' )
        {
            $problems[] = array( 'level' => 'error', 'code' => 'smtp',
                                 'text' => ezpI18n::tr( $i18n, 'The transport is "smtp" but SmtpTransportServer is empty.' ), 'url' => false );
        }
        if ( strpos( $transport['sender'], '@example.' ) !== false )
        {
            $problems[] = array( 'level' => 'warning', 'code' => 'sender',
                                 'text' => ezpI18n::tr( $i18n, 'The default sender address is %sender. Set EmailSender in cjw_newsletter.ini or on each list.', null, array( '%sender' => $transport['sender'] ) ), 'url' => false );
        }
        if ( $summary['orphans']['subscriptions'] || $summary['orphans']['send_items'] )
        {
            $problems[] = array( 'level' => 'warning', 'code' => 'orphans',
                                 'text' => ezpI18n::tr( $i18n, '%subscriptions subscriptions belong to newsletter users that no longer exist and %items waiting mails can never be sent.', null,
                                                        array( '%subscriptions' => $summary['orphans']['subscriptions'], '%items' => $summary['orphans']['send_items'] ) ),
                                 'url' => false, 'action' => 'repair' );
        }
        if ( $users['bounced'] )
        {
            $problems[] = array( 'level' => 'info', 'code' => 'bounced',
                                 'text' => ezpI18n::tr( $i18n, '%count newsletter users are marked as bounced; they get no mail.', null, array( '%count' => $users['bounced'] ) ), 'url' => 'newsletter/user_list' );
        }
        $stuck = 0;
        foreach ( (array)$db->arrayQuery( 'SELECT id FROM cjwnl_edition_send WHERE status IN ( 0, 1, 2 ) AND created < ' . ( time() - 86400 ) ) as $r )
        {
            ++$stuck;
        }
        if ( $stuck )
        {
            $problems[] = array( 'level' => 'warning', 'code' => 'stuck',
                                 'text' => ezpI18n::tr( $i18n, '%count sends are waiting for more than a day. Is the cronjob running?', null, array( '%count' => $stuck ) ), 'url' => false );
        }
        $failed = self::count( 'SELECT COUNT(*) FROM cjwnl_edition_send_item WHERE status = ' . CjwNewsletterEditionSendItem::STATUS_ABORT );
        if ( $failed )
        {
            $problems[] = array( 'level' => 'info', 'code' => 'failed_items',
                                 'text' => ezpI18n::tr( $i18n, '%count mails could not be sent (aborted items).', null, array( '%count' => $failed ) ), 'url' => false );
        }
        if ( ( $sends['waiting'] + $sends['queued'] + $sends['sending'] + $sends['finished'] ) && !$summary['runs']['queue_process'] )
        {
            $problems[] = array( 'level' => 'info', 'code' => 'no_run',
                                 'text' => ezpI18n::tr( $i18n, 'The mail queue has not run yet. Put "php runcronjobs.php cjw_newsletter" in cron.' ), 'url' => false );
        }
        if ( $summary['mailboxes']['unparsed'] )
        {
            $problems[] = array( 'level' => 'info', 'code' => 'unparsed',
                                 'text' => ezpI18n::tr( $i18n, '%count collected mails are not parsed yet.', null, array( '%count' => $summary['mailboxes']['unparsed'] ) ), 'url' => 'newsletter/mailbox_item_list' );
        }
        if ( $summary['mailboxes']['total'] && !$summary['mailboxes']['active'] )
        {
            $problems[] = array( 'level' => 'info', 'code' => 'no_active_mailbox',
                                 'text' => ezpI18n::tr( $i18n, 'No mail account is active, so bounces are not collected.' ), 'url' => 'newsletter/mailbox_list' );
        }
        unset( $problems );
        // extension point: what the feature areas show on the dashboard, as summary.areas.<handler class>; a handler
        // may also return 'problems' (same form as above), which are added to the list
        $summary['areas'] = array();
        foreach ( CjwNewsletterExtensionPoints::call( 'dashboardSummary', array( $summary ) ) as $class => $areaSummary )
        {
            $summary['areas'][$class] = is_array( $areaSummary ) ? $areaSummary : array();
            if ( isset( $areaSummary['problems'] ) && is_array( $areaSummary['problems'] ) )
                foreach ( $areaSummary['problems'] as $problem )
                    $summary['problems'][] = $problem;
        }
        return $summary;
    }
}

?>
