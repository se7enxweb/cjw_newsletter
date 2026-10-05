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

    // ------------------------------------------------------------------ 4.2.0: the part of the category's row

    /**
     * The newsletter's own choices on the preference page: the language of the newsletters and the interests of the
     * lists, and the mail-in addresses of the lists for information.
     *
     * @return string|null
     */
    public function partTemplate( expMailRecipient $recipient, expMailCategory $category, $mode )
    {
        if ( !class_exists( 'CjwNewsletterRendering' ) )
            return null;
        $variables = $this->partVariables( $recipient, $category, $mode );
        return $variables['lists'] ? 'design:mailpreferences/category/newsletter.tpl' : null;
    }

    /**
     * @return array hash( key, lists: hash( id, name, status, active, interests: hash( id, name, checked ), mailin ),
     *               languages: locale => name, language, has_interests, configure_url )
     */
    public function partVariables( expMailRecipient $recipient, expMailCategory $category, $mode )
    {
        $out = array( 'key' => $category->identifier, 'lists' => array(), 'languages' => array(), 'language' => '',
                      'has_interests' => false, 'configure_url' => '' );
        $newsletterUser = self::newsletterUser( $recipient );
        if ( !is_object( $newsletterUser ) )
            return $out;
        $out['language'] = (string)$newsletterUser->attribute( 'language' );
        $url = 'newsletter/configure/' . $newsletterUser->attribute( 'hash' );
        eZURI::transformURI( $url, false, 'full' );
        $out['configure_url'] = $url;
        $names = CjwNewsletterRendering::contentLanguages();
        $picked = CjwNewsletterInterests::idsForUser( $newsletterUser->attribute( 'id' ) );
        $active = array( CjwNewsletterSubscription::STATUS_CONFIRMED, CjwNewsletterSubscription::STATUS_APPROVED, CjwNewsletterSubscription::STATUS_PENDING );
        foreach ( (array)CjwNewsletterSubscription::fetchSubscriptionListByNewsletterUserId( $newsletterUser->attribute( 'id' ) ) as $subscription )
        {
            if ( !in_array( (int)$subscription->attribute( 'status' ), $active, true ) )
                continue;
            $listId = (int)$subscription->attribute( 'list_contentobject_id' );
            $list = CjwNewsletterList::fetchByListObjectVersion( $listId, 0 );
            if ( !is_object( $list ) )
                continue;
            $interests = array();
            foreach ( CjwNewsletterInterests::forList( $list ) as $interest )
                $interests[] = array( 'id' => (int)$interest->attribute( 'id' ), 'name' => (string)$interest->attribute( 'name' ),
                                      'checked' => in_array( (int)$interest->attribute( 'id' ), $picked, true ) );
            $out['has_interests'] = $out['has_interests'] || (bool)$interests;
            foreach ( CjwNewsletterRendering::listLanguages( $list ) as $locale )
                $out['languages'][$locale] = isset( $names[$locale] ) ? $names[$locale] : $locale;
            $mailin = array();
            if ( class_exists( 'CjwNewsletterMailinAddress' ) )
                foreach ( (array)CjwNewsletterMailinAddress::fetchListByListContentobjectId( $listId ) as $address )
                    if ( (int)$address->attribute( 'is_active' ) === 1 )
                        $mailin[] = array( 'email' => (string)$address->attribute( 'email' ), 'action' => (string)$address->attribute( 'action' ) );
            $out['lists'][] = array( 'id' => $listId, 'name' => CjwNewsletterMailPreferences::listName( $subscription ),
                                     'status' => (string)$subscription->attribute( 'status_string' ),
                                     'active' => (int)$subscription->attribute( 'status' ) !== CjwNewsletterSubscription::STATUS_PENDING,
                                     'interests' => $interests, 'mailin' => $mailin,
                                     'main_language' => CjwNewsletterRendering::mainLanguage( $list ) );
        }
        return $out;
    }

    /**
     * Stores the language and the interests of the part (POST MailPreferencePart[<category>][Language],
     * [Interest][]). Only what the page offered is taken.
     *
     * @return array error strings, 'changed' => int
     */
    public function storePart( expMailRecipient $recipient, expMailCategory $category, eZHTTPTool $http, expConsentContext $context )
    {
        $posted = $http->hasPostVariable( 'MailPreferencePart' ) ? $http->postVariable( 'MailPreferencePart' ) : array();
        $mine = is_array( $posted ) && isset( $posted[$category->identifier] ) && is_array( $posted[$category->identifier] ) ? $posted[$category->identifier] : null;
        if ( $mine === null || empty( $mine['PartShown'] ) || !class_exists( 'CjwNewsletterRendering' ) )
            return array();
        $newsletterUser = self::newsletterUser( $recipient );
        if ( !is_object( $newsletterUser ) )
            return array();
        $offer = $this->partVariables( $recipient, $category, 'store' );
        $changed = 0;
        $errors = array();
        if ( isset( $mine['Language'] ) && is_string( $mine['Language'] ) )
        {
            $language = trim( $mine['Language'] );
            if ( $language !== '' && !isset( $offer['languages'][$language] ) )
                $errors[] = ezpI18n::tr( 'cjw_newsletter/rendering', 'This language is not offered.' );
            else if ( $language !== (string)$newsletterUser->attribute( 'language' ) )
            {
                $newsletterUser->setAttribute( 'language', $language );
                $newsletterUser->store();
                $changed++;
            }
        }
        $offered = array();
        foreach ( $offer['lists'] as $list )
            foreach ( $list['interests'] as $interest )
                $offered[] = (int)$interest['id'];
        if ( $offered )
        {
            $wanted = isset( $mine['Interest'] ) && is_array( $mine['Interest'] ) ? array_map( 'intval', $mine['Interest'] ) : array();
            $changed += CjwNewsletterInterests::setForUser( $newsletterUser->attribute( 'id' ), $wanted, array_values( array_unique( $offered ) ) );
        }
        $errors['changed'] = $changed;
        return $errors;
    }

    /**
     * The person was erased (expMailPreferences::erase(): "delete my data", or the removal of the account): the
     * newsletter user goes, with his subscriptions, his interests and his language, and through the extension point
     * userRemoved what the feature areas keep of him (the SMS channel's codes and messages). A blacklist entry
     * stays without the user, as the kernel's suppression list stays: it must keep blocking the address. The
     * per-person statistics go through the statistics category's own erased().
     */
    public function erased( expMailRecipient $recipient, expConsentContext $context )
    {
        $newsletterUser = self::newsletterUser( $recipient );
        if ( !is_object( $newsletterUser ) )
            return;
        if ( class_exists( 'CjwNewsletterInterests' ) )
            CjwNewsletterInterests::removeForUser( $newsletterUser->attribute( 'id' ) );
        $newsletterUser->remove();
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
