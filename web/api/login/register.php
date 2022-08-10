<?php
    require_once "../require.php";
    CheckParam(array("name","email","passwd"),$_POST);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $array=array();
    $uid=$login_controller->UserRegister($_POST["name"],$_POST["email"],$_POST["passwd"]);
    if ($uid==-3) $api_controller->error_username_used($_POST["name"]);
    if ($uid==-2) $api_controller->error_email_used($_POST["email"]);
    if ($uid==-1) $api_controller->error_system_crashed();
    $user=$user_controller->GetWholeUserInfo($uid);
    $api_controller->output($user);
?>