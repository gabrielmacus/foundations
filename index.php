<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 19:30
 */
include (".base/autoload.php");

$conn = new Connection();
$conn->host ="localhost";
$conn->db ="php";

//$conn->insert(['Noticia','Internacionales'],array("a"=>1));
$activeRecord = new ActiveRecord();
$activeRecord->mongodb =$conn->connect();

//$autorId = $activeRecord->insert(['Autor'],['name'=>'Robert','surname'=>'Wilson','age'=>32]);


/*

$activeRecord->update(["_id"=>$noticiaId],['$set':])

var_dump($activeRecord->find('Noticia'));
*/

//var_dump($activeRecord->findRelations(['59cf163e145f8b401500002a','59cf16f2145f8b401500002d']));

//var_dump($activeRecord->find('Noticia'));



/*
$noticiaId = $activeRecord->insert(['Noticia','Policiales']
    ,
    [
        'authors'=>[ "59d979ee145f8bd86c000030"=>['data'=>['disclaimer'=>'It`s a disclaimer']]],
        'title'=>'Policia salva gatito en un árbol',
        'text'=>'Lorewp wejorfdjopfdops dsfopj pofjdspo dfjsdfpo'
    ]);
*/
/*
var_dump($activeRecord->findBreadcrumb('Noticia'));
var_dump($activeRecord->findBreadcrumbReverse('Policiales'));
*/
/*
 * {
"_id" : ObjectId("59d00940145f8b4015000031"),
"item1" : {
"_id" : ObjectId("59d00940145f8b4015000030"),
"type" : "Noticia"
},
"item2" : {
"_id" : ObjectId("59d00673145f8b401500002f"),
"type" : "Autor"
},
"created_at" : 1506806080,
"disclaimer" : "It`s a disclaimer"
}*/

echo json_encode($activeRecord->find());