<?php
class Hal_Stats {

    const TABLE = 'DOC_STAT_COUNTER';
    const MAX_DOCS_REQUESTED = 2000;

	/**
	 * Retourne une liste de champs pour les stats
	 *
	 * @return array
	 */
	static public function getStatFields() {
		$translator = Zend_Registry::get ( 'Zend_Translate' );

		$sc = new Ccsd_Search_Solr_Schema(['env' => APPLICATION_ENV, 'core' => 'hal', 'handler' => 'schema']);

        /**
		 * champs simples
		 */

		$sc->getSchemaFields ( false );

		$blacklistedFields = array (
				'label_bibtex',
				'label_coins',
				'label_endnote',
				'label_xml',
				'label_s',
				'abstract_s',
				'comment_s',
				'citationFull_s',
				'citationRef_s',
				'title_s',
				'subTitle_s'
		);

		$allowedTypes = array (
				'string',
				'int',
				'tint',
				'double',
                'boolean'
		);

		foreach ( $sc->getFields () as $field ) {

			$field = ( array ) $field;

			if (! in_array ( $field ['type'], $allowedTypes )) {
				continue;
			}

			if (in_array ( $field ['name'], $blacklistedFields )) {
				continue;
			}

			if ($translator->isTranslated ( 'hal_' . $field ['name'] )) {
				$label = $translator->translate ( 'hal_' . $field ['name'] );
			} else {
				$label = 'zzz_' . $field ['name']; // classer les champs non traduits à la fin
			}

			if ((isset ( $field ['multiValued'] )) and ($field ['multiValued'] == true)) {
				$multi [$field ['name']] = $label;

			} else {
				$uni [$field ['name']] = $label;
			}
		}

		/**
		 * champs dynamiques
		 */

		$blacklistedDynamicFields = array (
				'*_subTitle_s',
				'*_title_s',
				'*_abstract_s',
				'*_keyword_s'
		);

		$sc->getSchemaDynamicFields ();
		foreach ( $sc->getDynamicFields () as $dfield ) {
			$dfield = ( array ) $dfield;

			if (! in_array ( $dfield ['type'], $allowedTypes )) {
				continue;
			}

			if (in_array ( $dfield ['name'], $blacklistedDynamicFields )) {
				continue;
			}

			foreach ( $dfield ['fieldList'] as $fieldList ) {

				if ($translator->isTranslated ( 'hal_' . $fieldList )) {
					$label = $translator->translate ( 'hal_' . $fieldList );
				} else {
					$label = 'zzz_'  . $fieldList; // classer les champs non traduits à la fin
				}

				if ($dfield ['multiValued'] == true) {
					$multi [$fieldList] = $label;

				} else {
					$uni [$fieldList] = $label;
				}
			}
		}

		$result ['multi'] = $multi;
		$result ['uni'] = $uni;
		$result ['all'] = $uni + $multi;

		uasort ( $result ['uni'], 'strcoll');
		uasort ( $result ['multi'], 'strcoll');
		uasort ( $result ['all'], 'strcoll');





		//supprime les prefixes de tri
		$result ['uni'] = str_replace('zzz_', '', $result ['uni']);
		$result ['multi'] = str_replace('zzz_', '', $result ['multi']);
		$result ['all'] = str_replace('zzz_', '', $result ['all']);


		return $result;
	}
	static public function getCount($q, $filters) {
		// Construction de la requete solr
		$query = "q=" . ($q != '' ? urlencode ( $q ) : '*');
		if (count ( $filters )) {
			$query .= '&fq=' . implode ( '&fq=', array_map ( 'urlencode', $filters ) );
		}
		$query .= "&start=0&rows=0&wt=phps&omitHeader=true";
		$solrResult = Ccsd_Tools::solrCurl ( $query );
		$solrResult = unserialize ( $solrResult );
		if (isset ( $solrResult ['response'] ['numFound'] )) {
			return $solrResult ['response'] ['numFound'];
		}
		return 0;
	}
	static public function getRepartitionData($q, $filters, $facet, $pivot = '', $sort = 'count', $cumul = false, $additional = '') {
		// Construction de la requete solr
		$query = "q=" . ($q != '' ? urlencode ( $q ) : '*');
		if (is_array ( $filters ) && count ( $filters )) {
			$query .= '&fq=' . implode ( '&fq=', array_map ( 'urlencode', $filters ) );
		} else if ($filters != '') {
			$query .= $filters;
		}
		$query .= "&start=0&rows=0&wt=phps&omitHeader=true&facet.mincount=1&facet.limit=10000";

		if ($pivot != '') {
			$query .= "&facet=true&facet.pivot.mincount=1&facet.pivot={!key=pivot}" . $facet . "," . $pivot;
		} else {
			$query .= "&facet=true&facet.field=" . $facet ;
		}
		$query .= '&facet.sort=' . $sort;

		if ($additional != '') {
			$query .= "&" . $additional;
		}

        try {
            $solrResult = Ccsd_Tools::solrCurl($query, 'hal', 'select', 29);
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            $solrResult = [];
            $solrResult ['response'] ['numFound'] = 0;
        }

        $solrResult = unserialize ( $solrResult );

		$data = array ();
		$data ['query'] = $query;
		$data ['nb'] = $solrResult ['response'] ['numFound'];
		if ($pivot != '' && isset ( $solrResult ['facet_counts'] ['facet_pivot'] ['pivot'] )) {
			$data ['data'] = self::prepareDataPivot ( $solrResult ['facet_counts'] ['facet_pivot'] ['pivot'], $facet, $cumul );
		} else if (isset ( $solrResult ['facet_counts'] ['facet_fields'] [$facet] )) {
			$data ['data'] = self::prepareDataFacet ( $solrResult ['facet_counts'] ['facet_fields'] [$facet], $facet, $cumul );
		}
		return $data;
	}
	static public function getDocids($q, $filters, $rows = self::MAX_DOCS_REQUESTED) {
		// Construction de la requete solr
		$query = "q=" . ($q != '' ? urlencode ( $q ) : '*');
		if (is_array ( $filters ) && count ( $filters )) {
			$query .= '&fq=' . implode ( '&fq=', array_map ( 'urlencode', $filters ) );
		} else if ($filters != '') {
			$query .= $filters;
		}
		$query .= "&start=0&rows=" . $rows . "&fl=docid&wt=phps&omitHeader=true";

		$solrResult = Ccsd_Tools::solrCurl ( $query );
		$solrResult = unserialize ( $solrResult );

		$data = array ();
		$data ['total'] = $solrResult ['response'] ['numFound'];
		$data ['docids'] = array ();
		if (isset ( $solrResult ['response'] ['docs'] ) && is_array ( $solrResult ['response'] ['docs'] )) {
			$data ['nb'] = count ( $solrResult ['response'] ['docs'] );
			foreach ( $solrResult ['response'] ['docs'] as $row ) {
				$data ['docids'] [] = $row ['docid'];
			}
		}
		return $data;
	}

	/*
	 * Récupération des stats de consultation
	 *
	 * @param string hit|map|id
	 * @param array liste des docids
	 * @param string date début
	 * @param string date fin
	 * @param string type de consultation
	 * @param string option d'affichage year|month ou domain|country
	 * @return array
	 */
	static public function getConsult($graph = 'hit', $docids = [], $start = null, $end = null, $type = 'notice', $option = 'country') {


		if ($end == null) {
			$end = date ( 'Y' );
		}
		if ($start == null) {
			$start = date ( 'Y' ) - 5;
		}

		// La base des stats est un réplicat de la base de Hal. Il est donc possible d'accéder aux tables de Hal notamment pour les jointures avec Document
		$db = Hal_Db_Adapter_Stats::getAdapter(APPLICATION_ENV);

		$sql = $db->select ();
		if ($graph == 'hit') {
			$field = new Zend_Db_Expr ( "DATE_FORMAT(c.DHIT, '" . ($option == 'month' ? '%Y-%m' : '%Y') . "') AS D" );
		} else if ($graph == 'map') {
			$field = ($option == 'domain' ? 'v.DOMAIN' : 'v.COUNTRY');
		} else {
			// Cas d'une ressource
			$sql->joinLeft ( [
					'd' => 'DOCUMENT'
			], 'c.DOCID=d.DOCID', '' );
			$field = 'd.IDENTIFIANT';
		}
		$sql->from ( ['c' => self::TABLE],
            [$field, 'SUM(COUNTER) AS C']);

		$sql->where ( 'c.DOCID IN (?)', $docids )->where ( 'c.DHIT >= ?', $start )->where ( 'c.DHIT <= ?', $end );
		if ($type != 'all') {
			$sql->where ( 'c.CONSULT = ?', $type );
		}
		$sql->joinLeft ( ['v' => 'STAT_VISITOR'], 'c.VID=v.VID', '' );

		if ($graph == 'hit') {
			$group = 'D';
			$order = 'D ASC';
		} else if ($graph == 'map') {
			$group = ($option == 'domain' ? 'v.DOMAIN' : 'v.COUNTRY');
			$order = ($option == 'domain' ? 'C DESC' : 'v.COUNTRY ASC');
		} else {
			$group = 'd.IDENTIFIANT';
			$order = 'C DESC';
		}
		$sql->group ( $group )->order ( $order );
		return $db->fetchPairs ( $sql );
	}
	static private function prepareDataFacet($data, $facet, $cumul) {
		$result = array ();
		if (count ( $data )) {
			$translate = (substr ( $facet, - 2 ) == '_s');

			$count = 0;
			$result [] = array (
					Zend_Registry::get ( 'Zend_Translate' )->translate ( 'hal_' . $facet ),
					Zend_Registry::get ( 'Zend_Translate' )->translate ( 'Documents' )
			);
			foreach ( $data as $k => $v ) {
				if ($cumul) {
					$count += $v;
				} else {
					$count = $v;
				}

				if ($translate) {
					$label = Hal_Referentiels_Metadata::getLabel ( substr ( $facet, 0, - 2 ), $k );
					if (Zend_Registry::get ( 'Zend_Translate' )->isTranslated ( $label )) {
						$k = Zend_Registry::get ( 'Zend_Translate' )->translate ( $label );
					} else if (Zend_Registry::get ( 'Zend_Translate' )->isTranslated ( $k )) {
						$k = Zend_Registry::get ( 'Zend_Translate' )->translate ( $k );
					}
				}

				$result [] = array (
						( string ) $k,
						$count
				);
			}
		}
		return $result;
	}
	static private function prepareDataPivot($data, $facet, $cumul) {
		$result = array ();
		if (count ( $data )) {
			// Récupération de l'en-tête
			$header = array ();
			foreach ( $data as $i => $item ) {
				foreach ( $item ['pivot'] as $pivot ) {
					$key = Zend_Registry::get ( 'Zend_Translate' )->translate ( $pivot ['value'] );
					if (! in_array ( $key, $header )) {
						$header [$pivot ['value']] = $key;
					}
				}
			}
			$translate = (substr ( $facet, - 2 ) == '_s');

			$result [] = array_merge ( array (
					Zend_Registry::get ( 'Zend_Translate' )->translate ( 'hal_' . $facet ),
					Zend_Registry::get ( 'Zend_Translate' )->translate ( 'total' )
			), array_values ( $header ) );

			// Parcours pour remplir le tableau
			foreach ( $data as $i => $item ) {
                $count = array ();
                if ($translate) {
					$label = Hal_Referentiels_Metadata::getLabel ( substr ( $facet, 0, - 2 ), $item ['value'] );
					if (Zend_Registry::get ( 'Zend_Translate' )->isTranslated ( $label )) {
						$item ['value'] = Zend_Registry::get ( 'Zend_Translate' )->translate ( $label );
					} else if (Zend_Registry::get ( 'Zend_Translate' )->isTranslated ( $item ['value'] )) {
						$item ['value'] = Zend_Registry::get ( 'Zend_Translate' )->translate ( $item ['value'] );
					}
				}
				$row = array (
						( string ) $item ['value']
				);

				if ($cumul && !empty($count ['all'])) {
					$count ['all'] += $item ['count'];
				} else {
					$count ['all'] = $item ['count'];
				}
				$row [] = $count ['all'];

				foreach ( array_keys ( $header ) as $field ) {
                    if (empty($count[$field])){
                        $row [$field] = 0;
                    } else {
                        $row [$field] = $count[$field];
                    }
				}

				foreach ( $item ['pivot'] as $pivot ) {
                    if ($cumul && !empty($count [$pivot ['value']])) {
						$count [$pivot ['value']] += $pivot ['count'];
					} else {
						$count [$pivot ['value']] = $pivot ['count'];
					}
                    $row [$pivot ['value']] = $count [$pivot ['value']];
                }
				$result [] = array_values ( $row );
			}
		}
        return $result;
	}

    static public function delete($docid)
    {
        $db = Hal_Db_Adapter_Stats::getAdapter(APPLICATION_ENV);
        $db->delete('DOC_STAT_COUNTER', 'DOCID = ' . $docid);
    }

    /**
     * Déplacement des statistiques d'un document vers un autre
     * @param $fromid
     * @param $toid
     */
    static public function moveStats($fromid, $toid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        try {
            $db->update(self::TABLE, ['DOCID' => $toid], ['DOCID = ? ' => $fromid]);
        } catch (Exception $e) {
            // TODO : Dans le cas où la clé primaire U_STAT existe déjà, on ne plante pas... que faire ?
        }
    }
}