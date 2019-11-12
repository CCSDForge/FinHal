<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 18/09/17
 * Time: 17:47
 */

/** @property int    $result
 *  @property string $reason
 *  @property string $edit
 *  @property string $alternate
 *  @property string $externalId
 *
 * We implement ArrayAccess for compatibility: Don't use it
 */

class Hal_Transfert_Response implements ArrayAccess
{
    const OK       = 1;  // Transfert Ok: document a publier
    const WARN     = 2;  // Transfert Ok mais status distant non satisfaisant (On Hold sur Arxiv...)
                         // Signifie qu'il peut encore passer a Submitted
    const INTERNAL = 4;  // Transfert pas Ok
    /** @var  int */
    private $_result;
    /** @var  string */
    private $_reason;

    private $_alternate;
    private $_edit;
    private $_externalId;
    private $_error;

    /** Constructor
     * @param int    $result
     * @param string $reason
     */
    public function __construct($result = self::OK , $reason = '') {
        $this -> set_result($result);
        $this -> set_reason($reason);
    }

    /** to handle $o -> result without ()
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        switch ($name) {
            case 'result': $ret =  $this -> get_result();
                break;
            case 'reason': $ret =  $this -> get_reason();
                break;
            case 'error': $ret =  $this -> get_error();
                break;
            case 'edit': $ret =  $this -> get_edit();
                break;
            case 'alternate': $ret =  $this -> get_alternate();
                break;
            case 'externalId': $ret =  $this -> get_externalId();
                break;
            default:
                $ret =  null;
        }
        return $ret;
    }

    /** to handle $o -> result without ()
     * @param string $name
     * @param mixed   $value
     * @throws Hal_Transfert_Exception
     */
    public function __set($name, $value) {
        switch ($name) {
            case 'result':     $this -> set_result($value);
                break;
            case 'reason':     $this -> set_reason($value);
                break;
            case 'error':      $this -> set_error($value);
                break;
            case 'edit':       $this -> set_edit($value);
                break;
            case 'alternate':  $this -> set_alternate($value);
                break;
            case 'externalId': $this -> set_externalId($value);
                break;
            default:
                throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, "Can't set $name property");
        }
    }
    /** getter */
    public function get_result() {
        return $this -> _result;
    }
    /** getter */
    public function get_reason() {
        return $this -> _reason;
    }
    /** getter */
    public function get_edit() {
        return $this -> _edit;
    }
    /** getter */
    public function get_error() {
        return $this -> _error;
    }
    /** getter */
    public function get_alternate() {
        return $this -> _alternate;
    }
    /** getter */
    public function get_externalId() {
        return $this -> _externalId;
    }
    /** setters
     * @param int $v
     * @throws Hal_Transfert_Exception
     */
    public function set_result($v) {
        switch ($v) {
            case null:
            case self::OK:
            case self::WARN:
            case self::INTERNAL:
                $this->_result = $v;
                break;
            default:
                throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, "$v: Bad result code");
        }
    }
    /** setters
     * @param string $s
     */
    public function set_reason($s) {
        $this -> _reason = $s;
    }
    /** setters
     * @param string $s
     */
    public function set_edit($s) {
        $this -> _edit = $s;
    }
    /** setters
     * @param string $s
     */
    public function set_error($s) {
        $this -> _error = $s;
    }
    /** setters
     * @param string $s
     */
    public function set_alternate($s) {
        $this -> _alternate = $s;
    }
    /** setters
     * @param string $sreturn isset($this->container[$offset]);
     */
    public function set_externalId($s) {
        $this -> _externalId = $s;
    }
    /** Getter for ArrayObject interface
     * @deprecated
     */
    public function offsetGet($offset) {
        // Todo: We can log to find remaining usage!
        return $this->$offset;
    }
    /** Setter for ArrayObject interface
     * @deprecated
     */
    public function offsetSet($offset, $value) {
        // Todo: We can log to find remaining usage!
        if (is_null($offset)) {
            throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, "Bad null offset for Response object");
        } else {
            $this->$offset = $value;
        }
    }
    /** Unsetter for ArrayObject interface
     * @deprecated
     */
    public function offsetUnset($offset) {
        // Todo: We can log to find remaining usage!
        $this->$offset = null;
    }
    /**  for ArrayObject interface
     * @deprecated
     */
    public function offsetExists($offset) {
        // Todo: We can log to find remaining usage!
        switch ($offset) {
            case 'result':
            case 'reason':
            case 'edit':
            case 'alternate':
            case 'externalId':
                return true;
            default:
                return false;
        }
    }
}