<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 16/08/17
 * Time: 09:41
 */
class Hal_Document_Doublonresearcher
{
    /**
     * Retourne les dépôts (en base) potentiellement doublon de l'objet
     *
     * @param Hal_Document $document
     * @return array
     */
    static public function doublon(Hal_Document $document)
    {
        $out = [];
        $docidsForId = $document->getDocids();
        $doublons = Hal_Document_Doublonresearcher::getDoublonsOnIds($document->getIdsCopy(), $docidsForId);
        if ($document->getTypDoc() == 'THESE' && ($nnt = $document->getMeta('nnt'))) {
            $doublons = array_merge($doublons, Hal_Document_Doublonresearcher::getDoublonOnNNT($nnt, $docidsForId));
        }

        if (count($doublons)) {
            foreach ($doublons as $doublon) {
                if (array_key_exists($doublon['IDENTIFIANT'], $out)) {
                    $out[$doublon['IDENTIFIANT']][$doublon['CODE']] = '1.0';
                } else {
                    $out[$doublon['IDENTIFIANT']] = [$doublon['CODE'] => '1.0'];
                }
            }
        }
        return $out;
    }

    /**
     * Retourne les doublons d'un document selon son doi, arxiv, etc...
     *
     * @param array $idcopy liste des identifiants exterieurs du document
     * @param array $notdocid liste des docid exclus
     * @return array
     */
    static public function getDoublonsOnIds($idcopy, $notdocid)
    {
        if (!empty($idcopy)) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $query = 'SELECT ' . ' `copy`.`CODE`, `doc`.`IDENTIFIANT` FROM `' . Hal_Document_Meta_Identifier::TABLE_COPY . '` AS `copy` INNER JOIN `' . Hal_Document::TABLE . '` AS `doc` ON doc.DOCID=copy.DOCID ';
            $query .= 'WHERE (doc.DOCSTATUS != ' . Hal_Document::STATUS_MERGED . ') AND (doc.DOCSTATUS != ' . Hal_Document::STATUS_DELETED . ') ';
            $query .= ' AND (';
            $copy = [];
            foreach ($idcopy as $code => $id) {
                // On utilise LIKE BINARY pour prendre en compte la case de l'identifiant
                $copy[] = '(copy.LOCALID LIKE BINARY "' . $id . '" AND copy.CODE = "' . $code . '")';
            }
            $query .= implode(' OR ', $copy);
            $query .= ')';
            if (!empty($notdocid)) {
                $query .= ' AND `copy`.`DOCID` NOT IN (' . implode(',', $notdocid) . ')';
            }
            return $db->fetchAll($query);
        }
        return [];
    }

    /**
     * Pour le NNT on considere les doublons qui ne sont pas deja efface ou merge...
     * @param string $nnt
     * @param int[] $notdocid
     * @return array
     */
    static public function getDoublonOnNNT($nnt, $notdocid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(['d' => Hal_Document::TABLE], ['IDENTIFIANT', new Zend_Db_Expr('"NNT" AS CODE')])
            ->joinLeft(['m' => Hal_Document_Metadatas::TABLE_META], 'd.DOCID = m.DOCID', null)
            ->where('m.METANAME = ?', 'nnt')
            ->where('m.METAVALUE = ?', $nnt)
            ->where('d.DOCSTATUS != ' . Hal_Document::STATUS_MERGED)
            ->where('d.DOCSTATUS != ' . Hal_Document::STATUS_DELETED);
        if (!empty($notdocid)) {
            $sql->where('d.DOCID NOT IN (?)', $notdocid);
        }
        return $db->fetchAll($sql);
    }


    /**
     * Retourne les doublons d'un document selon ses métadonnées
     *
     * @param array $value
     * @param string $meta
     * @param array $notdocid liste des docid exclus
     * @return array
     */
    static public function getDoublonsOnMeta($value, $meta, $notdocid)
    {
        if (!empty($value)) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $sql = $db->select()->from(['meta' => Hal_Document_Metadatas::TABLE_META], ['meta.METANAME'])->where('meta.METAVALUE IN (?)', $value)->where('meta.METANAME = ?', $meta)->join(['doc' => Hal_Document::TABLE], 'doc.DOCID=meta.DOCID', ['doc.IDENTIFIANT']);
            if (!empty($notdocid)) {
                $sql->where('meta.DOCID NOT IN (?)', $notdocid);
            }
            return $db->fetchAll($sql);
        }

        return [];
    }

    /**
     * @param Hal_Document $document
     * @return array
     */
    static public function getDoublonsOnTitle(Hal_Document $document)
    {
        $titles = $document->getTitle();

        foreach ($titles as $title) {
            //recherche de doublon
            $q = 'q=title_st:' . urlencode($title) . '&fl=docid,citationFull_s';
            try {
                $solrResult = unserialize(Ccsd_Tools::solrCurl($q));

                if (!empty($solrResult) && !empty($solrResult['response']) && !empty($solrResult['response']['docs'])) {
                    return $solrResult['response']['docs'];
                }
            } catch (Exception $e) {
                // Pas de résultat trouvé
            }
        }

        // Soit on a trouvé des résultats pour l'un des titres, soit on renvoit une liste vide
        return [];
    }


}
