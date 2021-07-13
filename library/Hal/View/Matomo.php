<?php

namespace Hal\View;

/**
 * Class Matomo
 * @package Hal\View
 */

class Matomo implements Tracker
{
    /**
     * @param \Hal_View $url
     * @param \Hal_View $piwikId
     */

    protected $url;
    protected $piwikId;

    public function __construct($url, $piwikId){

        $this->url = $url;
        $this->piwikId = $piwikId;
    }

    public function addHeader()
    {
        // TODO: Implement addHeader() method.
    }

    public function getHeader(): string {
        // TODO: Implement getHeader() method.
        $config = \Hal\Config::getInstance();
        $domain    = $config->getOption('tarteaucitron.domain');
        $matomo = $config->getOption('matomo.enable');

        if (!$matomo) {
            return "";
        }

        if ($domain) {
            // Tarteaucitron enable
            $script = <<< EOV
        <script>
        tarteaucitron.user.matomoId = $this->piwikId;
        tarteaucitron.user.matomoHost = "$this->url";
        (tarteaucitron.job = tarteaucitron.job || []).push('matomo');
        </script>
EOV;
        } else {
            // No RGPD Anti tracker: just piwik code

            $script = <<< EOV
        <script type="text/javascript">
        var pkBaseURL = (("https:" == document.location.protocol) ? "https://piwik-hal.ccsd.cnrs.fr/" : "http://piwik-hal.ccsd.cnrs.fr/");
        document.write(decodeURI("%3Cscript src=\'" + pkBaseURL + "piwik.js\' type=\'text/javascript\'%3E%3C/script%3E"));

        try {
            var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", $this->piwikId);
            piwikTracker.trackPageView();
            piwikTracker.enableLinkTracking();
            //Archive HAL

            piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 92);
            piwikTracker.trackPageView();
            piwikTracker.enableLinkTracking();
        } catch( err ) {}
        </script><noscript><p><img src="https://piwik-hal.ccsd.cnrs.fr/piwik.php?idsite=<?php echo $this->piwikId ?>" style="border:0" alt="" /></p></noscript>
EOV;
        }
        return($script);
    }

    public function followHeader(): bool
    {
        // TODO: Implement followHeader() method.
    }
}