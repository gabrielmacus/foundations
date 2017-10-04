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

    /**
     * Checks if connection to db is established
     * @throws DatabaseNotConnected
     */
    protected function checkConnection()
    {

        if(empty($this->mongodb))
        {
            throw new DatabaseNotConnected($this->db);
        }
    }

    /**
     * Inserts a relation between two items
     * @param $item1
     * @param $item2
     * @param bool $extraData Extra data to be stored with the relationship
     * @return array Stored relation
     * @throws CreateException
     * @throws PropertyNotDefined
     */
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

    /**
     * Finds items related to the ones given in the $items array
     * @param $items Array with items ids
     * @return array Array of relations
     * @throws PropertyNotDefined
     */
    function findRelations($items)
    {
        $this->checkConnection();

        if(!is_array($items))
        {
            $items = [$items];
        }
        foreach ($items as $k=> $i)
        {
            if(!MongoId::isValid($i) )
            {
                throw new PropertyNotDefined("_id");
            }
            else
            {
                $items[$k]=new MongoId($i);
            }
        }

        $collection=$this->mongodb->relations;

        $data =array('$or' => array(
            array("item1._id" => ['$in'=>$items]),
            array("item2._id" => ['$in'=>$items])
        ));


        $cursor= $collection->find($data);

        $results=[];

        while ($result = $cursor->next())
        {
            $results[]=$result;
        }

        return $results;

    }

    /**
     * Inserts a new item
     * @param $breadcrumb Array with types/subtypes corresponding to the current item
     * @param $data Data of the item to be inserted
     * @return string Id of de inserted item
     * @throws CreateException
     * @throws TypeNotDefined
     *
     */
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

                    foreach ($v as $clave => $valor)
                    {

                        foreach ($valor as $i => $j)
                        {
                            $item2 = array("_id"=>$j["_id"], "type"=>$k);

                            if(isset($j["data"]))
                            {
                                $this->insertRelation($item1,$item2,$j["data"]);
                            }
                            else
                            {
                                $this->insertRelation($item1,$item2);
                            }

                        }

                    }

            }
        }

        if(!empty($result["err"]))
        {
            throw new CreateException($data);
        }

        return strval($data["_id"]);
    }

    /**
     * Inserts a new type
     * @param $type Type to be inserted
     * @param int $belongs Id corresponding to the father of the item to be inserted, zero if it's a root type
     * @return mixed
     * @throws CreateException
     */
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

    /**
     * Inserts a breadcrumb of types
     * @param $breadcrumb Breadcrumb to be inserted
     * @return int|mixed Last inserted type id
     */
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

    /**
     * Finds a complete breadcrumb of types, given a root type
     * @param $type Root type
     * @param array $breadcrumb Array where the function stores the breadcrumb
     * @param string $attr Attribute that corresponds to the $type argument
     * @return array Breadcrumb of types
     */
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

        while($item=$cursor->next())
        {
            $item["_id"]=strval($item["_id"]);

            $breadcrumb[]=$item;

            $this->findBreadcrumb($item["_id"],$breadcrumb,'_id');

        }


        return $breadcrumb;

    }

    /**
     * Finds a complete breadcrumb of types, given a child type
     * @param $type Child type
     * @param array $breadcrumb Array where the function stores the breadcrumb
     * @param string $attr Attribute that corresponds to the $type argument
     * @return array Breadcrumb of types
     */
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

    function find($data =array(),&$result=array(),$process=false,$dominant = false)
    {
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

        $collection=$this->mongodb->items;

        $cursor=$collection->find($data);

        $assocItemsIds=[];

        while ($item = $cursor->next())
        {

           $result[strval($item["_id"])]=$item;

           foreach ($item as $k=>$v)
           {
               if(is_array($v) && $k != '_id' && $k != 'type')
               {
                   foreach ($v as $clave => $valor)
                   {

                       if(is_array($valor))
                       {
                           foreach ($valor as $i=>$j)
                           {

                               if(isset($j["_id"]) && MongoId::isValid($j["_id"]))
                               {
                                   //$assocItemsIds[]=new MongoId($j["_id"]);
                                   $id = $j["_id"] ;
                                   $j["_id"] = new MongoId($id);
                                   $assocItemsIds[$k][$id]=$j;
                               }
                           }

                       }


                   }

               }

           }

        }

        echo json_encode($assocItemsIds);


        //$relations = $this->findRelations($item["_id"]);
        return $result;
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