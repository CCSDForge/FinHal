<?php

namespace Hal\View;

/**
 * Class HeaderJS
 * @package Hal\View
 */
class HeaderJS
{
    /**
     * @param \Hal_View $view
     */
    static public function addHeader($view)
    {
        $config = \Hal\Config::getInstance();
        $atinternet = $config->getOption('atinternet.enable');
        $url = "//tag.aticdn.net/606517/smarttag.js";
        if ($atinternet) {
            // ATInternet Enable
            $view->headScript()->appendFile($url);
        } else {
            // No ATInternet Tracker
        }
    }

    /**
     * @return string
     */
    public static function getHeader($controller, $action, $tag_name, $documentidlayout) {
        $config = \Hal\Config::getInstance();
        $domain    = $config->getOption('tarteaucitron.domain');
        $atinternet = $config->getOption('atinternet.enable');

        if ('' != $tag_name) {$formatpagename = "Nom de la page: %1\$s ";} else {$formatpagename ="";}
        if ('' != $documentidlayout) {$formatdocumentid = "Document ID: %2\$s";} else {$formatdocumentid ="";}
        $formatpage = $formatpagename . $formatdocumentid;
        $pagename = sprintf($formatpage, $tag_name, $documentidlayout);

        $scripttarteau = <<< EOV
        <script>
        tarteaucitron.user.atLibUrl = '//tag.aticdn.net/606517/smarttag.js';
        tarteaucitron.user.atMore = function () { /* add here your optionnal ATInternet.Tracker.Tag configuration */ };
        (tarteaucitron.job = tarteaucitron.job || []).push('atinternet');
        </script>
EOV;
        if (self::followHeader($controller, $action)) {
            $scriptatinternet = <<< EOV
        <script type='text/javascript'>         // Ajoute 28.07.2020.
        var haltag = new ATInternet.Tracker.Tag();
        var pagename = '$pagename'; 
        haltag.page.set({name:pagename}); 
        haltag.dispatch();
        </script>";
EOV;
        } else {
            $scriptatinternet = '';
        }

        if (!$atinternet) {
            return "";
        }

        if ($domain) {
            // Tarteaucitron enable
            $script = $scripttarteau . $scriptatinternet;
        } else {
            // No RGPD Anti tracker: just ATInternet code
            $script = $scriptatinternet;
        }

        return($script);
    }
    /**
     * @return boolean
     */
    private static function followHeader($controller, $action) {

        $filenamesuivi = SPACE . CONFIG . 'atinternet.suivi.json';
        if (file_exists($filenamesuivi)) {
            $listesuivi = json_decode(file_get_contents($filenamesuivi), true);
            if (is_array($listesuivi)) {
                foreach ($listesuivi as $listesui) {
                    if ($listesui['controller'] == $controller AND $listesui['action'] == $action AND $listesui['follow'] == 'yes')
                    { return true; }
                }
            }
        }
        return false;
    }
}
