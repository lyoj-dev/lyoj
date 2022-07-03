<?php
    require_once "../require.php";
    CheckParam(array("code","lang","pid"),$_POST);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $status_controller=new Status_Controller;
    $user_controller=new User_Controller;
    $problem_controller=new Problem_Controller;
    $config=GetConfig();
    if (!$login_controller->CheckLogin()) $api_controller->error_login_failed();
    $uid=$login_controller->GetLoginID(); 
    $pid=$_POST["pid"]; $lang=$_POST["lang"]; $code=$_POST["code"];
    $cid=0; if (array_key_exists("cid",$_POST)) $cid=intval($_POST["cid"]);
    $sid=$status_controller->Submit($lang,$code,$uid,$pid,$cid);
    $api_controller->output(array(
        "id"=>$sid,
        "lang"=>$config["lang"][$lang],
        "code"=>htmlentities($code)
    ));
?>