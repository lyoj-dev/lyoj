<?php
require_once "../require.php";
CheckParam(array("id"),$_GET);
$api_controller=new API_Controller;
$status_controller=new Status_Controller;
$id=$_GET["id"];$res=$status_controller->GetJudgeResultById($id);
$res=array_merge(array("id"=>$id),$res);
$api_controller->output($res);
?>