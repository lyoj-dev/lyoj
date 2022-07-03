<?php
    require_once "../require.php";
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $uid=$login_controller->CheckLogin();
    if (!$uid) $api_controller->error_login_failed();
    $permission=$user_controller->GetWholeUserInfo($uid)["permission"];
    if ($permission<2) $api_controller->error_permission_denied();
    CheckParam(array("id"),$_POST);
    $admin_controller=new Admin_Controller;
    $admin_controller->RunCrontab($_POST["id"]);
    $api_controller->output(array());
?>