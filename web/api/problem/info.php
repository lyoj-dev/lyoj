<?php
    require_once "../require.php";
    CheckParam(array("pid"),$_GET);
    $api_controller=new API_Controller;
    $problem_controller=new Problem_Controller;
    $api_controller->output($problem_controller->OutputAPIInfo($_GET["pid"]));
?>