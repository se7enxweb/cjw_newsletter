<?php
/**
 * File containing the CjwNewsletterSuppressionImport class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage deliverability
 */

/**
 * Imports a CSV file of addresses into the kernel suppression list (expMailSuppression): a do-not-contact list, the
 * bounces of an earlier mail system, a list of legal requests. No optional mail of the site goes to these addresses
 * afterwards, and the newsletter's suppression listener puts each one on its blacklist.
 *
 * The file has one address per row, in the column "email" (also "e-mail", "mail", "address") or, without such a
 * header, in the first column that holds an address. The delimiter (";", ",", tab or "|") is found from the first
 * line. Every new entry is written to the consent log with the source "import". A dry run counts and changes
 * nothing. The file itself is never stored.
 *
 * @package cjw_newsletter
 * @subpackage deliverability
 */
class CjwNewsletterSuppressionImport
{
    /** the reasons an import may give (expMailSuppression::REASONS without "bridge", which belongs to the newsletter blacklist) */
    static $reasons = array( 'legal', 'admin', 'bounce', 'complaint', 'unsubscribe_all' );

    /**
     * @return bool the kernel suppression list exists here
     */
    static function available()
    {
        return class_exists( 'expMailSuppression' ) && CjwNewsletterMailPreferences::available();
    }

    /**
     * @return int the most rows a file may have ([DeliverabilitySettings] SuppressionImportMaxRows)
     */
    static function maxRows()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $max = $ini->hasVariable( 'DeliverabilitySettings', 'SuppressionImportMaxRows' ) ? (int)$ini->variable( 'DeliverabilitySettings', 'SuppressionImportMaxRows' ) : 100000;
        return max( 1, $max );
    }

    /**
     * Reads the addresses of a CSV text.
     *
     * @param string $csv
     * @return array emails (lower case, in file order, without duplicates), invalid (hash( line, value ), at most 50),
     *               invalid_count, duplicates, rows, column (the header used, or the number of the column), too_many (bool)
     */
    static function parse( $csv )
    {
        $csv = (string)$csv;
        if ( strpos( $csv, "\xEF\xBB\xBF" ) === 0 )
            $csv = substr( $csv, 3 );
        $csv = str_replace( array( "\r\n", "\r" ), "\n", $csv );
        $out = array( 'emails' => array(), 'invalid' => array(), 'invalid_count' => 0, 'duplicates' => 0, 'rows' => 0, 'column' => '', 'too_many' => false );
        $lines = explode( "\n", $csv );
        $first = '';
        foreach ( $lines as $line )
            if ( trim( $line ) !== '' )
            {
                $first = $line;
                break;
            }
        if ( $first === '' )
            return $out;
        $delimiter = ';';
        $best = -1;
        foreach ( array( ';', ',', "\t", '|' ) as $candidate )
        {
            $n = substr_count( $first, $candidate );
            if ( $n > $best )
            {
                $best = $n;
                $delimiter = $candidate;
            }
        }
        $column = null;
        $header = str_getcsv( $first, $delimiter, '"', '' );
        foreach ( $header as $i => $name )
        {
            if ( in_array( strtolower( trim( (string)$name ) ), array( 'email', 'e-mail', 'mail', 'address', 'email_address', 'e-mail-adresse', 'adresse' ), true ) )
            {
                $column = $i;
                $out['column'] = trim( (string)$name );
                break;
            }
        }
        $skipHeader = $column !== null;
        $seen = array();
        $max = self::maxRows();
        foreach ( $lines as $number => $line )
        {
            if ( trim( $line ) === '' )
                continue;
            if ( $skipHeader )
            {
                $skipHeader = false;
                continue;
            }
            $out['rows']++;
            if ( $out['rows'] > $max )
            {
                $out['too_many'] = true;
                $out['rows']--;
                break;
            }
            $cells = str_getcsv( $line, $delimiter, '"', '' );
            $value = '';
            if ( $column !== null )
                $value = isset( $cells[$column] ) ? trim( (string)$cells[$column] ) : '';
            else
            {
                foreach ( $cells as $i => $cell )
                    if ( strpos( (string)$cell, '@' ) !== false )
                    {
                        $value = trim( (string)$cell );
                        if ( $out['column'] === '' )
                            $out['column'] = '#' . ( $i + 1 );
                        break;
                    }
            }
            $value = trim( $value, " \t<>\"'" );
            if ( $value === '' || !eZMail::validate( $value ) )
            {
                $out['invalid_count']++;
                if ( count( $out['invalid'] ) < 50 )
                    $out['invalid'][] = array( 'line' => $number + 1, 'value' => mb_substr( $value !== '' ? $value : trim( $line ), 0, 80 ) );
                continue;
            }
            $email = strtolower( $value );
            if ( isset( $seen[$email] ) )
            {
                $out['duplicates']++;
                continue;
            }
            $seen[$email] = true;
            $out['emails'][] = $email;
        }
        return $out;
    }

    /**
     * Imports a CSV text.
     *
     * @param string $csv
     * @param string $reason one of self::$reasons
     * @param string $note a note for the admins on every entry (addresses are taken out of it)
     * @param bool $dryRun count only
     * @param string $source the name of the file, for the note and the consent log
     * @return array ok, error, dry_run, reason, rows, valid, invalid_count, invalid, duplicates, already, added, column, too_many
     */
    static function import( $csv, $reason, $note = '', $dryRun = false, $source = '' )
    {
        $result = array( 'ok' => false, 'error' => '', 'dry_run' => (bool)$dryRun, 'reason' => (string)$reason, 'rows' => 0, 'valid' => 0,
                         'invalid_count' => 0, 'invalid' => array(), 'duplicates' => 0, 'already' => 0, 'added' => 0, 'column' => '', 'too_many' => false );
        if ( !self::available() )
        {
            $result['error'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'The e-mail preferences of Exponential are not installed here: there is no suppression list.' );
            return $result;
        }
        if ( !in_array( $reason, self::$reasons, true ) )
        {
            $result['error'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'Choose a reason: %reasons.', null, array( '%reasons' => implode( ', ', self::$reasons ) ) );
            return $result;
        }
        $parsed = self::parse( $csv );
        foreach ( array( 'rows', 'invalid_count', 'invalid', 'duplicates', 'column', 'too_many' ) as $key )
            $result[$key] = $parsed[$key];
        $result['valid'] = count( $parsed['emails'] );
        if ( $parsed['too_many'] )
        {
            $result['error'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'The file has more than %max rows. Split it.', null, array( '%max' => self::maxRows() ) );
            return $result;
        }
        if ( $result['rows'] === 0 )
        {
            $result['error'] = ezpI18n::tr( 'cjw_newsletter/deliverability', 'The file holds no rows.' );
            return $result;
        }
        $label = trim( 'CSV import ' . basename( (string)$source ) . ' ' . date( 'Y-m-d' ) );
        $fullNote = trim( $label . ( trim( (string)$note ) !== '' ? ': ' . trim( (string)$note ) : '' ) );
        $context = null;
        if ( !$dryRun )
            $context = PHP_SAPI === 'cli' ? expConsentContext::system( $fullNote, 'import' ) : expConsentContext::fromRequest( 'import', $fullNote );
        $actor = (int)eZUser::currentUserID();
        foreach ( $parsed['emails'] as $email )
        {
            if ( expMailSuppression::isSuppressed( $email ) )
            {
                $result['already']++;
                continue;
            }
            $result['added']++;
            if ( $dryRun )
                continue;
            expMailSuppression::add( $email, $reason, $fullNote, $actor );
            $recipient = expMailRecipient::fromAddress( $email );
            if ( $recipient !== null )
                expConsentLog::record( $recipient, '', 'suppress', '', $reason, $context );
        }
        if ( !$dryRun && class_exists( 'expAudit' ) )
            expAudit::event( 'system.cjw_newsletter.suppression_import', array( 'object' => 'cjw_newsletter:suppression_import',
                'after' => array( 'reason' => $reason, 'rows' => $result['rows'], 'added' => $result['added'], 'already' => $result['already'] ) ) );
        $result['ok'] = true;
        return $result;
    }
}

?>
