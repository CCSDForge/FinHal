<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 10/11/2017
 * Time: 14:52
 */

if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}


Zend_Registry::set('languages', array('fr', 'en', 'es', 'eu'));
Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));
Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator(Zend_Registry::get('Zend_Locale')));

println();
println('', '*****************************************', 'blue');
println('', '*  Nettoyage du référentiel Laboratoire *', 'blue');
println('', '*****************************************', 'blue');
println('> Début du script: ', date("H:i:s", $timestart));
println('> Environnement: ', APPLICATION_ENV);

$cursor = 1;
$limit = 1000000;

$results = ['total' => 0];


$query = $db->query('SELECT * FROM REF_STRUCTURE WHERE VALID = "INCOMING" AND STRUCTID >= ' . $cursor . ' AND PAYSID = "fr" ORDER BY STRUCTID ASC LIMIT ' . $limit);
$reqSql = $query->fetchAll();

foreach ($reqSql as $row) {
    $structure = [
        'structid'   => $row['STRUCTID'],
        'sigle'   => trim(($row['SIGLE'])),
        'name'   => trim(($row['STRUCTNAME'])),
        //'type'   => trim(($row['TYPESTRUCT'])),
        //'valid'   => trim(($row['VALID'])),
        'code'  =>  ''
    ];

  //Récupération identifiants
  $query = $db->query('SELECT CODE FROM REF_STRUCT_PARENT WHERE  STRUCTID = ' . $structure['structid'] . ' AND CODE != ""');
  $resCodes = $query->fetchAll();
  if (isset($resCodes[0]['CODE'])) {
      $structure['code'] = trim(strtoupper($resCodes[0]['CODE']));
  }

  $correctDoublon = searchDoublon($structure);
  $results['total']++;
  if ($correctDoublon != null) {
    if (!isset($results[$correctDoublon['rule']])) {
        $results[$correctDoublon['rule']] = 0;
    }
      $results[$correctDoublon['rule']]++;
  }
  echoDoublon($structure, $correctDoublon);
}

println('**********');
foreach ($results as $type => $value) {
    println($type . ':' . "\t" . $value);
}


function searchDoublon($structure)
{
    $queries = [];
    if ($structure['code'] != '') {
        $queries['code'] = 'https://api.archives-ouvertes.fr/ref/structure/?q=code_s:' . $structure['code'] . '&fq=valid_s:(VALID%20OR%20OLD)&fl=*';
    }

    foreach (['code-name' => $structure['name'], 'code-sigle' => $structure['sigle']] as $rule => $value) {
        if (preg_match('/((umr|fre|ura|erl|upr|umi|ea)\s*\d+)/i', $value, $matches)) {
            $matches[0] = str_replace(' ', '', $matches[0]);
            $queries[$rule] = 'https://api.archives-ouvertes.fr/ref/structure/?q=code_s:' . $matches[0] . '&fq=valid_s:(VALID%20OR%20OLD)&fl=*';
        }
    }

    $queries['name'] = 'https://api.archives-ouvertes.fr/ref/structure/?q=name_sci:"' . urlencode($structure['name']) . '"&fq=valid_s:(VALID%20OR%20OLD)&fl=*';
    $queries['sigle'] = 'https://api.archives-ouvertes.fr/ref/structure/?q=acronym_sci:' . ($structure['sigle']) . '&fq=valid_s:(VALID%20OR%20OLD)&fl=*';


    foreach ($queries as $rule => $query) {
        //echo $query;
        $s = curl_init();
        curl_setopt ( $s, CURLOPT_URL, $query );
        curl_setopt($s,CURLOPT_RETURNTRANSFER,true);
        $info = curl_exec ( $s );
        if (curl_errno ( $s ) != CURLE_OK) {
            exit(curl_errno( $s ));
        }
        $result = json_decode($info);
        if (isset($result->response->docs) && count($result->response->docs) > 0) {
            $structurefound = [
                'structid' => $result->response->docs[0]->docid,
                'sigle' => isset($result->response->docs[0]->acronym_s)?:'',
                'name' => $result->response->docs[0]->name_s,
                //'type' => $result->response->docs[0]->type_s,
                //'valid' => $result->response->docs[0]->valid_s,
                'code' => '',
                'rule' => $rule
            ];
            if (isset($result->response->docs[0]->code_s)) {
                $structurefound['code'] = $result->response->docs[0]->code_s[0];
            }

            return $structurefound;
        }
    }

    return null;
}



function echoDoublon($old, $new = null)
{
    $str = $old['structid'];
    if ($new != null) {
        $str .= "\t";
        $str .= $new['structid'];
        $str .= "\t";
        $str .= $new['rule'];
    }


    /*$str = implode("\t ", $old);
    if ($new != null) {
        $str .= "\t =>\t ";
        $str .= implode("\t ", $new);
    }*/
    println($str);
}


