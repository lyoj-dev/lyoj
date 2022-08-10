<?php
    require_once "../require.php";
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    if (!$login_controller->CheckLogin()) 
        $api_controller->output(array(
            "DedeUserID"=>$_COOKIE["DedeUserID"],
            "DedeUserID__ckMd5"=>$_COOKIE["DedeUserID__ckMd5"],
            "CSRF_TOKEN"=>$_COOKIE["CSRF_TOKEN"],
            "SESSDATA"=>$_COOKIE["SESSDATA"],
            "login"=>0
        ));
    $uid=$login_controller->GetLoginID();
    $api_controller->output(array(
        "DedeUserID"=>$_COOKIE["DedeUserID"],
        "DedeUserID__ckMd5"=>$_COOKIE["DedeUserID__ckMd5"],
        "CSRF_TOKEN"=>$_COOKIE["CSRF_TOKEN"],
        "SESSDATA"=>$_COOKIE["SESSDATA"],
        "login"=>1,
        "user"=>$user_controller->GetWholeUserInfo($uid)
    ));
?>