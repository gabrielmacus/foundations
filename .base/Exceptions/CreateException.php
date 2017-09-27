<?php

/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 27/09/2017
 * Time: 11:32 AM
 */
class CreateException extends Exception
{
    function __construct($data)
    {
        $msg="Error inserting data";
        if(!empty($data))
        {
            $data = json_encode($data);
            $msg = "Error inserting {$data}";
        }
        
        parent::__construct($msg);
    }

}