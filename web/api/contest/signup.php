<?php
    require_once "../require.php";
    CheckParam(array("id"),$_POST);
    $api_controller=new API_Controller;
    $contest_controller=new Contest_Controller;
    $login_controller=new Login_Controller;
    if (!$login_controller->CheckLogin()) $api_controller->error_login_failed();
    if ($contest_controller->GetContest($_POST["id"],$_POST["id"],true)==null) $api_controller->error_contest_not_found($_POST["id"]);
    $contest_controller->SignupContest($_POST["id"]);
    $dat=$contest_controller->GetContest($_POST["id"],$_POST["id"],true)[0];
    $api_controller->output($dat);
?>