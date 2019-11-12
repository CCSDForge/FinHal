<?php
/*
 * Lecture du fichier pwd.json pour generer les constantes de connexion aux services (BD, CAS,...)
 * Pour chaque service XXX, les constantes XXX_HOST, XXX_NAME, XXX_USER, XXX_PWD sont positionnees
 * Pour les base de donnees, l'url de connection mysql:host=...;user=... est disponibledans la constante XXX_PDO_URL
 */

/* 
 * Remarque: on devrait utiliser PWD_FILE ou avoir un path plus general 
 *           Va-t-on mettre autre chose dans PWD_PATH ??
 *
 *           On devrait creer une classe CCSD/Db pour stocker ces infos
 *           et une CCSD/DbFactory pour stocker l'ensemble de db
 */

$instance = getenv('INSTANCE');
if ($instance) {
    $sep = '-';
} else {
    # Compatibilite
    $instance = '';
    $sep = '';
}
if (is_dir(__DIR__ . '/../config'.$instance)) {
    $ret = best_define('PWD_PATH', __DIR__ . '/../config' . $instance);
}

//BDD Login / PWD
$path = PWD_PATH . '/pwd.json'; 

if (file_exists($path)) {
    $string = file_get_contents($path);
    $fileContent = json_decode($string, true);
    
    // Création des constantes d'accès aux bases de données et services
    foreach ($fileContent as $bdd => $array) {      
        foreach ($array as $key => $value) {
            define($bdd.'_'.$key, $value);
        }
        if (isset($array['NAME'])) {
                define($bdd. '_PDO_URL', "mysql:host=" . $array['HOST'] . (isset($array['PORT']) ? ";port=". $array['PORT'] : '') . ";dbname=". $array['NAME']);
        }
    }
} else {
    die('MISSING FILE : '.$path);   
}

// TODO: A mettre ailleurs!!!  Mais attention, les include path ne sont pas forcement definis
/**
 * S'assure que la constante de nom $name est definie
 * Si non definie, prendra la valeur de la variable d'environnement du meme nom
 * Sinon, prendra la valeur par defaut propose.
 * La valeur par defaut peut etre null mais un message sera emis
 * Pour eviter le message, passer le parametre warn a False

 * @param string $name     : Name of constant to define
 * @param mixed  $default  : Default value
 * @param bool $warn       :
 * @return array|false|string
 */
function best_define($name, $default, $warn=true) {

    if ($warn && ($default === null)) {
        error_log("WARNING: Definition de la constante $name a null");
    }
    if (defined($name)) {
        return constant($name); }
    $env_value = getenv($name);
    if ($env_value !== false) {
        define($name, $env_value);
        return $env_value;
    } else {
        define($name, $default);
        return $default;
    }
}