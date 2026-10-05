<?php
/**
 * File containing the CjwNewsletterMappedImport class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage importexport
 */

/**
 * A CSV import with a column mapping into one newsletter list (cjw_newsletter 4.2.0).
 *
 * The settings of an import are kept as JSON in cjwnl_import.data_xml (mapping, delimiter, header, encoding,
 * output formats, update of existing subscribers); the consent source in cjwnl_import.consent_source.
 *
 * Per row:
 * - an empty, invalid or repeated address is skipped and reported;
 * - an address on the suppression list or the newsletter blacklist, or of a person who switched the newsletter
 *   (or all optional mail) off in the e-mail preferences, is skipped and reported;
 * - a person who unsubscribed (the newsletter user, or the subscription of this list) is not subscribed again;
 * - an existing newsletter user is updated, never duplicated: the non-empty mapped values replace the stored ones
 *   (unless "update existing" is off), an existing subscription of the list is approved;
 * - a new person gets a confirmed newsletter user and an approved subscription;
 * - the consent is recorded in the kernel consent log with the source "import" and the consent source wording.
 *
 * A dry run does all the checks and counts and writes nothing but the import's own counters.
 *
 * @package cjw_newsletter
 * @subpackage importexport
 */
class CjwNewsletterMappedImport
{
    const TYPE = 'cjwnl_csv_mapped';

    const STATUS_NEW = 0;
    const STATUS_PREVIEWED = 1;
    const STATUS_RUNNING = 2;
    const STATUS_DONE = 3;
    const STATUS_FAILED = 9;

    /** @var string[] the reasons a row is skipped */
    static $skipReasons = array( 'missing_email', 'invalid', 'duplicate', 'suppressed', 'blacklisted', 'opted_out', 'removed_self', 'bounced' );

    /**
     * @return array reason => its text, for the reports (also the reasons of the eznewsletter migration)
     */
    static function reasonNames()
    {
        $texts = array( 'missing_email' => 'No e-mail address', 'invalid' => 'Not a valid e-mail address', 'duplicate' => 'Repeated in the file',
                        'suppressed' => 'On the suppression list', 'blacklisted' => 'On the newsletter blacklist',
                        'opted_out' => 'Switched the newsletter off in the e-mail preferences', 'removed_self' => 'Unsubscribed before',
                        'bounced' => 'The address bounced', 'error' => 'Failed (see the debug log)', 'no_list' => 'No target list',
                        'robinson' => 'On the old do-not-contact list', 'never_confirmed' => 'Never confirmed', 'already_migrated' => 'Taken over by an earlier run',
                        'salutation' => 'Salutation not understood', 'language' => 'Not a locale (like ger-DE)', 'phone_number' => 'Not a phone number' );
        $result = array();
        foreach ( $texts as $reason => $text )
            $result[$reason] = ezpI18n::tr( 'cjw_newsletter/importexport', $text );
        return $result;
    }

    /**
     * The settings of an import, from data_xml, with the defaults.
     *
     * @param CjwNewsletterImport $import
     * @return array mapping, delimiter, has_header, encoding, formats, update_existing
     */
    static function settings( $import )
    {
        $data = json_decode( (string)$import->attribute( 'data_xml' ), true );
        $data = is_array( $data ) ? $data : array();
        return array( 'mapping' => isset( $data['mapping'] ) && is_array( $data['mapping'] ) ? $data['mapping'] : array(),
                      'delimiter' => CjwNewsletterCsvMapper::delimiterCharacter( isset( $data['delimiter'] ) ? $data['delimiter'] : ';' ),
                      'has_header' => isset( $data['has_header'] ) ? (bool)$data['has_header'] : true,
                      'encoding' => isset( $data['encoding'] ) && in_array( $data['encoding'], CjwNewsletterCsvMapper::$encodings, true ) ? $data['encoding'] : 'UTF-8',
                      'formats' => isset( $data['formats'] ) && is_array( $data['formats'] ) && $data['formats'] ? array_values( array_map( 'intval', $data['formats'] ) ) : array( 0 ),
                      'update_existing' => isset( $data['update_existing'] ) ? (bool)$data['update_existing'] : true );
    }

    /**
     * @param CjwNewsletterImport $import
     * @param array $settings as settings() returns them
     */
    static function storeSettings( $import, $settings )
    {
        $settings['delimiter'] = CjwNewsletterCsvMapper::delimiterName( $settings['delimiter'] );
        $import->setAttribute( 'data_xml', json_encode( $settings ) );
    }

    /**
     * @param CjwNewsletterImport $import
     * @return CjwNewsletterCsvMapper|null null when the file is gone or outside the upload folder
     */
    static function mapper( $import )
    {
        $file = (string)$import->attribute( 'data_text' );
        if ( !CjwNewsletterCsvMapper::isImportFile( $file ) )
            return null;
        $settings = self::settings( $import );
        return new CjwNewsletterCsvMapper( $file, $settings['delimiter'], $settings['has_header'], $settings['encoding'] );
    }

    /**
     * The consent source of an import: its own, else [ImportMappingSettings] DefaultConsentSource.
     * @return string
     */
    static function consentSource( $import )
    {
        $source = trim( (string)$import->attribute( 'consent_source' ) );
        if ( $source !== '' )
            return $source;
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        return $ini->hasVariable( 'ImportMappingSettings', 'DefaultConsentSource' ) ? (string)$ini->variable( 'ImportMappingSettings', 'DefaultConsentSource' ) : 'import';
    }

    /**
     * Runs the import (or its dry run) over every row of the file.
     *
     * @param CjwNewsletterImport $import
     * @param bool $dryRun
     * @param object|false $out a sink with output() (eZCLI, CjwNewsletterJobOutput)
     * @return array totals (rows, users_created, users_updated, subscriptions_created, subscriptions_updated,
     *               unchanged, skipped, errors, reasons => reason => count), rows (one result per row), error
     */
    static function run( $import, $dryRun = false, $out = false )
    {
        $result = array( 'totals' => self::emptyTotals(), 'rows' => array(), 'error' => '', 'dry_run' => (bool)$dryRun );
        $mapper = self::mapper( $import );
        if ( !$mapper )
        {
            $result['error'] = 'file';
            return $result;
        }
        if ( !$dryRun && (int)$import->attribute( 'status' ) === self::STATUS_DONE )
        {
            $result['error'] = 'done';
            return $result;
        }
        $settings = self::settings( $import );
        $mapping = CjwNewsletterCsvMapper::cleanMapping( $settings['mapping'], count( $mapper->header() ) );
        if ( !in_array( 'email', $mapping, true ) )
        {
            $result['error'] = 'no_email';
            return $result;
        }
        $listId = (int)$import->attribute( 'list_contentobject_id' );
        $listObject = eZContentObject::fetch( $listId );
        $listName = $listObject instanceof eZContentObject ? (string)$listObject->attribute( 'name' ) : '#' . $listId;
        $importId = (int)$import->attribute( 'id' );
        $source = self::consentSource( $import );
        $wording = ezpI18n::tr( 'cjw_newsletter/importexport', 'Newsletter "%list": subscribed by the CSV import %id (consent: %source)', null,
                                array( '%list' => $listName, '%id' => $importId, '%source' => $source ) );

        if ( !$dryRun )
        {
            $import->setAttribute( 'status', self::STATUS_RUNNING );
            $import->store();
            CjwNewsletterLog::writeNotice( 'CjwNewsletterMappedImport::run', 'import', 'start',
                                           array( 'import_id' => $importId, 'rows' => $mapper->rowCount(), 'current_user' => eZUser::currentUserID() ) );
        }

        $seen = array();
        $db = eZDB::instance();
        foreach ( $mapper->rows() as $index => $row )
        {
            $line = $index + 1 + ( $settings['has_header'] ? 1 : 0 );
            $values = CjwNewsletterCsvMapper::mapRow( $row, $mapping );
            try
            {
                if ( !$dryRun )
                    $db->begin();
                $rowResult = self::importRow( $values, $listId, $settings, $importId, $wording, $seen, $dryRun );
                if ( !$dryRun )
                    $db->commit();
            }
            catch ( Throwable $e )
            {
                if ( !$dryRun )
                    $db->rollback();
                eZDebug::writeError( 'CSV import ' . $importId . ', line ' . $line . ': ' . $e->getMessage(), __METHOD__ );
                $rowResult = array( 'email' => isset( $values['email'] ) ? $values['email'] : '', 'action' => 'failed', 'reason' => 'error', 'notes' => array() );
            }
            $rowResult['line'] = $line;
            self::count( $result['totals'], $rowResult );
            $result['rows'][] = $rowResult;
            if ( $out )
                $out->output( 'Line ' . $line . ': ' . $rowResult['email'] . ' ' . $rowResult['action'] . ( $rowResult['reason'] !== '' ? ' (' . $rowResult['reason'] . ')' : '' ) );
        }
        $result['totals']['rows'] = count( $result['rows'] );

        $import->setAttribute( 'is_dry_run', $dryRun ? 1 : 0 );
        $import->setAttribute( 'skipped_count', $result['totals']['skipped'] );
        $import->setAttribute( 'error_count', $result['totals']['errors'] );
        if ( $dryRun )
        {
            if ( (int)$import->attribute( 'status' ) < self::STATUS_PREVIEWED )
                $import->setAttribute( 'status', self::STATUS_PREVIEWED );
            $import->store();
        }
        else
        {
            $import->setAttribute( 'status', self::STATUS_DONE );
            $import->setImported();
            CjwNewsletterLog::writeNotice( 'CjwNewsletterMappedImport::run', 'import', 'end',
                                           array( 'import_id' => $importId, 'current_user' => eZUser::currentUserID() ) );
            self::audit( $import, $result['totals'] );
        }
        self::storeResult( $importId, $result );
        return $result;
    }

    /** @return array the counters, all 0 */
    static function emptyTotals()
    {
        $reasons = array();
        foreach ( self::$skipReasons as $reason )
            $reasons[$reason] = 0;
        return array( 'rows' => 0, 'users_created' => 0, 'users_updated' => 0, 'subscriptions_created' => 0, 'subscriptions_updated' => 0,
                      'unchanged' => 0, 'skipped' => 0, 'errors' => 0, 'reasons' => $reasons );
    }

    protected static function count( &$totals, $rowResult )
    {
        switch ( $rowResult['action'] )
        {
            case 'skipped':
                $totals['skipped']++;
                if ( isset( $totals['reasons'][$rowResult['reason']] ) )
                    $totals['reasons'][$rowResult['reason']]++;
                return;
            case 'failed':
                $totals['errors']++;
                return;
        }
        if ( !empty( $rowResult['user'] ) )
            $totals[$rowResult['user'] === 'created' ? 'users_created' : 'users_updated']++;
        if ( $rowResult['subscription'] === 'created' )
            $totals['subscriptions_created']++;
        else if ( $rowResult['subscription'] === 'updated' )
            $totals['subscriptions_updated']++;
        else
            $totals['unchanged']++;
    }

    /**
     * One row.
     *
     * @return array email, action (created, updated, unchanged, skipped, failed), reason, user (created, updated or
     *               ''), subscription (created, updated, unchanged), notes (fields that were not taken)
     */
    protected static function importRow( $values, $listId, $settings, $importId, $wording, &$seen, $dryRun )
    {
        $email = isset( $values['email'] ) ? mb_strtolower( trim( $values['email'] ) ) : '';
        $r = array( 'email' => $email, 'action' => 'skipped', 'reason' => '', 'user' => '', 'subscription' => '', 'notes' => array() );
        if ( $email === '' )
        {
            $r['reason'] = 'missing_email';
            return $r;
        }
        if ( !ezcMailTools::validateEmailAddress( $email ) )
        {
            $r['reason'] = 'invalid';
            return $r;
        }
        if ( isset( $seen[$email] ) )
        {
            $r['reason'] = 'duplicate';
            return $r;
        }
        $seen[$email] = true;
        $blocked = CjwNewsletterImportConsent::blockReason( $email );
        if ( $blocked !== null )
        {
            $r['reason'] = $blocked;
            return $r;
        }

        $fields = self::cleanValues( $values, $r['notes'] );
        $user = CjwNewsletterUser::fetchByEmail( $email );
        $subscription = null;
        if ( is_object( $user ) )
        {
            $status = (int)$user->attribute( 'status' );
            if ( $status === CjwNewsletterUser::STATUS_REMOVED_SELF )
            {
                $r['reason'] = 'removed_self';
                return $r;
            }
            if ( $status === CjwNewsletterUser::STATUS_BLACKLISTED )
            {
                $r['reason'] = 'blacklisted';
                return $r;
            }
            if ( $status === CjwNewsletterUser::STATUS_BOUNCED_HARD )
            {
                $r['reason'] = 'bounced';
                return $r;
            }
            $subscription = CjwNewsletterSubscription::fetchByListIdAndNewsletterUserId( $listId, $user->attribute( 'id' ) );
            if ( is_object( $subscription ) )
            {
                $subStatus = (int)$subscription->attribute( 'status' );
                if ( $subStatus === CjwNewsletterSubscription::STATUS_REMOVED_SELF )
                {
                    $r['reason'] = 'removed_self';
                    return $r;
                }
                if ( $subStatus === CjwNewsletterSubscription::STATUS_BLACKLISTED )
                {
                    $r['reason'] = 'blacklisted';
                    return $r;
                }
                if ( $subStatus === CjwNewsletterSubscription::STATUS_BOUNCED_HARD )
                {
                    $r['reason'] = 'bounced';
                    return $r;
                }
            }
        }

        // the user
        if ( !is_object( $user ) )
        {
            $r['user'] = 'created';
            if ( !$dryRun )
            {
                $user = CjwNewsletterUser::create( $email, isset( $fields['salutation'] ) ? $fields['salutation'] : 0,
                    isset( $fields['first_name'] ) ? $fields['first_name'] : '', isset( $fields['last_name'] ) ? $fields['last_name'] : '',
                    false, CjwNewsletterUser::STATUS_PENDING, 'csvimport', '', '', '', '' );
                foreach ( $fields as $field => $value )
                    $user->setAttribute( $field, $value );
                $user->setAttribute( 'status', CjwNewsletterUser::STATUS_CONFIRMED );
                $user->setAttribute( 'import_id', $importId );
                $user->store();
            }
        }
        else
        {
            $changed = false;
            if ( $settings['update_existing'] )
            {
                foreach ( $fields as $field => $value )
                {
                    if ( $value !== '' && $value !== 0 && (string)$user->attribute( $field ) !== (string)$value )
                    {
                        $changed = true;
                        if ( !$dryRun )
                            $user->setAttribute( $field, $value );
                    }
                }
            }
            if ( (int)$user->attribute( 'status' ) !== CjwNewsletterUser::STATUS_CONFIRMED )
            {
                $changed = true;
                if ( !$dryRun )
                    $user->setAttribute( 'status', CjwNewsletterUser::STATUS_CONFIRMED );
            }
            if ( $changed )
            {
                $r['user'] = 'updated';
                if ( !$dryRun )
                    $user->store();
            }
        }

        // the subscription of the list
        $active = array( CjwNewsletterSubscription::STATUS_APPROVED );
        if ( is_object( $subscription ) && in_array( (int)$subscription->attribute( 'status' ), $active, true ) )
        {
            $r['subscription'] = 'unchanged';
        }
        else if ( is_object( $subscription ) )
        {
            $r['subscription'] = 'updated';
            if ( !$dryRun )
            {
                $subscription->setAttribute( 'status', CjwNewsletterSubscription::STATUS_APPROVED );
                $subscription->setAttribute( 'import_id', $importId );
                CjwNewsletterImportConsent::storeQuietly( $subscription );
            }
        }
        else
        {
            $r['subscription'] = 'created';
            if ( !$dryRun )
            {
                $subscription = CjwNewsletterSubscription::create( $listId, $user->attribute( 'id' ), $settings['formats'],
                                                                   CjwNewsletterSubscription::STATUS_APPROVED, 'csvimport' );
                $subscription->setAttribute( 'import_id', $importId );
                CjwNewsletterImportConsent::storeQuietly( $subscription );
            }
        }
        if ( !$dryRun && $r['subscription'] !== 'unchanged' )
            CjwNewsletterImportConsent::recordOn( $user, $wording );

        $r['action'] = $r['user'] === 'created' ? 'created' : ( ( $r['user'] === 'updated' || $r['subscription'] !== 'unchanged' ) ? 'updated' : 'unchanged' );
        return $r;
    }

    /**
     * The mapped values, cleaned: salutation as a number, language as a locale, the phone number normalised.
     * A value that cannot be used is dropped and named in $notes.
     *
     * @param array $values field => value
     * @param string[] $notes
     * @return array field => value (without email)
     */
    static function cleanValues( $values, &$notes )
    {
        $fields = array();
        $known = array_keys( CjwNewsletterUser::definition()['fields'] );
        foreach ( $values as $field => $value )
        {
            if ( $field === 'email' || !in_array( $field, $known, true ) )
                continue;
            $value = trim( (string)$value );
            if ( $value === '' )
                continue;
            switch ( $field )
            {
                case 'salutation':
                    $salutation = self::salutation( $value );
                    if ( $salutation === null )
                        $notes[] = 'salutation';
                    else
                        $fields[$field] = $salutation;
                    break;
                case 'language':
                    $value = str_replace( '_', '-', $value );
                    if ( preg_match( '/^([a-z]{3})-([a-z]{2})$/i', $value, $m ) )
                        $fields[$field] = strtolower( $m[1] ) . '-' . strtoupper( $m[2] );
                    else
                        $notes[] = 'language';
                    break;
                case 'phone_number':
                    $phone = self::phone( $value );
                    if ( $phone === null )
                        $notes[] = 'phone_number';
                    else
                        $fields[$field] = $phone;
                    break;
                default:
                    $fields[$field] = mb_substr( $value, 0, 255 );
            }
        }
        return $fields;
    }

    /**
     * @param string $value 1, 2, or a word (Mr, Ms, Herr, Frau, ...)
     * @return int|null
     */
    static function salutation( $value )
    {
        $available = CjwNewsletterUser::getAvailableSalutationNameArrayFromIni();
        if ( ctype_digit( $value ) )
            return isset( $available[(int)$value] ) || (int)$value === 0 ? (int)$value : null;
        $key = preg_replace( '/[^a-z]/', '', mb_strtolower( $value ) );
        $words = array( 1 => array( 'mr', 'mister', 'herr', 'hr', 'm', 'monsieur', 'male', 'man' ),
                        2 => array( 'ms', 'mrs', 'miss', 'frau', 'fr', 'mme', 'madame', 'f', 'female', 'woman' ) );
        foreach ( $words as $id => $list )
            if ( in_array( $key, $list, true ) && isset( $available[$id] ) )
                return $id;
        foreach ( $available as $id => $name )
            if ( mb_strtolower( (string)$name ) === mb_strtolower( $value ) )
                return (int)$id;
        return null;
    }

    /**
     * @param string $value
     * @return string|null the number with only a leading + and digits, null when it is not one
     */
    static function phone( $value )
    {
        $value = trim( (string)$value );
        if ( preg_match( '/[^0-9+\s().\/-]/', $value ) )
            return null;
        $plus = strpos( ltrim( $value ), '+' ) === 0;
        if ( strpos( ltrim( $value ), '00' ) === 0 )
        {
            $plus = true;
            $value = substr( ltrim( $value ), 2 );
        }
        $digits = preg_replace( '/[^0-9]/', '', $value );
        if ( strlen( $digits ) < 6 || strlen( $digits ) > 20 )
            return null;
        return ( $plus ? '+' : '' ) . $digits;
    }

    /** The audit event of a finished import (data.import.csv). */
    protected static function audit( $import, $totals )
    {
        if ( !class_exists( 'expAudit' ) )
            return;
        $reasons = array_filter( $totals['reasons'] );
        expAudit::event( 'data.import.csv', array(
            'object' => array( 'type' => 'cjw_newsletter_list', 'id' => (int)$import->attribute( 'list_contentobject_id' ) ),
            'after' => array( 'import_id' => (int)$import->attribute( 'id' ), 'rows' => $totals['rows'],
                              'users_created' => $totals['users_created'], 'users_updated' => $totals['users_updated'],
                              'subscriptions_created' => $totals['subscriptions_created'], 'subscriptions_updated' => $totals['subscriptions_updated'],
                              'skipped' => $totals['skipped'], 'errors' => $totals['errors'] ),
            'x' => array( 'extension' => 'cjw_newsletter', 'mapping_id' => (int)$import->attribute( 'mapping_id' ),
                          'consent_source' => self::consentSource( $import ), 'skipped_by_reason' => $reasons ) ) );
    }

    /** @return string the file that keeps the result of the last run of an import */
    static function resultFilePath( $importId )
    {
        return CjwNewsletterCsvMapper::directory() . '/' . (int)$importId . '-mapped_result.json';
    }

    static function storeResult( $importId, $result )
    {
        $file = self::resultFilePath( $importId );
        eZDir::mkdir( dirname( $file ), false, true );
        return file_put_contents( $file, json_encode( $result ) ) !== false;
    }

    /** @return array|null the result of the last run */
    static function readResult( $importId )
    {
        $file = self::resultFilePath( $importId );
        $data = is_file( $file ) ? json_decode( (string)file_get_contents( $file ), true ) : null;
        return is_array( $data ) ? $data : null;
    }
}

?>
