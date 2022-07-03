<?php
    require_once "../require.php";
    $api_controller=new API_Controller;
    $login_controller=new Login_Controller;
    $public=$login_controller->UserLoginPublicKey();
    $api_controller->output(array(
        "key"=>$public
    ));
?>