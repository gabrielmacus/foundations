<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 07/10/2017
 * Time: 21:31
 */

/**
 * @param $array Array being updated
 * @param $data Data to replace existent one
 * @param $key Key of the property being updated
 * @param $value Existant value. Optional
 */
function array_update(&$array,$data, $key, $value=null)
{


    if (is_array($array))
    {

        if (isset($array[$key]) &&  (empty($value) || $array[$key] == $value))
        {

             $array[$key] = $data;
        }


        foreach ($array as $k =>$v)
        {

            array_update($array[$k],$data,$key,$value);
        }

    }

}