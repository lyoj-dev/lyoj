<?php
require_once "../require.php";
$api_controller=new API_Controller;
$login_controller=new Login_Controller;
$user_controller=new User_Controller;
CheckParam(array("id"),$_POST);
$uid=$login_controller->CheckLogin();
if (!$uid) $api_controller->error_login_failed();
if ($uid!=$_POST["id"]&&$user_controller->GetWholeUserInfo($uid)["permission"]<=1) $api_controller->error_permission_denied();
$api_controller->output($user_controller->UpdateInfo($_POST["id"],$_POST["intro"]));
?>