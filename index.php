<?php
/**
 * Created by PhpStorm.
 * User: Puers
 * Date: 25/09/2017
 * Time: 19:30
 */
include (".base/autoload.php");


$start = microtime(true);

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

/*
$asistencia = $activeRecord->insert(['Assistance'],['date'=>234234234]);

$alumnos=[$activeRecord->insert(['Student'],['name'=>'Gabriel','surname'=>'Macus','age'=>21,'assistance:students'=>[$asistencia]]),$activeRecord->insert(['Student'],['name'=>'Rocio','surname'=>'Duré','age'=>22])];

$activeRecord->insert(['Room'],['number'=>1,'room'=>10,'students:rooms'=>$alumnos]);
*/

/*
$activeRecord->update(['_id'=>'59db8b6acb0b66180e000039'],['remove_related'=>['59d4d4fdcb0b66f807000029']]);

$activeRecord->update(['_id'=>'59db8b6acb0b66180e000039'],['$set'=>['authors:news'=>['59d4d4fdcb0b66f807000029'=>['data'=>['text'=>'Updating demo']]]]]);
*/

/*
$noticiaId = $activeRecord->insert(['Noticia','Policiales']
    ,
    [
        'news:authors'=>[ "59d4d4fdcb0b66f807000029","59db8ba8cb0b66180e00003b"],
        'title'=>'North Korea strikes!!',
        'text'=>'1000 101010 10 101 01 01 0'
    ]);*/


/*
 *   "name" : "Bob",
    "surname" : "Dylan",
    "age" : 100,
    "type" : ObjectId("59d4d4fd8a214c116efbcc72"),
    "created_at" : 1507560360*/

/*
$carId=  $activeRecord->insert(['Car'],['brand'=>'Toyota','model'=>'Corolla','year'=>2013]);

$activeRecord->update(['_id'=>'59db8ba8cb0b66180e00003b'],['$set'=>['authors:cars'=>[$carId]]]);

*/

//$activeRecord->delete("59d4d4fdcb0b66f807000029");

//echo json_encode($activeRecord->find(['_id'=>'59dcd11bcb0b66bc02000036']));


/*
$alumnoId = $activeRecord->insert(['Student'],['name'=>'Gabriel','surname'=>'Macus','age'=>21,'dni'=>'39717030']);

$roomId = $activeRecord->insert(['Room'],['number'=>2,'rooms:students'=>[$alumnoId=>['data'=>['demo'=>true]]]]);

$schoolId = $activeRecord->insert(['School'],['schools:rooms'=>[$roomId],'name'=>'Nº 1 Del Centenario','address'=>'Alameda de la Federación']);
*/
echo json_encode($activeRecord->find(['_id'=>'59de0f4fcb0b66981600002e']));



$end  = microtime(true);

$time =$end-$start;
$timeMs = round(($end-$start)*1000);
$timeUs = round(($end - $start)*1000000);
/*
$wheelId=$activeRecord->insert(['Wheel'],['size'=>17.5,'brand'=>'Pirelli']);

$activeRecord->update(['_id'=>'59dcebbbcb0b66bc02000048'],['$set'=>['cars:wheels'=>[$wheelId]]]);
*/





echo "<h2>{$time} s</h2>";

echo "<h2>{$timeMs} ms</h2>";

echo "<h2>{$timeUs} µs</h2>";