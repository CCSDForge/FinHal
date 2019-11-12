<?php

class Hal_View_Helper_Widget extends Zend_View_Helper_Abstract
{
    const TYPE_NEWS     =   'news';
    const TYPE_FEED     =   'feed';
    const TYPE_COUNT    =   'count';
    const TYPE_LAST     =   'last';
    const TYPE_LINK     =   'link';
    const TYPE_STATS    =   'stats';
    const TYPE_SHERPA   =   'sherpa';
    const TYPE_TEXT     =   'text';
    const TYPE_CARTOHAL =   'cartohal';


    protected $_url = '';

    protected $_title = '';

    protected $_content = '';

    protected $_href = '';

    protected $_format = '';

    protected $_links = array();

    protected $_class = 'widget';

    protected $_id = '';

    protected $_limit = 5;

    protected $_display = 'mapAndTable';

    protected $_searchCriteria = 'structCountry_s';

    protected $_type = 'text';

    protected $_options = array();

    public function widget($json)
    {
        $this->init();
        if (is_array($json)) {
            $data = $json;
        } else {
            try {
                $data = Zend_Json::decode($json);
            } catch (Exception $e) {return $this;}
        }

        $params = array('class', 'id', 'type', 'options', 'title', 'content', 'limit', 'href', 'format', 'links', 'display', 'searchCriteria');

        foreach ($params as $meta) {
            if (! isset($data[$meta])) continue;
            $this->{'_' . $meta} = $data[$meta];
        }

        if ($this->_id == '') {
            $this->_id = 'widget-' . rand(0, 10000);
        }

        if ($this->_type== '') {
            $this->_type = self::TYPE_TEXT;
        }

        return $this;
    }

    public function init()
    {
        $this->_url = (defined('PREFIX_URL') ? PREFIX_URL : '' ) . 'widget';
        $this->_id = '';
        $this->_title = '';
        $this->_type = '';
        $this->_content = '';
        $this->_format = '';

    }

    public function render()
    {
        if (!$this->_id) return '';
        $result = '<div';
        //Id de l'élément
        $result .= ' id="' . $this->_id . '"';

        //Classe
        $result .= ' class="widget' . ($this->_class != '' ? ' ' . $this->_class : '') . '"';
        $result .= '>';

        $result .= '<h3 class="widget-header">';
        if ($this->_title != '') {
            $result .= $this->_title;
        } else {
            $result .=  $this->view->translate('widget_' . $this->_type);
        }
        $result .= '</h3>';


        $result .= '<div class="widget-content">';
        if ($this->_type == self::TYPE_TEXT) {
            $result .= $this->_content;
        } else if ($this->_type == self::TYPE_LINK) {
            if (! is_array($this->_links)) {
                $this->_links = array($this->_links);
            }
            $result .= '<ul>';
            foreach($this->_links as $link => $label) {
                $result .= '<li><a href="' . $link . '" target="_blank">' . $label . '</a></li>';
            }
            $result .= '</ul>';
        } else {
            $cacheFileName = 'home.' . $this->_type ;
            if ((is_array($this->_format) && count($this->_format)) || $this->_format != '') {
                $cacheFileName .= '.' . (is_array($this->_format) ? implode('.', $this->_format) : $this->_format);
            }
            if ($this->_type == self::TYPE_NEWS && Zend_Registry::isRegistered('lang')) {
                $cacheFileName .= '.' . Zend_Registry::get('lang');
            }
            if ($this->_href != '') {
                $cacheFileName .= '.' . str_replace(array('/', ':'), '', $this->_href);
            }
            $cacheFileName .= '.html';

            if ($this->_type == self::TYPE_NEWS || $this->_type == self::TYPE_SHERPA) {
                $duration = 0;
            } else if ($this->_type == self::TYPE_LAST) {
                $duration = 3600;
            } else {
                $duration = 86400;
            }
            if (Hal_Cache::exist($cacheFileName, $duration)) {
                $result .= Hal_Cache::get($cacheFileName);
            } else if (self::TYPE_CARTOHAL == $this->_type) {

                $this->view->displayMap = $this->_display == 'map' || $this->_display == 'mapAndTable' ;
                $this->view->displayTable = $this->_display == 'table' || $this->_display == 'mapAndTable';
                $this->view->searchCriteria = $this->_searchCriteria;

                // On fait une exception pour CartoHal car CartoHal fonctionne avec Angular et des librairies JS
                // On a donc besoin de charger la vue du widget directement plutôt que de retarder l'affichage sinon le JS n'est pas lancé
                $result .= $this->view->render("widget/cartohal.phtml");

            } else if ($this->_id != ''){
                //On recharge le widget
                $result .= '<center><img src="/img/loading.gif" /></center>';
                $options = array(
                    'type'  =>  $this->_type,
                    'limit'  =>  $this->_limit,
                    'format'  =>  $this->_format,
                    'href'  =>  $this->_href,
                    'cache' => $cacheFileName
                );
                $this->view->jQuery()->addOnload('$("#' . $this->_id . ' .widget-content").load("' . $this->_url . '", ' . Zend_Json::encode($options). ');');
            }
        }
        $result .= '</div>';
        $result .= '</div>';

        return $result;
    }

    public function __toString()
    {
        return $this->render();
    }
}