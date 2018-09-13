<?php
/**
 * Created by PhpStorm.
 * User: rebirth.huang
 * Date: 2018/9/12
 * Time: 20:28
 */
require_once("../vendor/autoload.php");

$name = $_GET['name'];
$description = $_GET['description'];

$obj = new \Rbac\Rbac("127.0.0.1","root","root","test",3306);

//用户权限获取
$permissionList = $obj->permissionList(1);

//用户权限验证
$isCheck = $obj->check(2,'/add/role');

//权限节点添加
$res = $obj->addPermission($name,$description,"/add/user");

var_dump($res);exit;

