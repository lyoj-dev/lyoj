<?php
    require_once "../require.php";
    CheckParam(array("id","title","starttime","duration","intro","problem","type","rated","tags","lang"),$_POST);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $contest_controller=new Contest_Controller;
    $uid=$login_controller->CheckLogin();
    if (!$uid) $api_controller->error_login_failed();
    $permission=$user_controller->GetWholeUserInfo($uid)["permission"];
    if ($permission<2) $api_controller->error_permission_denied();
    $contest_controller->UpdateContest($_POST["id"],$_POST["title"],$_POST["starttime"],
        $_POST["duration"],$_POST["intro"],$_POST["problem"],$_POST["type"],$_POST["rated"],$_POST["tags"],$_POST["lang"]);
    $api_controller->output(array(
        "id"=>$_POST["id"],
        "title"=>$_POST["title"],
        "starttime"=>$_POST["starttime"],
        "duration"=>$_POST["duration"],
        "intro"=>$_POST["intro"],
        "problem"=>$_POST["problem"],
        "type"=>$_POST["type"],
        "rated"=>$_POST["rated"]
    ));
?>