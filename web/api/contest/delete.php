<?php
    require_once "../require.php";
    CheckParam(array("id"),$_POST);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $contest_controller=new Contest_Controller;
    $uid=$login_controller->CheckLogin();
    if (!$uid) $api_controller->error_login_failed();
    $permission=$user_controller->GetWholeUserInfo($uid)["permission"];
    if ($permission<2) $api_controller->error_permission_denied();
    $contest_controller->DeleteContest(intval($_POST["id"]));
    $api_controller->output(array("id"=>intval($_POST["id"])));
?>