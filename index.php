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

var_dump($activeRecord->insert('Data',array("title"=>'Qwerty','Evento'=> ['59ca6583cb0b662404000029'])));

echo json_encode($activeRecord->find('Data'));