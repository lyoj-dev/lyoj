<?php
require_once "../require.php";
CheckParam(array("uid"),$_POST);
$api_controller=new API_Controller;
$login_controller=new Login_Controller;
$user_controller=new User_Controller;
$uid=$login_controller->CheckLogin();
if (!$uid) $api_controller->error_login_failed();
$permission=$user_controller->GetWholeUserInfo($uid)["permission"];
if ($permission<2) $api_controller->error_permission_denied();
$user_controller->BanUser(intval($_POST["uid"]));
$api_controller->output(array(
    "uid"=>intval($_POST["uid"])
));
?>