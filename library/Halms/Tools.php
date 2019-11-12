<?php

Class Halms_Tools {

    /**
     * Retourne le PMCid d'un article PubMed
     *
     * @param int $pmid
     * @return int|bool
     */
    public static function isPMC ($pmid = 0) {
        if ( $pmid ) {
            try {
                $return = json_decode(file_get_contents('http://www.ncbi.nlm.nih.gov/pmc/utils/idconv/v1.0/?versions=no&format=json&ids='.$pmid));
                if ( isset($return->status) && $return->status == 'ok' ) {
                    if ( isset($return->records[0]->pmcid) && preg_match('/^PMC([0-9]+)$/', $return->records[0]->pmcid, $match) ) {
                        return (int)$match[1];
                    }
                }
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

}