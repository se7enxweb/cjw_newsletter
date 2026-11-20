<?php
/**
 * File containing the CjwNewsletterSegmentEvaluator class
 *
 * @copyright Copyright CJW Network and Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage segments
 */

/**
 * Evaluates segment conditions against the approved subscriptions of one list. No kernel class is used.
 *
 * @package cjw_newsletter
 * @subpackage segments
 */
class CjwNewsletterSegmentEvaluator
{
    /** Same value as CjwNewsletterSubscription::STATUS_APPROVED, kept here to stay kernel-free. */
    const STATUS_APPROVED = 2;

    /** @var CjwNewsletterSegmentDb */
    private $db;

    /** @var int number of known items read per query */
    private $batchSize;

    public function __construct( CjwNewsletterSegmentDb $db, $batchSize = 5000 )
    {
        $this->db = $db;
        $this->batchSize = max( 1, (int)$batchSize );
    }

    /**
     * @return bool true when the eZ Tags table eztags_attribute_link exists
     */
    public function tagsAvailable()
    {
        $probes = array(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'eztags_attribute_link'",
            "SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = 'eztags_attribute_link'",
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'eztags_attribute_link'",
        );
        foreach ( $probes as $sql )
        {
            try
            {
                return count( $this->db->rows( $sql ) ) > 0;
            }
            catch ( \Throwable $e )
            {
                // Not this database flavour, try the next probe.
            }
        }
        return false;
    }

    /**
     * @param int $listId
     * @param callable $hasConsent receives a newsletter user id, returns bool
     * @return int distinct users with an approved subscription to the list for whom $hasConsent is false
     */
    public function nonConsentingCount( $listId, $hasConsent )
    {
        $count = 0;
        foreach ( array_unique( $this->approvedSubscriptions( (int)$listId ) ) as $userId )
        {
            if ( !call_user_func( $hasConsent, $userId ) )
            {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return int[] subscription id => newsletter user id, approved subscriptions of the list
     */
    private function approvedSubscriptions( $listId )
    {
        $map = array();
        $rows = $this->db->rows( 'SELECT id, newsletter_user_id FROM cjwnl_subscription WHERE list_contentobject_id = '
            . $listId . ' AND status = ' . self::STATUS_APPROVED );
        foreach ( $rows as $row )
        {
            $map[(int)$row['id']] = (int)$row['newsletter_user_id'];
        }
        return $map;
    }

    /**
     * @param int $listId
     * @param string $matchMode 'all' (intersection) or 'any' (union)
     * @param array[] $conditions
     * @param int $now unix timestamp
     * @param int $cutoff oldest send date that counts for behavior conditions
     * @param callable $hasConsent receives a newsletter user id, returns bool
     * @return int[] ids of the matching approved subscriptions, ascending; empty on an invalid condition
     */
    public function members( $listId, $matchMode, array $conditions, $now, $cutoff, $hasConsent )
    {
        if ( !$conditions || ( $matchMode !== 'all' && $matchMode !== 'any' ) )
        {
            return array();
        }

        $clean = array();
        foreach ( $conditions as $condition )
        {
            $normalised = is_array( $condition ) ? CjwNewsletterSegmentCondition::normalise( $condition ) : false;
            if ( !is_array( $normalised ) )
            {
                return array();
            }
            $clean[] = $normalised;
        }

        $result = null;
        foreach ( $clean as $condition )
        {
            $set = CjwNewsletterSegmentCondition::isBehavior( $condition )
                ? $this->behaviorMatching( (int)$listId, $condition, (int)$now, (int)$cutoff, $hasConsent )
                : $this->matching( (int)$listId, $condition, (int)$now );
            if ( $result === null )
            {
                $result = $set;
            }
            else if ( $matchMode === 'all' )
            {
                $result = array_values( array_intersect( $result, $set ) );
            }
            else
            {
                $result = array_values( array_unique( array_merge( $result, $set ) ) );
            }
        }

        sort( $result, SORT_NUMERIC );
        return $result;
    }

    /**
     * @return int[] ids of approved subscriptions whose user matches a behavior condition and consents now
     */
    private function behaviorMatching( $listId, array $c, $now, $cutoff, $hasConsent )
    {
        $users = $this->behaviorUsers( $listId, $c, $now, $cutoff );
        $ids = array();
        $consent = array();
        foreach ( $this->approvedSubscriptions( $listId ) as $subscriptionId => $userId )
        {
            if ( !isset( $users[$userId] ) )
            {
                continue;
            }
            if ( !isset( $consent[$userId] ) )
            {
                $consent[$userId] = (bool)call_user_func( $hasConsent, $userId );
            }
            if ( $consent[$userId] )
            {
                $ids[] = $subscriptionId;
            }
        }
        return $ids;
    }

    /**
     * @return array user id => true for the users matching the behavior condition (consent not yet applied)
     */
    private function behaviorUsers( $listId, array $c, $now, $cutoff )
    {
        // person_tracking: bit 1 = per-person clicks, bit 2 = per-person opens.
        $knownFrom = 'FROM cjwnl_edition_send_item i INNER JOIN cjwnl_edition_send s ON s.id = i.edition_send_id';
        $knownWhere = ' WHERE s.list_contentobject_id = ' . $listId
            . " AND s.channel = 'email' AND s.test_group_id = 0 AND i.status = 1"
            . ' AND s.created >= ' . $cutoff;

        switch ( $c['type'] )
        {
            case 'clicked_object':
            case 'clicked_tag':
                $objects = $c['type'] === 'clicked_object'
                    ? 'l.contentobject_id = ' . (int)$c['contentobject_id']
                    : 'l.contentobject_id IN ( SELECT object_id FROM eztags_attribute_link WHERE keyword_id = ' . (int)$c['tag_id'] . ' )';
                if ( $c['type'] === 'clicked_tag' && !$this->tagsAvailable() )
                {
                    return array();
                }
                $rows = $this->db->rows( 'SELECT DISTINCT i.newsletter_user_id ' . $knownFrom
                    . ' INNER JOIN cjwnl_link_click k ON k.edition_send_item_id = i.id'
                    . ' INNER JOIN cjwnl_link l ON l.id = k.link_id'
                    . $knownWhere . ' AND ( i.person_tracking & 1 ) <> 0 AND ' . $objects );
                $users = array();
                foreach ( $rows as $row )
                {
                    $users[(int)$row['newsletter_user_id']] = true;
                }
                return $users;
        }

        $negated = $c['type'] === 'not_opened' || $c['type'] === 'not_clicked';
        $opens = $c['type'] === 'opened' || $c['type'] === 'not_opened';
        $bit = $opens ? 2 : 1;
        $positive = $opens ? '( i.first_opened > 0 OR i.open_count > 0 )' : 'i.click_count > 0';
        $n = (int)$c['n'];
        $days = !$negated && $c['window'] === 'days';
        $sql = 'SELECT i.newsletter_user_id, CASE WHEN ' . $positive . ' THEN 1 ELSE 0 END AS hit '
            . $knownFrom . $knownWhere . ' AND ( i.person_tracking & ' . $bit . ' ) <> 0'
            . ( $days ? ' AND s.created >= ' . ( $now - $n * 86400 ) : '' )
            . ' ORDER BY i.newsletter_user_id, s.created DESC, i.id DESC';

        // Rows arrive grouped by user, so only the counters of the current user are kept.
        $users = array();
        $current = null;
        $seen = 0;
        $hit = false;
        $flush = function () use ( &$users, &$current, &$seen, &$hit, $negated, $days, $n )
        {
            if ( $current === null )
            {
                return;
            }
            if ( $negated ? ( $seen >= $n && !$hit ) : $hit )
            {
                $users[$current] = true;
            }
        };
        $offset = 0;
        do
        {
            $rows = $this->db->rows( $sql, $offset, $this->batchSize );
            foreach ( $rows as $row )
            {
                $userId = (int)$row['newsletter_user_id'];
                if ( $userId !== $current )
                {
                    $flush();
                    $current = $userId;
                    $seen = 0;
                    $hit = false;
                }
                $seen++;
                if ( ( $days || $seen <= $n ) && (int)$row['hit'] === 1 )
                {
                    $hit = true;
                }
            }
            $offset += $this->batchSize;
        } while ( count( $rows ) === $this->batchSize );
        $flush();

        return $users;
    }

    /**
     * @return int[] subscription ids matching one cleaned condition
     */
    private function matching( $listId, array $c, $now )
    {
        $where = '';
        switch ( $c['type'] )
        {
            case 'subscribed_within':
                // The field name is one of the whitelisted values.
                $where = 's.' . $c['field'] . ' >= ' . ( $now - $c['days'] * 86400 );
                break;

            case 'language':
                $where = 'u.language IN ( ' . $this->quoteList( $c['values'] ) . ' )';
                break;

            case 'output_format':
                $where = "s.output_format_array_string LIKE '%;" . ( $c['format'] === 'html' ? 0 : 1 ) . ";%'";
                break;

            case 'salutation':
                $where = 'u.salutation IN ( ' . implode( ', ', array_map( 'intval', $c['values'] ) ) . ' )';
                break;

            case 'profile':
                $col = 'u.' . $c['field'];
                switch ( $c['op'] )
                {
                    case 'set':
                        $where = "( $col IS NOT NULL AND $col <> '' )";
                        break;
                    case 'not_set':
                        $where = "( $col IS NULL OR $col = '' )";
                        break;
                    case 'equals':
                        $where = $col . " = '" . $this->db->quote( $c['value'] ) . "'";
                        break;
                    default:
                        $like = str_replace( array( '!', '%', '_' ), array( '!!', '!%', '!_' ), $c['value'] );
                        $where = $col . " LIKE '%" . $this->db->quote( $like ) . "%' ESCAPE '!'";
                }
                break;

            case 'interest':
                $where = ( $c['picked'] ? 'EXISTS' : 'NOT EXISTS' )
                    . ' ( SELECT 1 FROM cjwnl_user_interest ui'
                    . ' INNER JOIN cjwnl_interest i ON i.id = ui.interest_id'
                    . ' WHERE ui.newsletter_user_id = s.newsletter_user_id'
                    . ' AND ui.interest_id = ' . (int)$c['interest_id']
                    . ' AND i.list_contentobject_id = ' . $listId . ' )';
                break;
        }

        $sql = 'SELECT s.id FROM cjwnl_subscription s'
            . ' INNER JOIN cjwnl_user u ON u.id = s.newsletter_user_id'
            . ' WHERE s.list_contentobject_id = ' . $listId
            . ' AND s.status = ' . self::STATUS_APPROVED
            . ' AND ' . $where;

        $ids = array();
        foreach ( $this->db->rows( $sql ) as $row )
        {
            $ids[] = (int)$row['id'];
        }
        return $ids;
    }

    private function quoteList( array $values )
    {
        $quoted = array();
        foreach ( $values as $value )
        {
            $quoted[] = "'" . $this->db->quote( $value ) . "'";
        }
        return implode( ', ', $quoted );
    }
}
