<?php
session_start();
$username="zlt-root";
$password="Zhongji1234!";
$host="localhost";
$port=3300;
$dbname="zlt-bbs";
$conn=mysqli_connect($host,$username,$password,$dbname,$port);
if($conn->connect_error){
    die("连接数据库失败".$conn->connect_error);
}
$conn->query('set names utf8');
