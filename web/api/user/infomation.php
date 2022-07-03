<?php
require_once "../require.php";
$api_controller=new API_Controller;
$login_controller=new Login_Controller;
$user_controller=new User_Controller;
$uid=$login_controller->CheckLogin();
if (!$uid) $api_controller->error_login_failed();
$api_controller->output($user_controller->UpdateInfo($uid,$_POST["intro"]));
?>