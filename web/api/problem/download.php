<?php
    require_once "../require.php";
    CheckParam(array("id"),$_GET);
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $user_controller=new User_Controller;
    $uid=$login_controller->CheckLogin();
    if (!$uid) $api_controller->error_login_failed();
    $permission=$user_controller->GetWholeUserInfo($uid)["permission"];
    if ($permission<2) $api_controller->error_permission_denied();
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=data.zip");
    $fp=fopen("../../../problem/".$_GET["id"]."/config.json","r");
    $json=fread($fp,filesize("../../../problem/".$_GET["id"]."/config.json"));
    fclose($fp); $json=json_decode($json,true);
    $zip=new ZipFile(); $zip->setDoWrite();
    if ($json["spj"]["type"]==0) {
        $fp=fopen("../../../problem/".$_GET["id"]."/".$json["spj"]["source"],"r");
        $content=fread($fp,filesize("../../../problem/".$_GET["id"]."/".$json["spj"]["source"]));
        $zip->addFile($content,iconv("utf-8","gbk",$json["spj"]["source"])); fclose($fp);
        ob_flush(); flush(); 
    } for ($i=0;$i<count($json["data"]);$i++) {
        $fp=fopen("../../../problem/".$_GET["id"]."/".$json["data"][$i]["input"],"r");
        $content=fread($fp,filesize("../../../problem/".$_GET["id"]."/".$json["data"][$i]["input"]));
        $zip->addFile($content,iconv("utf-8","gbk",$json["data"][$i]["input"])); fclose($fp);
        ob_flush(); flush(); 
        $fp=fopen("../../../problem/".$_GET["id"]."/".$json["data"][$i]["output"],"r");
        $content=fread($fp,filesize("../../../problem/".$_GET["id"]."/".$json["data"][$i]["output"]));
        $zip->addFile($content,iconv("utf-8","gbk",$json["data"][$i]["output"])); fclose($fp);
        ob_flush(); flush(); 
    } $zip->file();
?>