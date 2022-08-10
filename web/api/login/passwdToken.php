<?php
    require_once "../require.php";
    CheckParam(array("email"),$_POST);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $uid=$user_controller->GetEmailId($_POST["email"]);
    if ($uid==0) API_Controller::error_email_not_exist($_POST["email"]);
    $user=$user_controller->GetWholeUserInfo($uid);
    $login_controller->CreateToken($_POST["email"]);
    $api_controller->output(array("uid"=>$uid,"email"=>$_POST["email"]));
?>