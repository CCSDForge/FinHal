<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 03/08/17
 * Time: 10:30
 */

/**
 * @property string $type
 * @property Hal_Document $document
 * @property Hal_Submit_Status $submitStatus
 * @property Hal_Submit_Options $submitOptions
 * @property Hal_Mail[] $mail
 * @property Hal_Website_Navigation[] $website
 */
class Hal_Session_Namespace extends Zend_Session_Namespace
{
    // Attention: This is a placeHolder for dynamic property
    // In general, added properties must be static
    // It's a bad idea to add something here because a lot of Hal Code use Zend_Session_Namespace
    // to retrieve static object.
}