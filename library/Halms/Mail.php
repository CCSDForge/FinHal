<?php

class Halms_Mail extends Ccsd_Mail
{
    public function __construct($charset = 'UTF-8') {
        if (isset ( $charset )) {
            parent::__construct ( $charset );
        }
        $this->setPath ( CCSD_MAIL_PATH );

        $this->setFrom ( HALMS_MAIL, HALMS_USERNAME );
        $this->setReplyTo ( $this->getFrom () );
        $this->setReturnPath ( $this->getFrom () );
        $this->addHeader ( 'X-Mailer', HALMS_USERNAME );
    }
}