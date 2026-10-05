<?php
/**
 * File containing the CjwNewsletterHandler class
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

/**
 * The newsletter card of the notification settings pages (notification/settings, cjw_newsletter 4.2.0): the
 * newsletters of the signed-in user, their language and interests, and a link to the e-mail preference page. The
 * page renders it from design:notification/handler/cjwnewsletter/settings/edit.tpl, which lists the parts of
 * cjw_newsletter.ini [NotificationCardSettings] Parts[].
 *
 * It sends nothing: handle() skips every event. The language and the interests are stored the same way as on the
 * preference page (CjwNewsletterMailCategoryHandler::storePart(), POST MailPreferencePart[newsletter][...]).
 *
 * @package cjw_newsletter
 */
class CjwNewsletterHandler extends eZNotificationEventHandler
{
    const NOTIFICATION_HANDLER_ID = 'cjwnewsletter';

    /** @var array|null the card of the request */
    protected $card = null;

    public function __construct()
    {
        parent::__construct( self::NOTIFICATION_HANDLER_ID, ezpI18n::tr( 'cjw_newsletter/rendering', 'Newsletters' ) );
    }

    function attributes()
    {
        return array_merge( array( 'card', 'parts' ), parent::attributes() );
    }

    function hasAttribute( $attr )
    {
        return in_array( $attr, $this->attributes() );
    }

    function attribute( $attr )
    {
        if ( $attr === 'card' )
            return $this->card();
        if ( $attr === 'parts' )
            return self::parts();
        return parent::attribute( $attr );
    }

    /** No notification of this handler is ever sent. */
    function handle( $event )
    {
        return eZNotificationEventHandler::EVENT_SKIPPED;
    }

    /** @return string[] the card's part templates ([NotificationCardSettings] Parts[]) */
    static function parts()
    {
        $ini = eZINI::instance( 'cjw_newsletter.ini' );
        $out = array();
        if ( $ini->hasVariable( 'NotificationCardSettings', 'Parts' ) )
            foreach ( (array)$ini->variable( 'NotificationCardSettings', 'Parts' ) as $name )
                if ( preg_match( '#^design:[a-z0-9_/]+\.tpl$#', (string)$name ) && !in_array( $name, $out, true ) )
                    $out[] = $name;
        return $out;
    }

    /**
     * @return array hash( available, newsletter_user, part (the preference page part's variables), category_on,
     *               preferences_url, subscribe_url )
     */
    function card()
    {
        if ( $this->card !== null )
            return $this->card;
        $card = array( 'available' => false, 'newsletter_user' => false, 'part' => array( 'lists' => array() ),
                       'category_on' => null, 'preferences_url' => 'mailpreferences/settings', 'subscribe_url' => 'newsletter/subscribe' );
        $user = eZUser::currentUser();
        if ( !$user || !$user->isRegistered() || !class_exists( 'CjwNewsletterMailCategoryHandler' ) || !class_exists( 'expMailRecipient' ) )
            return $this->card = $card;
        try
        {
            $recipient = expMailRecipient::fromUser( $user );
            $category = self::category();
            $newsletterUser = CjwNewsletterUser::fetchByEzUserId( $user->attribute( 'contentobject_id' ) );
            if ( !is_object( $newsletterUser ) )
                $newsletterUser = CjwNewsletterUser::fetchByEmail( $user->attribute( 'email' ) );
            $card['available'] = true;
            $card['newsletter_user'] = is_object( $newsletterUser ) ? $newsletterUser : false;
            if ( $category )
            {
                $handler = new CjwNewsletterMailCategoryHandler();
                $card['part'] = $handler->partVariables( $recipient, $category, 'account' );
                $prefs = expMailPreferences::forRecipient( $recipient );
                $card['category_on'] = $prefs->masterOn() && $prefs->state( $category->identifier ) !== expMailPreferences::OFF;
            }
        }
        catch ( Throwable $e )
        {
            eZDebug::writeError( $e->getMessage(), __METHOD__ );
        }
        return $this->card = $card;
    }

    /** @return expMailCategory|null the category of the newsletters (the one with this extension's handler) */
    static function category()
    {
        if ( !class_exists( 'expMailCategoryRegistry' ) )
            return null;
        foreach ( expMailCategoryRegistry::instance()->all() as $category )
        {
            $handler = $category->handler();
            if ( $handler instanceof CjwNewsletterMailCategoryHandler )
                return $category;
        }
        return null;
    }

    /** The card's form: the language and the interests, as on the preference page. */
    function storeSettings( $http, $module )
    {
        $user = eZUser::currentUser();
        $category = self::category();
        if ( !$user || !$user->isRegistered() || !$category || !$http->hasPostVariable( 'MailPreferencePart' ) )
            return true;
        $handler = new CjwNewsletterMailCategoryHandler();
        $result = $handler->storePart( expMailRecipient::fromUser( $user ), $category, $http,
                                       expConsentContext::fromRequest( 'page', ezpI18n::tr( 'cjw_newsletter/rendering', 'Newsletters' ) ) );
        foreach ( $result as $key => $error )
            if ( $key !== 'changed' && is_string( $error ) )
                eZDebug::writeWarning( $error, __METHOD__ );
        $this->card = null;
        return true;
    }
}

?>
