<?php

/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 27/09/2017
 * Time: 11:51 AM
 */
class DeleteException extends Exception
{
    function __construct($data)
    {
        $msg="Error deleting data";
        if(!empty($data))
        {
            $data = json_encode($data);
            $msg = "Error deleting {$data}";
        }

        parent::__construct($msg);
    }

}