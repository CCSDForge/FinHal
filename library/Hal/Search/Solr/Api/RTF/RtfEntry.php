<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 21/03/18
 * Time: 10:07
 */

/**
 * Class Hal_Search_Solr_Api_RTF_RtfEntry
 */
class Hal_Search_Solr_Api_RTF_RtfEntry
{
    /** @var string  // The html code for bibliographics entry (comme from citationFull_s in general case */
    private $html = '';
    /** @var int  // order in bibliographics list of the item */
    private $order = 0;
    /** @var string[] // Array of text to emphase in Rtf */
    private $emphase = [];
    /** @var string[] // Array of text to boldify in Rtf */
    private $boldify = [];
    /**
     * Hal_Search_Solr_Api_RTF_RtfEntry constructor.
     * @param string $htmlstring
     * @param int $order
     */
    public function __construct($htmlstring, $order) {
        $this -> html = $htmlstring;
        $this -> order = $order;
    }
    /**
     * @param int $i
     * @return string
     */
    private function tabulate($i) {
        return sprintf("%5s", "[$i]");
    }

    /**
     * @param string $s
     * @return string
     */
    private function utf8_to_rtf($s) {
        $iterator = new Ccsd_Tools_MbStringIterator($s );
        $newString = '';

        foreach ($iterator as $char) {
            $next =0; // On envoie car par car, on ne veux pas suivre l'offset de ordutf8! Toujours mettre zero
            $code = $iterator -> ordutf8($char, $next);
            $newString .= ($code > 128) ? '{\\u' . $code . "}"  : $char;
        }
        return $newString;
    }
    /**
     * @return string
     */
    public function __toString() {
        $rtf = '\pard\plain\s62\ql\fi-567\li567\sb0\sa0\f0\fs20\sl240\slmult1 \sb60 \li450\fi0';
        $rtf .= $this -> tabulate($this ->order) . "\\tab";
        $s = $this -> html;
        //Avant tout remplacement de lettre accentuee, on change les apostrophe
        // Les apostrophes:  m'a, t'est s'est l'ajout
        $s = preg_replace('|([dljmst])\'([^\s])|', "\\1\\rquote \\2", $s);

        // A faire avant toute transformation, sinon les textes ne matches pas.
        foreach ($this -> emphase as $emphaseText) {
            $s = str_replace($emphaseText, "{\\i $emphaseText}", $s);
        }
        foreach ($this -> boldify as $emphaseText) {
            $s = str_replace($emphaseText, "{\\b $emphaseText}", $s);
        }
        // Remplacement des carateres utf8
        $newString = $this -> utf8_to_rtf($s);

        $newString = preg_replace('|<i>(.*)</i>|', "{\\i \\1}", $newString);
        $newString = strip_tags($newString);
        $newString = str_replace('&#x3008;','{\\u12296}', $newString);
        $newString = str_replace('&#x3009;','{\\u12297}', $newString);
        $newString = str_replace('&lt;','<', $newString);
        $newString = str_replace('&gt;','>', $newString);
        $newString = str_replace('-','\\endash ', $newString);
        $newString = preg_replace('|"(.*)"|', "\\rdblquote \\1\\rdblquote ", $newString);

        return $rtf  ."\n" . $newString . "\par\n";
    }

    /**
     * @param $string
     * @return Hal_Search_Solr_Api_RTF_RtfEntry
     */
    public function emphase($string) {
        $this->emphase [] = $string;
        return $this;
    }
    /**
     * @param $string
     * @return Hal_Search_Solr_Api_RTF_RtfEntry
     */
    public function boldify($string) {
        $this->boldify [] = $string;
        return $this;
    }

    /**
     * Add text to emphase when generating Rtf data
     * @param array $entry
     * @param string $field
     */
    public function emphased_ifSet($entry, $field)
    {
        // On emphase seulement les chaines assez longue...
        if (array_key_exists($field, $entry)) {
            $s = $entry[$field];
            if (is_string($s) && (strlen($s) > 5)) {
                $this->emphase($s);
            }
        }
    }
    /**
     * Add text to boldify when generating Rtf data
     * @param array $entry
     * @param string $field
     */
    public function boldify_ifSet($entry, $field)
    {
        // On emphase seulement les chaines assez longue...
        if (array_key_exists($field, $entry)) {
            $s = $entry[$field];
            if (is_string($s) && (strlen($s) > 5)) {
                $this->boldify($s);
            }
        }
    }
}