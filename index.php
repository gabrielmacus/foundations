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
$conn->connect();
//$conn->insert(['Noticia','Internacionales'],array("a"=>1));
$conn->find('');