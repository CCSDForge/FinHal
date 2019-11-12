<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 06/07/17
 * Time: 16:11
 */

/** Inspire d'arxiv et porte pour SWH
 * BM: Devrait etre pour chaque transfert avec tracking info
 *
 *  Arxiv
 * <deposit>
 * <submission_id>2095318</submission_id>
 * <tracking_id>https://arxiv.org/resolve/app/17120135</tracking_id>
 * <arxiv_id>1712.07490</arxiv_id>
 * <status/></deposit>
 *
 * SWH
 * <entry>
 * <deposit_id>63</deposit_id>
 * <deposit_status>ready-for-checks</deposit_status>
 * <deposit_status_detail>Deposit is ready for additional checks (tarball ok, etc...)</deposit_status_detail>
 * </entry>
 *
 */
class Hal_Transfert_TrackingInfo {
    /**
     * @var string //  submission Identifier
     */
    private $submissionId;

    /** @var string //  tracking Url */
    private $trackingUrl;
    
    /** @var string //  submission status */
    private $status;

    /** @var string //  submission status */
    private $remoteId;

    /** @var  string //  response in xml */
    private $xml;

    /** @var string  // error message for Arxiv */
    private $error;

    /** @var string */
    private $comment = '';

    /** @var  Hal_Transfert  Pour avoir acces aux definitions du transporteur */
    private $transfertManager;
    /**
     * Hal_Arxiv_TrackingInfo constructor.
     * @param $xmlString: return of http get on tracking url
     * @param Hal_Transfert $manager
     */
    public function __construct($xmlString, $manager) {
        try {
            $xmlResponse = @ new SimpleXMLElement($xmlString);
        } catch (Exception $e) {
            $xmlResponse = @ new SimpleXMLElement("<entry><status>Exception when retreiving Url</status></entry>");
        }
        $submissionIdTag = $manager -> submissionIdTag;
        $remoteIdTag     = $manager -> remoteIdTag;
        $trackingIdTag   = $manager -> trackingIdTag;
        $statusTag       = $manager -> statusTag;
        $commentTag      = $manager -> commentTag;
        $errorTag        = $manager -> errorTag;

        $this -> submissionId = (string) $xmlResponse -> $submissionIdTag;
        $this -> remoteId     = (string) $xmlResponse -> $remoteIdTag;
        $this -> trackingUrl  = (string) $xmlResponse -> $trackingIdTag;
        $this -> status       = (string) $xmlResponse -> $statusTag;
        $this -> error        = (string) $xmlResponse -> $errorTag;
        $this -> comment      = (string) $xmlResponse -> $commentTag;

        $this -> xml          = $xmlString;

        $this -> transfertManager = $manager;
    }

    /**
     * Constructeur statique
     * @param Hal_Transfert $manager
     * @param $url
     */
    public static function getTrackingInfo($url, $manager) {
        // Fileget content don't work if auth is needed (as for SWH... Use curl
        $Curl = curl_init($url);

        curl_setopt ( $Curl, CURLOPT_USERAGENT, 'Curl' );
        curl_setopt ( $Curl, CURLOPT_URL, $url );
        curl_setopt ( $Curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $Curl, CURLOPT_CONNECTTIMEOUT, 12 );
        curl_setopt ( $Curl, CURLOPT_TIMEOUT, 30 );
        curl_setopt ( $Curl, CURLOPT_USERPWD, $manager->getUser() . ':' . $manager->getPwd() );
        $return = curl_exec($Curl);

        return new self($return, $manager);
    }

    /** Compat with old code */
    public function asXML() {
        return $this -> get_xml();
    }

    /** getter */
    public function get_submissionId() {
        return $this -> submissionId;
    }
    /** getter */
    public function get_trackingUrl() {
        return $this -> trackingUrl ;
    }
    /** getter  get_arxiv_id*/
    public function get_remoteId() {
        return $this -> remoteId ;
    }

    /** get extra info
     * @param string $field
     */
    public function get_extraInfo($field) {
        if ($field != '') {
            try {
                return $this->$field;
            } catch (Exception $e) {
                return '';
            }
        }
        return '';
    }

    /** getter */
    public function get_status() {
        return $this -> status;
    }
    /** getter */
    public function get_xml() {
        return $this -> xml;
    }
    /** getter */
    public function get_error() {
        return $this -> error;
    }
}