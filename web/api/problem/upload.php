<?php
require_once "../require.php";
CheckParam(array("file","pid"),$_POST);
$api_controller=new API_Controller;
$login_controller=new Login_Controller;
$user_controller=new User_Controller;
$problem_controller=new Problem_Controller;
$uid=$login_controller->CheckLogin();
if (!$uid) $api_controller->error_login_failed();
$permission=$user_controller->GetWholeUserInfo($uid)["permission"];
if ($permission<2) $api_controller->error_permission_denied();
$problem_controller->UploadData($_POST["pid"],$_POST["file"]);
$api_controller->output(array(
    "id"=>intval($_POST["pid"])
))
?>