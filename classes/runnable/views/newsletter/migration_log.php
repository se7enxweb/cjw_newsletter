<?php
/**
 * newsletter/migration_log[/<run id>]: the runs of ext:cjw_newsletter:import-eznewsletter, and the rows of one run
 * (filtered by (action)/<action> and (table)/<old table>, paged with (offset)).
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

namespace Exponential\View\Extension\CjwNewsletter\Newsletter
{

class MigrationLog extends \Exponential\Runnable\ModuleView
{
    const LIMIT = 50;

    public function run( array $scope )
    {
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        include_once( 'kernel/common/template.php' );
        $module = $Params['Module'];
        $runId = isset( $Params['RunId'] ) ? (string)$Params['RunId'] : '';
        $userParameters = isset( $Params['UserParameters'] ) && is_array( $Params['UserParameters'] ) ? $Params['UserParameters'] : array();
        $offset = isset( $userParameters['offset'] ) ? max( 0, (int)$userParameters['offset'] ) : 0;
        $actions = array( 'created', 'merged', 'skipped', 'failed', 'recorded' );
        $tables = \CjwNewsletterEznewsletterSource::$tables;
        $action = isset( $userParameters['action'] ) && in_array( $userParameters['action'], $actions, true ) ? $userParameters['action'] : '';
        $table = isset( $userParameters['table'] ) && in_array( $userParameters['table'], $tables, true ) ? $userParameters['table'] : '';

        $tpl = templateInit();
        $tpl->setVariable( 'reasons', \CjwNewsletterMappedImport::reasonNames() );
        $path = array( array( 'url' => 'newsletter/index', 'text' => \ezpI18n::tr( 'cjw_newsletter/path', 'Newsletter' ) ),
                       array( 'url' => $runId !== '' ? 'newsletter/migration_log' : false, 'text' => \ezpI18n::tr( 'cjw_newsletter/importexport', 'eznewsletter migration' ) ) );

        if ( $runId === '' )
        {
            $tpl->setVariable( 'runs', \CjwNewsletterEznewsletterMigration::runs( self::LIMIT, $offset ) );
            $tpl->setVariable( 'run_count', \CjwNewsletterEznewsletterMigration::runCount() );
            $tpl->setVariable( 'view_parameters', array( 'offset' => $offset ) );
            $tpl->setVariable( 'limit', self::LIMIT );
            $tpl->setVariable( 'tables_found', $this->tablesFound() );
            $Result = array( 'content' => $tpl->fetch( 'design:newsletter/importexport/migration_log.tpl' ), 'path' => $path );
            return $this->viewResult( $Result, null );
        }

        if ( !preg_match( '/^[a-z0-9-]{1,40}$/', $runId ) )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $conditions = array( 'run_id' => $runId );
        if ( $action !== '' )
            $conditions['action'] = $action;
        if ( $table !== '' )
            $conditions['source_table'] = $table;
        $total = \CjwNewsletterMigrationLog::fetchListCount( array( 'run_id' => $runId ) );
        if ( !$total )
            return $this->viewResult( null, $module->handleError( \eZError::KERNEL_NOT_AVAILABLE, 'kernel' ) );
        $summary = null;
        foreach ( \CjwNewsletterEznewsletterMigration::runs( 1000 ) as $run )
            if ( $run['run_id'] === $runId )
                $summary = $run;
        $db = \eZDB::instance();
        $byTable = $db->arrayQuery( "SELECT source_table, action, COUNT(*) AS c FROM cjwnl_migration_log WHERE run_id = '" . $db->escapeString( $runId ) . "'"
                                    . ' GROUP BY source_table, action ORDER BY source_table, action' );
        $matrix = array();
        foreach ( is_array( $byTable ) ? $byTable : array() as $row )
            $matrix[$row['source_table']][$row['action']] = (int)$row['c'];

        $tpl->setVariable( 'run_id', $runId );
        $tpl->setVariable( 'summary', $summary );
        $tpl->setVariable( 'matrix', $matrix );
        $tpl->setVariable( 'actions', $actions );
        $tpl->setVariable( 'action', $action );
        $tpl->setVariable( 'table', $table );
        $reasons = \CjwNewsletterMappedImport::reasonNames();
        $rows = array();
        foreach ( \CjwNewsletterMigrationLog::fetchList( $conditions, self::LIMIT, $offset, array( 'id' => 'asc' ) ) as $logRow )
        {
            $message = (string)$logRow->attribute( 'message' );
            $parts = explode( ': ', $message, 2 );
            if ( $logRow->attribute( 'action' ) === 'skipped' && isset( $reasons[$parts[0]] ) )
                $message = $reasons[$parts[0]] . ( isset( $parts[1] ) ? ' (' . $parts[1] . ')' : '' );
            else if ( $logRow->attribute( 'action' ) === 'recorded' )
            {
                $send = json_decode( $message, true );
                if ( is_array( $send ) )
                    $message = \ezpI18n::tr( 'cjw_newsletter/importexport', '"%name", sent %date: %sent of %items mails', null,
                        array( '%name' => isset( $send['name'] ) ? $send['name'] : '', '%date' => isset( $send['send_date'] ) ? substr( $send['send_date'], 0, 10 ) : '',
                               '%sent' => isset( $send['sent'] ) ? (int)$send['sent'] : 0, '%items' => isset( $send['items'] ) ? (int)$send['items'] : 0 ) );
            }
            $rows[] = array( 'source_table' => $logRow->attribute( 'source_table' ), 'source_id' => $logRow->attribute( 'source_id' ),
                             'action' => $logRow->attribute( 'action' ), 'target_table' => $logRow->attribute( 'target_table' ),
                             'target_id' => (int)$logRow->attribute( 'target_id' ), 'details' => $message );
        }
        $tpl->setVariable( 'rows', $rows );
        $tpl->setVariable( 'row_count', \CjwNewsletterMigrationLog::fetchListCount( $conditions ) );
        $tpl->setVariable( 'limit', self::LIMIT );
        $tpl->setVariable( 'view_parameters', array( 'offset' => $offset, 'action' => $action, 'table' => $table ) );
        $tpl->setVariable( 'page_uri', 'newsletter/migration_log/' . $runId . ( $action !== '' ? '/(action)/' . $action : '' ) . ( $table !== '' ? '/(table)/' . $table : '' ) );
        $path[] = array( 'url' => false, 'text' => $runId );
        $Result = array( 'content' => $tpl->fetch( 'design:newsletter/importexport/migration_run.tpl' ), 'path' => $path );
        return $this->viewResult( $Result, null );
    }

    /**
     * @return array table => row count of the old tables in this database (none: the old extension never ran here)
     */
    protected function tablesFound()
    {
        $found = array();
        try
        {
            $source = \CjwNewsletterEznewsletterSource::sameDatabase();
            foreach ( \CjwNewsletterEznewsletterSource::$tables as $table )
                if ( $source->hasTable( $table ) )
                    $found[$table] = $source->count( $table );
        }
        catch ( \Throwable $e )
        {
            \eZDebug::writeError( $e->getMessage(), __METHOD__ );
        }
        return $found;
    }
}

}
