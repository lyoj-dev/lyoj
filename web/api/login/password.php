<?php
    require_once "../require.php";
    CheckParam(array("email","passwd"),$_POST);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $array=array();
    if (!$user_controller->GetEmailId($_POST["email"])) $api_controller->error_email_not_exist($_POST["email"]);
    $uid=$login_controller->UserLoginPasswd($_POST["email"],$_POST["passwd"],$array);
    if ($uid==-3) $api_controller->error_email_not_verify($_POST["email"]);
    if ($uid==-2) $api_controller->error_salt_timed_out();
    if ($uid==-1) $api_controller->error_system_crashed();
    if ($uid==0) $api_controller->error_passwd_wrong();
    $user=$user_controller->GetWholeUserInfo($uid);
    $user=array("user"=>$user);$user=array_merge($user,$array);
    $api_controller->output($user);
?>