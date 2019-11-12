<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 13/12/16
 * Time: 14:48
 */
class Hal_Document_Meta_Boolean extends Hal_Document_Meta_Simple
{
    /**
     * @param string $filter
     * @return int
     */
    static public function getDefaultValue($filter='')
    {
        return 0;
    }
}
