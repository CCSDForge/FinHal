<?php

namespace Hal\View;

/**
 * Class Tarteaucitron
 * @package Hal\View
 */

class Tarteaucitron implements Tracker
{
    /**
     * @param \Hal_View $view
     */
    protected $view;

    public function __construct($view) {

        $this->view = $view;
    }

    public function addHeader()
    {
        // TODO: Implement addHeader() method.
        $config = \Hal\Config::getInstance();
        $domain = $config->getOption('tarteaucitron.domain');
        $url = $config->getOption('tarteaucitron.scriptUrl', CCSDLIB . "/js/tarteaucitron/tarteaucitron.js");
        $highPrivacy = $config->getOption('tarteaucitron.highPrivacy', 'false');
        $privacyUrl= $config->getOption('tarteaucitron.privacyUrl', '');

        if ($domain) {
            // Tarteaucitron Enable
            $this->view->headScript()->appendFile($url);
            $script = <<< EOV
        tarteaucitron.init({
    	  "privacyUrl": "$privacyUrl", /* Privacy policy url */

    	  "hashtag": "#tarteaucitron", /* Open the panel with this hashtag */
    	  "cookieName": "tarteaucitron", /* Cookie name */

    	  "orientation": "bottom", /* Banner position (top - bottom) */
    	  "showAlertSmall": true, /* Show the small banner on bottom right */
    	  "cookieslist": true, /* Show the cookie list */

    	  "adblocker": false, /* Show a Warning if an adblocker is detected */
    	  "AcceptAllCta" : true, /* Show the accept all button when highPrivacy on */
    	  "highPrivacy": $highPrivacy, /* Disable auto consent */
    	  "handleBrowserDNTRequest": false, /* If Do Not Track == 1, disallow all */

    	  "removeCredit": false, /* Remove credit link */
    	  "moreInfoLink": true, /* Show more info link */
    	  "useExternalCss": false, /* If false, the tarteaucitron.css file will be loaded */

    	  "cookieDomain": "$domain", /* Shared cookie for multisite */

    	  "readmoreLink": "/cookiespolicy" /* Change the default readmore link */
        });
EOV;
            $this->view->headScript()->appendScript($script);
        } else {
            // No RGPD Anti Tracker
        }
    }

    public function getHeader(): string
    {
        // TODO: Implement getHeader() method.
    }

    public function followHeader(): bool
    {
        // TODO: Implement followHeader() method.
    }
}