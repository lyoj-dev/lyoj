<?php
    require_once "../require.php";
    CheckParam(array("data","key"),$_POST);
    $api_controller=new API_Controller;
    $api_controller->output(array(
        "result"=>openssl_public_encrypt($_POST["data"],$encrypted,$_POST["key"])?base64_encode($encrypted):null
    ));
?>