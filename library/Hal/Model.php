<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 20/09/18
 * Time: 18:20
 */

interface Hal_Model
{
     public function save();
     static function load($id);
}