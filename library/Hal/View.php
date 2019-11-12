<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 25/07/17
 * Time: 10:54
 */

/**
 * @method ZendX_JQuery_View_Helper_JQuery_Container jQuery()
 *   definie par: @see ZendX_JQuery_View_Helper_JQuery::jQuery
 * @method Ccsd_View_Helper_Confirm confirm(string $title, string $content, string $trigger =''))
 *   definie par @see Ccsd_View_Helper_Confirm::confirm
 *
 * La declaration de la methode dans @see Zend_View::translate ne corresponds pas
 * a @see Zend_View_Helper_Translate::translate
 * @method string|Zend_View_Helper_Translate translate($messageid = null)
 *
 */

/**
 * Class Hal_View
 * A foo class for putting CCSD HAL magick properties on  view.
 *
 * @see ViewController
 *      @property Ccsd_Form $form
 *      @property Hal_Document_References references
 *      @property bool $hideFiles
 *      @property string showAll  ( 'moderate', '' ...)
 * @property Hal_User          $user
 * @property Hal_Document      $document
 * @property Hal_Document[]    $documents
 * @property string            $href
 * @property Hal_Document_File $file
 * @property string[]          $filters        // Used in Search Controller
 * @property string            $Description    // an Url in Search Controller
 * @property string            $siteUrl        // Used in Search Controller
 * @property string            $ShortName      // Used in Search Controller
 * @property array             $facetsArray    // Used in Search Controller
 * @property int               $numFound       // Used in Search Controller
 * @property \Solarium\QueryType\Select\Result\Result $results    // Used in Search Controller
 * @property int               $paginatorNumberOfResultsPerPage   // Used in Search Controller
 * @property string[]          $exportUriList  // Used in Search Controller
 * @property                   $submitType
 * @property                   $docType
 * @property Ccsd_Form         $formAdvanced
 *
 * @see ArchiveController::ajaxdetailsAction
 * @property int               $status         // Status d'archivage
 * @property array             $history        // Element d'history de l'archivage d'un document
 *                             in @see ArchiveController::ajaxdetailsAction () for ex
 *                             in @see ViewController::historyAction()
 * @see ArchiveController::indexAction
 * @property string            $start          //Date
 * @property string            $end            // Date
 * @property int[]             $docids
 *
 * @property Hal_Document_Author $author
 *
 * ----------------- Step  view  : @see Hal_Submit_Step       --------------
 * @property bool     $valid
 * @property string   $error
 * @property string   $msg
 *
 * ----------------- Step / Recap view : @see Hal_Submit_Step_Recap --------------
 * @property bool     $submitArxiv
 * @property bool     $submitPMC
 * @property bool     $submitSWH
 * @property array    $swhErrors
 * @property bool     $goToSWH
 * @property string   $btnLabel
 * @property string   $format
 * @property string   $typdoc
 * @property string   $typdocLabel
 * @property string   $citation
 * @property string   $doublonID
 * @property string   $doublonCit
 * @property int      $canTransferArxiv
 * @property int      $docstatus
 * @property array    $arxivErrors
 * @property bool     $goToArxiv
 * @property array    $pmcErrors
 * @property array    $shErrors
 * @property bool     $filesInTmpDir
 *
 * ----------------- Step / File view : @see Hal_Submit_Step_File--------------
 * @property string   $filemode
 * @property string   $controller
 * @property bool     $addFile
 * @property bool     $editFile
 * @property string   $type
 * @property bool     $submitFulltext
 * @property array    $filesNotDeletable
 * @property bool     $onlyAnnex
 * @property string[] $extensions      // Extensions des fichiers acceptés
 * @property string[] $mainFileType    // Extensions de fichiers
 * @property string[] $fileVisibility
 * @property string   $embargo  // Max embargo for typdoc
 * @property string   $divOrigin
 * @property string   $divLicence
 * @property bool     $showLicence
 * @property bool     $requiredLicence
 * @property string[] $licences
 * @property Hal_Document_File[]   $files
 * @property array    $formats
 * @property string[] $ftp
 * @property string   $idext
 * @property string   $idtype  "doi", "arxiv",...
 * @property string   $idurl
 * @property array    $listTypdocs
 * @property array    $types
 * @property string   $idplaceholder
 * @property string   $currentTypdoc
 * @property string   $iconwarning
 * @property int      $i
 * @property bool     $canChange
 * @property Ccsd_Referentiels_Journal $journal
 * @property Hal_Website_Navigation_Page $page
 * @property bool[] $pagesDisplay
 *
 * @property Hal_Site_Collection   $collection
 * @property Hal_Site_Collection[] $collections
 * ----------------- Step Meta view : @see Hal_Submit_Step_Meta ------------------
 * @property bool     $metamode
 *
 * ----------------- Step / Author view : @see Hal_Submit_Step_Author--------------
 * @property int      $authormode
 * @property array    $errors
 * @property int      $id
 * @property int      $docauthid
 * @property int      $authorid
 * @property int      $structid
 * @property Hal_Document_Structure $structure
 * @property Hal_Document_Author[]    $authors
 *              !!!!  @see SubmitController::ajaxgetmyauthorsformAction
 *              !!!!  We found also a array of [ $id => fullname ]
 * @property Hal_Document_Structure[] $structures
 *
 * @method documents ($id, $class)
 *    @see Aurehal_View_Helper_Documents::documents
 * Todo: better support Specific method for subclass
 *       Having a function getStepAuthor return the good class is better than returning just generic Step class
 * @method setAuthorOrder(array $authors)
 * @method jwplayer(array $options)
 *     @see Ccsd_View_Helper_Jwplayer
 *
 * Dans Ccsd_Referentiels_Europeanproject
 * @property string identifier
 * @property Ccsd_Referentiels_Europeanproject europeanproject
 * @property bool $options
 * @property Ccsd_Form_Element_Referentiel $item
 *
 * @property string resultMessage
 *
 * @property array[] resultAuth   : tableau d'authentification reussi... pas encore d'objet associe :-(
 * @property string $url
 */
class Hal_View extends Ccsd_View {

}