<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 23/08/17
 * Time: 14:39
 */

/**
 * Foo Class to declare correctly method of adapter used by @see Zend_Translate::__call
 *
 * @method  Zend_Translate_Adapter addTranslation($option=array())
 *     @see Zend_Translate_Adapter::addTranslation
 * @method  bool isAvailable($locale)
 *     @see Zend_Translate_Adapter::isAvailable
 * @method  Zend_Translate_Adapter setLocale($locale)
 *     @see Zend_Translate_Adapter::setLocale
 * @method  translate($msg,$locale=null)
 *     @see Zend_Translate_Adapter::translate
 * @method  Zend_Translate_Adapter getAdapter
 *     @see Zend_Translate::getAdapter
 * @method  bool isTranslated($msg, $original = false, $locale = null)
 *     @see Zend_Translate_Adapter::isTranslated
 */

class Hal_Translate extends Zend_Translate
{

}