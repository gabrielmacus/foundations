<?php

/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 19:15
 */

/**
 * Class ActiveRecord
 *
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

        if (empty($this->mongodb)) {
            throw new DatabaseNotConnected($this->db);
        }
    }

    /**
     * Inserts a relation between two items
     * @param $item1
     * @param $item2
     * @param $collection Collection where data will be stored in JSON
     * @param bool $extraData Extra data to be stored with the relationship
     * @return array Stored relation
     * @throws CreateException
     * @throws PropertyNotDefined
     */
    function insertRelation($item1, $item2, $extraData = false)
    {
        $this->checkConnection();

        if (empty($item1) || empty($item1["_id"]) || !MongoId::isValid($item1["_id"]) || empty($item2) || empty($item2["_id"]) || !MongoId::isValid($item2["_id"])) {
            throw new PropertyNotDefined("_id");
        }

        $item1["_id"] = new MongoId($item1["_id"]);
        $item2["_id"] = new MongoId($item2["_id"]);
        $data = array("item1" => $item1, "item2" => $item2,"created_at" => time());

        if (!empty($extraData) && is_array($extraData)) {
            $data = array_merge($data, $extraData);
        }

        $collection = $this->mongodb->relations;

        $result = $collection->insert($data);

        if (!empty($result["err"])) {
            throw new CreateException($data);
        }

        return $data;

    }



    /**
     * Deletes relationships of an item
     * @param $id Id of the item to which the relationships will be deleted
     * @param bool $relatedItemsIds Related items id
     * @throws DatabaseNotConnected
     * @throws PropertyNotDefined
     */
    function removeRelations($id,$relatedItemsIds  = false)
    {

        $this->checkConnection();

        if(!MongoId::isValid($id))
        {
            throw new PropertyNotDefined("_id");
        }

        $id = new MongoId($id);



            if(empty($relatedItemsIds) || count($relatedItemsIds) == 0)
            {
                $data =  [
                    '$or' => array(
                        array('item2._id'=>$id),
                        array('item1._id'=>$id)
                    )
                ];
            }
            else
            {
                foreach ($relatedItemsIds as $k=>$v)
                {

                    $relatedItemsIds[$k] = new MongoId($v);
                }
                $data =  [
                    '$or' => array(
                        array("item1._id" => ['$in' => $relatedItemsIds],'item2._id'=>$id),
                        array("item2._id" => ['$in' => $relatedItemsIds],'item1._id'=>$id)
                    )
                ];

            }



            $collection = $this->mongodb->relations;
            $collection->remove($data);



    }

    function insert($breadcrumb, $data)
    {
        if (empty($breadcrumb)) {
            throw new TypeNotDefined();
        }

        $this->checkConnection();

        if (!is_array($breadcrumb)) {
            $breadcrumb = [$breadcrumb];
        }

        $id = $this->insertBreadcrumb($breadcrumb);

        $data["type"] = new MongoId($id);

        $data["created_at"] = time();

        $collection = $this->mongodb->items;

        $result = $collection->insert($data);

        $this->processRelatedItems($data);

        if (!empty($result["err"])) {
            throw new CreateException($data);
        }

        return strval($data["_id"]);
    }

    /**
     * Check if the given data has related items and stores the relationship
     * @param $data Data to be checked
     * @throws CreateException
     * @throws DatabaseNotConnected
     * @throws PropertyNotDefined
     */
    function processRelatedItems($data)
    {
        $this->checkConnection();
        foreach ($data as $k => $v) {

            if (is_array($v)) {
                $k = explode(":",$k);
                foreach ($v as $clave => $valor) {


                    if(MongoId::isValid($clave) || MongoId::isValid($valor))
                    {

                        $name1=  $k[0];
                        $name2 = $k[1];

                        $mongoId = (MongoId::isValid($clave))?$clave:$valor;


                        $item1 = array("_id" => new MongoId($mongoId),"name"=>$name2 );

                        $item2 = array("_id" => $data["_id"],"name"=>$name1);

                        if (!empty($valor["data"])) {
                            $this->insertRelation($item1, $item2,$valor["data"]);
                        } else {
                            $this->insertRelation($item1,$item2);
                        }


                    }



                }
            }


        }
    }
    /**
     * Inserts a new type
     * @param $type Type to be inserted
     * @param int $belongs Id corresponding to the father of the item to be inserted, zero if it's a root type
     * @return mixed
     * @throws CreateException
     */
    function insertType($type, $belongs = 0)
    {
        $collection = $this->mongodb->types;

        $tData = array('name' => $type, 'belongs' => $belongs);

        $result = $collection->update(
            $tData,
            array('$setOnInsert' => $tData),
            array('upsert' => true)
        );

        if (!empty($result["err"])) {
            throw new CreateException($tData);
        }

        $cursor = $collection->find($tData);

        return $cursor->next()["_id"];
    }

    /**
     * Inserts a breadcrumb of types
     * @param $breadcrumb Breadcrumb to be inserted
     * @return int|mixed Last inserted type id
     */
    function insertBreadcrumb($breadcrumb)
    {

        if (!is_array($breadcrumb)) {
            $breadcrumb = [$breadcrumb];
        }
        $insertedTypes = [];
        foreach ($breadcrumb as $k => $v) {
            $id = 0;
            if (count($insertedTypes)) {
                $id = $insertedTypes[$k - 1];
            }
            $id = $this->insertType($v, $id);
            $insertedTypes[$k] = $id;
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
    function findBreadcrumb($type, &$breadcrumb = array(), $attr = 'name')
    {
        $this->checkConnection();

        $collection = $this->mongodb->types;

        if ($attr == 'name') {
            $cursor = $collection->find(['name' => $type]);
            $mainType = $cursor->next();
            $type = $mainType["_id"];
        } elseif ($attr == '_id') {
            $cursor = $collection->find(['_id' => new MongoId($type)]);
            $mainType = $cursor->next();
            $type = $mainType["_id"];
        }

        if (count($breadcrumb) == 0) {
            $breadcrumb[] = $mainType;
        }

        $data["belongs"] = new MongoId($type);


        $cursor = $collection->find($data);

        while ($item = $cursor->next()) {
            $item["_id"] = strval($item["_id"]);

            $breadcrumb[] = $item;

            $this->findBreadcrumb($item["_id"], $breadcrumb, '_id');

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
    function findBreadcrumbReverse($type, &$breadcrumb = array(), $attr = 'name')
    {
        $this->checkConnection();
        $collection = $this->mongodb->types;
        $data[$attr] = $type;
        $cursor = $collection->find($data);
        if ($item = $cursor->next()) {
            $item["_id"] = strval($item["_id"]);
            $breadcrumb[] = $item;
            if ($item['belongs'] != "0") {
                $this->findBreadcrumbReverse($item["belongs"], $breadcrumb, '_id');
            }
        }
        return array_reverse($breadcrumb);

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

        if (!is_array($items)) {
            $items = [$items];
        }
        foreach ($items as $k => $i) {
            if (!MongoId::isValid($i)) {
                throw new PropertyNotDefined("_id");
            } else {
                $items[$k] = new MongoId($i);
            }
        }

        $collection = $this->mongodb->relations;

        $data = array('$or' => array(
            array("item1._id" => ['$in' => $items]),
            array("item2._id" => ['$in' => $items])
        ));

        $cursor = $collection->find($data);

        $results = [];

        $resultsIds=[];

        while ($result = $cursor->next())
        {

            $a[]=$result;
            $id1 = strval($result["item1"]["_id"]);
            $id2 = strval( $result["item2"]["_id"]);

            $results[$id2][$id1] = $result["item1"];
            $results[$id1][$id2]  = $result["item2"];

            $resultsIds[] = new MongoId($id1);
            $resultsIds[] = new MongoId($id2);
        }


        $collection  = $this->mongodb->items;

        $cursor = $collection->find(['_id'=>['$in'=>$resultsIds]]);

        $relatedItems=[];

        while ($result =$cursor->next())
        {

            $relatedItems[strval($result["_id"])]=$result;
        }



        foreach ($results as $k=>$v)
        {

            foreach ($v as $clave => $valor)
            {
                $data = $results[$k][$clave];

                $relatedItems[$clave]['relation_name'] = $data['name'];

                if(!empty($data['data']))
                {
                    $relatedItems[$clave]['relation_data'] = $data['data'];

                }

                $results[$k][$clave] =$relatedItems[$clave];


            }
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

    function _joinRelatedItems(&$arr,$parents,$item)
    {

        foreach ($arr as $k=>$v)
        {

            if(is_array($v) && $k != '_relations')
            {

                foreach ($parents  as $key => $value)
                {

                    if(strval($value["_id"]) == '59de0f4fcb0b66981600002f')
                    {
                        //   var_dump($value);
                    }


                    if($k == strval($value["_id"]))
                    {

                        if(empty($arr[$k][$value["name"]][strval($item["_id"])]))
                        {
                            $arr[$k][$value["name"]][strval($item["_id"])] = [];
                        }

                        $arr2 =  $arr[$k][$value["name"]][strval($item["_id"])] ;

                        $arr[$k][$value["name"]][strval($item["_id"])] = array_merge( $arr2,$item);

                        /*
                                                      echo json_encode($arr);
                                                      echo "<br><br>";*/
                    }

                }




                if(!strpos($k,':'))
                {
                    $this->joinRelatedItems($arr[$k],$parents,$item);
                }

            }
        }

    }

    function joinRelatedItems(&$arr,$relations,$data=false,&$alreadyProcessed = array())
    {


        foreach ($arr as $k=>$v)
        {
            if(is_array($v) && !strpos($k,":"))
            {

                foreach ($relations as $clave => $valor)
                {

                    if($k == strval($clave))
                    {

                        foreach ($valor as $key => $value)
                        {

                            if(empty($alreadyProcessed[$k]) || !in_array($key,$alreadyProcessed[$k]))
                            {
                                $alreadyProcessed[$k][]=$key;

                                if(empty(  $arr[$k][$value["relation_name"]][$key] ))
                                {
                                    $arr[$k][$value["relation_name"]][$key] =[];
                                }

                                $arr[$k][$value["relation_name"]][$key] = array_merge($value,$arr[$k][$value["relation_name"]][$key]);
                            }

                        }

                    }
                }


                var_dump($k);
                $this->joinRelatedItems($arr[$k],$relations,2,$alreadyProcessed);


                /*
                if(!in_array($k,$alreadyProcessed))
                {
                    $this->joinRelatedItems($arr[$k],$relations,$data,$alreadyProcessed);
                }*/

            }


        }

    }



    function find($data = array(), &$result = array(), $child=false,$process = false,&$alreadySearchedRelations = [])
    {
        $this->checkConnection();

        if (empty($data)) {
            $data = [];
        } else {
            if (!empty($data["_id"])) {

                if (MongoId::isValid($data["_id"])) {
                    $data["_id"] = new MongoId($data["_id"]);
                } elseif (!empty($data["_id"]['$in'])) {
                    foreach ($data["_id"]['$in'] as $k => $v) {
                        if (MongoId::isValid($v)) {
                            $data["_id"]['$in'][$k] = new MongoId($v);
                        }

                    }
                }
            }

        }

        $collection = $this->mongodb->items;

        $cursor = $collection->find($data);



        while ($item = $cursor->next()) {

            $id = strval($item["_id"]);

            if(!$child)
            {
                $result[$id]=$item;
            }
            else
            {
                var_dump($item);
                exit();
            }

            if(!in_array($id,$alreadySearchedRelations))
            {
                $alreadySearchedRelations[]=new MongoId($id);
            }


        }

        $relations = $this->findRelations($alreadySearchedRelations);


        if(count($relations))
        {
            $this->joinRelatedItems($result,$relations);



            $relationsIds= [];
            foreach ($relations as $k=>$v)
            {
                foreach ($v as $clave => $valor)
                {
                    $relationsIds[]=new MongoId($clave);
                }

            }




            $relations = $this->findRelations($relationsIds);

            $this->joinRelatedItems($result,$relations,1);


//            $this->find(['_id'=>['$in'=>$relationsIds]],$result,true,$process);

        }



        return $result;
    }



    function update($data, $newData, $upsert = false)
    {
        $this->checkConnection();

        $collection = $this->mongodb->items;

        $dataToProccess =(!empty($newData['$set']))?$newData['$set']:$newData;

        unset($dataToProccess['remove_related']);

        if (!empty($data['_id']) && MongoId::isValid($data['_id'])) {
            $data['_id'] = new MongoId($data['_id']);

            $dataToProccess['_id'] = $data['_id'];
        }

        unset($newData["_id"]);

        $relatedItemsToRemove=[];

        if(!empty($newData['$set']))
        {

            if(!empty($newData['$set']['remove_related']))
            {
                $relatedItemsToRemove = $newData['$set']['remove_related'];
                unset($newData['$set']['remove_related']);
            }

            $newData['$set']["updated_at"] = time();


        }
        else
        {


            if(!empty($newData['remove_related']))
            {
                $relatedItemsToRemove = $newData['remove_related'];
                unset($newData['remove_related']);
            }
            $newData["updated_at"] = time();
        }



        if(count($relatedItemsToRemove) && !empty($data["_id"]))
        {
            $this->removeRelations($data["_id"],$relatedItemsToRemove);
        }


        if (!empty($upsert) && is_array($upsert)) {
            $id = $this->insertBreadcrumb($upsert);

            $newData["type"] = new MongoId($id);

            $result = $collection->update(
                $data,
                array('$setOnInsert' => $newData),
                array('upsert' => true)
            );
        } else {
            $result = $collection->update($data, $newData);
        }


        $this->processRelatedItems($dataToProccess);


        if (!empty($result["err"])) {
            throw new UpdateException($data);
        }

        return $newData;

    }

    function delete($id)
    {

        if (!MongoId::isValid($id)) {
            throw new PropertyNotDefined("_id");
        }
        

        $data = array("_id" => new MongoId($id));

        $this->checkConnection();

        $collection = $this->mongodb->items;

        $this->removeRelations($id);

        $result = $collection->remove($data, array("justOne" => true));

        if (!empty($result["err"])) {
            throw new DeleteException($data);
        }

        return $id;
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new PropertyNotDefined($property);
        }
    }
}