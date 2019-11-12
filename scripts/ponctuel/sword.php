<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 28/03/18
 * Time: 10:35
 */

require_once __DIR__ . "/../../library/Hal/Script.php";

/**
 * Class SwordScript
 * Cmd line to import by sword
 */
class SwordScript extends Hal_Script {

    /** Flags pour l'import Sword */
    const AllowGrobidCompletionFlag      = 'AllowGrobidCompletion';
    const AllowIdExtCompletionFlag       = 'AllowIdExtCompletion';
    const AllowAffiliationCompletionFlag = 'AllowAffiliationCompletion';
    const ExportToArxivFlag = 'ExportToArxiv';
    const ExportToPMCFlag   = 'ExportToPMC';
    const HideForRePEcFlag  = 'HideForRePEc';
    const HideInOAIFlag     = 'HideInOAI';

    /**
     * @see SwordController
     */
    const COMP_GROBID      = 'grobid';
    const COMP_IDEXT       = 'idext';
    const COMP_AFFILIATION = 'affiliation';

    protected $options = [
        'portail|p-s'   => "portail name (Hal by default)",
        'packaging|P-s' => "Packaging type (xml or default aofr)",
        'user|u-s'      => 'Username',
        'meta|f-s'      => 'Metadata file of packaging type',
        'arxiv'         => 'Export to Arxiv if possible',
        'pmc'           => 'Export to PMC if possible',
        'norepec'       => "Don't export to Repec",
        'nooai'         => 'Hide for AOI',
        'grobid|g'      => "Allow grobid completion",
        'external|E'    => "Allow completion with external Id (like Arxiv, DOI,...)",
        'affiliate'     => "Allow affiliation completion"
    ];

    /**
     * For a flag name, return the http header for sword
     * @param string $flag
     */
    public static function getHeaderFlag($flag) {
        switch ($flag) {
            case self::ExportToArxivFlag: $h = 'Export-To-Arxiv: true' ; break;
            case self::ExportToPMCFlag  : $h = 'Export-To-PMC: true'   ; break;
            case self::HideForRePEcFlag : $h = 'Hide-For-RePEc: true'  ; break;
            case self::HideInOAIFlag    : $h = 'Hide-In-OAI: true'     ; break;
            case self::AllowGrobidCompletionFlag     : $h = 'X-Allow-Completion: ' . self::COMP_GROBID     ; break;
            case self::AllowIdExtCompletionFlag      : $h = 'X-Allow-Completion: ' . self::COMP_IDEXT      ; break;
            case self::AllowAffiliationCompletionFlag: $h = 'X-Allow-Completion: ' . self::COMP_AFFILIATION; break;
            default:
                Ccsd_Tools::panicMsg(__FILE__, __LINE__, "($flag) is not a valid flag for getHeaderFlag");
        }
    }

    /**
     * @param Zend_Console_Getopt $options
     * @throws Exception
     */
    public function main($options) {
        $username = $options -> u;
        if (!$username) {
            die( "Need a username");
        }
        $portail   = isset($options -> portail)   ? $options -> portail   : 'hal';
        $packaging = isset($options -> packaging) ? $options -> packaging : 'aofr';
        switch ($packaging) {
            case "xml":
            case "aofr":
                break;
            default:
                die ("$packaging is not a correct value for --packaging parameter");
        }
        if (isset($options -> arxiv)) {
            $flags [self::ExportToArxivFlag] = True;
        }
        if (isset($options -> pmc)) {
            $flags [self::ExportToPMCFlag] = True;
        }
        if (isset($options -> norepec)) {
            $flags [self::HideForRePEcFlag] = True;
        }
        if (isset($options -> nooai)) {
            $flags [self::HideInOAIFlag] = True;
        }
        if (isset($options -> grobid)) {
            $flags [self::AllowGrobidCompletionFlag] = True;
        }
        if (isset($options -> external)) {
            $flags [self::AllowIdExtCompletionFlag] = True;
        }
        if (isset($options -> affiliate)) {
            $flags [self::AllowAffiliationCompletionFlag] = True;
        }

        $site = Hal_Site::exist($portail);
        if (!$site) {
            die("$portail is not a valid site");
        }
        // Todo: accept a zip file
        // $file = ...
        $file = null;

        $metafile = $options -> meta;
        if (! file_exists($metafile)) {
            die("File $metafile doesn't exists");
        }
        $fh = fopen($metafile, "r");
        if (!$fh) {
            die("Can't open open file $metafile");
        }

        $password = $this->tty_read_passwd();
        $this -> sword_import($site, $username, $password, $packaging, $metafile, $file, $flags);
    }

    /**
     * @param string $portail
     * @return string
     */
    private function getSwordLocation($portail) {
        return HAL_API . "/sword/$portail";
    }
    /**
     * @param Hal_Site $portail
     * @param string $username
     * @param string $password
     * @param string $packaging
     * @param string $meta   // xml string
     * @param string $file   //filename
     * @param array $flags   // Keys are options, values (currently) just set (not used)
     * @return true|Hal_Transfert_Response
     */
    private function sword_import($portail, $username, $password, $packaging, $metafile, $file=null, $flags = []) {

        global $sal_useragent;  // TRICK: Only way to add header to curl used in SWORDAPPClient, we save and restaure it after...

        $depositlocation = $this->getSwordLocation($portail);
        $sword = new SWORDAPPClient();
        $sal_useragent_save = $sal_useragent;  // Save
        $sep = '';
        if (isset($sal_useragent) && ($sal_useragent != '')) {
            $sep = "\r\n";
        }
        foreach ($flags as $flag => $flagValue) {
            $sal_useragent .= "$sep" . self::getHeaderFlag($flag);
            $sep = "\r\n"; // maintenant, une concanenation demande le separateur
        }

        $swordResponse = $sword->depositAtomEntry($depositlocation, $username, $password, '',$metafile,  true);
        $sal_useragent = $sal_useragent_save;  // Restaure
        if (($swordResponse->sac_status < 200) || ($swordResponse->sac_status >= 300)) {
            // Probleme au depot:
            $reason = $swordResponse -> sac_statusmessage;
            $result = Hal_Transfert_Response::INTERNAL;
            $response = new Hal_Transfert_Response($result, $reason);
            return $response;
        }

        $swordResponse
        // Ok, suppose
        return true;
    }
}

$script = new SwordScript;
$script ->run();

// curl  -X POST -u LOGIN:PWD https://api-preprod.archives-ouvertes.fr/sword/PORTAIL -H "X-Packaging:http://purl.org/net/sword-types/AOfr" -H "Content-Type:text/xml" --data-binary @FICHIER_XML
