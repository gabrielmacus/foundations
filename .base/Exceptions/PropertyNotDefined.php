<?php

/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 20:50
 */
class PropertyNotDefined extends Exception
{
public function __construct($property)
{
    $string = (!empty($property))?"Property {$property} not defined":"Property not defined";
    parent::__construct($string);
}
}