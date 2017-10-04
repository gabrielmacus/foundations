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
        'authors'=>[['_id'=>'59d4d4fdcb0b66f807000029','data'=>['disclaimer'=>'It`s a disclaimer']]],
        'title'=>'Policia salva gatito en un árbol',
        'text'=>'Lorewp wejorfdjopfdops dsfopj pofjdspo dfjsdfpo'
    ]);
*/
/*
var_dump($activeRecord->findBreadcrumb('Noticia'));
var_dump($activeRecord->findBreadcrumbReverse('Policiales'));
*/

$activeRecord->find();