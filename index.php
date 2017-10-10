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

//$autorId = $activeRecord->insert(['Autor'],['name'=>'Robert','surname'=>'Johnson','age'=>33]);
/*59dbd8f5145f8bd86c00003e

59daa49a145f8bd86c00003c*/
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
        'authors:news'=>[ "59dc189f145f8bd86c00004c"=>['data'=>['disclaimer'=>'It`s a disclaimer']],"59dc188e145f8bd86c00004b"],
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

//$fileId= $activeRecord->insert(['Files'],['name'=>'photo.jpg','size'=>3423423]);
//$photoId = $activeRecord->update(['_id'=>'59dc188e145f8bd86c00004b'],['$set'=>['name'=>'John','photos:authors'=>['59dc225a145f8bd86c000056'=>['data'=>['caption'=>'Foto re peola']]]]]);

/*
$asistencia = $activeRecord->insert(['Assistance'],['date'=>234234234]);

$alumnos=[$activeRecord->insert(['Student'],['name'=>'Gabriel','surname'=>'Macus','age'=>21,'assistance:students'=>[$asistencia]]),$activeRecord->insert(['Student'],['name'=>'Rocio','surname'=>'Duré','age'=>22])];

$activeRecord->insert(['Room'],['number'=>1,'room'=>10,'students:rooms'=>$alumnos]);
*/



echo json_encode($activeRecord->find());