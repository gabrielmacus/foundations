<?php

/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 21:48
 */
class TypeNotDefined extends Exception
{
public function __construct()
{
    parent::__construct("Type should be defined");
}
}