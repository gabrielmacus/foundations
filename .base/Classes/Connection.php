<?php

/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 19:17
 */
class Connection
{

    protected $port;
    protected $host;
    protected $db;
    protected $password;
    protected $user;
    function __construct()
    {

    }

    function connect()
    {
        $connUrl ="mongodb://{$this->host}";
        if(!empty($this->user) && !empty($this->password))
        {
            $connUrl ="mongodb://{$this->user}:{$this->password}@{$this->host}";
        }
        $mongoClient= new MongoClient($connUrl);

        $db =$this->db;

        return $mongoClient->$db;
    }
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
        else
        {
            throw new PropertyNotDefined($property);
        }
    }




}