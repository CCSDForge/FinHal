<?php

/**
 * @see ZendX_JQuery
 */
require_once 'ZendX/JQuery.php';

/**
 * jQuery View Helper. Transports all jQuery stack and render information across all views.
 * CCSD : modifié pour ajouter un numéro de version à la fin de l'url pour éviter les problèmes de cache
 */
class Hal_View_Helper_JQuery_Container extends ZendX_JQuery_View_Helper_JQuery_Container
{


    /**
     * Render jQuery stylesheets
     *
     * @return string
     */
    protected function _renderStylesheets()
    {
        if (0 == ($this->getRenderMode() & ZendX_JQuery::RENDER_STYLESHEETS)) {
            return '';
        }

        foreach ($this->getStylesheets() as $stylesheet) {
            $stylesheets[] = $stylesheet;
        }

        if (empty($stylesheets)) {
            return '';
        }

        array_reverse($stylesheets);
        $style = '';
        foreach ($stylesheets AS $stylesheet) {

            $stylesheet = self::addApplicationVersionToUrl($stylesheet);

            if ($this->view instanceof Zend_View_Abstract) {
                $closingBracket = ($this->view->doctype()->isXhtml()) ? ' />' : '>';
            } else {
                $closingBracket = ' />';
            }

            $style .= '<link rel="stylesheet" href="' . $stylesheet . '" ' .
                'type="text/css" media="screen"' . $closingBracket . PHP_EOL;
        }

        return $style;
    }


    /**
     * Add Application version at the end of a URL
     * @param string $url
     * @return string $url with app version
     */
    protected static function addApplicationVersionToUrl($url)
    {
        $separator = '?';

        // check if url has parameters
        $urlParsed = parse_url($url, PHP_URL_QUERY);
        if ((isset($urlParsed)) && ($urlParsed != null)) {
            $separator = '&amp;';
        }
        return $url . $separator . Hal_Settings::getApplicationVersion();
    }


    /**
     * Renders all javascript file related stuff of the jQuery enviroment.
     *
     * @return string
     */
    protected function _renderScriptTags()
    {
        $scriptTags = '';
        if (($this->getRenderMode() & ZendX_JQuery::RENDER_LIBRARY) > 0) {
            $source = $this->_getJQueryLibraryPath();
            $source = $this->addApplicationVersionToUrl($source);

            $scriptTags .= '<script type="text/javascript" src="' . $source . '"></script>' . PHP_EOL;

            if ($this->uiIsEnabled()) {
                $uiPath = $this->_getJQueryUiLibraryPath();
                $uiPath = $this->addApplicationVersionToUrl($uiPath);
                $scriptTags .= '<script type="text/javascript" src="' . $uiPath . '"></script>' . PHP_EOL;
            }

            if (ZendX_JQuery_View_Helper_JQuery::getNoConflictMode() == true) {
                $scriptTags .= '<script type="text/javascript">var $j = jQuery.noConflict();</script>' . PHP_EOL;
            }
        }

        if (($this->getRenderMode() & ZendX_JQuery::RENDER_SOURCES) > 0) {
            foreach ($this->getJavascriptFiles() AS $javascriptFile) {
                $javascriptFile = $this->addApplicationVersionToUrl($javascriptFile);
                $scriptTags .= '<script type="text/javascript" src="' . $javascriptFile . '"></script>' . PHP_EOL;
            }
        }

        return $scriptTags;
    }


}
