<?php
/**
 * File containing the CjwNewsletterGateTransport class
 *
 * The transport the mail gate of the e-mail preferences hands a newsletter mail to: it sends the wrapped ezcMail
 * with the newsletter's own transport (SMTP, sendmail or file, cjw_newsletter.ini) and keeps its answer, which is
 * true or the Exception of the transport.
 *
 * @copyright Copyright (C) 2007-2026 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package cjw_newsletter
 * @subpackage classes
 */
class CjwNewsletterGateTransport extends eZMailTransport
{
    /** @var CjwNewsletterTransport */
    protected $transport;

    /** @var true|Exception|null the answer of the last send */
    public $lastResult = null;

    /**
     * @param CjwNewsletterTransport $transport
     */
    public function __construct( CjwNewsletterTransport $transport )
    {
        $this->transport = $transport;
    }

    /**
     * @param eZMail $mail an eZMail whose Mail is the built newsletter mail
     * @return bool
     */
    function sendMail( eZMail $mail )
    {
        $this->lastResult = $this->transport->send( $mail->Mail );
        return $this->lastResult === true;
    }
}

?>
