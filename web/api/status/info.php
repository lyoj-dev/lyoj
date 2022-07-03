<?php
require_once "../require.php";
CheckParam(array("id"),$_GET);
$id=$_GET["id"];
$api_controller=new API_Controller;
$status_controller=new Status_Controller;
$result=$status_controller->GetJudgeInfoById($id);
$api_controller->output($result);
?>