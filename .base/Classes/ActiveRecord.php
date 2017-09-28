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
    function insertRelation($item1,$item2,$extraData=false)
    {
        $this->checkConnection();
        
        if(empty($item1) || empty($item1["_id"]) || !MongoId::isValid($item1["_id"]) || empty($item2) || empty($item2["_id"]) || !MongoId::isValid($item2["_id"]))
        {
            throw new PropertyNotDefined("_id");
        }

        $item1["_id"] = new MongoId($item1["_id"]);
        $item2["_id"] = new MongoId($item2["_id"]);
        $data = array("item1"=>$item1,"item2"=>$item2,"created_at"=>time());
        
        if(!empty($extraData) && is_array($extraData))
        {
            $data = array_merge($data,$extraData); 
        }

        $collection=$this->mongodb->relations;

        $result=$collection->insert($data);

        if(!empty($result["err"]))
        {
            throw new CreateException($data);
        }
        
        return $data;

    }
    function findRelations($item)
    {
        $this->checkConnection();

        if(!MongoId::isValid($item) )
        {
            throw new PropertyNotDefined("_id");
        }

        $collection=$this->mongodb->relations;

        $item = new MongoId($item);

        $data =array('$or' => array(
            array("item1._id" => $item),
            array("item2._id" => $item)
        ));

        $cursor= $collection->find($data);

        $results=[];

        while ($result = $cursor->next())
        {
            $results[]=$result;
        }

        return $results;

    }
    function insertType($type,$belongs=0)
    {
        $collection=$this->mongodb->types;

        $tData= array('name' => $type,'belongs'=>$belongs);

        $result= $collection->update(
            $tData,
            array('$setOnInsert' => $tData),
            array('upsert' => true)
        );
        
        if(!empty($result["err"]))
        {
            throw new CreateException($tData);
        }

        $cursor=$collection->find($tData);

        return $cursor->next()["_id"];
    }
    function findBreadcrumbReverse($type,&$breadcrumb=array(),$attr='name')
    {
        $this->checkConnection();
        $collection=$this->mongodb->types;
        $data[$attr]=$type;
        $cursor=$collection->find($data);
        if($item=$cursor->next())
        {
            $item["_id"]=strval($item["_id"]);
            $breadcrumb[]=$item;
            if($item['belongs'] != "0")
            {
                $this->findBreadcrumbReverse($item["belongs"],$breadcrumb,'_id');
            }
        }
        return array_reverse($breadcrumb);

    }
    function findBreadcrumb($type,&$breadcrumb=array(),$attr='name')
    {
        $this->checkConnection();

        $collection=$this->mongodb->types;

        if($attr == 'name')
        {
            $cursor= $collection->find(['name'=>$type]);
            $mainType = $cursor->next();
            $type = $mainType["_id"];
        }
        elseif($attr == '_id')
        {
            $cursor= $collection->find(['_id'=>new MongoId($type)]);
            $mainType = $cursor->next();
            $type = $mainType["_id"];
        }

        if(count($breadcrumb)==0)
        {
            $breadcrumb[]=$mainType;
        }

        $data["belongs"]=new MongoId($type);

        $cursor=$collection->find($data);

        if($item=$cursor->next())
        {
            $item["_id"]=strval($item["_id"]);

            $breadcrumb[]=$item;

            $this->findBreadcrumb($item["_id"],$breadcrumb,'_id');

        }


        return $breadcrumb;

    }
    function insertBreadcrumb($breadcrumb)
    {

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

        return $id;
    }
    function find($type,$data =array(),&$result=array(),$process=false,$dominant = false)
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
            if(!empty($data["_id"]) && MongoId::isValid($data["_id"]))
            {
                $data["_id"] = new MongoId($data["_id"]);
            }
        }

        $attr = (MongoId::isValid($type))?'_id':'name';

        $breadcrumb = array();
        
        $this->findBreadcrumb($type,$breadcrumb,$attr);
        

        $inBreadcrumb = array_map(function($el)
        {
            return new MongoId($el['_id']);

        },$breadcrumb);

        $data["type"] = ['$in' => $inBreadcrumb ];//new MongoId(end($breadcrumb)["_id"]);

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

            $relations = $this->findRelations($item["_id"]);

            foreach ($relations as $k => $v)
            {

                foreach ($v as $clave => $valor)
                {


                    if(($clave == 'item1' || $clave == 'item2') && strval($valor["_id"]) != $key && strval($valor["_id"]) != $dominant)
                    {

                        $t =$valor["type"];

                        $result[$key][$t]=array();

                        $this->find($t,array("_id"=>new MongoId($valor["_id"])),$result[$key][$t],false,$key);


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
            $breadcrumb=[$breadcrumb];
        }

        $id = $this->insertBreadcrumb($breadcrumb);

        $data["type"]= new MongoId($id);
        
        $data["created_at"] = time();

        $collection=$this->mongodb->items;

        $result= $collection->insert($data);

        foreach ($data as $k=>$v)
        {
            if(count($this->findBreadcrumbReverse($k)) > 0)
            {
                $item1 = array("_id"=>$data["_id"], "type"=>reset($breadcrumb));
                if(is_array($v))
                {
                    foreach ($v as $clave => $valor)
                    {

                        $item2 = array("_id"=>$valor, "type"=>$k);

                        $this->insertRelation($item1,$item2);
                    }
                }
                else
                {

                    $item2 = array("_id"=>$v, "type"=>$k);

                    $this->insertRelation($item1,$item2);
                }
            }
        }

        if(!empty($result["err"]))
        {
            throw new CreateException($data);
        }
        
        return strval($data["_id"]);
    }
    function update($data,$newData,$upsert=false)
    {
        $this->checkConnection();

        $collection=$this->mongodb->items;

        if(!empty($data['_id']) && MongoId::isValid($data['_id']))
        {
            $data['_id']= new MongoId($data['_id']);
        }

        unset($newData["_id"]);

        $newData["updated_at"] = time();

        if(!empty($upsert)  && is_array($upsert))
        {
            $id= $this->insertBreadcrumb($upsert);

            $newData["type"] = new MongoId($id);

           $result= $collection->update(
                $data,
                array('$setOnInsert' => $newData),
                array('upsert' => true)
            );
        }
        else
        {
            $result=    $collection->update($data,$newData);
        }

        if(!empty($result["err"]))
        {
            throw new UpdateException($data);
        }

        return $newData;
        
    }
    function delete($id)
    {
        
        if( !MongoId::isValid($id))
        {
            throw new PropertyNotDefined("_id");
        }

        $data = array("_id"=>new MongoId($id));
        
        $this->checkConnection();

        $collection=$this->mongodb->items;

        $result=$collection->remove($data,array("justOne"=>true));

        if(!empty($result["err"]))
        {
            throw new DeleteException($data);
        }

        return $id;
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