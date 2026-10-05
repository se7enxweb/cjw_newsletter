<?php
/**
 * The newsletter's branch of the audit taxonomy, registered in settings/audit.ini.append.php
 * ([AuditEventSettings] Branches[cjw_newsletter]). One event per run: object = the run, after = who started it
 * (cron, console, a login name) and the totals; result failed with the reason when the run had errors.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 */

class cjwNewsletterAuditBranch implements expAuditTaxonomyBranch
{
    public function events()
    {
        return array(
            'system.cjw_newsletter.queue_create'  => array( 'label' => 'Newsletter queue created', 'severity' => 'notice', 'channel' => 'system', 'default' => 'on' ),
            'system.cjw_newsletter.queue_process' => array( 'label' => 'Newsletter mails sent', 'severity' => 'notice', 'channel' => 'system', 'default' => 'on' ),
            'system.cjw_newsletter.mailbox'       => array( 'label' => 'Newsletter mail accounts read', 'severity' => 'notice', 'channel' => 'system', 'default' => 'on' ),
            'system.cjw_newsletter.import'        => array( 'label' => 'Newsletter CSV import', 'severity' => 'notice', 'channel' => 'system', 'default' => 'on' ),
            'system.cjw_newsletter.repair'        => array( 'label' => 'Newsletter orphans removed', 'severity' => 'notice', 'channel' => 'system', 'default' => 'on' )
        );
    }
}

?>
