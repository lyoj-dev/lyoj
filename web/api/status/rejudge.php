<?php
require_once "../require.php";
CheckParam(array("id"),$_POST);
$api_controller=new API_Controller;
$status_controller=new Status_Controller;
$login_controller=new Login_Controller;
$user_controller=new User_Controller;
$uid=$login_controller->CheckLogin();
if (!$uid) $api_controller->error_login_failed();
if ($user_controller->GetWholeUserInfo($uid)["permission"]<2) $api_controller->error_permission_denied();
$status_controller->SubmitRejudge($_POST["id"]);
$api_controller->output(array());
?>