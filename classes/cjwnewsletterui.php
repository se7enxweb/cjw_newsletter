<?php
//
// Copyright (C) 1998 - 2026 7x & Exponential Foundation. All rights reserved.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 (or any later version).
//

/*!
  \class CjwNewsletterUI cjwnewsletterui.php
  \brief Helpers shared by the module views: flash notices, list parameters (paging, sorting, filter) and
         the table state.
*/

class CjwNewsletterUI
{
    const NOTICE_SESSION_KEY = 'CjwNewsletterNotices';

    /*!
     \static
     Remember a message for the next page shown to this user.

     \param $type 'feedback', 'warning' or 'error' (the admin message classes)
    */
    static function notice( $type, $text )
    {
        $http = eZHTTPTool::instance();
        $list = $http->hasSessionVariable( self::NOTICE_SESSION_KEY ) ? $http->sessionVariable( self::NOTICE_SESSION_KEY ) : array();
        if ( !is_array( $list ) )
        {
            $list = array();
        }
        $list[] = array( 'type' => in_array( $type, array( 'feedback', 'warning', 'error' ), true ) ? $type : 'feedback',
                         'text' => (string)$text );
        $http->setSessionVariable( self::NOTICE_SESSION_KEY, $list );
    }

    /*!
     \static
     \return the remembered messages, which are forgotten at the same time
    */
    static function takeNotices()
    {
        $http = eZHTTPTool::instance();
        if ( !$http->hasSessionVariable( self::NOTICE_SESSION_KEY ) )
        {
            return array();
        }
        $list = $http->sessionVariable( self::NOTICE_SESSION_KEY );
        $http->removeSessionVariable( self::NOTICE_SESSION_KEY );
        return is_array( $list ) ? $list : array();
    }

    /*!
     \static
     The parameters of a list view: (offset), (q), (sort), (order). Unknown or malformed values fall back
     to the defaults.

     \param $params the view's $Params array
     \param $sortFields the sort keys the view offers, the first one is the default
     \return array( 'offset', 'limit', 'q', 'sort', 'order' )
    */
    static function listParameters( $params, $sortFields, $limit = 15 )
    {
        $user = isset( $params['UserParameters'] ) && is_array( $params['UserParameters'] ) ? $params['UserParameters'] : array();
        $offset = isset( $params['Offset'] ) ? (int)$params['Offset'] : ( isset( $user['offset'] ) ? (int)$user['offset'] : 0 );
        $sort = isset( $user['sort'] ) && in_array( $user['sort'], $sortFields, true ) ? $user['sort'] : $sortFields[0];
        $order = isset( $user['order'] ) && $user['order'] === 'desc' ? 'desc' : 'asc';
        $q = isset( $user['q'] ) ? trim( rawurldecode( (string)$user['q'] ) ) : '';
        return array( 'offset' => max( 0, $offset ), 'limit' => (int)$limit, 'q' => mb_substr( $q, 0, 100 ),
                      'sort' => $sort, 'order' => $order );
    }

    /*!
     \static
     Filter, sort and cut a list of objects. The object attributes are read with attribute().

     \param $items array of objects
     \param $vp the result of listParameters()
     \param $searchFields attribute names the filter text is looked for in
     \param $total receives the number of items after the filter
     \return the items of the requested page
    */
    static function page( $items, $vp, $searchFields, &$total )
    {
        $items = is_array( $items ) ? $items : array();
        if ( $vp['q'] !== '' )
        {
            $needle = mb_strtolower( $vp['q'] );
            $items = array_values( array_filter( $items, function ( $item ) use ( $needle, $searchFields )
            {
                foreach ( $searchFields as $field )
                {
                    if ( mb_strpos( mb_strtolower( (string)$item->attribute( $field ) ), $needle ) !== false )
                    {
                        return true;
                    }
                }
                return false;
            } ) );
        }
        $sort = $vp['sort'];
        $desc = $vp['order'] === 'desc';
        usort( $items, function ( $a, $b ) use ( $sort, $desc )
        {
            $x = $a->attribute( $sort );
            $y = $b->attribute( $sort );
            $r = ( is_numeric( $x ) && is_numeric( $y ) ) ? ( $x <=> $y ) : strnatcasecmp( (string)$x, (string)$y );
            return $desc ? -$r : $r;
        } );
        $total = count( $items );
        return array_slice( $items, $vp['offset'], $vp['limit'] );
    }

    /*!
     \static
     The URL part for a list view with its parameters, for example 'newsletter/list/(q)/news/(sort)/name'.
    */
    static function listURL( $view, $vp, $override = array() )
    {
        $vp = array_merge( $vp, $override );
        $url = 'newsletter/' . $view;
        if ( $vp['offset'] )
        {
            $url .= '/(offset)/' . (int)$vp['offset'];
        }
        if ( $vp['q'] !== '' )
        {
            $url .= '/(q)/' . rawurlencode( $vp['q'] );
        }
        $url .= '/(sort)/' . $vp['sort'] . '/(order)/' . $vp['order'];
        return $url;
    }

    /*!
     \static
     The SQL condition of a text filter: a case insensitive "contains" on each of the columns, joined by OR. The text is
     escaped for SQL and for LIKE ( ! is the escape character, the same on every database ).

     \param $q the text, an empty text gives an empty condition
     \param $columns array of qualified column names, for example array( 'cjwnl_user.email' )
     \return the condition in parentheses, '' for an empty text
    */
    static function searchCondition( $q, $columns )
    {
        $q = trim( (string)$q );
        if ( $q === '' )
        {
            return '';
        }
        $db = eZDB::instance();
        $like = "'%" . $db->escapeString( str_replace( array( '!', '%', '_' ), array( '!!', '!%', '!_' ), mb_strtolower( $q ) ) ) . "%'";
        $parts = array();
        foreach ( $columns as $column )
        {
            $parts[] = 'LOWER( ' . $column . ' ) LIKE ' . $like . " ESCAPE '!'";
        }
        return '( ' . implode( ' OR ', $parts ) . ' )';
    }

    /*!
     \static
     The COUNT of a table with custom conditions through eZPersistentObject, which works on every database.
    */
    static function countRows( $definition, $table, $where )
    {
        $rows = eZPersistentObject::fetchObjectList( $definition, array(), array( $definition['keys'][0] => array( '>', 0 ) ), null, null, false, false,
                                                     array( array( 'operation' => 'COUNT( ' . $table . '.' . $definition['keys'][0] . ' )', 'name' => 'row_count' ) ),
                                                     null, $where !== '' ? ' AND ' . $where : null );
        return $rows ? (int)$rows[0]['row_count'] : 0;
    }
}

?>
