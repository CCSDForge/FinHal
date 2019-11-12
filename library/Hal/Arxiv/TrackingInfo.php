<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 06/07/17
 * Time: 16:11
 */
class Hal_Arxiv_TrackingInfo {
    /**
     * @var string // Arxiv submission Identifier
     */
    private $submissionId;

    /** @var string // Arxiv tracking Url */
    private $trackingUrl;
    
    /** @var string // Arxiv submission status */
    private $status;

    /** @var string // Arxiv submission status */
    private $remoteId;

    /** @var  string // Arxiv response in xml */
    private $xml;

    /** @var string  // error message for Arxiv */
    private $error;

    /**
     * Hal_Arxiv_TrackingInfo constructor.
     * @param $xmlString: return of http get on tracking url
     */
    public function __construct($xmlString) {
        $arxiv =@ new SimpleXMLElement($xmlString);

        $this -> submissionId = (string) $arxiv -> submission_id;
        $this -> remoteId     = (string) $arxiv -> arxiv_id;
        $this -> trackingUrl  = (string) $arxiv -> tracking_id;
        $this -> status       = (string) $arxiv -> status;
        $this -> error        = (string) $arxiv -> error;
        $this -> xml          = $xmlString;
    }

    /**
     * Constructeur statique
     * @param $url
     * @return Hal_Arxiv_TrackingInfo
     */
    public static function getTrackingInfo($url) {
            $url = ( !preg_match("~^https?://~", $url) ) ? "http://".$url : $url;
            $return = file_get_contents($url);
            return new self($return);
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
    /** getter */
    public function get_remoteid() {
        return $this -> remoteId ;
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