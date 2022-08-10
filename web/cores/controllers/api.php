<?php
class API_Controller{
    function __construct() {
        error_reporting(E_ERROR);
	ini_set("display_errors","Off");
    }	    
    /**
     * api输出函数 output
     * @param array $param 输出参数
     * @return void
     */
    static function output(array $param):void {
        $data=array("code"=>0,"message"=>"","data"=>$param,"ttl"=>1);
        echo "<pre style='word-wrap: break-word;white-space: pre-wrap;'>".str_replace("\\/","/",json_encode($data, JSON_UNESCAPED_UNICODE))."</pre>";
        exit;
    }

    /**
     * api错误抛出函数 error_* 
     * @return void
     */
    // 变量名未找到
    static function error_param_not_found(string $param_name):void {
        Error_Controller::Common("Cannot found param \"$param_name\"!",-404,true);
    }
    // 登录态无效
    static function error_login_failed():void {
        Error_Controller::Common("Not Login!",-101,true);
    }
    // 邮箱不存在
    static function error_email_not_exist(string $email):void {
        Error_Controller::Common("Connot found user \"$email\"",-626,true);
    }
    // 用户名或密码错误
    static function error_passwd_wrong():void {
        Error_Controller::Common("Username or password is incorrect!",-629,true);
    }
    // 服务器错误
    static function error_system_crashed():void {
        Error_Controller::Common("System Error!",-500,true);
    }
    // 邮箱未验证
    static function error_email_not_verify(string $email):void {
        Error_Controller::Common("Email '$email' not verify!",-113,true);
    }
    // 盐值过期
    static function error_salt_timed_out():void {
        Error_Controller::Common("Salt value timed out! Please try again!",-658,true);
    }
    // 邮箱已注册
    static function error_email_used(string $email):void {
        Error_Controller::Common("Email '$email' have been used!",-652,true);
    }
    // 用户名已注册
    static function error_username_used(string $name):void {
        Error_Controller::Common("Username '$name' have been used!",-652,true);
    }
    // 比赛未找到
    static function error_contest_not_found($id):void {
        Error_Controller::Common("Contest id $id not found!",-404,true);
    }
    // 没有权限
    static function error_permission_denied():void {
        Error_Controller::Common("Permission denied",-403,true);
    }
    // ZIP文件错误
    static function error_invalid_zip():void {
        Error_Controller::Common("Invalid ZIP Package",-400,true);
    }
    // 题目未找到
    static function error_problem_not_found($id):void {
        Error_Controller::Common("Problem id $id not found",-404,true);
    }
    // 提交未找到
    static function error_status_not_found($id):void {
        Error_Controller::Common("Status id $id not found",-404,true);
    }
    // 用户未找到
    static function error_user_not_found($id):void {
        Error_Controller::Common("User id $id not found",-404,true);
    }
    // Token 无效
    static function error_invalid_token():void {
        Error_Controller::Common("Invalid token",-629,true);
    }
    // 题目 id 已被占用
    static function error_problem_id_exist($id):void {
        Error_Controller::Common("Problem id $id exist",-400,true);
    }
}
?>
