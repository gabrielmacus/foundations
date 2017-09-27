<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 27/09/2017
 * Time: 11:35 AM
 */

class UpdateException extends Exception
{
    function __construct($data)
    {
        $msg="Error updating data";
        if(!empty($data))
        {
            $data = json_encode($data);
            $msg = "Error updating {$data}";
        }

        parent::__construct($msg);
    }

}