<?php

/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 19:17
 */
class Connection
{

    protected $mongodb;
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

        $this->mongodb = $mongoClient->$db;
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
    protected function checkConnection()
    {
        if(empty($this->mongodb))
        {
            throw new DatabaseNotConnected($this->db);
        }
    }
    protected  function insertType($type,$belongs)
    {

        $collection=$this->mongodb->types;
        $tData= array('name' => $type,'belongs'=>$belongs);
        $collection->update(
            $tData,
            array('$setOnInsert' => $tData),
            array('upsert' => true)
        );

        $cursor=$collection->find($tData);

        return $cursor->next()["_id"];
    }
    function findBreadcrumbs()
    {
        $this->checkConnection();
        $collection=$this->mongodb->types;
        $cursor=$collection->find();
        $_types=[];
        while ($type = $cursor->next())
        {
            $_types[$type["_id"]]=$type;
        }

    }
    function find($type,$data =array(),$process=false)
    {

        $this->checkConnection();
        if(empty($data))
        {
            $data=[];
        }

        $data["type"] = $type;


        $collection=$this->mongodb->items;
        $items=[];
        $cursor=$collection->find($data);
        while ($item = $cursor->next())
        {
            if(is_callable($process))
            {


                $items[]= $process($item);
            }
            else
            {
                $items[]=$item;
            }

        }

        return $items;
    }
    function insert($breadcrumb,$data)
    {
        if(empty($breadcrumb))
        {
            throw new TypeNotDefined();
        }

        $this->checkConnection();

        if(!is_array($breadcrumb))
        {
            $breadcrumb = [$breadcrumb];
        }

        $insertedTypes=[];
        foreach ($breadcrumb as $k=>$v)
        {
            $id=0 ;
            if(count($insertedTypes))
            {
                $id=$insertedTypes[$k-1];
            }
            $id =$this->insertType($v,$id);
            $insertedTypes[$k]=$id;
        }

        $collection=$this->mongodb->items;
        $data["type"]= new MongoId($id);

        return $collection->insert($data);
    }


}