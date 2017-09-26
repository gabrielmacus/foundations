<?php

/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 20:57
 */
class DatabaseNotConnected extends Exception
{
    public function __construct($db)
    {
        $string = (!empty($property))?"DB {$db} not connected":"DB not connected";
        parent::__construct($string);
    }

}