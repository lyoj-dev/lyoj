<?php
    require_once "../require.php";
    $api_controller=new API_Controller;
    // $login_controller=new Login_Controller;
    // $user_controller=new User_Controller;
    // $uid=$login_controller->CheckLogin();
    // if (!$uid) $api_controller->error_login_failed();
    // $permission=$user_controller->GetWholeUserInfo($uid)["permission"];
    // if ($permission<2) $api_controller->error_permission_denied();
    // $admin_controller=new Admin_Controller;
    // $arr=$admin_controller->GetDatabaseInfo();
    $api_controller->output(array("status"=>shell_exec("ps -ef | grep mysqld | grep -v grep")==""?"inactive (dead)":"active (running)"));
?>