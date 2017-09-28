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
$autorId = $activeRecord->insert(['Autor'],['name'=>'Gabriel','surname'=>'Macus','age'=>21]);
$noticiaId = $activeRecord->insert(['Noticia','Internacionales'],['title'=>'U.S.A ataca!!','text'=>'Lorewp wejorfdjopfdops dsfopj pofjdspo dfjsdfpo']);


$activeRecord->update(["_id"=>$noticiaId],['$set':])

/*
$noticias = $activeRecord->find('Noticia');

echo json_encode($noticias);
*/

//var_dump($activeRecord->findBreadcrumb('Noticia'));

var_dump($activeRecord->find('Noticia'));