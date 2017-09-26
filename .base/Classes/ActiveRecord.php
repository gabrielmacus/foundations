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
            $item["_id"]=strval($item["_id"]);

            //$item["_id"] = strval($item["_id"]);
            $breadcrumb[]=$item;
            if($item['belongs'] != "0")
            {
                $this->findBreadcrumb($item["belongs"],$breadcrumb,'_id');
            }
        }

        return array_reverse($breadcrumb);

    }

    function find($type,$data =array(),&$result=array(),$process=false)
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
        else
        {

        }

        $breadcrumb = $this->findBreadcrumb($type);

        $data["type"] = new MongoId(end($breadcrumb)["_id"]);

        $collection=$this->mongodb->items;

        $cursor=$collection->find($data);

        while ($item = $cursor->next())
        {

            $key =strval($item["_id"]);

            $item['type'] = $breadcrumb;

            $item["_id"] = $key;

            if(is_callable($process))
            {
                $result[$key]= $process($item);
            }
            else
            {
                $result[$key]=$item;
            }

            //TODO : this may be refactored in a function
            foreach ($item as $k => $v)
            {

                if(MongoId::isValid($v) && $k != "type" && $k!='_id')
                {
                    $result[$key][$k] = array();

                    $this->find($k,array("_id"=>new MongoId($v)),$result[$key][$k]);
                }
                else if(is_array($v))
                {
                    foreach ($v as $clave => $valor)
                    {
                        if(MongoId::isValid($valor))
                        {
                            $result[$key][$k] = array();

                            $this->find($k,array("_id"=>new MongoId($valor)),$result[$key][$k]);
                        }

                    }
                }
            }
        }

        return $result;
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


        $collection->insert($data);
        return strval($data["_id"]);
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