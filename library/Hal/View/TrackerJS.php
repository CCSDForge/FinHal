<?php


namespace Hal\View;

/**
 * Class TrackerJS
 * @package Hal\View
 */
class TrackerJS
{
    /**
     * @param \Hal_View $view
     */
    static public function addRgpdAntiTracker($view)
    {
        $config = \Hal\Config::getInstance();
        $domain = $config->getOption('tarteaucitron.domain');
        $url = $config->getOption('tarteaucitron.scriptUrl', CCSDLIB . "/js/tarteaucitron/tarteaucitron.js");

        if ($domain) {
            // Tarteaucitron Enable
            $view->headScript()->appendFile($url);

            $script = <<< EOV
        tarteaucitron.init({
    	  "privacyUrl": "", /* Privacy policy url */

    	  "hashtag": "#tarteaucitron", /* Open the panel with this hashtag */
    	  "cookieName": "tarteaucitron", /* Cookie name */

    	  "orientation": "top", /* Banner position (top - bottom) */
    	  "showAlertSmall": true, /* Show the small banner on bottom right */
    	  "cookieslist": true, /* Show the cookie list */

    	  "adblocker": false, /* Show a Warning if an adblocker is detected */
    	  "AcceptAllCta" : true, /* Show the accept all button when highPrivacy on */
    	  "highPrivacy": false, /* Disable auto consent */
    	  "handleBrowserDNTRequest": false, /* If Do Not Track == 1, disallow all */

    	  "removeCredit": false, /* Remove credit link */
    	  "moreInfoLink": true, /* Show more info link */
    	  "useExternalCss": false, /* If false, the tarteaucitron.css file will be loaded */

    	  "cookieDomain": "$domain", /* Shared cookie for multisite */

    	  "readmoreLink": "/cookiespolicy" /* Change the default readmore link */
        });
EOV;
            $view->headScript()->appendScript($script);
        } else {
            // No RGPD Anti Tracker
        }
    }
    /**
     * @param string $url
     * @param int $piwikId
     * @return string
     */
    static public function getMatomoTracker($url, $piwikId) {
        $config = \Hal\Config::getInstance();
        $domain    = $config->getOption('tarteaucitron.domain');
        $matomo = $config->getOption('matomo.enable');
        $globalPiwikiId = $config->getOption('matomo.globalPiwikiId');
        if (!$matomo) {
            return "";
        }

        if ($domain) {
            // Tarteaucitron enable
            $script = "<script>";
            $script .= "tarteaucitron.user.matomoId = $piwikId;\n";
            $script .= "tarteaucitron.user.matomoHost = \"$url\";\n";
            $script .= "(tarteaucitron.job = tarteaucitron.job || []).push('matomo');";

            if ($globalPiwikiId) {
                $script .= "tarteaucitron.user.matomoHalId = $globalPiwikiId;\n";
                $script .= "tarteaucitron.user.matomoHalHost = \"$url\";\n";
                $script .= "(tarteaucitron.job = tarteaucitron.job || []).push('matomoHal');\n";
            }
            $script .= "</script>\n";
        } else {
        // No RGPD Anti tracker: just piwik code

            $script = <<< EOV
        <script type="text/javascript">
        var pkBaseURL = (("https:" === document.location.protocol) ? "https://piwik-hal.ccsd.cnrs.fr/" : "http://piwik-hal.ccsd.cnrs.fr/");
        document.write(decodeURI("%3Cscript src=\'" + pkBaseURL + "piwik.js\' type=\'text/javascript\'%3E%3C/script%3E"));

        try {
            var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", $piwikId);
            piwikTracker.trackPageView();
            piwikTracker.enableLinkTracking();
            //Archive HAL

            piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 92);
            piwikTracker.trackPageView();
            piwikTracker.enableLinkTracking();
        } catch( err ) {}
    </script><noscript><p><img src="https://piwik-hal.ccsd.cnrs.fr/piwik.php?idsite=<?php echo $piwikId ?>" style="border:0" alt="" /></p></noscript>
EOV;

        }
        return($script);
    }
    /**
     * @param \Hal_View $view
     */
    static public function getSocialsTracker($view) {

    }
}