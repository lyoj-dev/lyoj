<?php
require_once "../require.php";
$api_controller=new API_Controller;
$login_controller=new Login_Controller;
$user_controller=new User_Controller;
$uid=$login_controller->CheckLogin();
CheckParam(array("data","id"),$_POST);
if (!$uid) $api_controller->error_login_failed();
if ($uid!=$_POST["id"]&&$user_controller->GetWholeUserInfo($uid)["permission"]<=1) $api_controller->error_permission_denied();
$user_controller->UploadHeader($_POST["id"],$_POST["data"]);
$api_controller->output(array("uid"=>$_POST["id"],"url"=>GetRealUrl("data/user/$uid/header.jpg",null)));
?>