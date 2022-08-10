<?php
    require_once "../require.php";
    CheckParam(array("l","r"),$_GET);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $uid=$login_controller->CheckLogin();
    if (!$uid) $api_controller->error_login_failed();
    $permission=$user_controller->GetWholeUserInfo($uid)["permission"];
    if ($permission<2) $api_controller->error_permission_denied();
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=code.zip");
    $zip=new ZipFile(); $zip->setDoWrite();
    $db=new Database_Controller;
    for ($i=$_GET["l"];$i<=$_GET["r"];$i++) {
        $code=$db->Query("SELECT code,lang FROM status WHERE id=$i")[0];
        $filename="";
        if ($code["lang"]<=11) $filename=$i.".cpp";
        else if ($code["lang"]<=25) $filename=$i.".c";
        else if ($code["lang"]==26) $filename=$i.".pas";
        else if ($code["lang"]<=31) $filename=$i.".java";
        else if ($code["lang"]<=33) $filename=$i.".php";
        else if ($code["lang"]<=35) $filename=$i.".py";
        $zip->addFile($code["code"],iconv("utf-8","gbk",$filename));
        ob_flush(); flush(); 
    } $zip->file();
?>