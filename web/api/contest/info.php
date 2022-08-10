<?php
    require_once "../require.php";
    CheckParam(array("id"),$_GET);
    $api_controller=new API_Controller;
    $contest_controller=new Contest_Controller;
    $dat=$contest_controller->GetContest($_GET["id"],$_GET["id"],true)[0];
    $api_controller->output($dat);
?>