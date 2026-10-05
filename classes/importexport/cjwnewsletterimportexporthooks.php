<?php
/**
 * File containing the CjwNewsletterImportExportHooks class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage importexport
 */

/**
 * The extension point handler of the area import/export and migration ([ExtensionPointSettings] Handlers[]).
 * It gives the dashboard its block: the last imports and the last eznewsletter migration runs.
 *
 * @package cjw_newsletter
 * @subpackage importexport
 */
class CjwNewsletterImportExportHooks
{
    /**
     * @param array $summary the dashboard summary
     * @return array imports (the last five), import_count, runs (the last three migration runs), run_count,
     *               problems (an import that failed or is stuck)
     */
    static function dashboardSummary( $summary )
    {
        $result = array( 'imports' => array(), 'import_count' => 0, 'runs' => array(), 'run_count' => 0, 'problems' => array() );
        try
        {
            $db = eZDB::instance();
            $result['import_count'] = CjwNewsletterImport::fetchAllImportItemsCount();
            $rows = $db->arrayQuery( 'SELECT id, type, list_contentobject_id, created, imported, imported_subscription_count, status, is_dry_run, skipped_count, error_count'
                                     . ' FROM cjwnl_import ORDER BY id DESC', array( 'limit' => 5 ) );
            foreach ( is_array( $rows ) ? $rows : array() as $row )
            {
                $list = eZContentObject::fetch( (int)$row['list_contentobject_id'] );
                $result['imports'][] = array( 'id' => (int)$row['id'], 'mapped' => $row['type'] === CjwNewsletterMappedImport::TYPE,
                                              'list' => $list instanceof eZContentObject ? (string)$list->attribute( 'name' ) : '',
                                              'created' => (int)$row['created'], 'imported' => (int)$row['imported'],
                                              'subscriptions' => (int)$row['imported_subscription_count'], 'status' => (int)$row['status'],
                                              'dry_run' => (int)$row['is_dry_run'] === 1, 'skipped' => (int)$row['skipped_count'],
                                              'errors' => (int)$row['error_count'] );
                if ( (int)$row['status'] === CjwNewsletterMappedImport::STATUS_RUNNING && (int)$row['created'] < time() - 3600 )
                    $result['problems'][] = array( 'level' => 'warning', 'url' => 'newsletter/import_mapping/' . (int)$row['id'],
                        'text' => ezpI18n::tr( 'cjw_newsletter/importexport', 'The import %id has been running for more than an hour.', null, array( '%id' => (int)$row['id'] ) ) );
            }
            $result['runs'] = CjwNewsletterEznewsletterMigration::runs( 3 );
            $result['run_count'] = CjwNewsletterEznewsletterMigration::runCount();
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( 'Import/export dashboard: ' . $e->getMessage(), __METHOD__ );
        }
        return $result;
    }
}

?>
