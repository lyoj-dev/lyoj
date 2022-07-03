<?php
    require_once "../require.php";
    CheckParam(array("email"),$_GET);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    if (!$user_controller->GetEmailId($_GET["email"])) $api_controller->error_email_not_exist($_GET["email"]);
    $salt=$login_controller->UserLoginSalt($_GET["email"]);
    $api_controller->output(array(
        "uid"=>$user_controller->GetEmailId($_GET["email"]),
        "salt"=>$salt,
    ));
?>