<?php

/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 19:15
 */
class ActiveRecord
{
    protected $mongodb;


    protected function checkConnection()
    {
        if(empty($this->mongodb))
        {
            throw new DatabaseNotConnected($this->db);
        }
    }
    protected  function insertType($type,$belongs=0)
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
    function findBreadcrumb($type,&$breadcrumb=array(),$attr='name')
    {
        $this->checkConnection();
        $collection=$this->mongodb->types;

        $data[$attr]=$type;

        $cursor=$collection->find($data);

        if($item=$cursor->next())
        {
            $breadcrumb[]=$item;
            if($item['belongs'] != "0")
            {
                $this->findBreadcrumb($item["belongs"],$breadcrumb,'_id');
            }
        }

        return array_reverse($breadcrumb);

    }
    function find($type,$data =array(),$process=false)
    {
        if(empty($type))
        {
            throw  new TypeNotDefined();
        }

        $this->checkConnection();

        if(empty($data))
        {
            $data=[];
        }

        $breadcrumb = $this->findBreadcrumb($type);

        $data["type"] = end($breadcrumb)["_id"];

        $collection=$this->mongodb->items;

        $items=[];

        $cursor=$collection->find($data);

        while ($item = $cursor->next())
        {
            $item['type'] = $breadcrumb;
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