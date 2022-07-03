<?php
    function solve($md) {
        $md=str_replace("\\","\\\\",$md); $md=str_replace("\n","\\n",$md); 
        $md=str_replace("\r","",$md); $md=str_replace("'","\\'",$md);
        return $md;
    } require_once "../require.php";
    CheckParam(array("pid"),$_POST);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $problem_controller=new Problem_Controller;
    $uid=$login_controller->CheckLogin();
    if (!$uid) $api_controller->error_login_failed();
    $permission=$user_controller->GetWholeUserInfo($uid)["permission"];
    if ($permission<2) $api_controller->error_permission_denied();
    $_POST["background"]=solve($_POST["background"]);
    $_POST["description"]=solve($_POST["description"]);
    $_POST["input"]=solve($_POST["input"]);
    $_POST["output"]=solve($_POST["output"]);
    $_POST["hint"]=solve($_POST["hint"]);
    $_POST["cases"]=solve($_POST["cases"]);
    $tags=$_POST["tags"]; $tags=explode(",",$tags);
    for ($i=0,$n=count($tags);$i<$n;$i++) if ($tags[$i]=="") unset($tags[$i]);
    $json=json_decode($_POST["data"],true);
    $problem_controller->UpdateProblem(
        intval($_POST["pid"]),$_POST["background"],$_POST["description"],
        $_POST["input"],$_POST["output"],$_POST["input-file"],$_POST["output-file"],
        $_POST["cases"],$_POST["hint"],$_POST["title"],$json["time"],$json["memory"],
        $json["score"],$json["subtask"],$_POST["subtask_dependence"],
        intval($_POST["difficulty"]),intval($_POST["spj_type"]),$_POST["spj_source"],
        $_POST["spj_compile"],$_POST["spj_name"],$_POST["spj_param"],$tags,intval($_POST["contest"]));
    $api_controller->output(array(
        "id"=>intval($_POST["pid"]),
        "title"=>$_POST["title"],
        "background"=>$_POST["background"],
        "description"=>$_POST["description"],
        "input-file"=>$_POST["input-file"],
        "output-file"=>$_POST["output-file"],
        "input"=>$_POST["input"],
        "output"=>$_POST["output"],
        "cases"=>$_POST["case"],
        "hint"=>$_POST["hint"],
        "tags"=>$_POST["tags"],
        "contest"=>$_POST["contest"],
        "difficulty"=>$_POST["difficulty"]
    ));
?>