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

//var_dump($activeRecord->insert('Autor',array("name"=>'Gabriel',"surname"=>"Macus","age"=>21)));

//echo json_encode($activeRecord->find('Data'));

//var_dump($activeRecord->delete('59cbb52d45693f6dade6916d'));

//var_dump($activeRecord->insert('Evento',array('title'=>'North Korea strikes U.S.A')));

//echo json_encode($activeRecord->find('Evento'));


$q=['_id'=>'59cbe6c7cb0b66ac13000042'];
$evento =$activeRecord->find('Evento',$q);
$evento =reset($evento);
var_dump($evento);

$id=$activeRecord->insert('Autor',array("name"=>'Bob',"surname"=>"Dylan","age"=>33));
$evento['Autor'][]=$id;

$activeRecord->update($q,['$set'=>$evento]);

echo json_encode($activeRecord->find('Evento',$q));
