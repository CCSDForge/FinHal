<?php

namespace Hal\View;

use phpDocumentor\Reflection\Types\Void_;

/**
 * Class Atinternet
 * @package Hal\View
 */

class Atinternet implements \Hal\View\Tracker
{
    /**
     * @param \Hal_View $view
     * @param \Hal_View $controller
     * @param \Hal_View $action
     * @param \Hal_View $tag_name
     * @param \Hal_View $documentid
     */
    protected $view;
    protected $controller;
    protected $action;
    protected $tag_name;
    protected $documentid;

    public function __construct($view, $controller, $action, $tag_name, $documentid) {

        $this->view = $view;
        $this->controller = $controller;
        $this->action = $action;
        $this->tag_name = $tag_name;
        $this->documentid = $documentid;
    }
    public function addHeader() {
        // TODO: Implement addHeader() method.
        $config = \Hal\Config::getInstance();
        $domain = $config->getOption('tarteaucitron.domain');
        $atinternet = $config->getOption('atinternet.enable');
        $url = "//tag.aticdn.net/606517/smarttag.js";
        if ($atinternet && !$domain) {
            // ATInternet Enable
            $this->view->headScript()->appendFile($url);
        } else {
            // No ATInternet Tracker
        }
    }

    public function getHeader(): string {
        // TODO: Implement getHeader() method.
        $config = \Hal\Config::getInstance();
        $domain = $config->getOption('tarteaucitron.domain');
        $atinternet = $config->getOption('atinternet.enable');

        if ('' != $this->tag_name) {$formatpagename = "Nom de la page: %1\$s ";} else {$formatpagename ="";}
        if ('' != $this->documentid) {$formatdocumentid = "Document ID: %2\$s";} else {$formatdocumentid ="";}
        $formatpage = $formatpagename . $formatdocumentid;
        $pagename = sprintf($formatpage, $this->tag_name, $this->documentid);

        $scripttarteau = <<< EOV
        <script>
        tarteaucitron.user.atLibUrl = '//tag.aticdn.net/606517/smarttag.js';
        tarteaucitron.user.atMore = function () { /* add here your optionnal ATInternet.Tracker.Tag configuration */ };
        (tarteaucitron.job = tarteaucitron.job || []).push('atinternet');
        </script>
EOV;
        if (self::followHeader()) {
            $scriptatinternet = <<< EOV
        <script type='text/javascript'>
            var haltag = new ATInternet.Tracker.Tag();
            var pagename = '$pagename';
            haltag.page.set({name:pagename});
            haltag.dispatch();
        </script>
        <script type='text/javascript'>
            var fdownload = new ATInternet.Tracker.Tag();
            var callback = function() {console.log('executed-file');};
            fdownload.clickListener.send({
                elem: document.getElementById('filedownload'),
                name: '$this->documentid',
                level2: 'clickLvl2',
                type: 'action',
                callback: callback
            });
        </script>
        <script type='text/javascript'> 
            var idownload = new ATInternet.Tracker.Tag();
            var callback = function() {console.log('executed-imagette');};
            idownload.clickListener.send({
                elem: document.getElementById('imagedownload'),
                name: '$this->documentid',
                level2: 'clickLvl2',
                type: 'action',
                callback: callback
            });
        </script>
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

    public function followHeader(): bool {
        // TODO: Implement followHeader() method.
        $filenamesuivi = SPACE . CONFIG . 'atinternet.suivi.json';
        if (file_exists($filenamesuivi)) {
            $listesuivi = json_decode(file_get_contents($filenamesuivi), true);
            if (is_array($listesuivi)) {
                foreach ($listesuivi as $listesui) {
                    if ($listesui['controller'] == $this->controller AND $listesui['action'] == $this->action AND $listesui['follow'] == 'yes')
                    { return true; }
                }
            }
        }
        return false;
    }
}
