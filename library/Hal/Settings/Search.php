<?php

/**
 * Paramètres pour la recherche et la documentation de l'API
 */
class Hal_Settings_Search {

    const DEFAULT_NUMBER_SEARCH_RESULTS = 30;

    static $_numberSearchResultsArray = array(
        30,
        50,
        100
    );

    const PAGINATOR_STYLE = 'Elastic';

    public static $referentiels = array(
        'anrproject',
        'author',
        'authorstructure',
        //'affiliation',
        'europeanproject',
        'doctype',
        'domain',
        'instance',
        'journal',
        'metadata',
        'metadatalist',
        'structure',
    );
    public static $fieldUsageTypes = array(
        // 'Texte pour affichage ou facettes',
        'type_string_usagePrint' => 1,
        'type_string_usageFacet' => 1,
        'type_string_usageSearch' => 0,
        'type_string_usageSort' => 1,
        // 'Texte pour affichage ou facettes stringCaseInsensitive',
        'type_stringCaseInsensitive_usagePrint' => 1,
        'type_stringCaseInsensitive_usageFacet' => 1,
        'type_stringCaseInsensitive_usageSearch' => 1,
        'type_stringCaseInsensitive_usageSort' => 1,
        // 'Texte pour affichage ou facettes',
        'type_string_trim_usagePrint' => 1,
        'type_string_trim_usageFacet' => 1,
        'type_string_trim_usageSearch' => 0,
        'type_string_trim_usageSort' => 1,
        // Texte pour générer des facettes',
        'type_facetString_usagePrint' => 1,
        'type_facetString_usageFacet' => 1,
        'type_facetString_usageSearch' => 0,
        'type_facetString_usageSort' => 0,
        // 'Texte pour recherche',
        'type_text_usagePrint' => 0,
        'type_text_usageFacet' => 0,
        'type_text_usageSearch' => 1,
        'type_text_usageSort' => 0,
        // "Texte pour une recherche de type auto-complétion",
        'type_text_autocomplete_usagePrint' => 0,
        'type_text_autocomplete_usageFacet' => 0,
        'type_text_autocomplete_usageSearch' => 1,
        'type_text_autocomplete_usageSort' => 0,
        // 'Booléen (true ou false)',
        'type_boolean_usagePrint' => 1,
        'type_boolean_usageFacet' => 1,
        'type_boolean_usageSearch' => 1,
        'type_boolean_usageSort' => 1,
        // Nombre entier',
        'type_int_usagePrint' => 1,
        'type_int_usageFacet' => 1,
        'type_int_usageSearch' => 1,
        'type_int_usageSort' => 1,
        // 'Nombre entier (-128 ? +127)',
        'type_tint_usagePrint' => 1,
        'type_tint_usageFacet' => 1,
        'type_tint_usageSearch' => 1,
        'type_tint_usageSort' => 1,
        'type_double_usagePrint' => 1,
        'type_double_usageFacet' => 1,
        'type_double_usageSearch' => 1,
        'type_double_usageSort' => 1,
        'type_long_usagePrint' => 1,
        'type_long_usageFacet' => 1,
        'type_long_usageSearch' => 1,
        'type_long_usageSort' => 1,
        // Texte pour une recherche phonétique',
        'type_phonetic_usagePrint' => 0,
        'type_phonetic_usageFacet' => 0,
        'type_phonetic_usageSearch' => 1,
        'type_phonetic_usageSort' => 0,
        'type_textStopWords_usagePrint' => 0,
        'type_textStopWords_usageFacet' => 0,
        'type_textStopWords_usageSearch' => 1,
        'type_textStopWords_usageSort' => 0,
        'type_location_usagePrint' => 1,
        'type_location_usageFacet' => 1,
        'type_location_usageSearch' => 1,
        'type_location_usageSort' => 0,
        // 'Date ISO 8601 (eg 2013-01-31T23:12:01Z)',
        'type_tdate_usagePrint' => 1,
        'type_tdate_usageFacet' => 0,
        'type_tdate_usageSearch' => 1,
        'type_tdate_usageSort' => 1,
        // 'Identifiant',
        'type_identifier_usagePrint' => 0,
        'type_identifier_usageFacet' => 0,
        'type_identifier_usageSearch' => 1,
        'type_identifier_usageSort' => 0,
        // 'Tri lexicographique',
        'type_alphaOnlySort_usagePrint' => 0,
        'type_alphaOnlySort_usageFacet' => 0,
        'type_alphaOnlySort_usageSearch' => 0,
        'type_alphaOnlySort_usageSort' => 1
    );
    public static $defaultFacetSortingType = 'index';
    public static $facetSortingTypeIconsClass = array(
        'count' => 'glyphicon glyphicon-sort-by-order',
        'index' => 'glyphicon glyphicon-sort-by-alphabet'
    );

}
