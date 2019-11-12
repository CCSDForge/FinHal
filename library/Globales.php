<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 21/08/17
 * Time: 15:47
 */

/**
 * Class Globales
 * @deprecated : La plupart des constantes sont maintenants directement associee aux Sites
 * Plus besoin d'inventer un mecanisme de constantes
 */
class Globales extends Ccsd_Globales
{
    public function init() {
        foreach (
            [
                'APPLICATION_DIR',
                'APPLICATION_INI',
                'COLLECTION' ,   # collection name
                'COLLECTION_URL' ,
                'DATA_ROOT',
                'DEFAULT_CACHE_PATH',
                'DEFAULT_CONFIG_PATH',
                'DEFAULT_SHARED_DATA',
                'DEFAULT_SID',
                'DEFAULT_SPACE_URL',
                'DOCS_ROOT',
                'MODULE',        # Collection or  portal
                'PATH_PAGES',
                'PORTAIL',
                'PREFIX_URL',
                'SESSION_NAMESPACE',
                'SITEID',
                'SITENAME',
                'SPACE',
                'SPACE_NAME',
                'SPACE_URL',

                'USE_MAIL',      # For sending mail or just display mail for information on the page
                'USE_DBCACHE',   # Test of Database caching : experimental
                'USE_ROBOTSTXT', # If false, robots will be completly forbidden, if true, a real robots.txt will be computed
                'USE_DEBUG',     # If true, usefull debug information will be displayed on page
                'USE_TRACKER',   # If set, piwik or alike tracker code will be inserted into page
                                 # piwik url and const HAL_PIWIK_ID must be set accordingly
                'USE_XSENDFILE', # If set, will use Xsendfile apache module to distribute binary file.

            ] as $name) {
            $this->record($name);
        }
    }
}

$glob = new Globales();
