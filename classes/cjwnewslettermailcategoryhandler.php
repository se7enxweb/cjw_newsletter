<?php
/**
 * File containing the CjwNewsletterMailCategoryHandler class
 *
 * The handler of the category "newsletter" of the e-mail preferences (Exponential 6.0.15 and later;
 * settings/mailpreferences.ini.append.php of this extension names it). It reads the newsletter subscriptions as
 * the state of the category, so nothing is migrated, and lists them on the preference page:
 *
 *  - a person with a confirmed or approved subscription, who has made no choice on the preference page, is "on";
 *  - switching the category off on the preference page, or the master switch, leaves the subscriptions as they
 *    are ("dormant"): the mail gate does not send the editions, and they come back when it is switched on again.
 *
 * The class is only loaded through that setting, which only installations with the e-mail preferences read.
 *
 * @copyright Copyright (C) 2007-2026 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage classes
 */
class CjwNewsletterMailCategoryHandler implements expMailCategoryHandler
{
    public function stateFor( expMailRecipient $recipient, expMailCategory $category )
    {
        $newsletterUser = self::newsletterUser( $recipient );
        if ( !is_object( $newsletterUser ) )
            return null;
        $status = (int)$newsletterUser->attribute( 'status' );
        if ( in_array( $status, array( CjwNewsletterUser::STATUS_BLACKLISTED, CjwNewsletterUser::STATUS_BOUNCED_HARD,
                                       CjwNewsletterUser::STATUS_REMOVED_SELF, CjwNewsletterUser::STATUS_REMOVED_ADMIN ), true ) )
            return null;
        return CjwNewsletterMailPreferences::activeSubscriptionCount( $newsletterUser->attribute( 'id' ) ) > 0 ? true : null;
    }

    public function frequencyFor( expMailRecipient $recipient, expMailCategory $category )
    {
        return null; // every list keeps its own schedule
    }

    /**
     * The subscriptions stay as they are: the category switched off makes them dormant, it does not remove them.
     */
    public function changed( expMailRecipient $recipient, expMailCategory $category, $state, expConsentContext $context )
    {
    }

    /**
     * The lists of the person, for the preference page.
     *
     * @return array[] hash( name, status, active, url )
     */
    public function subscriptions( expMailRecipient $recipient, expMailCategory $category )
    {
        $newsletterUser = self::newsletterUser( $recipient );
        if ( !is_object( $newsletterUser ) )
            return array();
        $out = array();
        $active = array( CjwNewsletterSubscription::STATUS_CONFIRMED, CjwNewsletterSubscription::STATUS_APPROVED );
        $url = 'newsletter/configure/' . $newsletterUser->attribute( 'hash' );
        eZURI::transformURI( $url, false, 'full' );
        foreach ( (array)CjwNewsletterSubscription::fetchSubscriptionListByNewsletterUserId( $newsletterUser->attribute( 'id' ) ) as $subscription )
        {
            $out[] = array( 'name' => CjwNewsletterMailPreferences::listName( $subscription ),
                            'status' => (string)$subscription->attribute( 'status_string' ),
                            'active' => in_array( (int)$subscription->attribute( 'status' ), $active, true ),
                            'url' => $url );
        }
        return $out;
    }

    /**
     * @param expMailRecipient $recipient
     * @return CjwNewsletterUser|false
     */
    protected static function newsletterUser( expMailRecipient $recipient )
    {
        $newsletterUser = false;
        if ( $recipient->userId() > 0 )
            $newsletterUser = CjwNewsletterUser::fetchByEzUserId( $recipient->userId() );
        if ( !is_object( $newsletterUser ) && $recipient->email() !== '' )
            $newsletterUser = CjwNewsletterUser::fetchByEmail( $recipient->email() );
        return $newsletterUser;
    }
}

?>
