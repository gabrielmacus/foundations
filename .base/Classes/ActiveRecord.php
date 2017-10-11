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

        while ($result = $cursor->next())
        {

            $a[]=$result;
            //$results[] = $result;
            $id1 = strval($result["item1"]["_id"]);
            $id2 = strval( $result["item2"]["_id"]);

            if(in_array($result["item2"]["_id"],$items))
            {

                $results[$id1][]  = $result["item2"];
            }
            else
            {
                $results[$id2][] = $result["item1"];
            }


        }
        return $results;

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

    /**
     * Inserts a new item
     * @param $breadcrumb Array with types/subtypes corresponding to the current item
     * @param $data Data of the item to be inserted
     * @return string Id of de inserted item
     * @throws CreateException
     * @throws TypeNotDefined
     *
     */

    function joinRelatedItems(&$arr,$parents,$item)
    {


        foreach ($arr as $k=>$v)
        {


            if(is_array($v) && $k != '_relations')
            {


                  foreach ($parents  as $key => $value)
                  {

                      if($k == strval($value["_id"]))
                      {

                          $id= strval($value["_id"]);

                          $controls[$id] = $id;

                          $arr[$k][$value["name"]][strval($item["_id"])] = $item;

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

    function _joinRelatedItems(&$arr,$parents,$item,&$controls = array())
    {

        var_dump(strval($item["_id"]));
        var_dump($parents);

        foreach ($arr as $k=>$v)
        {

            if(is_array($v) && $k != '_relations')
            {


                foreach ($parents  as $key => $value)
                {


                    $id= strval($value["_id"]);

                    if($k == $id)
                    {
                        $controls[$id] = $id;

                        $arr[$id][$value["name"]][strval($item["_id"])] = $item;

                    }



                    /*
                        if(strval($item['_id']) == '59de0f50cb0b669816000031')
                        {
                            var_dump($arr);
                        }*/

                }

                if(!strpos($k,':'))
                {
                    $this->joinRelatedItems($arr[$k],$parents,$item,$controls);
                }

            }
        }

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


                        $item1 = array("_id" => new MongoId($mongoId),"name"=>$name1);

                        $item2 = array("_id" => $data["_id"],"name"=>$name2);

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

        $itemsIds = [];



        while ($item = $cursor->next()) {

            $item["_id"] = strval($item["_id"]);

            if (empty($alreadySearchedRelations[$item["_id"]])) {
                $itemsIds[] = new MongoId($item["_id"]);
            }

            if (!$child) {

                $result[$item["_id"]] = $item;

            } else {

                $parents =$result['_relations'][$item["_id"]];
                if(!empty($result['59de0f4fcb0b66981600002e']['rooms']['59de0f4fcb0b66981600002f']['schools'])) {

                    echo json_encode($result);
                    $data = true;
                }

                    $this->joinRelatedItems($result, $parents, $item);

                if(!empty($data))
                {
                    echo "<h1>*****************</h1>";
                    echo json_encode($result);

                }

               $alreadySearchedRelations = array_merge($alreadySearchedRelations,$result['_relations']);


            }

        }

        /*
        echo "<h1>--------------------</h1>";
        echo json_encode($result);

        echo "<h1>--------------------</h1>";*/

        $assocItemsIds = [];
        if (count($itemsIds) > 0) {

            $relations = $this->findRelations($itemsIds);

            foreach ($relations as $k => $v) {

                $assocItemsIds[] = new MongoId($k);



            }


            $result['_relations'] = $relations;


        }

        /*
        if(!empty($result['59de0f4fcb0b66981600002e']['rooms']['59de0f4fcb0b66981600002f']['schools']))
        {

            echo json_encode($result);

            echo "<h2>Here</h2>";

        }*/

        if(count($assocItemsIds) > 0)
        {

            $this->find(['_id'=>['$in'=>$assocItemsIds]],$result,true,$process,$alreadySearchedRelations);

        }

        unset($result['_relations']);

        return $result;
    }

    function __find($data = array(), &$result = array(), $child=false,$process = false,&$alreadySearchedRelations = [])
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

        $itemsIds = [];

        while ($item = $cursor->next()) {

            $item["_id"] = strval($item["_id"]);

            if (empty($alreadySearchedRelations[$item["_id"]])) {
                $itemsIds[] = new MongoId($item["_id"]);
            }

            if (!$child) {

                $result[$item["_id"]] = $item;

            } else {

                $parents =$result['_relations'][$item["_id"]];

                $this->joinRelatedItems($result, $parents, $item);

                $alreadySearchedRelations = array_merge($alreadySearchedRelations,$result['_relations']);


            }

        }

        $assocItemsIds = [];
        if (count($itemsIds) > 0) {

            $relations = $this->findRelations($itemsIds);

            foreach ($relations as $k => $v) {

                $assocItemsIds[] = new MongoId($k);



            }

            $result['_relations'] = $relations;


        }



        if(count($assocItemsIds) > 0)
        {

            $this->find(['_id'=>['$in'=>$assocItemsIds]],$result,true,$process,$alreadySearchedRelations);

        }

        unset($result['_relations']);

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