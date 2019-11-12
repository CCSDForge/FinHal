#! /usr/bin/env /opt/php5/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: marmol
 *
 * Ce scripts controle les hierarchies de structures
 *    Verification de l'imbrication
 *    Integrite de REF_PARENT: une structure de ref_parent doit exister...
 *    Ne verifie pas les cycles
 *    Les structutes non institution/comu doivent avoir un parent
 */
require_once(__DIR__ . '/../public/bddconst.php');
if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}


define('DBSTRUCTIDFIELD'  , 'STRUCTID');
define('DBPARENTIDFIELD'  , 'PARENTID');
define('DBCODEFIELD'      , 'CODE');
define('DBTYPESTRUCTFIELD', 'TYPESTRUCT');
define('DBSIGLESTRUCTFIELD', 'SIGLE');

define('RESEARCHTEAM','researchteam');
define('DEPARTMENT','department');
define('LABORATORY','laboratory');
define('REGROUPLABORATORY','regrouplaboratory');
define('INSTITUTION','institution');
define('REGROUPINSTITUTION','regroupinstitution');


function main()
{
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    $sql = $db->select()->from('REF_STRUCTURE');

// Reinexation des structures pour un acces par StructId
    $rawStructures = $db->fetchAll($sql);
    $structures = [];
    foreach ($rawStructures as $key => $row) {
        $structId = $row[DBSTRUCTIDFIELD];
        $structures[$structId] = $row;
        unset($rawStructures[$key]);
    }

    $sql = $db->select()->from('REF_STRUCT_PARENT');
    $parents = $db->fetchAll($sql);
    $commonParents = [];
    $count = 0;
    $indexedChild = [];
    foreach ($parents as $row) {
        $indexedChild [$row[DBSTRUCTIDFIELD]] = true;
        $structId = $row[DBSTRUCTIDFIELD];
        $parentId = $row[DBPARENTIDFIELD];
        $code = $row[DBCODEFIELD];

        if (!array_key_exists($structId, $structures)) {
            print "Hum... $structId dans REF_PARENT comme structId mais pas dans ref_struct\n";
            continue;
        }
        $structure = $structures[$structId];
        $typeStruct  = $structure[DBTYPESTRUCTFIELD];
        $sigleStruct = $structure[DBSIGLESTRUCTFIELD];

        if (!array_key_exists($structId, $structures)) {
            print "Hum... $parentId dans REF_PARENT comme parentId mais pas dans ref_struct\n";
            continue;
        }
        $parent = $structures[$parentId];
        $typeParent  = $parent[DBTYPESTRUCTFIELD];
        $sigleParent = $parent[DBSIGLESTRUCTFIELD];

        if (compare($typeStruct, $typeParent) != 1) {
            $count++;
            if (array_key_exists($parentId,$commonParents)) {
                $commonParents [$parentId]++;
            } else {
                $commonParents [$parentId] = 1;
            }
            print "Structure $sigleStruct: $structId ($typeStruct) est enfant de $sigleParent: $parentId ($typeParent)\n";
        }
    }
    $nbmult = 0;
    $nbmultRel = 0;
    foreach ($commonParents as $p => $c) {
        if ($c >2) {
            print "Structure $p referencee $c fois\n";
            $nbmult++;
            $nbmultRel += $c;
        }
    }
    $nbSansParent=0;
    /** @var Ccsd_Referentiels_Structure $struct */
    foreach ($structures as $structId => $structure) {
        $typeStruct = $structure[DBTYPESTRUCTFIELD];
        if ((compare($typeStruct, 'institution') > 0) && (!array_key_exists($structId, $indexedChild))) {
            $sigleStruct = $structure[DBSIGLESTRUCTFIELD];
            print "Structure $sigleStruct: $structId ($typeStruct) n'a pas de parent\n";
            $nbSansParent++;
        }
    }
    print "$nbSansParent structures < institution sans parent\n";
    print "$count mauvaise relation\n";
    print "$nbmult parents representant $nbmultRel relations\n";


}

$matrice = [
    RESEARCHTEAM =>
        [ RESEARCHTEAM       => 0 ,
          DEPARTMENT         => 1 ,
          LABORATORY         => 1 ,
          REGROUPLABORATORY  => 1 ,
          INSTITUTION        => 1 ,
          REGROUPINSTITUTION => 1
        ],
    DEPARTMENT =>
        [ RESEARCHTEAM       => -1 ,
          DEPARTMENT         => 0 ,
          LABORATORY         => 1 ,
          REGROUPLABORATORY  => 1 ,
          INSTITUTION        => 1 ,
          REGROUPINSTITUTION => 1
        ],
    LABORATORY =>
        [ RESEARCHTEAM       => -1 ,
          DEPARTMENT         => -1 ,
          LABORATORY         => 0 ,
          REGROUPLABORATORY  => 1 ,
          INSTITUTION        => 1 ,
          REGROUPINSTITUTION => 1
        ],
    REGROUPLABORATORY =>
        [ RESEARCHTEAM       => -1 ,
          DEPARTMENT         => -1 ,
          LABORATORY         => -1 ,
          REGROUPLABORATORY  => 0 ,
          INSTITUTION        => 1 ,
          REGROUPINSTITUTION => 1
        ],
    INSTITUTION =>
        [ RESEARCHTEAM       => -1 ,
          DEPARTMENT         => -1 ,
          LABORATORY         => -1 ,
          REGROUPLABORATORY  => -1 ,
          INSTITUTION        => 0 ,
          REGROUPINSTITUTION => 1
        ],
    REGROPINSTITUTION =>
        [ RESEARCHTEAM       => -1 ,
          DEPARTMENT         => -1 ,
          LABORATORY         => -1 ,
          REGROUPLABORATORY  => -1 ,
          INSTITUTION        => -1 ,
          REGROUPINSTITUTION => 0
        ]
];

/**
 * retourn 1 si $a est dans $b, 0 si meme niveau, -1 si $a contient $b
 * @param $a
 * @param $b
 * @return int
 */

function compare($a, $b) {
    global $matrice;
    return $matrice[$a][$b];
}

main();